<?php
/**
 * Client to communicate with an external Composer repository.
 *
 * @since   0.1.0
 * @license GPL-2.0-or-later
 * @package PixelgradeLT
 */

declare ( strict_types=1 );

namespace PixelgradeLT\Records\Client;

use Composer\Composer;
use Composer\Config;
use Composer\Config\JsonConfigSource;
use Composer\Factory;
use Composer\IO\BaseIO;
use Composer\IO\IOInterface;
use Composer\IO\NullIO;
use Composer\Json\JsonFile;
use Composer\Json\JsonValidationException;
use JsonSchema\Validator;
use PixelgradeLT\Records\Client\Builder\ComposerArchiveBuilder;
use PixelgradeLT\Records\Client\Builder\ComposerPackagesBuilder;
use Psr\Log\LoggerInterface;
use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;

/**
 * Class for communicating with an external Composer repository.
 *
 * @since 0.1.0
 */
class ComposerClient implements Client {

	/**
	 * The Composer instance.
	 *
	 * @var Composer
	 */
	protected $composer;

	/**
	 * The working directory path.
	 *
	 * @var string
	 */
	protected $working_directory;

	/**
	 * Logger that also implements the IOInterface so it can be used with Composer.
	 *
	 * @var LoggerInterface|BaseIO
	 */
	protected $logger;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param string $working_directory
	 * @param BaseIO $logger
	 */
	public function __construct(
		string $working_directory,
		BaseIO $logger
	) {
		$this->working_directory = $working_directory;
		$this->logger            = $logger;
	}

	public function getPackages( array $args ): array {
		// load auth.json authentication information and pass it to the io interface
		$io = $this->getIO();
		$io->loadConfiguration( $this->getConfiguration() );

		$verbose        = true;
		// Allow the passing of a full composer config.
		$config = ! empty( $args['config'] ) ? $args['config'] : $this->getDefaultConfig();

		if ( ! empty( $args['repositories'] ) ) {
			$config['repositories'] = $args['repositories'];
		}

		if ( ! empty( $args['require'] ) ) {
			$config['require'] = $args['require'];
		}

		$packagesFilter = ! empty( $args['packages'] ) ? $args['packages'] : [];
		$repositoryUrl  = ! empty( $args['repository-url'] ) ? $args['repository-url'] : null;
		$skipErrors     = false;

		$outputDir = trailingslashit( $this->working_directory ) . 'testing123';

		if ( null !== $repositoryUrl && count( $packagesFilter ) > 0 ) {
			throw new \InvalidArgumentException( 'The arguments "package" and "repository-url" can not be used together.' );
		}

		// disable packagist by default
		unset( Config::$defaultRepositories['packagist'], Config::$defaultRepositories['packagist.org'] );

		$composer         = $this->getComposer( $config );
		$packageSelection = new ComposerPackageSelection( $io, $outputDir, $config, $skipErrors );

		if ( null !== $repositoryUrl ) {
			$packageSelection->setRepositoryFilter( $repositoryUrl, false );
		} else {
			$packageSelection->setPackagesFilter( $packagesFilter );
		}

		$packages = $packageSelection->select( $composer, $verbose );

		if ( isset( $config['archive']['directory'] ) ) {
			$downloads = new ComposerArchiveBuilder( $io, $outputDir, $config, $skipErrors );
			$downloads->setComposer( $composer );
			$downloads->dump( $packages );
		}

		$packages = $packageSelection->clean();

		if ( $packageSelection->hasFilterForPackages() || $packageSelection->hasRepositoryFilter() ) {
			// in case of an active filter we need to load the dumped packages.json and merge the
			// updated packages in
			$oldPackages = $packageSelection->load();
			$packages    += $oldPackages;
			ksort( $packages );
		}

//		$packagesBuilder = new ComposerPackagesBuilder( $io, $outputDir, $config, $skipErrors );
//		$packagesBuilder->dump( $packages );

		return $packages;
	}

	public function getDefaultConfig(): array {
		return [
			'name'                      => 'pixelgradelt_records/fake_repo',
			'repositories'              => [],
			'require-all'               => false,
			'require-dependencies'      => true,
			'require-dev-dependencies'  => false,
			'require-dependency-filter' => true,
			'minimum-stability'         => 'dev',
			'providers'                 => false,
		];
	}

	/**
	 * @param array|string|null $config Either a configuration array or a filename to read from, if null it will read from the default filename
	 *
	 * @return Composer
	 */
	public function getComposer( $config = null ): Composer {
		if ( null === $this->composer ) {
			try {
				$this->composer = Factory::create( $this->logger, $config );
			} catch ( \InvalidArgumentException $e ) {
				$this->logger->error( $e->getMessage() );
				exit( 1 );
			}
		}

		return $this->composer;
	}

	/**
	 * @return IOInterface
	 */
	public function getIO() {
		if ( null === $this->logger ) {
			$this->logger = new NullIO();
		}

		return $this->logger;
	}

	/**
	 * @param IOInterface $io
	 */
	public function setIO( IOInterface $io ) {
		$this->logger = $io;
	}

	private function getConfiguration(): Config {
		$config = new Config();

		// add dir to the config
		$config->merge( [ 'config' => [ 'home' => $this->getComposerHome() ] ] );

		// load global auth file
		$file = new JsonFile( $config->get( 'home' ) . '/auth.json' );
		if ( $file->exists() ) {
			$config->merge( [ 'config' => $file->read() ] );
		}
		$config->setAuthConfigSource( new JsonConfigSource( $file, true ) );

		return $config;
	}

	private function getComposerHome(): string {
		$home = getenv( 'COMPOSER_HOME' );
		if ( ! $home ) {
			if ( defined( 'PHP_WINDOWS_VERSION_MAJOR' ) ) {
				if ( ! getenv( 'APPDATA' ) ) {
					throw new \RuntimeException( 'The APPDATA or COMPOSER_HOME environment variable must be set for composer to run correctly' );
				}
				$home = strtr( getenv( 'APPDATA' ), '\\', '/' ) . '/Composer';
			} else {
				if ( ! getenv( 'HOME' ) ) {
					throw new \RuntimeException( 'The HOME or COMPOSER_HOME environment variable must be set for composer to run correctly' );
				}
				$home = rtrim( getenv( 'HOME' ), '/' ) . '/.composer';
			}
		}

		return $home;
	}

	/**
	 * @throws ParsingException        if the json file has an invalid syntax
	 * @throws JsonValidationException if the json file doesn't match the schema
	 */
	private function check( string $configFile ): bool {
		$content = file_get_contents( $configFile );

		$parser = new JsonParser();
		$result = $parser->lint( $content );
		if ( null === $result ) {
			if ( defined( 'JSON_ERROR_UTF8' ) && JSON_ERROR_UTF8 === json_last_error() ) {
				throw new \UnexpectedValueException( '"' . $configFile . '" is not UTF-8, could not parse as JSON' );
			}

			$data = json_decode( $content );

			$schemaFile = __DIR__ . '/../../../res/satis-schema.json';
			$schema     = json_decode( file_get_contents( $schemaFile ) );
			$validator  = new Validator();
			$validator->check( $data, $schema );

			if ( ! $validator->isValid() ) {
				$errors = [];
				foreach ( (array) $validator->getErrors() as $error ) {
					$errors[] = ( $error['property'] ? $error['property'] . ' : ' : '' ) . $error['message'];
				}

				throw new JsonValidationException( 'The json config file does not match the expected JSON schema', $errors );
			}

			return true;
		}

		throw new ParsingException( '"' . $configFile . '" does not contain valid JSON' . "\n" . $result->getMessage(), $result->getDetails() );
	}
}

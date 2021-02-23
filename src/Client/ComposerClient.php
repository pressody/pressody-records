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
use Composer\IO\BufferIO;
use Composer\IO\IOInterface;
use Composer\IO\NullIO;
use Composer\Json\JsonFile;
use Composer\Json\JsonValidationException;
use Composer\Package\BasePackage;
use Composer\Package\Dumper\ArrayDumper;
use Composer\Package\PackageInterface;
use Composer\Semver\VersionParser;
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
	 *
	 * @var BaseIO
	 */
	protected $io;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param string $working_directory
	 * @param BaseIO $io
	 */
	public function __construct(
		BaseIO $io = null
	) {
		$this->io = $io;
	}

	/**
	 * @param array $args
	 *
	 * @throws \Exception
	 * @return PackageInterface[]
	 */
	public function getPackages( array $args ): array {
		// load auth.json authentication information and pass it to the io interface
		$io = $this->getIO();
		$io->loadConfiguration( $this->getConfiguration() );

		$verbose = false;
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
		$skipErrors     = ! empty( $args['skip-errors'] ) ? $args['skip-errors'] : false;
		$outputDir      = ! empty( $args['output-dir'] ) ? $args['output-dir'] : get_temp_dir();

		if ( null !== $repositoryUrl && count( $packagesFilter ) > 0 ) {
			throw new \InvalidArgumentException( 'The arguments "package" and "repository-url" can not be used together.' );
		}

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

		return $packages;
	}

	/**
	 * @param PackageInterface[] $packages         List of packages to standardize.
	 * @param bool               $minimumStability The minimum stability to filter version packages by.
	 *
	 * @return array List of packages with a hierarchical organization: package > versions > version package config.
	 */
	public function standardizePackagesForJson( array $packages, $minimumStability = false ): array {
		$packagesByName = [];
		$dumper         = new ArrayDumper();
		foreach ( $packages as $package ) {
			$packagesByName[ $package->getName() ][ $package->getPrettyVersion() ] = $dumper->dump( $package );
		}

		// Prune version packages by the specified minimum stability.
		if ( false !== $minimumStability ) {
			$minimumStability = VersionParser::normalizeStability( $minimumStability );
			$acceptableStabilities = [];
			foreach (BasePackage::$stabilities as $stability => $value) {
				if ($value <= BasePackage::$stabilities[$minimumStability]) {
					$acceptableStabilities[$stability] = $value;
				}
			}

			foreach ( $packagesByName as $packageName => $versionPackages ) {
				foreach ( $versionPackages as $version => $versionConfig ) {
					$versionStability = VersionParser::parseStability( $versionConfig['version'] );
					if ( ! in_array( $versionStability, $acceptableStabilities ) ) {
						unset( $packagesByName[ $packageName ][ $version ] );
					}
				}
			}
		}

		return $packagesByName;
	}

	/**
	 * @param PackageInterface[] $packages
	 * @param array $args Composer config args.
	 *
	 * @throws \Exception
	 * @return void
	 */
	public function archivePackages( array $packages, array $args ): void {
		// load auth.json authentication information and pass it to the io interface
		$io = $this->getIO();
		$io->loadConfiguration( $this->getConfiguration() );

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
		$skipErrors     = ! empty( $args['skip-errors'] ) ? $args['skip-errors'] : false;
		$outputDir      = ! empty( $args['output-dir'] ) ? $args['output-dir'] : false;

		if ( null !== $repositoryUrl && count( $packagesFilter ) > 0 ) {
			throw new \InvalidArgumentException( 'The arguments "package" and "repository-url" can not be used together.' );
		}

		$composer = $this->getComposer( $config );

		if ( isset( $config['archive']['directory'] ) ) {
			$downloads = new ComposerArchiveBuilder( $io, $outputDir, $config, $skipErrors );
			$downloads->setComposer( $composer );
			$downloads->dump( $packages );
		}
	}

	public function getDefaultConfig(): array {
		return [
			'name'                      => 'pixelgradelt_records/fake_project',
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
				$this->composer = Factory::create( $this->io, $config );
			} catch ( \InvalidArgumentException $e ) {
				$this->io->error( $e->getMessage() );
				exit( 1 );
			}
		}

		return $this->composer;
	}

	/**
	 * @return IOInterface
	 */
	public function getIO() {
		if ( null === $this->io ) {
			$this->io = new BufferIO();
		}

		return $this->io;
	}

	/**
	 * @param BaseIO $io
	 */
	public function setIO( BaseIO $io ) {
		$this->io = $io;
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
}

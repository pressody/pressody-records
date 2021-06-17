<?php
/**
 * Plugin service definitions.
 *
 * @since   0.1.0
 * @license GPL-2.0-or-later
 * @package PixelgradeLT
 */

declare ( strict_types=1 );

namespace PixelgradeLT\Records;

use Cedaro\WP\Plugin\Provider\I18n;
use Composer\Semver\VersionParser;
use Pimple\Container as PimpleContainer;
use Pimple\ServiceIterator;
use Pimple\ServiceProviderInterface;
use Pimple\Psr11\ServiceLocator;
use PixelgradeLT\Records\Exception\PixelgradeltRecordsException;
use PixelgradeLT\Records\Logging\Handler\FileLogHandler;
use PixelgradeLT\Records\Logging\Logger;
use PixelgradeLT\Records\Logging\LogsManager;
use PixelgradeLT\Records\PostType\PackagePostType;
use Psr\Log\LogLevel;
use PixelgradeLT\Records\Authentication\ApiKey;
use PixelgradeLT\Records\Authentication;
use PixelgradeLT\Records\HTTP\Request;
use PixelgradeLT\Records\Integration;
use PixelgradeLT\Records\PackageType\LocalPlugin;
use PixelgradeLT\Records\PackageType\LocalTheme;
use PixelgradeLT\Records\Provider;
use PixelgradeLT\Records\Repository;
use PixelgradeLT\Records\Screen;
use PixelgradeLT\Records\Storage;
use PixelgradeLT\Records\Transformer\ComposerPackageTransformer;
use PixelgradeLT\Records\Transformer\ComposerRepositoryTransformer;
use PixelgradeLT\Records\Validator;

/**
 * Plugin service provider class.
 *
 * @since 0.1.0
 */
class ServiceProvider implements ServiceProviderInterface {
	/**
	 * Register services.
	 *
	 * @param PimpleContainer $container Container instance.
	 */
	public function register( PimpleContainer $container ) {
		$container['api_key.factory'] = function () {
			return new ApiKey\Factory();
		};

		$container['api_key.repository'] = function ( $container ) {
			return new ApiKey\Repository(
				$container['api_key.factory']
			);
		};

		$container['archiver'] = function ( $container ) {
			return ( new Archiver( $container['logs.logger'] ) )
				->register_validators( $container['validators.artifact'] );
		};

		$container['authentication.servers'] = function ( $container ) {
			$servers = apply_filters(
				'pixelgradelt_records/authentication_servers',
				[
					20  => 'authentication.api_key',
					100 => 'authentication.unauthorized', // The last server to take action.
				]
			);

			return new ServiceIterator( $container, $servers );
		};

		$container['authentication.api_key'] = function ( $container ) {
			return new ApiKey\Server(
				$container['api_key.repository']
			);
		};

		$container['authentication.unauthorized'] = function () {
			return new Authentication\UnauthorizedServer();
		};

		$container['client.composer'] = function ( $container ) {
			return new Client\ComposerClient(
				$container['storage.composer_working_directory']
			);
		};

		$container['client.composer.custom_token_auth'] = function () {
			return new Client\CustomTokenAuthentication();
		};

		$container['crypter'] = function () {
			$crypter = new StringCrypter();
			// Load the encryption key from the environment.
			try {
				$crypter->loadEncryptionKey( $_ENV['LTRECORDS_ENCRYPTION_KEY'] );
			} catch ( PixelgradeltRecordsException $e ) {
				// Do nothing right now.
				// We should handle a failed encryption setup through health checks and when attempting to encrypt or decrypt.
			}

			return $crypter;
		};

		$container['hash.generator'] = function ( $container ) {
			// We will use the randomly generated storage directory name as the salt,
			// so that if that changes the hashes are also invalidated.
			return new StringHashes(
				$container['storage.working_directory_name'],
				5
			);
		};

		$container['hooks.activation'] = function () {
			return new Provider\Activation();
		};

		$container['hooks.admin_assets'] = function () {
			return new Provider\AdminAssets();
		};

		$container['hooks.authentication'] = function ( $container ) {
			return new Provider\Authentication(
				$container['authentication.servers'],
				$container['http.request']
			);
		};

		$container['hooks.capabilities'] = function () {
			return new Provider\Capabilities();
		};

		$container['hooks.custom_vendor'] = function () {
			return new Provider\CustomVendor();
		};

		$container['hooks.deactivation'] = function () {
			return new Provider\Deactivation();
		};

		$container['hooks.health_check'] = function ( $container ) {
			return new Provider\HealthCheck(
				$container['http.request']
			);
		};

		$container['hooks.i18n'] = function () {
			return new I18n();
		};

		$container['hooks.package_archiver'] = function ( $container ) {
			return new Provider\PackageArchiver(
				$container['repository.managed'],
				$container['release.manager'],
				$container['package.manager'],
				$container['storage.packages'],
				$container['logs.logger']
			);
		};

		$container['hooks.part_archiver'] = function ( $container ) {
			return new Provider\PackageArchiver(
				$container['repository.parts'],
				$container['release.manager'],
				$container['part.manager'],
				$container['storage.packages'],
				$container['logs.logger']
			);
		};

		$container['hooks.package_post_type'] = function ( $container ) {
			return new PostType\PackagePostType(
				$container['package.manager']
			);
		};

		$container['hooks.part_post_type'] = function ( $container ) {
			return new PostType\PartPostType(
				$container['part.manager']
			);
		};

		$container['hooks.request_handler'] = function ( $container ) {
			return new Provider\RequestHandler(
				$container['http.request'],
				$container['route.controllers']
			);
		};

		$container['hooks.rest'] = function ( $container ) {
			return new Provider\REST( $container['rest.controllers'] );
		};

		$container['hooks.rewrite_rules'] = function () {
			return new Provider\RewriteRules();
		};

		$container['hooks.upgrade'] = function ( $container ) {
			return new Provider\Upgrade(
				$container['repository.installed.managed'],
				$container['release.manager'],
				$container['storage.packages'],
				$container['htaccess.handler'],
				$container['logs.logger']
			);
		};

		$container['htaccess.handler'] = function ( $container ) {
			return new Htaccess( $container['storage.working_directory'] );
		};

		$container['http.request'] = function () {
			$request = new Request( $_SERVER['REQUEST_METHOD'] ?? '' );

			// phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification
			$request->set_query_params( wp_unslash( $_GET ) );
			$request->set_header( 'Authorization', get_authorization_header() );

			if ( isset( $_SERVER['PHP_AUTH_USER'] ) ) {
				$request->set_header( 'PHP_AUTH_USER', $_SERVER['PHP_AUTH_USER'] );
				$request->set_header( 'PHP_AUTH_PW', $_SERVER['PHP_AUTH_PW'] ?? null );
			}

			return $request;
		};

		$container['logs.logger'] = function ( $container ) {
			return new Logger(
				$container['logs.level'],
				[
					$container['logs.handlers.file'],
				]
			);
		};

		$container['logs.level'] = function () {
			// Log warnings and above when WP_DEBUG is enabled.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$level = LogLevel::WARNING;
			}

			return $level ?? '';
		};

		$container['logs.handlers.file'] = function () {
			return new FileLogHandler();
		};

		$container['logs.manager'] = function ( $container ) {
			return new LogsManager( $container['logs.logger'] );
		};

		$container['package.factory'] = function ( $container ) {
			return new PackageFactory(
				$container['package.manager'],
				$container['release.manager'],
				$container['archiver'],
				$container['logs.logger']
			);
		};

		$container['package.manager'] = function ( $container ) {
			return new PackageManager(
				$container['client.composer'],
				$container['version.parser'],
				$container['wordpress.readme_parser'],
				$container['logs.logger'],
				$container['hash.generator'],
			);
		};

		$container['part.factory'] = function ( $container ) {
			return new PartFactory(
				$container['part.manager'],
				$container['release.manager'],
				$container['archiver'],
				$container['logs.logger']
			);
		};

		$container['part.manager'] = function ( $container ) {
			return new PartManager(
				$container['client.composer'],
				$container['version.parser'],
				$container['wordpress.readme_parser'],
				$container['logs.logger'],
				$container['hash.generator'],
			);
		};

		$container['plugin.envato_market'] = function () {
			return new Integration\EnvatoMarket();
		};

		$container['plugin.members'] = function () {
			return new Integration\Members();
		};

		$container['plugin.gpl_vault'] = function () {
			return new Integration\GPLVault();
		};

		$container['release.manager'] = function ( $container ) {
			return new ReleaseManager(
				$container['storage.packages'],
				$container['archiver'],
				$container['version.parser'],
				$container['client.composer'],
				$container['logs.logger']
			);
		};

		// This is the repo that hold all of our packages (regular managed packages or parts) that we want to expose to the public.
		$container['repository.all.managed'] = function ( $container ) {
			return new Repository\MultiRepository(
				[
					$container['repository.parts'],
					$container['repository.managed'],
				]
			);
		};

		$container['repository.local.plugins'] = function ( $container ) {
			return new Repository\CachedRepository(
				new Repository\InstalledPlugins(
					$container['package.factory']
				)
			);
		};

		$container['repository.local.themes'] = function ( $container ) {
			return new Repository\CachedRepository(
				new Repository\InstalledThemes(
					$container['package.factory']
				)
			);
		};

		$container['repository.external.plugins'] = function ( $container ) {
			return new Repository\CachedRepository(
				new Repository\ExternalPlugins(
					$container['package.factory'],
					$container['package.manager']
				)
			);
		};

		$container['repository.external.themes'] = function ( $container ) {
			return new Repository\CachedRepository(
				new Repository\ExternalThemes(
					$container['package.factory'],
					$container['package.manager']
				)
			);
		};

		$container['repository.external.wpcore'] = function ( $container ) {
			return new Repository\CachedRepository(
				new Repository\ExternalWPCore(
					$container['package.factory'],
					$container['package.manager']
				)
			);
		};

		$container['repository.manual.plugins'] = function ( $container ) {
			return new Repository\CachedRepository(
				new Repository\ManualPlugins(
					$container['package.factory'],
					$container['package.manager']
				)
			);
		};

		$container['repository.manual.themes'] = function ( $container ) {
			return new Repository\CachedRepository(
				new Repository\ManualThemes(
					$container['package.factory'],
					$container['package.manager']
				)
			);
		};
		$container['repository.manual.wpcore'] = function ( $container ) {
			return new Repository\CachedRepository(
				new Repository\ManualWPCore(
					$container['package.factory'],
					$container['package.manager']
				)
			);
		};

		$container['repository.installed'] = function ( $container ) {
			return new Repository\MultiRepository(
				[
					$container['repository.local.plugins'],
					$container['repository.local.themes'],
				]
			);
		};

		$container['repository.installed.managed'] = function ( $container ) {
			/**
			 * Filter the list of installed plugins attached to a package (package type: local.plugin).
			 *
			 * @since 0.1.0
			 *
			 * @see   PackagePostType::attach_post_meta_fields()
			 *
			 * The basename is the main plugin file's relative path from the root plugin directory.
			 *
			 * Example: plugin-name/plugin-name.php
			 *
			 * @param array $plugins Array of plugin basenames.
			 */
			$plugins = apply_filters( 'pixelgradelt_records/installed_plugins_in_use', $container['package.manager']->get_managed_installed_plugins() );

			/**
			 * Filter the list of installed themes attached to a package (package type: local.theme).
			 *
			 * @since 0.1.0
			 *
			 * @see   PackagePostType::attach_post_meta_fields()
			 *
			 * @param array $themes Array of theme slugs.
			 */
			$themes = apply_filters( 'pixelgradelt_records/installed_themes_in_use', $container['package.manager']->get_managed_installed_themes() );

			return $container['repository.installed']
				->with_filter(
					function ( $package ) use ( $plugins ) {
						if ( ! $package instanceof LocalPlugin ) {
							return true;
						}

						return in_array( $package->get_basename(), $plugins, true );
					}
				)
				->with_filter(
					function ( $package ) use ( $themes ) {
						if ( ! $package instanceof LocalTheme ) {
							return true;
						}

						return in_array( $package->get_slug(), $themes, true );
					}
				);
		};

		// This is the repo that hold all of our managed packages (themes or plugins).
		$container['repository.managed'] = function ( $container ) {
			return new Repository\MultiRepository(
				[
					$container['repository.installed.managed'],
					$container['repository.external.plugins'],
					$container['repository.external.themes'],
					$container['repository.external.wpcore'],
					$container['repository.manual.plugins'],
					$container['repository.manual.themes'],
					$container['repository.manual.wpcore'],
				]
			);
		};

		$container['repository.parts.external'] = function ( $container ) {
			return new Repository\CachedRepository(
				new Repository\ExternalParts(
					$container['part.factory'],
					$container['part.manager']
				)
			);
		};

		$container['repository.parts.manual'] = function ( $container ) {
			return new Repository\CachedRepository(
				new Repository\ManualParts(
					$container['part.factory'],
					$container['part.manager']
				)
			);
		};

		// This is the repo that hold all of our managed parts (they are plugins at their core).
		$container['repository.parts'] = function ( $container ) {
			return new Repository\MultiRepository(
				[
					$container['repository.parts.external'],
					$container['repository.parts.manual'],
				]
			);
		};

		$container['rest.controller.api_keys'] = function ( $container ) {
			return new REST\ApiKeysController(
				'pixelgradelt_records/v1',
				'apikeys',
				$container['api_key.factory'],
				$container['api_key.repository']
			);
		};

		$container['rest.controller.compositions'] = function ( $container ) {
			return new REST\CompositionsController(
				'pixelgradelt_records/v1',
				'compositions',
				$container['repository.all.managed'],
				$container['transformer.composer_repository'],
				$container['crypter']
			);
		};

		$container['rest.controller.packages'] = function ( $container ) {
			return new REST\PackagesController(
				'pixelgradelt_records/v1',
				'packages',
				$container['repository.all.managed'],
				$container['transformer.composer_package']
			);
		};

		$container['rest.controllers'] = function ( $container ) {
			return new ServiceIterator(
				$container,
				[
					'api_keys'     => 'rest.controller.api_keys',
					'compositions' => 'rest.controller.compositions',
					'packages'     => 'rest.controller.packages',
				]
			);
		};

		$container['route.composer.packages'] = function ( $container ) {
			return new Route\ComposerPackages(
				$container['repository.all.managed'],
				$container['transformer.composer_repository']
			);
		};
		$container['route.composer.parts']    = function ( $container ) {
			return new Route\ComposerPackages(
				$container['repository.parts'],
				$container['transformer.composer_repository']
			);
		};

		$container['route.download'] = function ( $container ) {
			return new Route\DownloadPackage(
				$container['repository.all.managed'],
				$container['package.manager'],
				$container['release.manager']
			);
		};

		$container['route.controllers'] = function ( $container ) {
			return new ServiceLocator(
				$container,
				[
					'composer_packages' => 'route.composer.packages',
					'download'          => 'route.download',
					'composer_parts'    => 'route.composer.parts',
				]
			);
		};

		$container['screen.edit_package'] = function ( $container ) {
			return new Screen\EditPackage(
				$container['package.manager'],
				$container['repository.managed'],
				$container['transformer.composer_package']
			);
		};

		$container['screen.edit_part'] = function ( $container ) {
			return new Screen\EditPart(
				$container['part.manager'],
				$container['repository.parts'],
				$container['transformer.composer_package']
			);
		};

		$container['screen.list_packages'] = function ( $container ) {
			return new Screen\ListPackages(
				$container['package.manager']
			);
		};
		$container['screen.list_parts']    = function ( $container ) {
			return new Screen\Listparts(
				$container['part.manager']
			);
		};

		$container['screen.edit_user'] = function ( $container ) {
			return new Screen\EditUser(
				$container['api_key.repository']
			);
		};

		$container['screen.manage_plugins'] = function ( $container ) {
			return new Screen\ManagePlugins( $container['repository.installed.managed'] );
		};

		$container['screen.settings'] = function ( $container ) {
			return new Screen\Settings(
				$container['repository.all.managed'],
				$container['api_key.repository'],
				$container['transformer.composer_package']
			);
		};

		$container['storage.packages'] = function ( $container ) {
			$path = \path_join( $container['storage.working_directory'], 'packages/' );

			return new Storage\Local( $path );
		};

		$container['storage.working_directory'] = function ( $container ) {
			if ( \defined( 'PIXELGRADELT_RECORDS_WORKING_DIRECTORY' ) ) {
				return PIXELGRADELT_RECORDS_WORKING_DIRECTORY;
			}

			$upload_config = \wp_upload_dir();
			$path          = \path_join( $upload_config['basedir'], $container['storage.working_directory_name'] );

			return (string) trailingslashit( apply_filters( 'pixelgradelt_records/working_directory', $path ) );
		};

		$container['storage.working_directory_name'] = function () {
			$directory = \get_option( 'pixelgradelt_records_working_directory' );

			if ( ! empty( $directory ) ) {
				return $directory;
			}

			// Append a random string to help hide it from nosey visitors.
			$directory = sprintf( 'pixelgradelt_records-%s', generate_random_string() );

			// Save the working directory so we will always use the same directory.
			\update_option( 'pixelgradelt_records_working_directory', $directory );

			return $directory;
		};

		$container['storage.composer_working_directory'] = function ( $container ) {
			return \path_join( $container['storage.working_directory'], 'composer/' );
		};

		$container['transformer.composer_package'] = function ( $container ) {
			return new ComposerPackageTransformer( $container['package.factory'] );
		};

		$container['transformer.composer_repository'] = function ( $container ) {
			return new ComposerRepositoryTransformer(
				$container['transformer.composer_package'],
				$container['package.manager'],
				$container['release.manager'],
				$container['version.parser'],
				$container['logs.logger']
			);
		};

		$container['validator.hidden_directory'] = function () {
			return new Validator\HiddenDirectoryValidator();
		};

		$container['validator.zip'] = function () {
			return new Validator\ZipValidator();
		};

		$container['validators.artifact'] = function ( $container ) {
			$servers = \apply_filters(
				'pixelgradelt_records/artifact_validators',
				[
					10 => 'validator.zip',
					20 => 'validator.hidden_directory',
				]
			);

			return new ServiceIterator( $container, $servers );
		};

		$container['version.parser'] = function () {
			return new ComposerVersionParser( new VersionParser() );
		};

		$container['wordpress.readme_parser'] = function () {
			return new WordPressReadmeParser();
		};
	}
}

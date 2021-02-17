<?php
/**
 * Plugin service definitions.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records;

use Cedaro\WP\Plugin\Provider\I18n;
use Composer\Semver\VersionParser;
use Pimple\Container as PimpleContainer;
use Pimple\ServiceIterator;
use Pimple\ServiceProviderInterface;
use Pimple\Psr11\ServiceLocator;
use PixelgradeLT\Records\PostType\PackagePostType;
use Psr\Log\LogLevel;
use PixelgradeLT\Records\Authentication\ApiKey;
use PixelgradeLT\Records\Authentication;
use PixelgradeLT\Records\HTTP\Request;
use PixelgradeLT\Records\Integration;
use PixelgradeLT\Records\Logger;
use PixelgradeLT\Records\PackageType\Plugin;
use PixelgradeLT\Records\PackageType\Theme;
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
		$container['api_key.factory'] = function() {
			return new ApiKey\Factory();
		};

		$container['api_key.repository'] = function( $container ) {
			return new ApiKey\Repository(
				$container['api_key.factory']
			);
		};

		$container['archiver'] = function( $container ) {
			return ( new Archiver( $container['logger'] ) )
				->register_validators( $container['validators.artifact'] );
		};

		$container['authentication.servers'] = function( $container ) {
			$servers = apply_filters(
				'pixelgradelt_records_authentication_servers',
				[
					20  => 'authentication.api_key',
					100 => 'authentication.unauthorized',
				]
			);

			return new ServiceIterator( $container, $servers );
		};

		$container['authentication.api_key'] = function( $container ) {
			return new ApiKey\Server(
				$container['api_key.repository']
			);
		};

		$container['authentication.unauthorized'] = function( $container ) {
			return new Authentication\UnauthorizedServer();
		};

		$container['hooks.activation'] = function() {
			return new Provider\Activation();
		};

		$container['hooks.admin_assets'] = function() {
			return new Provider\AdminAssets();
		};

		$container['hooks.ajax.api_key'] = function( $container ) {
			return new Provider\ApiKeyAjax(
				$container['api_key.factory'],
				$container['api_key.repository']
			);
		};

		$container['hooks.authentication'] = function( $container ) {
			return new Provider\Authentication(
				$container['authentication.servers'],
				$container['http.request']
			);
		};

		$container['hooks.capabilities'] = function() {
			return new Provider\Capabilities();
		};

		$container['hooks.custom_vendor'] = function() {
			return new Provider\CustomVendor();
		};

		$container['hooks.deactivation'] = function() {
			return new Provider\Deactivation();
		};

		$container['hooks.health_check'] = function( $container ) {
			return new Provider\HealthCheck(
				$container['http.request']
			);
		};

		$container['hooks.i18n'] = function() {
			return new I18n();
		};

		$container['hooks.package_archiver'] = function( $container ) {
			return new Provider\PackageArchiver(
				$container['repository.installed'],
				$container['repository.configured.installed'],
				$container['release.manager'],
				$container['storage.packages'],
				$container['logger']
			);
		};

		$container['hooks.package_post_type'] = function() {
			return new PostType\PackagePostType();
		};

		$container['hooks.request_handler'] = function( $container ) {
			return new Provider\RequestHandler(
				$container['http.request'],
				$container['route.controllers']
			);
		};

		$container['hooks.rewrite_rules'] = function() {
			return new Provider\RewriteRules();
		};

		$container['hooks.upgrade'] = function( $container ) {
			return new Provider\Upgrade(
				$container['repository.configured.installed'],
				$container['release.manager'],
				$container['storage.packages'],
				$container['htaccess.handler'],
				$container['logger']
			);
		};

		$container['htaccess.handler'] = function( $container ) {
			return new Htaccess( $container['storage.working_directory'] );
		};

		$container['http.request'] = function() {
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

		$container['logger'] = function( $container ) {
			return new Logger( $container['logger.level'] );
		};

		$container['logger.level'] = function( $container ) {
			// Log warnings and above when WP_DEBUG is enabled.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$level = LogLevel::WARNING;
			}

			return $level ?? '';
		};

		$container['package.factory'] = function( $container ) {
			return new PackageFactory(
				$container['release.manager']
			);
		};

		$container['plugin.envato_market'] = function() {
			return new Integration\EnvatoMarket();
		};

		$container['plugin.members'] = function() {
			return new Integration\Members();
		};

		$container['release.manager'] = function( $container ) {
			return new ReleaseManager(
				$container['storage.packages'],
				$container['archiver']
			);
		};

		$container['repository.installed'] = function( $container ) {
			return new Repository\MultiRepository(
				[
					$container['repository.plugins'],
					$container['repository.themes'],
				]
			);
		};

		$container['repository.plugins'] = function( $container ) {
			return new Repository\CachedRepository(
				new Repository\InstalledPlugins(
					$container['package.factory']
				)
			);
		};

		$container['repository.themes'] = function( $container ) {
			return new Repository\CachedRepository(
				new Repository\InstalledThemes(
					$container['package.factory']
				)
			);
		};

		$container['repository.configured.installed'] = function( $container ) {
			/**
			 * Filter the list of installed plugins attached to a package (package type: local.plugin).
			 *
			 * @see PackagePostType::attach_post_meta_fields()
			 *
			 * Plugins should be added to the whitelist by appending a plugin's
			 * basename to the array. The basename is the main plugin file's
			 * relative path from the root plugin directory.
			 *
			 * Example: plugin-name/plugin-name.php
			 *
			 * @since 0.1.0
			 *
			 * @param array $plugins Array of plugin basenames.
			 */
			$plugins = apply_filters( 'pixelgradelt_records_installed_plugins_in_use', (array) PackagePostType::get_installed_plugins_in_use() );

			/**
			 * Filter the list of installed themes attached to a package (package type: local.theme).
			 *
			 * @see PackagePostType::attach_post_meta_fields()
			 *
			 * @since 0.1.0
			 *
			 * @param array $themes Array of theme slugs.
			 */
			$themes = apply_filters( 'pixelgradelt_records_installed_themes_in_use', (array) PackagePostType::get_installed_themes_in_use() );

			return $container['repository.installed']
				->with_filter(
					function( $package ) use ( $plugins ) {
						if ( ! $package instanceof Plugin ) {
							return true;
						}

						return in_array( $package->get_basename(), $plugins, true );
					}
				)
				->with_filter(
					function( $package ) use ( $themes ) {
						if ( ! $package instanceof Theme ) {
							return true;
						}

						return in_array( $package->get_slug(), $themes, true );
					}
				);
		};

		$container['route.composer'] = function( $container ) {
			return new Route\Composer(
				$container['repository.configured.installed'],
				$container['transformer.composer_repository']
			);
		};

		$container['route.download'] = function( $container ) {
			return new Route\Download(
				$container['repository.configured.installed'],
				$container['release.manager']
			);
		};

		$container['route.controllers'] = function( $container ) {
			return new ServiceLocator(
				$container,
				[
					'composer' => 'route.composer',
					'download' => 'route.download',
				]
			);
		};

		$container['screen.edit_user'] = function( $container ) {
			return new Screen\EditUser(
				$container['api_key.repository']
			);
		};

		$container['screen.manage_plugins'] = function( $container ) {
			return new Screen\ManagePlugins( $container['repository.configured.installed'] );
		};

		$container['screen.settings'] = function( $container ) {
			return new Screen\Settings(
				$container['repository.configured.installed'],
				$container['api_key.repository'],
				$container['transformer.composer_package']
			);
		};

		$container['storage.packages'] = function( $container ) {
			$path = path_join( $container['storage.working_directory'], 'packages/' );
			return new Storage\Local( $path );
		};

		$container['storage.working_directory'] = function( $container ) {
			if ( \defined( 'PIXELGRADELT_RECORDS_WORKING_DIRECTORY' ) ) {
				return PIXELGRADELT_RECORDS_WORKING_DIRECTORY;
			}

			$upload_config = wp_upload_dir();
			$path          = path_join( $upload_config['basedir'], $container['storage.working_directory_name'] );

			return (string) trailingslashit( apply_filters( 'pixelgradelt_records_working_directory', $path ) );
		};

		$container['storage.working_directory_name'] = function() {
			$directory = get_option( 'pixelgradelt_records_working_directory' );

			if ( ! empty( $directory ) ) {
				return $directory;
			}

			// Append a random string to help hide it from nosey visitors.
			$directory = sprintf( 'pixelgradelt_records-%s', generate_random_string() );

			// Save the working directory so we will always use the same directory.
			update_option( 'pixelgradelt_records_working_directory', $directory );

			return $directory;
		};

		$container['transformer.composer_package'] = function( $container ) {
			return new ComposerPackageTransformer( $container['package.factory'] );
		};

		$container['transformer.composer_repository'] = function( $container ) {
			return new ComposerRepositoryTransformer(
				$container['transformer.composer_package'],
				$container['release.manager'],
				$container['version.parser'],
				$container['logger']
			);
		};

		$container['validator.hidden_directory'] = function() {
			return new Validator\HiddenDirectoryValidator();
		};

		$container['validator.zip'] = function() {
			return new Validator\ZipValidator();
		};

		$container['validators.artifact'] = function( $container ) {
			$servers = apply_filters(
				'pixelgradelt_records_artifact_validators',
				[
					10 => 'validator.zip',
					20 => 'validator.hidden_directory',
				]
			);

			return new ServiceIterator( $container, $servers );
		};

		$container['version.parser'] = function() {
			return new ComposerVersionParser( new VersionParser() );
		};
	}
}

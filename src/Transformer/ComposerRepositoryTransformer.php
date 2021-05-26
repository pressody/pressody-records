<?php
/**
 * Composer repository transformer.
 *
 * @since   0.1.0
 * @license GPL-2.0-or-later
 * @package PixelgradeLT
 */

declare ( strict_types=1 );

namespace PixelgradeLT\Records\Transformer;

use PixelgradeLT\Records\PackageManager;
use Psr\Log\LoggerInterface;
use PixelgradeLT\Records\Capabilities;
use PixelgradeLT\Records\Package;
use PixelgradeLT\Records\ReleaseManager;
use PixelgradeLT\Records\Repository\PackageRepository;
use PixelgradeLT\Records\VersionParser;

/**
 * Composer repository transformer class.
 *
 * @since 0.1.0
 */
class ComposerRepositoryTransformer implements PackageRepositoryTransformer {

	/**
	 * Composer package transformer.
	 *
	 * @var PackageTransformer.
	 */
	protected PackageTransformer $composer_transformer;

	/**
	 * Package manager.
	 *
	 * @var PackageManager
	 */
	protected PackageManager $package_manager;

	/**
	 * Release manager.
	 *
	 * @var ReleaseManager
	 */
	protected ReleaseManager $release_manager;

	/**
	 * Version parser.
	 *
	 * @var VersionParser
	 */
	protected VersionParser $version_parser;

	/**
	 * Logger.
	 *
	 * @var LoggerInterface
	 */
	protected LoggerInterface $logger;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param PackageTransformer $composer_transformer Composer package transformer.
	 * @param PackageManager  $package_manager Packages manager.
	 * @param ReleaseManager     $release_manager      Release manager.
	 * @param VersionParser      $version_parser       Version parser.
	 * @param LoggerInterface    $logger               Logger.
	 */
	public function __construct(
		PackageTransformer $composer_transformer,
		PackageManager $package_manager,
		ReleaseManager $release_manager,
		VersionParser $version_parser,
		LoggerInterface $logger
	) {

		$this->composer_transformer = $composer_transformer;
		$this->package_manager = $package_manager;
		$this->release_manager      = $release_manager;
		$this->version_parser       = $version_parser;
		$this->logger               = $logger;
	}

	/**
	 * Transform a repository of packages into the format used in packages.json.
	 *
	 * @since 0.1.0
	 *
	 * @param PackageRepository $repository Package repository.
	 *
	 * @return array
	 */
	public function transform( PackageRepository $repository ): array {
		$items = [];

		foreach ( $repository->all() as $package ) {
			// We will not include packages without releases or packages that are not public (except for admin users).
			if ( ! $package->has_releases() || ! ( current_user_can( Capabilities::MANAGE_OPTIONS ) || $this->package_manager->is_package_public( $package ) ) ) {
				continue;
			}

			$package = $this->composer_transformer->transform( $package );
			$item    = $this->transform_item( $package );

			// Skip if there aren't any viewable releases.
			if ( empty( $item ) ) {
				continue;
			}

			$items[ $package->get_name() ] = $item;
		}

		return [ 'packages' => $items ];
	}

	/**
	 * Transform an item.
	 *
	 * @param Package $package Package instance.
	 *
	 * @return array
	 */
	protected function transform_item( Package $package ): array {
		$data = [];

		foreach ( $package->get_releases() as $release ) {
			$version = $release->get_version();
			$meta    = $release->get_meta();

			// Start with the hard-coded requires, if any.
			// This order is important since we go from lower to higher importance. Each one overwrites the previous.
			$require = [];
			// Merge the release require, if any.
			if ( ! empty( $meta['require'] ) ) {
				$require = array_merge( $require, $meta['require'] );
			}
			// Merge the managed required packages, if any.
			if ( ! empty( $meta['require_ltpackages'] ) ) {
				$require = array_merge( $require, $this->composer_transformer->transform_dependency_packages( $meta['require_ltpackages'] ) );
			}
			// We want to enforce a certain composer/installers require.
			$require = array_merge( $require, [ 'composer/installers' => '^1.0', ] );

			// Finally, allow others to have a say.
			$require = apply_filters( 'pixelgradelt_records_composer_package_require', $require, $package, $release );

			// Start with the hard-coded replaces, if any.
			// This order is important since we go from lower to higher importance. Each one overwrites the previous.
			$replace = [];
			// Merge the release replace, if any.
			if ( ! empty( $meta['replace'] ) ) {
				$replace = array_merge( $replace, $meta['replace'] );
			}
			// Merge the managed replaced packages, if any.
			if ( ! empty( $meta['replace_ltpackages'] ) ) {
				$replace = array_merge( $replace, $this->composer_transformer->transform_dependency_packages( $meta['replace_ltpackages'] ) );
			}

			// Finally, allow others to have a say.
			$replace = apply_filters( 'pixelgradelt_records_composer_package_replace', $replace, $package, $release );

			// We don't need the artifactmtime in the dist since that is only for internal use.
			if ( isset( $meta['dist']['artifactmtime'] ) ) {
				unset( $meta['dist']['artifactmtime'] );
			}

			$data[ $version ] = [
				'name'               => $package->get_name(),
				'version'            => $version,
				'version_normalized' => $this->version_parser->normalize( $version ),
				'dist'               => $meta['dist'],
				'require'            => $require,
				'replace'            => $replace,
				'type'               => $package->get_type(),
				'authors'            => $meta['authors'],
				'description'        => $meta['description'],
				'keywords'           => $meta['keywords'],
				'homepage'           => $meta['homepage'],
				'license'            => $meta['license'],
			];
		}

		return $data;
	}
}

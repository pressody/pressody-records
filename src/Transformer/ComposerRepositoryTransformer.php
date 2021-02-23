<?php
/**
 * Composer repository transformer.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Transformer;

use Psr\Log\LoggerInterface;
use PixelgradeLT\Records\Capabilities;
use PixelgradeLT\Records\Exception\FileNotFound;
use PixelgradeLT\Records\Exception\PixelgradeltRecordsException;
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
	 * Release manager.
	 *
	 * @var ReleaseManager
	 */
	protected $release_manager;

	/**
	 * Composer package transformer.
	 *
	 * @var PackageTransformer.
	 */
	protected $composer_transformer;

	/**
	 * Logger.
	 *
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * Version parser.
	 *
	 * @var VersionParser
	 */
	protected $version_parser;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param PackageTransformer $composer_transformer Composer package transformer.
	 * @param ReleaseManager     $release_manager      Release manager.
	 * @param VersionParser      $version_parser       Version parser.
	 * @param LoggerInterface    $logger               Logger.
	 */
	public function __construct(
		PackageTransformer $composer_transformer,
		ReleaseManager $release_manager,
		VersionParser $version_parser,
		LoggerInterface $logger
	) {
		$this->composer_transformer = $composer_transformer;
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
	 * @return array
	 */
	public function transform( PackageRepository $repository ): array {
		$items = [];

		foreach ( $repository->all() as $slug => $package ) {
			if ( ! $package->has_releases() ) {
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
	 * @return array
	 */
	protected function transform_item( Package $package ): array {
		$data = [];

		foreach ( $package->get_releases() as $release ) {
			// Skip if the current user can't view this release.
			if ( ! current_user_can( Capabilities::VIEW_PACKAGE, $package, $release ) ) {
				continue;
			}

			$version = $release->get_version();

			try {
				$data[ $version ] = [
					'name'               => $package->get_name(),
					'version'            => $version,
					'version_normalized' => $this->version_parser->normalize( $version ),
					'dist'               => [
						'type'   => 'zip',
						'url'    => $release->get_download_url(),
						'shasum' => $this->release_manager->checksum( 'sha1', $release ),
					],
					'require'            => [
						'composer/installers' => '^1.0',
					],
					'type'               => $package->get_type(),
					'authors'            => $package->get_authors(),
					'description'        => $package->get_description(),
					'keywords'           => $package->get_keywords(),
					'homepage'           => $package->get_homepage(),
				];
			} catch ( FileNotFound $e ) {
				$this->logger->error(
					'Package artifact could not be found for {package}:{version}.',
					[
						'exception' => $e,
						'package'   => $package->get_name(),
						'version'   => $version,
					]
				);
			}
		}

		return $data;
	}
}

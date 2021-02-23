<?php
/**
 * Package factory.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records;

use PixelgradeLT\Records\PackageType\BasePackage;
use PixelgradeLT\Records\PackageType\ExternalPackageBuilder;
use PixelgradeLT\Records\PackageType\LocalBasePackage;
use PixelgradeLT\Records\PackageType\PackageBuilder;
use PixelgradeLT\Records\PackageType\LocalPlugin;
use PixelgradeLT\Records\PackageType\LocalPluginBuilder;
use PixelgradeLT\Records\PackageType\LocalTheme;
use PixelgradeLT\Records\PackageType\LocalThemeBuilder;
use Psr\Log\LoggerInterface;

/**
 * Factory for creating package builders.
 *
 * @since 0.1.0
 */
final class PackageFactory {

	/**
	 * Package manager.
	 *
	 * @var PackageManager
	 */
	private $package_manager;

	/**
	 * Release manager.
	 *
	 * @var ReleaseManager
	 */
	private $release_manager;

	/**
	 * Logger.
	 *
	 * @var Logger
	 */
	protected $logger;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param PackageManager  $package_manager Packages manager.
	 * @param ReleaseManager  $release_manager Release manager.
	 * @param LoggerInterface $logger          Logger.
	 */
	public function __construct(
		PackageManager $package_manager,
		ReleaseManager $release_manager,
		LoggerInterface $logger
	) {
		$this->package_manager = $package_manager;
		$this->release_manager = $release_manager;
		$this->logger          = $logger;
	}

	/**
	 * Create a package builder.
	 *
	 * @since 0.1.0
	 *
	 * @param string $package_type Package type.
	 * @param string $source_type The managed package source type, if that is the case.
	 *
	 * @return LocalPluginBuilder|LocalThemeBuilder|ExternalPackageBuilder|PackageBuilder Package builder instance.
	 */
	public function create( string $package_type, string $source_type = '' ): PackageBuilder {

		if ( 'plugin' === $package_type && 'local.plugin' === $source_type ) {
			return new LocalPluginBuilder( new LocalPlugin(), $this->package_manager, $this->release_manager, $this->logger );
		}

		if ( 'theme' === $package_type && 'local.theme' === $source_type ) {
			return new LocalThemeBuilder( new LocalTheme(), $this->package_manager, $this->release_manager, $this->logger );
		}

		if ( in_array( $source_type, [ 'packagist.org', 'wpackagist.org', 'vcs', ] ) ) {
			return new ExternalPackageBuilder( new BasePackage(), $this->package_manager, $this->release_manager, $this->logger );
		}

		return new PackageBuilder( new BasePackage(), $this->package_manager, $this->release_manager, $this->logger );
	}
}

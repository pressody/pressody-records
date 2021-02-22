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
use PixelgradeLT\Records\PackageType\LocalBasePackage;
use PixelgradeLT\Records\PackageType\PackageBuilder;
use PixelgradeLT\Records\PackageType\LocalPlugin;
use PixelgradeLT\Records\PackageType\LocalPluginBuilder;
use PixelgradeLT\Records\PackageType\LocalTheme;
use PixelgradeLT\Records\PackageType\LocalThemeBuilder;

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
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param PackageManager $package_manager Packages manager.
	 * @param ReleaseManager $release_manager Release manager.
	 */
	public function __construct(
		PackageManager $package_manager,
		ReleaseManager $release_manager
	) {
		$this->package_manager = $package_manager;
		$this->release_manager = $release_manager;
	}

	/**
	 * Create a package builder.
	 *
	 * @since 0.1.0
	 *
	 * @param string $package_type Package type.
	 * @param string $source_type The managed package source type, if that is the case.
	 *
	 * @return LocalPluginBuilder|LocalThemeBuilder|PackageBuilder Package builder instance.
	 */
	public function create( string $package_type, string $source_type = '' ): PackageBuilder {
		switch ( $package_type ) {
			case 'plugin':
				switch ( $source_type ) {
					case 'local.plugin':
						return new LocalPluginBuilder( new LocalPlugin(), $this->package_manager, $this->release_manager );
					case 'packagist.org':
					case 'wpackagist.org':
					case 'vcs':
					default:
						break;
				}
				break;
			case 'theme':
				switch ( $source_type ) {
					case 'local.theme':
						return new LocalThemeBuilder( new LocalTheme(), $this->package_manager, $this->release_manager );
					case 'packagist.org':
					case 'wpackagist.org':
					case 'vcs':
					default:
						break;
				}
				break;
			default:
				break;
		}

		return new PackageBuilder( new BasePackage(), $this->package_manager, $this->release_manager );
	}
}

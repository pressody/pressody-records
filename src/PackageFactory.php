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

use PixelgradeLT\Records\PackageType\LocalBasePackage;
use PixelgradeLT\Records\PackageType\PackageBuilder;
use PixelgradeLT\Records\PackageType\LocalPlugin;
use PixelgradeLT\Records\PackageType\PluginBuilder;
use PixelgradeLT\Records\PackageType\LocalTheme;
use PixelgradeLT\Records\PackageType\ThemeBuilder;

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
	 * @return PluginBuilder|ThemeBuilder|PackageBuilder Package builder instance.
	 */
	public function create( string $package_type ): PackageBuilder {
		switch ( $package_type ) {
			case 'plugin':
				return new PluginBuilder( new LocalPlugin(), $this->package_manager, $this->release_manager );
			case 'theme':
				return new ThemeBuilder( new LocalTheme(), $this->package_manager, $this->release_manager );
		}

		return new PackageBuilder( new LocalBasePackage(), $this->package_manager, $this->release_manager );
	}
}

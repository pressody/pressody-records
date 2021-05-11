<?php
/**
 * Package factory.
 *
 * @since   0.1.0
 * @license GPL-2.0-or-later
 * @package PixelgradeLT
 */

declare ( strict_types=1 );

namespace PixelgradeLT\Records;

use PixelgradeLT\Records\PackageType\BasePackage;
use PixelgradeLT\Records\PackageType\ExternalBasePackage;
use PixelgradeLT\Records\PackageType\Builder\ExternalBasePackageBuilder;
use PixelgradeLT\Records\PackageType\Builder\BasePackageBuilder;
use PixelgradeLT\Records\PackageType\LocalPlugin;
use PixelgradeLT\Records\PackageType\Builder\LocalPluginBuilder;
use PixelgradeLT\Records\PackageType\LocalTheme;
use PixelgradeLT\Records\PackageType\Builder\LocalThemeBuilder;
use PixelgradeLT\Records\PackageType\Builder\ManualBasePackageBuilder;
use PixelgradeLT\Records\PackageType\PackageTypes;
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
	private PackageManager $package_manager;

	/**
	 * Release manager.
	 *
	 * @var ReleaseManager
	 */
	private ReleaseManager $release_manager;

	/**
	 * Archiver.
	 *
	 * @var Archiver
	 */
	protected Archiver $archiver;

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
	 * @param PackageManager  $package_manager Packages manager.
	 * @param ReleaseManager  $release_manager Release manager.
	 * @param Archiver        $archiver        Archiver.
	 * @param LoggerInterface $logger          Logger.
	 */
	public function __construct(
		PackageManager $package_manager,
		ReleaseManager $release_manager,
		Archiver $archiver,
		LoggerInterface $logger
	) {
		$this->package_manager = $package_manager;
		$this->release_manager = $release_manager;
		$this->archiver        = $archiver;
		$this->logger          = $logger;
	}

	/**
	 * Create a package builder.
	 *
	 * @since 0.1.0
	 *
	 * @param string $package_type Package type.
	 * @param string $source_type  The managed package source type, if that is the case.
	 *
	 * @return LocalPluginBuilder|LocalThemeBuilder|ExternalBasePackageBuilder|ManualBasePackageBuilder|BasePackageBuilder Package builder instance.
	 */
	public function create( string $package_type, string $source_type = '' ): BasePackageBuilder {

		if ( PackageTypes::PLUGIN === $package_type && 'local.plugin' === $source_type ) {
			return new LocalPluginBuilder( new LocalPlugin(), $this->package_manager, $this->release_manager, $this->archiver, $this->logger );
		}

		if ( PackageTypes::THEME === $package_type && 'local.theme' === $source_type ) {
			return new LocalThemeBuilder( new LocalTheme(), $this->package_manager, $this->release_manager, $this->archiver, $this->logger );
		}

		if ( 'local.manual' === $source_type ) {
			return new ManualBasePackageBuilder( new BasePackage(), $this->package_manager, $this->release_manager, $this->archiver, $this->logger );
		}

		if ( in_array( $source_type, [ 'packagist.org', 'wpackagist.org', 'vcs', ] ) ) {
			return new ExternalBasePackageBuilder( new ExternalBasePackage(), $this->package_manager, $this->release_manager, $this->archiver, $this->logger );
		}

		return new BasePackageBuilder( new BasePackage(), $this->package_manager, $this->release_manager, $this->archiver, $this->logger );
	}
}

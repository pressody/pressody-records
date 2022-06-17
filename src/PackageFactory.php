<?php
/**
 * Package factory.
 *
 * @since   0.1.0
 * @license GPL-2.0-or-later
 * @package Pressody
 */

declare ( strict_types=1 );

namespace Pressody\Records;

use Pressody\Records\PackageType\BasePackage;
use Pressody\Records\PackageType\Builder\ExternalWPCorePackageBuilder;
use Pressody\Records\PackageType\Builder\ManualWPCorePackageBuilder;
use Pressody\Records\PackageType\ExternalBasePackage;
use Pressody\Records\PackageType\Builder\ExternalBasePackageBuilder;
use Pressody\Records\PackageType\Builder\BasePackageBuilder;
use Pressody\Records\PackageType\LocalPlugin;
use Pressody\Records\PackageType\Builder\LocalPluginBuilder;
use Pressody\Records\PackageType\LocalTheme;
use Pressody\Records\PackageType\Builder\LocalThemeBuilder;
use Pressody\Records\PackageType\Builder\ManualBasePackageBuilder;
use Pressody\Records\PackageType\PackageTypes;
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
	 * @return LocalPluginBuilder|LocalThemeBuilder|ExternalBasePackageBuilder|ExternalWPCorePackageBuilder|ManualBasePackageBuilder|ManualWPCorePackageBuilder|BasePackageBuilder Package builder instance.
	 */
	public function create( string $package_type, string $source_type = '' ): BasePackageBuilder {
		if ( PackageTypes::WPCORE === $package_type ) {
			if ( in_array( $source_type, [ 'packagist.org', 'wpackagist.org', 'vcs', ] ) ) {
				return new ExternalWPCorePackageBuilder( new ExternalBasePackage(), $this->package_manager, $this->release_manager, $this->archiver, $this->logger );
			}

			if ( 'local.manual' === $source_type ) {
				return new ManualWPCorePackageBuilder( new BasePackage(), $this->package_manager, $this->release_manager, $this->archiver, $this->logger );
			}
		}

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

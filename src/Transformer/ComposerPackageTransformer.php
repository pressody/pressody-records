<?php
/**
 * Composer package transformer.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Transformer;

use PixelgradeLT\Records\Package;
use PixelgradeLT\Records\PackageFactory;
use function PixelgradeLT\Records\get_composer_vendor;

/**
 * Composer package transformer class.
 *
 * @since 0.1.0
 */
class ComposerPackageTransformer implements PackageTransformer {

	/**
	 * Package factory.
	 *
	 * @var PackageFactory
	 */
	protected PackageFactory $factory;

	/**
	 * Create a Composer package transformer.
	 *
	 * @since 0.1.0
	 *
	 * @param PackageFactory $factory Package factory.
	 */
	public function __construct( PackageFactory $factory ) {
		$this->factory = $factory;
	}

	/**
	 * Transform a package into a Composer package.
	 *
	 * @since 0.1.0
	 *
	 * @param Package $package Package.
	 *
	 * @return Package
	 */
	public function transform( Package $package ): Package {
		$builder = $this->factory->create( 'composer' )->with_package( $package );

		$vendor = get_composer_vendor();
		$name   = $this->normalize_package_name( $package->get_slug() );
		$builder->set_name( $vendor . '/' . $name );

		return $builder->build();
	}

	/**
	 * Transform a package's dependency packages into a Composer require list.
	 *
	 * @since 0.8.0
	 *
	 * @param array $ltpackages
	 *
	 * @return array
	 */
	public function transform_dependency_packages( array $ltpackages ): array {
		$composer_require = [];

		// Convert the managed dependency packages to the simple Composer format.
		foreach ( $ltpackages as $required_ltpackage ) {
			$composer_require[ $required_ltpackage['composer_package_name'] ] = $required_ltpackage['version_range'];

			if ( 'stable' !== $required_ltpackage['stability'] ) {
				$composer_require[ $required_ltpackage['composer_package_name'] ] .= '@' . $required_ltpackage['stability'];
			}
		}

		return $composer_require;
	}

	/**
	 * Normalize a package name for packages.json.
	 *
	 * @since 0.1.0
	 *
	 * @link https://github.com/composer/composer/blob/79af9d45afb6bcaac8b73ae6a8ae24414ddf8b4b/src/Composer/Package/Loader/ValidatingArrayLoader.php#L339-L369
	 *
	 * @param string $name Package name.
	 *
	 * @return string
	 */
	protected function normalize_package_name( string $name ): string {
		$name = strtolower( $name );
		return preg_replace( '/[^a-z0-9_\-\.]+/i', '', $name );
	}
}

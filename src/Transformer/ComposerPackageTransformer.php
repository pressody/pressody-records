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

/**
 * Composer package transformer class.
 *
 * @since 0.1.0
 */
class ComposerPackageTransformer implements PackageTransformer {
	/**
	 * Composer package type map.
	 *
	 * @var array
	 */
	const WORDPRESS_TYPES = [
		'dropin'   => 'wordpress-dropin',
		'muplugin' => 'wordpress-muplugin',
		'plugin'   => 'wordpress-plugin',
		'theme'    => 'wordpress-theme',
	];

	/**
	 * Package factory.
	 *
	 * @var PackageFactory
	 */
	protected $factory;

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
	 * @return Package
	 */
	public function transform( Package $package ): Package {
		$builder = $this->factory->create( 'composer' )->with_package( $package );

		$vendor = apply_filters( 'pixelgradelt_records_vendor', 'pixelgradelt_records' );
		$name   = $this->normalize_package_name( $package->get_slug() );
		$builder->set_name( $vendor . '/' . $name );

		if ( isset( self::WORDPRESS_TYPES[ $package->get_type() ] ) ) {
			$builder->set_type( self::WORDPRESS_TYPES[ $package->get_type() ] );
		}

		return $builder->build();
	}

	/**
	 * Normalize a package name for packages.json.
	 *
	 * @since 0.1.0
	 *
	 * @link https://github.com/composer/composer/blob/79af9d45afb6bcaac8b73ae6a8ae24414ddf8b4b/src/Composer/Package/Loader/ValidatingArrayLoader.php#L339-L369
	 *
	 * @param string $name Package name.
	 * @return string
	 */
	protected function normalize_package_name( $name ): string {
		$name = strtolower( $name );
		return preg_replace( '/[^a-z0-9_\-\.]+/i', '', $name );
	}
}

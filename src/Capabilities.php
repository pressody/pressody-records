<?php
/**
 * Capabilities.
 *
 * Meta capabilities are mapped to primitive capabilities in
 * \PixelgradeLT\Records\Provider\Capabilities.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records;

/**
 * Capabilities.
 *
 * @since 0.1.0
 */
final class Capabilities {
	/**
	 * Primitive capability for downloading packages.
	 *
	 * @var string
	 */
	const DOWNLOAD_PACKAGES = 'pixelgradelt_records_download_packages';

	/**
	 * Meta capability for downloading a specific package.
	 *
	 * @var string
	 */
	const DOWNLOAD_PACKAGE = 'pixelgradelt_records_download_package';

	/**
	 * Primitive capability for viewing packages.
	 *
	 * @var string
	 */
	const VIEW_PACKAGES = 'pixelgradelt_records_view_packages';

	/**
	 * Meta capability for viewing a specific package.
	 *
	 * @var string
	 */
	const VIEW_PACKAGE = 'pixelgradelt_records_view_package';

	/**
	 * Primitive capability for managing options.
	 *
	 * @var string
	 */
	const MANAGE_OPTIONS = 'pixelgradelt_records_manage_options';

	/**
	 * Register capabilities.
	 *
	 * @since 0.1.0
	 */
	public static function register() {
		$wp_roles = wp_roles();
		$wp_roles->add_cap( 'administrator', self::DOWNLOAD_PACKAGES );
		$wp_roles->add_cap( 'administrator', self::VIEW_PACKAGES );
		$wp_roles->add_cap( 'administrator', self::MANAGE_OPTIONS );
	}
}

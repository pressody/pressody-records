<?php
/**
 * Package Types.
 *
 * The slugs MUST BE THE SAME as the types defined by composer/installers.
 * @link https://packagist.org/packages/composer/installers
 *
 * @since   0.9.0
 * @license GPL-2.0-or-later
 * @package Pressody
 */

declare ( strict_types=1 );

namespace Pressody\Records\PackageType;

/**
 * Package Types.
 *
 * @since 0.9.0
 */
final class PackageTypes {
	/**
	 * Package type ID for WordPress Plugin packages.
	 * @link https://packagist.org/packages/composer/installers
	 *
	 * @var string
	 */
	const PLUGIN = 'wordpress-plugin';

	/**
	 * Package type ID for WordPress Theme packages.
	 * @link https://packagist.org/packages/composer/installers
	 *
	 * @var string
	 */
	const THEME = 'wordpress-theme';

	/**
	 * Package type ID for WordPress Must-Use Plugin packages.
	 * @link https://packagist.org/packages/composer/installers
	 *
	 * @var string
	 */
	const MUPLUGIN = 'wordpress-muplugin';

	/**
	 * Package type ID for WordPress Drop-In Plugin packages.
	 * @link https://packagist.org/packages/composer/installers
	 *
	 * @var string
	 */
	const DROPINPLUGIN = 'wordpress-dropin';

	/**
	 * Package type ID for WordPress Core packages.
	 * @link https://packagist.org/packages/roots/wordpress-core-installer
	 *
	 * @var string
	 */
	const WPCORE = 'wordpress-core';

	const DETAILS = [
		self::PLUGIN => [
			'name'        => 'WordPress Plugin',
			'description' => 'A WordPress plugin package.',
		],
		self::THEME => [
			'name'        => 'WordPress Theme',
			'description' => 'A WordPress theme package.',
		],
		self::MUPLUGIN => [
			'name'        => 'WordPress Must-Use Plugin',
			'description' => 'A WordPress Must-Use plugin package.',
		],
		self::DROPINPLUGIN => [
			'name'        => 'WordPress Drop-in Plugin',
			'description' => 'A WordPress Drop-in plugin package.',
		],
		self::WPCORE => [
			'name'        => 'WordPress Core',
			'description' => 'A WordPress core (source-code) package.',
		],
	];
}

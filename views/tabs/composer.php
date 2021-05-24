<?php
/**
 * Views: Composer tab content.
 *
 * @since   1.0.0
 * @license GPL-2.0-or-later
 * @package PixelgradeLT
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records;

use PixelgradeLT\Records\PackageType\BasePackage;

/**
 * @global BasePackage[] $packages
 * @global string $packages_permalink
 * @global string $parts_permalink
 * @global array $system_checks
 */
?>

<p>
	<?php esc_html_e( 'Your PixelgradeLT Records repository is available at:', 'pixelgradelt_records' ); ?>
	<a href="<?php echo esc_url( $packages_permalink ); ?>"><?php echo esc_html( $packages_permalink ); ?></a>. This includes <strong>all your packages, regardless of type.</strong>
</p>
<p>
	<?php
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Need to update global variable.
	$allowed_html = [ 'code' => [] ];
	printf(
		/* translators: 1: <code>repositories</code>, 2: <code>composer.json</code> */
		esc_html__( 'Add it to the %1$s list in your %2$s:', 'pixelgradelt_records' ),
		'<code>repositories</code>',
		'<code>composer.json</code>'
	);
	?>
</p>

<pre class="pixelgradelt_records-composer-snippet"><code>{
	"repositories": {
		"pixelgradelt_records": {
			"type": "composer",
			"url": "<?php echo esc_url( get_packages_permalink( [ 'base' => true ] ) ); ?>"
		}
	}
}</code></pre>

<?php
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Need to update global variable.
$allowed_html = [ 'code' => [] ];
printf(
	/* translators: 1: <code>config</code> */
	esc_html__( 'Or run the %1$s command:', 'pixelgradelt_records' ),
	'<code>config</code>'
);
?>

<p>
	<input
		type="text"
		class="pixelgradelt_records-cli-field large-text"
		readonly
		value="composer config repositories.pixelgradelt_records composer <?php echo esc_url( get_packages_permalink( [ 'base' => true ] ) ); ?>"
		onclick="this.select();"
	>
</p>

<p>
	<?php esc_html_e( 'You also have the PixelgradeLT Records Parts repository available at:', 'pixelgradelt_records' ); ?>
	<a href="<?php echo esc_url( $parts_permalink ); ?>"><?php echo esc_html( $parts_permalink ); ?></a>. This is a repository that <strong>includes just the parts</strong> (a subset of the main repository above).
</p>

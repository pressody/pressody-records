<?php
/**
 * Views: Package details
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.5.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records;

/**
 * @global Package $package
 */

?>

<table class="pixelgradelt_records-package widefat">
	<thead>
	<tr>
		<th colspan="2"><?php echo esc_html( $package->get_name() ); ?></th>
	</tr>
	</thead>
	<tbody>
	<?php
	$description = $package->get_description();
	if ( $description ) :
		?>
		<tr>
			<td colspan="2"><?php echo esc_html( wp_strip_all_tags( $description ) ); ?></td>
		</tr>
	<?php endif; ?>

	<?php
	$homepage = $package->get_homepage();
	if ( ! empty( $homepage ) ) { ?>
		<tr>
			<th><?php esc_html_e( 'Homepage', 'pixelgradelt_records' ); ?></th>
			<td><a href="<?php echo esc_url( $homepage ); ?>" target="_blank" rel="noopener noreferer"><?php echo esc_html( $homepage ); ?></a></td>
		</tr>
	<?php }

	$authors = $package->get_authors();
	if ( ! empty( $authors ) ) { ?>
	<tr>
		<th><?php esc_html_e( 'Authors', 'pixelgradelt_records' ); ?></th>
		<?php foreach ( $authors as $author ) { ?>

			<td><a href="<?php echo esc_url( $author['homepage'] ); ?>" target="_blank" rel="noopener noreferer"><?php echo esc_html( $author['name'] ); ?></a></td>

		<?php } ?>
	</tr>
	<?php } ?>

	<tr>
		<th><?php esc_html_e( 'Releases', 'pixelgradelt_records' ); ?></th>
		<td class="pixelgradelt_records-releases">
			<?php
			if ( $package->has_releases() ) {
				$versions = array_map(
					function( $release ) {
							return sprintf(
								'<a href="%1$s" data-version="%2$s" class="button pixelgradelt_records-release">%3$s</a>',
								esc_url( $release->get_download_url() ),
								esc_attr( $release->get_version() ),
								esc_html( $release->get_version() )
							);
					},
					$package->get_releases()
				);

				// Prepend the latest release.
				array_unshift(
					$versions,
					sprintf(
						'<a href="%1$s" data-version="%2$s" class="button pixelgradelt_records-release">%3$s</a>',
						esc_url( $package->get_latest_download_url() ),
						esc_attr( $package->get_latest_release()->get_version() ),
						esc_html_x( 'Latest', 'latest version', 'pixelgradelt_records' )
					)
				);

				echo wp_kses(
					implode( ' ', array_filter( $versions ) ),
					[
						'a' => [
							'class'        => true,
							'data-version' => true,
							'href'         => true,
						],
					]
				);
			}
			?>
		</td>
	</tr>
	<tr>
		<th><?php esc_html_e( 'Package Type', 'pixelgradelt_records' ); ?></th>
		<td><code><?php echo esc_html( $package->get_type() ); ?></code></td>
	</tr>
	</tbody>
</table>

<?php
/**
 * Views: Packages page
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records;

/**
 * @global $permalink
 */

$allowed_tags = [
	'a'  => [
		'href' => true,
	],
	'em' => [],
	'code' => [],
];

if ( ! empty( $packages ) ) { ?>
	<div class="pixelgradelt_records-card">
		<p>
			<?php
			printf( __( 'These are <strong>all the packages</strong> that PixelgradeLT Records makes available as Composer packages, regardless of their configuration.<br>
This view is primarily available to assist in <strong>double-checking that things work properly.</strong><br>
If you want to <strong>dig deeper,</strong> check <a href="%s" target="_blank">the actual JSON</a> of the PixelgradeLT Records repo.', 'pixelgradelt_records' ), esc_url( $permalink ) ); ?>
		</p>
	</div>
<?php }

/** @global Package[] $packages */
foreach ( $packages as $package ) :
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
		if ( $homepage ) :
			?>
			<tr>
				<th><?php esc_html_e( 'Homepage', 'pixelgradelt_records' ); ?></th>
				<td><a href="<?php echo esc_url( $homepage ); ?>" target="_blank" rel="noopener noreferer"><?php echo esc_html( $homepage ); ?></a></td>
			</tr>
		<?php endif; ?>

		<tr>
			<th><?php esc_html_e( 'Authors', 'pixelgradelt_records' ); ?></th>
			<td><a href="<?php echo esc_url( $package->get_author_url() ); ?>" target="_blank" rel="noopener noreferer"><?php echo esc_html( $package->get_author() ); ?></a></td>
		</tr>
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
	<?php
endforeach;

if ( empty( $packages ) ) :
	?>
	<div class="pixelgradelt_records-card">
		<h3><?php esc_html_e( 'No packages defined', 'pixelgradelt_records' ); ?></h3>
		<p>
			<?php esc_html_e( 'Plugins and themes need to be configured as Pixelgrade LT packages to make them available as Composer packages.', 'pixelgradelt_records' ); ?>
		</p>
		<p>
			<?php echo wp_kses( __( 'Go to <code>LT Packages > Add New</code> and start managing your first package.', 'pixelgradelt_records' ), $allowed_tags ); ?>
		</p>
	</div>
	<?php
endif;

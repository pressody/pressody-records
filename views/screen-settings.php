<?php
/**
 * Views: Settings page
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records;

use PixelgradeLT\Records\PackageType\BasePackage;

/**
 * @global string $packages_permalink
 * @global string $parts_permalink
 * @global array $system_checks
 * @global string $active_tab
 * @global array $tabs
 */

?>

<div class="pixelgradelt_records-screen">
	<div class="pixelgradelt_records-screen-content wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<h2 class="nav-tab-wrapper">
			<?php
			foreach ( $tabs as $tab_id => $tab_data ) {
				if ( ! current_user_can( $tab_data['capability'] ) ) {
					continue;
				}

				printf(
						'<a href="#pixelgradelt_records-%1$s" class="nav-tab%2$s">%3$s</a>',
						esc_attr( $tab_id ),
						$active_tab === $tab_id ? ' nav-tab-active' : '',
						esc_html( $tab_data['name'] )
				);
			}
			?>
		</h2>

		<?php
		foreach ( $tabs as $tab_id => $tab_data ) {
			if ( ! current_user_can( $tab_data['capability'] ) ) {
				continue;
			}

			printf(
					'<div id="pixelgradelt_records-%1$s" class="pixelgradelt_records-%1$s pixelgradelt_records-tab-panel%2$s">',
					esc_attr( $tab_id ),
					$active_tab === $tab_id ? ' is-active' : ''
			);

			require $this->plugin->get_path( "views/tabs/{$tab_id}.php" );

			echo '</div>';
		}
		?>
	</div>

	<div id="pixelgradelt_records-screen-sidebar" class="pixelgradelt_records-screen-sidebar"></div>
</div>

<?php
/**
 * Plugin activation routines.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Provider;

use Cedaro\WP\Plugin\AbstractHookProvider;

/**
 * Class to activate the plugin.
 *
 * @since 0.1.0
 */
class Activation extends AbstractHookProvider {

	// use this in case you need to update the table structures
	const DB_VERSION = '0.15.0';

	/**
	 * Register hooks.
	 *
	 * @since 0.1.0
	 */
	public function register_hooks() {
		register_activation_hook( $this->plugin->get_file(), [ $this, 'activate' ] );
	}

	/**
	 * Activate the plugin.
	 *
	 * - Sets a flag to flush rewrite rules after plugin rewrite rules have been
	 *   registered.
	 * - Registers capabilities for the admin role.
	 *
	 * @see \PixelgradeLT\Records\Provider\RewriteRules::maybe_flush_rewrite_rules()
	 *
	 * @since 0.1.0
	 */
	public function activate() {
		update_option( 'pixelgradelt_records_flush_rewrite_rules', 'yes' );

		$this->create_cron_jobs();
		$this->create_or_update_tables();
	}

	/**
	 * Create cron jobs (clear them first).
	 */
	private function create_cron_jobs() {
		// None so far.
	}

	private function create_or_update_tables() {
		global $wpdb;

		// We only do something if the DB version is different
		if ( self::DB_VERSION === get_option( 'pixelgradelt_records_dbversion' ) ) {
			return;
		}

		// Create/update the log table
		$log_table = "
CREATE TABLE {$wpdb->prefix}pixelgradelt_records_log (
  log_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  timestamp datetime NOT NULL,
  level smallint(4) NOT NULL,
  source varchar(200) NOT NULL,
  message longtext NOT NULL,
  context longtext NULL,
  PRIMARY KEY (log_id),
  KEY level (level)
)";
		$this->dbdelta( $log_table );

		// Remember the current DB version
		update_option( 'pixelgradelt_records_dbversion', self::DB_VERSION );
	}

	protected function dbdelta( $sql ) {
		global $wpdb;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$charset_collate = '';
		if ( $wpdb->has_cap( 'collation' ) ) {
			$charset_collate = $wpdb->get_charset_collate();
		}

		$sql .= " $charset_collate;";

		dbDelta( $sql );
	}

	/**
	 * Drop tables.
	 *
	 * @return void
	 */
	public static function drop_tables() {
		global $wpdb;

		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}pixelgradelt_records_log" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}

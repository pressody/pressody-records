<?php
/**
 * Log handling functionality.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.9.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Logging\Handler;

/**
 * Abstract WC Log Handler Class
 *
 * @since 0.9.0
 */
abstract class LogHandler implements LogHandlerInterface {

	/**
	 * Formats a timestamp for use in log messages.
	 *
	 * @param int $timestamp Log timestamp.
	 *
	 * @return string Formatted time for use in log entry.
	 */
	protected static function format_time( int $timestamp ): string {
		return date( 'c', $timestamp );
	}

	/**
	 * Builds a log entry text from level, timestamp and message.
	 *
	 * @param int    $timestamp Log timestamp.
	 * @param string $level     emergency|alert|critical|error|warning|notice|info|debug.
	 * @param string $message   Log message.
	 * @param array  $context   Additional information for log handlers.
	 *
	 * @return string Formatted log entry.
	 */
	protected static function format_entry( int $timestamp, string $level, string $message, array $context ): string {
		$time_string  = self::format_time( $timestamp );
		$level_string = strtoupper( $level );
		$entry        = "{$time_string} {$level_string} {$message}";

		return apply_filters(
			'pixelgradelt_records_format_log_entry',
			$entry,
			array(
				'timestamp' => $timestamp,
				'level'     => $level,
				'message'   => $message,
				'context'   => $context,
			)
		);
	}
}

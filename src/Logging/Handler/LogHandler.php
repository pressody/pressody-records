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
	 * @since 0.9.0
	 *
	 * @param int $timestamp Log timestamp.
	 *
	 * @return string Formatted time for use in log entry.
	 */
	protected function format_time( int $timestamp ): string {
		return date( 'c', $timestamp );
	}

	/**
	 * Builds a log entry text from level, timestamp and message.
	 *
	 * - Interpolates context values into message placeholders.
	 * - Appends additional context data as JSON.
	 *
	 * @since 0.9.0
	 *
	 * @param int    $timestamp Log timestamp.
	 * @param string $level     emergency|alert|critical|error|warning|notice|info|debug.
	 * @param string $message   Log message.
	 * @param array  $context   Additional information for log handlers.
	 *
	 * @return string Formatted log entry.
	 */
	protected function format_entry( int $timestamp, string $level, string $message, array $context ): string {
		$time_string  = $this->format_time( $timestamp );
		$level_string = strtoupper( $level );
		$entry        = "{$time_string} {$level_string} {$message}";

		$search  = [];
		$replace = [];

		$temp_context = $context;
		foreach ( $temp_context as $key => $value ) {
			$placeholder = '{' . $key . '}';

			if ( false === strpos( $message, $placeholder ) ) {
				continue;
			}

			array_push( $search, '{' . $key . '}' );
			array_push( $replace, $this->to_string( $value ) );
			unset( $temp_context[ $key ] );
		}

		$entry = str_replace( $search, $replace, $entry );

//		// Append additional context data.
//		if ( ! empty( $temp_context ) ) {
//			$entry .= ' ' . wp_json_encode( $temp_context, \JSON_UNESCAPED_SLASHES );
//		}

		return apply_filters(
			'pixelgradelt_records/format_log_entry',
			$entry,
			[
				'timestamp' => $timestamp,
				'level'     => $level,
				'message'   => $message,
				'context'   => $context,
			]
		);
	}

	/**
	 * Convert a value to a string.
	 *
	 * @since 0.9.0
	 *
	 * @param mixed $value Message.
	 * @return string
	 */
	protected function to_string( $value ): string {
		if ( is_wp_error( $value ) ) {
			$value = $value->get_error_message();
		} elseif ( is_object( $value ) && method_exists( '__toString', $value ) ) {
			$value = (string) $value;
		} elseif ( ! is_scalar( $value ) ) {
			$value = wp_json_encode( $value, \JSON_UNESCAPED_SLASHES, 128 );
		}

		return (string) $value;
	}
}

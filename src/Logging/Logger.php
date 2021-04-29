<?php
/**
 * Logger that dispatches log messages to the registered handlers.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.9.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Logging;

use Composer\IO\BaseIO;
use Exception;
use PixelgradeLT\Records\Utils\JSONCleaner;
use Psr\Log\LogLevel;
use function PixelgradeLT\Records\doing_it_wrong;

/**
 * Default logger class.
 *
 * @since 0.9.0
 */
final class Logger extends BaseIO {
	/**
	 * PSR log levels.
	 *
	 * @since 0.1.0
	 * @var array
	 */
	protected array $levels = [
		LogLevel::DEBUG,
		LogLevel::INFO,
		LogLevel::NOTICE,
		LogLevel::WARNING,
		LogLevel::ERROR,
		LogLevel::CRITICAL,
		LogLevel::ALERT,
		LogLevel::EMERGENCY,
	];

	/**
	 * Minimum log level.
	 *
	 * @since 0.1.0
	 * @var int
	 */
	protected int $minimum_level_severity;

	/**
	 * Stores registered log handlers.
	 *
	 * @since 0.9.0
	 * @var array
	 */
	protected array $handlers;

	/**
	 * Constructor method.
	 *
	 * @since 0.1.0
	 *
	 * @param string     $minimum_level Minimum level to log.
	 * @param array|null $handlers      Optional. Array of log handlers. If $handlers is not provided, the filter 'pixelgradelt_records_register_log_handlers' will be used to define the handlers. If $handlers is provided, the filter will not be applied and the handlers will be used directly.
	 */
	public function __construct( string $minimum_level, array $handlers = null ) {
		$this->minimum_level_severity = LogLevels::get_level_severity( $minimum_level );

		if ( null === $handlers ) {
			$handlers = apply_filters( 'pixelgradelt_records_register_log_handlers', array() );
		}

		$register_handlers = array();

		if ( ! empty( $handlers ) && is_array( $handlers ) ) {
			foreach ( $handlers as $handler ) {
				$implements = class_implements( $handler );
				if ( is_object( $handler ) && is_array( $implements ) && in_array( 'PixelgradeLT\Records\Logging\Handler\LogHandlerInterface', $implements, true ) ) {
					$register_handlers[] = $handler;
				} else {
					doing_it_wrong(
						__METHOD__,
						sprintf(
						/* translators: 1: class name 2: WC_Log_Handler_Interface */
							__( 'The provided handler %1$s does not implement %2$s.', '__plugin_txtd' ),
							'<code>' . esc_html( is_object( $handler ) ? get_class( $handler ) : $handler ) . '</code>',
							'<code>PixelgradeLT\Records\Logging\Handler\LogHandlerInterface</code>'
						),
						'0.9.0'
					);
				}
			}
		}

		$this->handlers  = $register_handlers;
	}

	/**
	 * Log a message.
	 *
	 * @since 0.1.0
	 *
	 * @param string $level   PSR log level.
	 * @param string $message Log message.
	 * @param array  $context Additional data.
	 */
	public function log( $level, $message, array $context = [] ) {
		if ( ! LogLevels::is_valid_level( $level ) ) {
			/* translators: 1: WC_Logger::log 2: level */
			doing_it_wrong( __METHOD__, sprintf( __( '%1$s was called with an invalid level "%2$s".', '__plugin_txtd' ), '<code>PixelgradeLT\Records\Logging\Logger::log</code>', $level ), '0.9.0' );
		}

		if ( ! $this->should_handle( $level ) ) {
			return;
		}

		$timestamp = current_time( 'timestamp', 1 );
		$message   = apply_filters( 'pixelgradelt_records_logger_log_message', $message, $level, $context );

		foreach ( $this->handlers as $handler ) {
			$handler->handle( $timestamp, $level, $message, $context );
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
//		error_log(
//			sprintf(
//				'PIXELGRADELT_RECORDS.%s: %s',
//				strtoupper( $level ),
//				$this->format( $message, $context )
//			)
//		);
	}

	/**
	 * Format a message.
	 *
	 * - Interpolates context values into message placeholders.
	 * - Appends additional context data as JSON.
	 * - Appends exception data.
	 *
	 * @since 0.1.0
	 *
	 * @param string $message Log message.
	 * @param array  $context Additional data.
	 * @return string
	 */
	protected function format( string $message, array $context = [] ): string {
		$search  = [];
		$replace = [];

		// Extract exceptions from the context array.
		$exception = $context['exception'] ?? null;
		unset( $context['exception'] );

		foreach ( $context as $key => $value ) {
			$placeholder = '{' . $key . '}';

			if ( false === strpos( $message, $placeholder ) ) {
				continue;
			}

			array_push( $search, '{' . $key . '}' );
			array_push( $replace, $this->to_string( $value ) );
			unset( $context[ $key ] );
		}

		$line = str_replace( $search, $replace, $message );

		// Append additional context data.
		if ( ! empty( $context ) ) {
			$line .= ' ' . wp_json_encode( $context, \JSON_UNESCAPED_SLASHES );
		}

		// Append an exception.
		if ( ! empty( $exception ) && $exception instanceof Exception ) {
			$line .= ' ' . $this->format_exception( $exception );
		}

		return $line;
	}

	/**
	 * Format an exception.
	 *
	 * @since 0.1.0
	 *
	 * @param Exception $e Exception.
	 * @return string
	 */
	protected function format_exception( Exception $e ): string {
		// Since the trace may contain in a step's args circular references, we need to replace such references with a string.
		// This is to avoid infinite recursion when attempting to json_encode().
		$trace = JSONCleaner::clean( $e->getTrace(), 6 );
		$encoded_exception = wp_json_encode(
			[
				'message' => $e->getMessage(),
				'code'    => $e->getCode(),
				'file'    => $e->getFile(),
				'line'    => $e->getLine(),
				'trace'   => $trace,
			],
			\JSON_UNESCAPED_SLASHES
		);

		if ( ! is_string( $encoded_exception ) ) {
			return 'failed-to-encode-exception';
		}

		return $encoded_exception;
	}

	/**
	 * Convert a value to a string.
	 *
	 * @since 0.1.0
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

		return $value;
	}

	/**
	 * Whether a message with a given level should be logged.
	 *
	 * @since 0.1.0
	 *
	 * @param string $level PSR Log level (emergency|alert|critical|error|warning|notice|info|debug).
	 *
	 * @return bool
	 */
	protected function should_handle( string $level ): bool {
		return $this->minimum_level_severity >= 0 && $this->minimum_level_severity <= LogLevels::get_level_severity( $level );
	}

	/*
	 * IOInterface bridge
	 */

	/**
	 * {@inheritDoc}
	 */
	public function isInteractive() {
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isVerbose() {
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isVeryVerbose() {
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isDebug() {
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isDecorated() {
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function write($messages, $newline = true, $verbosity = self::NORMAL) {
		if ( is_array( $messages ) ) {
			$separator = ' | ';
			if ( $newline ) {
				$separator = PHP_EOL;
			}
			$messages = implode( $separator, $messages );
		}

		$this->log( $this->matchVerbosityWithLevel( $verbosity ), $messages );
	}

	/**
	 * {@inheritDoc}
	 */
	public function writeError($messages, $newline = true, $verbosity = self::NORMAL) {
		$this->write( $messages, $newline, $verbosity );
	}

	/**
	 * {@inheritDoc}
	 */
	public function overwrite($messages, $newline = true, $size = 80, $verbosity = self::NORMAL) {
		// Nothing to do.
	}

	/**
	 * {@inheritDoc}
	 */
	public function overwriteError($messages, $newline = true, $size = 80, $verbosity = self::NORMAL) {
		// Nothing to do.
	}

	/**
	 * {@inheritDoc}
	 */
	public function ask($question, $default = null) {
		return $default;
	}

	/**
	 * {@inheritDoc}
	 */
	public function askConfirmation($question, $default = true) {
		return $default;
	}

	/**
	 * {@inheritDoc}
	 */
	public function askAndValidate($question, $validator, $attempts = false, $default = null) {
		return $default;
	}

	/**
	 * {@inheritDoc}
	 */
	public function askAndHideAnswer($question) {
		return null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function select($question, $choices, $default, $attempts = false, $errorMessage = 'Value "%s" is invalid', $multiselect = false) {
		return $default;
	}

	protected function matchVerbosityWithLevel( $verbosity ) {
		$logLevel = $this->minimum_level_severity;
		switch ( $verbosity ) {
			case self::QUIET:
				$logLevel = LogLevel::EMERGENCY;
				break;
			case self::NORMAL:
				$logLevel = LogLevel::ERROR;
				break;
			case self::VERBOSE:
				$logLevel = LogLevel::WARNING;
				break;
			case self::VERY_VERBOSE:
				$logLevel = LogLevel::INFO;
				break;
			case self::DEBUG:
				$logLevel = LogLevel::DEBUG;
				break;
			default:
				break;
		}

		return $logLevel;
	}

	/**
	 * Clear entries for a chosen file/source.
	 *
	 * @param string $source Source/handle to clear.
	 * @return bool
	 */
	public function clear( $source = '' ): bool {
		if ( ! $source ) {
			return false;
		}
		foreach ( $this->handlers as $handler ) {
			if ( is_callable( [ $handler, 'clear' ] ) ) {
				$handler->clear( $source );
			}
		}
		return true;
	}

	/**
	 * Clear all logs older than a defined number of days. Defaults to 30 days.
	 *
	 * @since 0.9.0
	 */
	public function clear_expired_logs() {
		$days      = absint( apply_filters( 'pixelgradelt_records_logger_days_to_retain_logs', 30 ) );
		$timestamp = strtotime( "-{$days} days" );

		foreach ( $this->handlers as $handler ) {
			if ( is_callable( [ $handler, 'delete_logs_before_timestamp' ] ) ) {
				$handler->delete_logs_before_timestamp( $timestamp );
			}
		}
	}
}

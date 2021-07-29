<?php
/**
 * Logs management routines.
 *
 * @since   0.9.0
 * @license GPL-2.0-or-later
 * @package PixelgradeLT
 */

declare ( strict_types=1 );

namespace PixelgradeLT\Records\Logging;

use Cedaro\WP\Plugin\AbstractHookProvider;
use PixelgradeLT\Records\Queue\QueueInterface;
use Psr\Log\LoggerInterface;

/**
 * Class to manage logs.
 *
 * @since 0.9.0
 */
class LogsManager extends AbstractHookProvider {

	/**
	 * Logger.
	 *
	 * @since 0.9.0
	 *
	 * @var LoggerInterface
	 */
	protected LoggerInterface $logger;

	/**
	 * Queue.
	 *
	 * @since 0.15.0
	 *
	 * @var QueueInterface
	 */
	protected QueueInterface $queue;

	/**
	 * Constructor.
	 *
	 * @since 0.9.0
	 *
	 * @param LoggerInterface $logger Logger.
	 * @param QueueInterface  $queue  Queue.
	 */
	public function __construct(
		LoggerInterface $logger,
		QueueInterface $queue
	) {
		$this->logger = $logger;
		$this->queue  = $queue;
	}

	/**
	 * Register hooks.
	 *
	 * @since 0.9.0
	 */
	public function register_hooks() {
		$this->add_action( 'init', 'schedule_cleanup_logs_event' );
		$this->add_action( 'pixelgradelt_records/midnight', 'cleanup_logs' );
	}

	/**
	 * Maybe schedule the action/event to run logs cleanup at, if it is not already scheduled.
	 *
	 * @since 0.15.0
	 */
	protected function schedule_cleanup_logs_event() {
		if ( ! $this->queue->get_next( 'pixelgradelt_records/midnight' ) ) {
			$this->queue->schedule_recurring( strtotime( 'tomorrow' ), DAY_IN_SECONDS, 'pixelgradelt_records/midnight', [], 'plt_rec' );
		}
	}

	protected function cleanup_logs() {
		if ( is_callable( array( $this->logger, 'clear_expired_logs' ) ) ) {
			$this->logger->clear_expired_logs();
		}
	}
}

<?php
/**
 * Upgrade routines.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Provider;

use Cedaro\WP\Plugin\AbstractHookProvider;
use Psr\Log\LoggerInterface;
use PixelgradeLT\Records\Exception\PixelgradeltRecordsException;
use PixelgradeLT\Records\Capabilities;
use PixelgradeLT\Records\Htaccess;
use PixelgradeLT\Records\ReleaseManager;
use PixelgradeLT\Records\Repository\PackageRepository;
use PixelgradeLT\Records\Storage\Local;
use PixelgradeLT\Records\Storage\Storage;

use const PixelgradeLT\Records\VERSION;

/**
 * Class for upgrade routines.
 *
 * @since 0.1.0
 */
class Upgrade extends AbstractHookProvider {
	/**
	 * Version option name.
	 *
	 * @var string
	 */
	const VERSION_OPTION_NAME = 'pixelgradelt_records_version';

	/**
	 * Htaccess handler.
	 *
	 * @var Htaccess
	 */
	protected $htaccess;

	/**
	 * Logger.
	 *
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * Release manager.
	 *
	 * @var ReleaseManager
	 */
	protected $release_manager;

	/**
	 * Package repository.
	 *
	 * @var PackageRepository
	 */
	protected $repository;

	/**
	 * Storage service.
	 *
	 * @var Storage
	 */
	protected $storage;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param PackageRepository $repository      Package repository.
	 * @param ReleaseManager    $release_manager Release manager.
	 * @param Storage           $storage         Storage service.
	 * @param Htaccess          $htaccess        Htaccess handler.
	 * @param LoggerInterface   $logger          Logger.
	 */
	public function __construct(
		PackageRepository $repository,
		ReleaseManager $release_manager,
		Storage $storage,
		Htaccess $htaccess,
		LoggerInterface $logger
	) {
		$this->htaccess        = $htaccess;
		$this->repository      = $repository;
		$this->release_manager = $release_manager;
		$this->storage         = $storage;
		$this->logger          = $logger;
	}

	/**
	 * Register hooks.
	 *
	 * @since 0.1.0
	 */
	public function register_hooks() {
		add_action( 'admin_init', [ $this, 'maybe_upgrade' ] );
	}

	/**
	 * Upgrade when the database version is outdated.
	 *
	 * @since 0.1.0
	 */
	public function maybe_upgrade() {
		$saved_version = get_option( self::VERSION_OPTION_NAME, '0' );

//		if ( version_compare( $saved_version, '0.3.0-dev1', '<' ) ) {
//			Capabilities::register();
//			$this->setup_storage();
//			$this->cache_packages();
//		}

		if ( version_compare( $saved_version, VERSION, '<' ) ) {
			update_option( self::VERSION_OPTION_NAME, VERSION );
		}
	}
}

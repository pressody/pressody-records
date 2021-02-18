<?php
/**
 * Client to communicate with an external Composer repository.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Client;

use Composer\Composer;
use Composer\Factory;
use Composer\IO\BaseIO;
use Psr\Log\LoggerInterface;

/**
 * Class for communicating with an external Composer repository.
 *
 * @since 0.1.0
 */
class ComposerClient implements Client {

	/**
	 * The Composer instance.
	 *
	 * @var Composer
	 */
	protected $composer;

	/**
	 * Logger that also implements the IOInterface so it can be used with Composer.
	 *
	 * @var LoggerInterface|BaseIO
	 */
	protected $logger;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param BaseIO $logger
	 */
	public function __construct(
		BaseIO $logger
	) {
		$this->logger = $logger;
	}

	/**
	 * @param array|string|null $config Either a configuration array or a filename to read from, if null it will read from the default filename
	 *
	 * @return Composer
	 */
	public function getComposer( $config = null ): Composer {
		if ( null === $this->composer ) {
			try {
				$this->composer = Factory::create( $this->logger, $config );
			} catch ( \InvalidArgumentException $e ) {
				$this->logger->error( $e->getMessage() );
				exit( 1 );
			}
		}

		return $this->composer;
	}
}

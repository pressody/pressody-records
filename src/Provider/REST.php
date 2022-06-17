<?php
/**
 * REST provider.
 *
 * @since   0.10.0
 * @license GPL-2.0-or-later
 * @package Pressody
 */

declare ( strict_types = 1 );

namespace Pressody\Records\Provider;

use Cedaro\WP\Plugin\AbstractHookProvider;
use Pimple\ServiceIterator;
use WP_REST_Controller;

/**
 * REST provider class.
 *
 * @since 0.10.0
 */
class REST extends AbstractHookProvider {
	/**
	 * REST controllers.
	 *
	 * @var ServiceIterator
	 */
	protected ServiceIterator $controllers;

	/**
	 * Constructor.
	 *
	 * @param ServiceIterator $controllers REST controllers.
	 */
	public function __construct( ServiceIterator $controllers ) {
		$this->controllers = $controllers;
	}

	/**
	 * Register hooks.
	 *
	 * @since 0.10.0
	 */
	public function register_hooks() {
		add_action( 'rest_api_init', [ $this, 'register_rest_controllers' ] );
	}

	/**
	 * Register REST controllers.
	 *
	 * @since 0.10.0
	 *
	 * @throws \LogicException If a registered controller doesn't extend WP_REST_Controller.
	 */
	public function register_rest_controllers() {
		foreach ( $this->controllers as $controller ) {
			if ( ! $controller instanceof WP_REST_Controller ) {
				throw new \LogicException( 'Authentication servers must implement \WP_REST_Controller.' );
			}

			$controller->register_routes();
		}
	}
}

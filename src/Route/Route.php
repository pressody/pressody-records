<?php
/**
 * Route interface.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Route;

use PixelgradeLT\Records\HTTP\Request;
use PixelgradeLT\Records\HTTP\Response;

/**
 * Route interface.
 *
 * @package PixelgradeLT
 * @since 0.1.0
 */
interface Route {
	/**
	 * Handle a request.
	 *
	 * @since 0.1.0
	 *
	 * @param Request $request HTTP request.
	 * @return Response
	 */
	public function handle( Request $request ): Response;
}

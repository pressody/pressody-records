<?php
/**
 * Response body interface.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\HTTP\ResponseBody;

/**
 * Response body interface.
 *
 * @since 0.1.0
 */
interface ResponseBody {
	/**
	 * Emit the response body.
	 */
	public function emit();
}

<?php
/**
 * Composer packages.json endpoint.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Route;

use PixelgradeLT\Records\Capabilities;
use PixelgradeLT\Records\Exception\HttpException;
use PixelgradeLT\Records\HTTP\Request;
use PixelgradeLT\Records\HTTP\Response;
use PixelgradeLT\Records\HTTP\ResponseBody\JsonBody;
use PixelgradeLT\Records\Repository\PackageRepository;
use PixelgradeLT\Records\Transformer\PackageRepositoryTransformer;
use WP_Http as HTTP;

/**
 * Class for rendering packages.json for Composer.
 *
 * @since 0.1.0
 */
class Composer implements Route {
	/**
	 * Package repository.
	 *
	 * @var PackageRepository
	 */
	protected $repository;

	/**
	 * Repository transformer.
	 *
	 * @var PackageRepositoryTransformer
	 */
	protected $transformer;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param PackageRepository            $repository  Package repository.
	 * @param PackageRepositoryTransformer $transformer Package repository transformer.
	 */
	public function __construct( PackageRepository $repository, PackageRepositoryTransformer $transformer ) {
		$this->repository  = $repository;
		$this->transformer = $transformer;
	}

	/**
	 * Handle a request to the packages.json endpoint.
	 *
	 * @since 0.1.0
	 *
	 * @param Request $request HTTP request instance.
	 * @throws HTTPException If the user doesn't have permission to view packages.
	 * @return Response
	 */
	public function handle( Request $request ): Response {
		if ( ! current_user_can( Capabilities::VIEW_PACKAGES ) ) {
			throw HttpException::forForbiddenResource();
		}

		return new Response(
			new JsonBody( $this->transformer->transform( $this->repository ) ),
			HTTP::OK,
			[ 'Content-Type' => 'application/json; charset=' . get_option( 'blog_charset' ) ]
		);
	}
}

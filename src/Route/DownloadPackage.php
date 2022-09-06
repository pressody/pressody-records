<?php
/**
 * Download package request handler.
 *
 * @since   0.1.0
 * @license GPL-2.0-or-later
 * @package Pressody
 */

/*
 * This file is part of a Pressody module.
 *
 * This Pressody module is free software: you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software Foundation, either version 2 of the License,
 * or (at your option) any later version.
 *
 * This Pressody module is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this Pressody module.
 * If not, see <https://www.gnu.org/licenses/>.
 *
 * Copyright (c) 2021, 2022 Vlad Olaru (vlad@thinkwritecode.com)
 */

declare ( strict_types=1 );

namespace Pressody\Records\Route;

use Pressody\Records\Capabilities;
use Pressody\Records\Exception\HttpException;
use Pressody\Records\Exception\InvalidReleaseVersion;
use Pressody\Records\HTTP\Request;
use Pressody\Records\HTTP\Response;
use Pressody\Records\Package;
use Pressody\Records\PackageManager;
use Pressody\Records\ReleaseManager;
use Pressody\Records\Repository\PackageRepository;

/**
 * Class to handle download package requests.
 *
 * @since 0.1.0
 */
class DownloadPackage implements Route {
	/**
	 * Latest version.
	 *
	 * @var string
	 */
	const LATEST_VERSION = 'latest';

	/**
	 * Regex for sanitizing package slugs.
	 *
	 * @var string
	 */
	const PACKAGE_SLUG_REGEX = '/[^A-Za-z0-9._\-]+/i';

	/**
	 * Regex for sanitizing package versions.
	 *
	 * @var string
	 */
	const PACKAGE_VERSION_REGEX = '/[^0-9a-z.-]+/i';

	/**
	 * Package manager.
	 *
	 * @var PackageManager
	 */
	protected PackageManager $package_manager;

	/**
	 * Release manager.
	 *
	 * @var ReleaseManager
	 */
	protected ReleaseManager $release_manager;

	/**
	 * Package repository.
	 *
	 * @var PackageRepository
	 */
	protected PackageRepository $repository;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param PackageRepository $repository      Package repository.
	 * @param PackageManager    $package_manager Packages manager.
	 * @param ReleaseManager    $release_manager Release manager.
	 */
	public function __construct(
		PackageRepository $repository,
		PackageManager $package_manager,
		ReleaseManager $release_manager
	) {

		$this->repository      = $repository;
		$this->package_manager = $package_manager;
		$this->release_manager = $release_manager;
	}

	/**
	 * Process a download request.
	 *
	 * Determines if the current request is for packages.json or a whitelisted
	 * package and routes it to the appropriate method.
	 *
	 * @since 0.1.0
	 *
	 * @param Request $request HTTP request instance.
	 *
	 * @throws HTTPException For invalid parameters or the user doesn't have
	 *                       permission to download the requested file.
	 * @return Response
	 */
	public function handle( Request $request ): Response {
		if ( ! current_user_can( Capabilities::DOWNLOAD_PACKAGES ) ) {
			throw HttpException::forForbiddenResource();
		}

		$managed_package_post_id = 0;
		if ( ! empty( $request['hashid'] ) ) {
			// Decode the hashid.
			$managed_package_post_id = $this->package_manager->hash_decode_id( $request['hashid'] );
		}

		$slug = preg_replace( self::PACKAGE_SLUG_REGEX, '', $request['slug'] );
		if ( empty( $slug ) ) {
			throw HttpException::forUnknownPackage( $slug );
		}

		$version = '';
		if ( ! empty( $request['version'] ) ) {
			$version = preg_replace( self::PACKAGE_VERSION_REGEX, '', $request['version'] );
		}

		// If we have a package post ID, this will be the source of truth.
		if ( $managed_package_post_id > 0 ) {
			$post = get_post( $managed_package_post_id );
			if ( empty( $post ) ) {
				throw HttpException::forUnknownPackageHashid( $request['hashid'] );
			}
			$package = $this->repository->first_where( [ 'managed_post_id' => $post->ID ] );
		} else {
			$package = $this->repository->first_where( [ 'slug' => $slug ] );
		}

		// Send a 404 response if the package doesn't exist.
		if ( ! $package instanceof Package ) {
			throw HttpException::forUnknownPackage( $slug );
		}

		return $this->send_package( $package, $version );
	}

	/**
	 * Send a package zip.
	 *
	 * Sends a 404 header if the specified version isn't available.
	 *
	 * @since 0.1.0
	 *
	 * @param Package $package Package object.
	 * @param string  $version Version of the package to send.
	 *
	 * @throws HTTPException For invalid or missing releases.
	 * @return Response
	 */
	protected function send_package( Package $package, string $version ): Response {
		if ( self::LATEST_VERSION === $version ) {
			$version = $package->get_latest_release()->get_version();
		}

		try {
			$release = $package->get_release( $version );
		} catch ( InvalidReleaseVersion $e ) {
			throw HttpException::forInvalidRelease( $package, $version );
		}

		// Ensure the user has access to download the release.
		if ( ! current_user_can( Capabilities::DOWNLOAD_PACKAGE, $package, $release ) ) {
			throw HttpException::forForbiddenPackage( $package );
		}

		try {
			// Store the release if an artifact doesn't already exist.
			$release = $this->release_manager->store( $release );
		} catch ( \Exception $e ) {
			// Send a 404 if the release isn't available.
			throw HttpException::forMissingRelease( $release );
		}

		return $this->release_manager->send( $release );
	}
}

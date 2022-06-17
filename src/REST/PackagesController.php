<?php
/**
 * Packages REST controller.
 *
 * @since   0.10.0
 * @license GPL-2.0-or-later
 * @package Pressody
 */

declare ( strict_types=1 );

namespace Pressody\Records\REST;

use Pressody\Records\Capabilities;
use Pressody\Records\Exception\FileNotFound;
use Pressody\Records\Package;
use Pressody\Records\PackageType\LocalPlugin;
use Pressody\Records\PackageType\LocalTheme;
use Pressody\Records\PackageType\PackageTypes;
use Pressody\Records\PartManager;
use Pressody\Records\Repository\PackageRepository;
use Pressody\Records\Transformer\PackageTransformer;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Packages REST controller class.
 *
 * @since 0.10.0
 */
class PackagesController extends WP_REST_Controller {
	/**
	 * Package slug pattern.
	 *
	 * @var string
	 */
	const SLUG_PATTERN = '[^.\/]+(?:\/[^.\/]+)?';

	/**
	 * Composer package transformer.
	 *
	 * @var PackageTransformer
	 */
	protected PackageTransformer $composer_transformer;

	/**
	 * Package repository.
	 *
	 * @var PackageRepository
	 */
	protected PackageRepository $repository;

	/**
	 * Constructor.
	 *
	 * @since 0.10.0
	 *
	 * @param string             $namespace            The namespace for this controller's route.
	 * @param string             $rest_base            The base of this controller's route.
	 * @param PackageRepository  $repository           Package repository.
	 * @param PackageTransformer $composer_transformer Package transformer.
	 */
	public function __construct(
		string $namespace,
		string $rest_base,
		PackageRepository $repository,
		PackageTransformer $composer_transformer
	) {
		$this->namespace            = $namespace;
		$this->rest_base            = $rest_base;
		$this->repository           = $repository;
		$this->composer_transformer = $composer_transformer;
	}

	/**
	 * Register the routes.
	 *
	 * @since 0.10.0
	 *
	 * @see   register_rest_route()
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => $this->get_collection_params(),
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);
	}

	/**
	 * Check if a given request has access to view the resources.
	 *
	 * @since 0.10.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! current_user_can( Capabilities::VIEW_PACKAGES ) ) {
			return new WP_Error(
				'rest_cannot_read',
				esc_html__( 'Sorry, you are not allowed to view packages.', 'pressody_records' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		return true;
	}

	/**
	 * Retrieve a collection of packages.
	 *
	 * @since 0.10.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$items = [];

		$repository = $this->repository->with_filter(
			function ( $package ) use ( $request ) {
				if ( ! empty( $request['type'] ) && ! in_array( $package->get_type(), $request['type'], true ) ) {
					return false;
				}

				if ( ! empty( $request['postId'] ) && ! in_array( $package->get_managed_post_id(), $request['postId'] ) ) {
					return false;
				}

				if ( ! empty( $request['postSlug'] ) && ! in_array( $package->get_slug(), $request['postSlug'] ) ) {
					return false;
				}

				if ( ! empty( $request['packageName'] ) && ! in_array( $package->get_composer_package_name(), $request['packageName'] ) ) {
					return false;
				}

				return true;
			}
		);

		foreach ( $repository->all() as $package ) {
			$data    = $this->prepare_item_for_response( $package, $request );
			$items[] = $this->prepare_response_for_collection( $data );
		}

		return rest_ensure_response( $items );
	}

	/**
	 * Retrieve the query parameters for collections of packages.
	 *
	 * @since 0.10.0
	 *
	 * @return array
	 */
	public function get_collection_params(): array {
		$params = [
			'context' => $this->get_context_param( [ 'default' => 'view' ] ),
		];

		$params['postId'] = [
			'description'       => esc_html__( 'Limit results to packages by one or more (managed) post IDs.', 'pressody_records' ),
			'type'              => 'array',
			'items'             => [
				'type' => 'integer',
			],
			'default'           => [],
			'sanitize_callback' => 'wp_parse_id_list',
		];

		$params['postSlug'] = [
			'description'       => esc_html__( 'Limit results to packages by one or more (managed) post slugs.', 'pressody_records' ),
			'type'              => 'array',
			'items'             => [
				'type' => 'string',
			],
			'default'           => [],
			'sanitize_callback' => 'wp_parse_slug_list',
		];

		$params['packageName'] = [
			'description'       => esc_html__( 'Limit results to packages by one or more Composer package names (including the vendor). Use the "postSlug" parameter if you want to provide only the name, without the vendor.', 'pressody_records' ),
			'type'              => 'array',
			'items'             => [
				'type' => 'string',
			],
			'default'           => [],
			'sanitize_callback' => 'wp_parse_slug_list',
		];

		$params['type'] = [
			'description'       => esc_html__( 'Limit results to packages of one or more types.', 'pressody_records' ),
			'type'              => 'array',
			'items'             => [
				'type' => 'string',
			],
			'default'           => [
				PackageTypes::PLUGIN,
				PackageTypes::MUPLUGIN,
				PackageTypes::DROPINPLUGIN,
				PackageTypes::THEME,
				PackageTypes::WPCORE,
			],
			'sanitize_callback' => 'wp_parse_slug_list',
		];

		return $params;
	}

	/**
	 * Prepare a single package output for response.
	 *
	 * @since 0.10.0
	 *
	 * @param Package         $package Package instance.
	 * @param WP_REST_Request $request Request instance.
	 *
	 * @return WP_REST_Response Response instance.
	 */
	public function prepare_item_for_response( $package, $request ): WP_REST_Response {
		$composer = $this->composer_transformer->transform( $package );

		if ( $package instanceof LocalPlugin ) {
			$id = substr( $package->get_basename(), 0, - 4 );
		} elseif ( $package instanceof LocalTheme ) {
			$id = $package->get_slug();
		} else {
			$id = $package->get_slug();
		}

		$data = [
			'id'          => $id,
			'slug'        => $package->get_slug(),
			'name'        => $package->get_name(),
			'description' => $package->get_description(),
			'homepage'    => $package->get_homepage(),
			'authors'     => $package->get_authors(),
			'keywords'    => $package->get_keywords(),
			'type'        => $package->get_type(),
			'visibility'  => $package->get_visibility(),
			'editLink'    => get_edit_post_link( $package->get_managed_post_id(),$request['context'] ),
			'ltType'      => get_post_type( $package->get_managed_post_id() ) === PartManager::PACKAGE_POST_TYPE ? 'part' : 'package',
		];

		$data['composer'] = [
			'name' => $composer->get_name(),
			'type' => $composer->get_type(),
		];

		$data['releases']         = $this->prepare_releases_for_response( $package, $request );
		$data['requiredPackages'] = $this->prepare_required_packages_for_response( $package, $request );
		$data['replacedPackages'] = $this->prepare_replaced_packages_for_response( $package, $request );

		$data = $this->filter_response_by_context( $data, $request['context'] );

		return rest_ensure_response( $data );
	}

	/**
	 * Prepare package releases for response.
	 *
	 * @param Package         $package Package instance.
	 * @param WP_REST_Request $request WP request instance.
	 *
	 * @return array
	 */
	protected function prepare_releases_for_response( Package $package, WP_REST_Request $request ): array {
		$releases = [];

		foreach ( $package->get_releases() as $release ) {
			// Skip if the current user can't view this release.
			if ( ! current_user_can( Capabilities::VIEW_PACKAGE, $package, $release ) ) {
				continue;
			}

			$version = $release->get_version();

			try {
				$releases[] = [
					'url'     => $release->get_download_url(),
					'version' => $version,
				];
				// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			} catch ( FileNotFound $e ) {
				// Skip if the release artifact is missing.
			}
		}

		return array_values( $releases );
	}

	/**
	 * Prepare package required packages for response.
	 *
	 * @param Package         $package Package instance.
	 * @param WP_REST_Request $request WP request instance.
	 *
	 * @return array
	 */
	protected function prepare_required_packages_for_response( Package $package, WP_REST_Request $request ): array {
		$requiredPackages = [];

		foreach ( $package->get_required_packages() as $requiredPackage ) {
			$package_name = $requiredPackage['composer_package_name'] . ':' . $requiredPackage['version_range'];
			if ( 'stable' !== $requiredPackage['stability'] ) {
				$package_name .= '@' . $requiredPackage['stability'];
			}

			$requiredPackages[] = [
				'name'        => $requiredPackage['composer_package_name'],
				'version'     => $requiredPackage['version_range'],
				'stability'   => $requiredPackage['stability'],
				'editLink'    => get_edit_post_link( $requiredPackage['managed_post_id'], $request['context'] ),
				'displayName' => $package_name,
			];
		}

		return array_values( $requiredPackages );
	}

	/**
	 * Prepare package replaced packages for response.
	 *
	 * @param Package         $package Package instance.
	 * @param WP_REST_Request $request WP request instance.
	 *
	 * @return array
	 */
	protected function prepare_replaced_packages_for_response( Package $package, WP_REST_Request $request ): array {
		$replacedPackages = [];

		foreach ( $package->get_replaced_packages() as $replacedPackage ) {
			$package_name = $replacedPackage['composer_package_name'] . ':' . $replacedPackage['version_range'];
			if ( 'stable' !== $replacedPackage['stability'] ) {
				$package_name .= '@' . $replacedPackage['stability'];
			}

			$replacedPackages[] = [
				'name'        => $replacedPackage['composer_package_name'],
				'version'     => $replacedPackage['version_range'],
				'stability'   => $replacedPackage['stability'],
				'editLink'    => get_edit_post_link( $replacedPackage['managed_post_id'], $request['context'] ),
				'displayName' => $package_name,
			];
		}

		return array_values( $replacedPackages );
	}

	/**
	 * Get the package schema, conforming to JSON Schema.
	 *
	 * @since 0.10.0
	 *
	 * @return array
	 */
	public function get_item_schema() {
		return [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'package',
			'type'       => 'object',
			'properties' => [
				'authors'          => [
					'description' => esc_html__( 'The package authors details.', 'pressody_records' ),
					'type'        => 'array',
					'context'     => [ 'view', 'edit' ],
					'readonly'    => true,
				],
				'composer'         => [
					'description' => esc_html__( 'Package data formatted for Composer.', 'pressody_records' ),
					'type'        => 'object',
					'context'     => [ 'view', 'edit' ],
					'readonly'    => true,
					'properties'  => [
						'name' => [
							'description' => __( 'Composer package name.', 'pressody_records' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'type' => [
							'description' => __( 'Composer package type.', 'pressody_records' ),
							'type'        => 'string',
							'enum'        => [ 'wordpress-plugin', 'wordpress-theme' ],
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
					],
				],
				'description'      => [
					'description' => esc_html__( 'The package description.', 'pressody_records' ),
					'type'        => 'string',
					'context'     => [ 'view', 'edit', 'embed' ],
					'readonly'    => true,
				],
				'homepage'         => [
					'description' => esc_html__( 'The package URL.', 'pressody_records' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => [ 'view', 'edit' ],
					'readonly'    => true,
				],
				'keywords'         => [
					'description' => esc_html__( 'The package keywords.', 'pressody_records' ),
					'type'        => 'array',
					'items'             => [
						'type' => 'string',
					],
					'context'     => [ 'view', 'edit' ],
					'readonly'    => true,
				],
				'name'             => [
					'description' => esc_html__( 'The name of the package.', 'pressody_records' ),
					'type'        => 'string',
					'context'     => [ 'view', 'edit', 'embed' ],
					'readonly'    => true,
				],
				'releases'         => [
					'description' => esc_html__( 'A list of package releases.', 'pressody_records' ),
					'type'        => 'array',
					'context'     => [ 'view', 'edit', 'embed' ],
					'readonly'    => true,
					'items'       => [
						'type'       => 'object',
						'readonly'   => true,
						'properties' => [
							'url'     => [
								'description' => esc_html__( 'A URL to download the release.', 'pressody_records' ),
								'type'        => 'string',
								'format'      => 'uri',
								'readonly'    => true,
							],
							'version' => [
								'description' => esc_html__( 'The release version.', 'pressody_records' ),
								'type'        => 'string',
								'readonly'    => true,
							],
						],
					],
				],
				'requiredPackages' => [
					'description' => esc_html__( 'A list of required packages.', 'pressody_records' ),
					'type'        => 'array',
					'context'     => [ 'view', 'edit', 'embed' ],
					'readonly'    => true,
					'items'       => [
						'type'       => 'object',
						'readonly'   => true,
						'properties' => [
							'name'        => [
								'description' => __( 'Composer package name.', 'pressody_records' ),
								'type'        => 'string',
								'context'     => [ 'view', 'edit' ],
								'readonly'    => true,
							],
							'version'     => [
								'description' => esc_html__( 'The required package version constraint.', 'pressody_records' ),
								'type'        => 'string',
								'context'     => [ 'view', 'edit' ],
								'readonly'    => true,
							],
							'stability'   => [
								'description' => esc_html__( 'The required package stability constraint.', 'pressody_records' ),
								'type'        => 'string',
								'context'     => [ 'view', 'edit' ],
								'readonly'    => true,
							],
							'editLink'    => [
								'description' => esc_html__( 'The required package post edit link.', 'pressody_records' ),
								'type'        => 'string',
								'format'      => 'uri',
								'context'     => [ 'view', 'edit' ],
								'readonly'    => true,
							],
							'displayName' => [
								'description' => esc_html__( 'The required package display name/string.', 'pressody_records' ),
								'type'        => 'string',
								'context'     => [ 'view', 'edit' ],
								'readonly'    => true,
							],
						],
					],
				],
				'replacedPackages' => [
					'description' => esc_html__( 'A list of replaced packages.', 'pressody_records' ),
					'type'        => 'array',
					'context'     => [ 'view', 'edit', 'embed' ],
					'readonly'    => true,
					'items'       => [
						'type'       => 'object',
						'readonly'   => true,
						'properties' => [
							'name'        => [
								'description' => __( 'Composer package name.', 'pressody_records' ),
								'type'        => 'string',
								'context'     => [ 'view', 'edit' ],
								'readonly'    => true,
							],
							'version'     => [
								'description' => esc_html__( 'The replaced package version constraint.', 'pressody_records' ),
								'type'        => 'string',
								'context'     => [ 'view', 'edit' ],
								'readonly'    => true,
							],
							'stability'   => [
								'description' => esc_html__( 'The replaced package stability constraint.', 'pressody_records' ),
								'type'        => 'string',
								'context'     => [ 'view', 'edit' ],
								'readonly'    => true,
							],
							'editLink'    => [
								'description' => esc_html__( 'The replaced package post edit link.', 'pressody_records' ),
								'type'        => 'string',
								'context'     => [ 'view', 'edit' ],
								'readonly'    => true,
							],
							'displayName' => [
								'description' => esc_html__( 'The replaced package display name/string.', 'pressody_records' ),
								'type'        => 'string',
								'context'     => [ 'view', 'edit' ],
								'readonly'    => true,
							],
						],
					],
				],
				'slug'             => [
					'description' => esc_html__( 'The package slug.', 'pressody_records' ),
					'type'        => 'string',
					'pattern'     => self::SLUG_PATTERN,
					'context'     => [ 'view', 'edit', 'embed' ],
					'required'    => true,
				],
				'type'             => [
					'description' => esc_html__( 'Type of package.', 'pressody_records' ),
					'type'        => 'string',
					'enum'        => [
						PackageTypes::PLUGIN,
						PackageTypes::MUPLUGIN,
						PackageTypes::DROPINPLUGIN,
						PackageTypes::THEME,
						PackageTypes::WPCORE,
					],
					'context'     => [ 'view', 'edit', 'embed' ],
					'required'    => true,
				],
				'visibility'       => [
					'description' => esc_html__( 'The package visibility (public, draft, private, etc.)', 'pressody_records' ),
					'type'        => 'string',
					'context'     => [ 'view', 'edit', 'embed' ],
					'readonly'    => true,
				],
				'editLink'    => [
					'description' => esc_html__( 'The package post edit link.', 'pressody_records' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => [ 'view', 'edit' ],
					'readonly'    => true,
				],
				'ltType'       => [
					'description' => esc_html__( 'The PD package type (package or part).', 'pressody_records' ),
					'type'        => 'string',
					'context'     => [ 'view', 'edit', 'embed' ],
					'readonly'    => true,
				],
			],
		];
	}
}

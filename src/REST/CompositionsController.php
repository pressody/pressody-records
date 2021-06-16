<?php
/**
 * Compositions REST controller.
 *
 * @since   0.10.0
 * @license GPL-2.0-or-later
 * @package PixelgradeLT
 */

declare ( strict_types=1 );

namespace PixelgradeLT\Records\REST;

use Composer\Json\JsonFile;
use PixelgradeLT\Records\Authentication\ApiKey\Server;
use PixelgradeLT\Records\Capabilities;
use PixelgradeLT\Records\Repository\PackageRepository;
use PixelgradeLT\Records\Transformer\PackageRepositoryTransformer;
use PixelgradeLT\Records\Transformer\PackageTransformer;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function PixelgradeLT\Records\get_packages_permalink;
use function PixelgradeLT\Records\is_debug_mode;
use function PixelgradeLT\Records\plugin;

/**
 * Compositions REST controller class.
 *
 * @since 0.10.0
 */
class CompositionsController extends WP_REST_Controller {

	/**
	 * Composer package name pattern.
	 *
	 * This is the same pattern present in the Composer schema: https://getcomposer.org/schema.json
	 *
	 * @var string
	 */
	const PACKAGE_NAME_PATTERN = '^[a-z0-9]([_.-]?[a-z0-9]+)*/[a-z0-9](([_.]?|-{0,2})[a-z0-9]+)*$';

	/**
	 * Repository transformer.
	 *
	 * @var PackageRepositoryTransformer
	 */
	protected PackageRepositoryTransformer $transformer;

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
	 * @param string                       $namespace   The namespace for this controller's route.
	 * @param string                       $rest_base   The base of this controller's route.
	 * @param PackageRepository            $repository  Package repository.
	 * @param PackageRepositoryTransformer $transformer Package repository transformer.
	 */
	public function __construct(
		string $namespace,
		string $rest_base,
		PackageRepository $repository,
		PackageRepositoryTransformer $transformer
	) {
		$this->namespace   = $namespace;
		$this->rest_base   = $rest_base;
		$this->repository  = $repository;
		$this->transformer = $transformer;
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
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_item' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'show_in_index'       => false,
					'args'                => [
						'context'         => $this->get_context_param( [ 'default' => 'edit' ] ),
						'siteId'          => [
							'description'       => esc_html__( 'The ID of the site the new composition is to be used on.', 'pixelgradelt_records' ),
							'type'              => 'string',
							'sanitize_callback' => function ( $value ) {
								return preg_replace( '/[^A-Za-z0-9\-_]+/', '', $value );
							},
							'context'           => [ 'view', 'edit' ],
							'required'          => true,
						],
						'userId'          => [
							'description' => esc_html__( 'The ID of the e-commerce user the new composition is tied to.', 'pixelgradelt_records' ),
							'type'        => 'integer',
							'context'     => [ 'view', 'edit' ],
							'required'    => true,
						],
						'orderId'         => [
							'description' => esc_html__( 'The e-commerce order ID(s) the new composition is tied to.', 'pixelgradelt_records' ),
							'type'        => 'array',
							'items'       => [
								'type' => 'integer',
							],
							'context'     => [ 'view', 'edit' ],
							'required'    => true,
						],
						'requirePackages' => [
							'description' => esc_html__( 'A LT Records packages (actual LT packages or LT parts) list to include in the composition. All packages that don\'t exist will be ignored.', 'pixelgradelt_records' ),
							'type'        => 'array',
							'items'       => [
								'type'       => 'object',
								'properties' => [
									'name'    => [
										'description'       => __( 'The LT Records package\'s full Composer package name.', 'pixelgradelt_records' ),
										'type'              => 'string',
										'pattern'           => self::PACKAGE_NAME_PATTERN,
										'sanitize_callback' => function ( $value ) {
											return preg_replace( '/[^a-z0-9_\-\.]+/i', '', strtolower( $value ) );
										},
									],
									'version' => [
										'description' => esc_html__( 'The package\'s version constraint.', 'pixelgradelt_records' ),
										'type'        => 'string',
									],
								],
							],
							'default'     => [],
							'context'     => [ 'view', 'edit' ],
						],
						'composer'        => [
							'type'        => 'object',
							'description' => __( 'composer.json project (root) properties according to the Composer 2.0 JSON schema. These will be merged or overwritten by our logic, if that is the case.', 'pixelgradelt_records' ),
							'default'     => [],
							'context'     => [ 'view', 'edit' ],
						],
					],
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);
	}

	/**
	 * Check if a given request has access to create a resource.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has access to create items, WP_Error object otherwise.
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! current_user_can( Capabilities::VIEW_PACKAGES ) ) {
			return new WP_Error(
				'rest_cannot_create',
				esc_html__( 'Sorry, you are not allowed to create compositions.', 'pixelgradelt_records' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		return true;
	}

	/**
	 * Create a resource.
	 *
	 * @since 0.10.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {
		// Start with the default, blank composition.
		$composition = $this->get_starter_composition();

		// Add it with any composer.json properties received.
		$composition = $this->add_composer_properties( $composition, $request );

		// Add the required LT packages.
		$composition = $this->add_required_packages( $composition, $request );

		// Return the composition.
		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $composition, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Add/merge to the composition any composer.json properties received in the request
	 *
	 * @param array           $composition The current composition details.
	 * @param WP_REST_Request $request     Full details about the request.
	 *
	 * @return array The updated composition details.
	 */
	protected function add_composer_properties( array $composition, WP_REST_Request $request ): array {
		if ( empty( $request['composer'] ) || ! is_array( $request['composer'] ) ) {
			return $composition;
		}

		// These properties will be overwritten with the received values.
		$overwrite = [
			'name',
			'license',
			'description',
			'homepage',
			'time',
			'authors',
			'keywords',
			'minimum-stability',
			'prefer-stable',
		];

		// These properties will be merged with the received values.
		// @todo Should we merge recursively?
		$merge = [
			'repositories',
			'require',
			'require-dev',
			'config',
			'extra',
			'scripts',
		];

		// All the allowed properties.
		$allowed = $overwrite + $merge;

		foreach ( $request['composer'] as $property => $value ) {
			if ( ! in_array( $property, $allowed ) ) {
				continue;
			}

			if ( in_array( $property, $merge ) && is_array( $composition[ $property ] ) && is_array( $value ) ) {
				$composition[ $property ] = array_merge( $composition[ $property ], $value );
			} else {
				$composition[ $property ] = $value;
			}
		}

		return $composition;
	}

	/**
	 * Add to the composition the required packages in the request.
	 *
	 * @param array           $composition The current composition details.
	 * @param WP_REST_Request $request     Full details about the request.
	 *
	 * @return array The updated composition details.
	 */
	protected function add_required_packages( array $composition, WP_REST_Request $request ): array {
		if ( empty( $request['requirePackages'] ) || ! is_array( $request['requirePackages'] ) ) {
			return $composition;
		}

		// Transform the repository into a Composer repository since we receive full package names (vendor and all).
		$repo_packages = $this->transformer->transform( $this->repository );

		foreach ( $request['requirePackages'] as $package_details ) {
			if ( empty( $package_details['name'] ) ) {
				continue;
			}

			// Check that the packages exists (and has releases) in our repository.
			if ( empty( $repo_packages['packages'][ $package_details['name'] ] ) ) {
				continue;
			}

			if ( empty( $package_details['version'] ) ) {
				$package_details['version'] = '*';
			}

			// Add it to the `require` list.
			$composition['require'][ $package_details['name'] ] = $package_details['version'];
		}

		return $composition;
	}

	/**
	 * Prepare a single composition output for response.
	 *
	 * @since 0.10.0
	 *
	 * @param array           $composition Composer.json contents.
	 * @param WP_REST_Request $request     Request instance.
	 *
	 * @return WP_REST_Response Response instance.
	 */
	public function prepare_item_for_response( $composition, $request ): WP_REST_Response {
		$data = $composition;
		$data = $this->filter_response_by_context( $data, $request['context'] );

		return rest_ensure_response( $data );
	}

	protected function get_starter_composition(): array {
		return [
			'name'              => 'pixelgradelt/site',
			'type'              => 'project',
			'license'           => 'MIT',
			'description'       => 'A Pixelgrade LT WordPress site.',
			'homepage'          => 'https://pixelgradelt.com',
			'time'              => date( "Y-m-d H:i:s" ),
			'authors'           => [
				[
					'name'     => 'Vlad Olaru',
					'email'    => 'vlad@pixelgrade.com',
					'homepage' => 'https://thinkwritecode.com',
					'role'     => 'Development, infrastructure, and product development',
				],
				[
					'name'     => 'George Olaru',
					'email'    => 'george@pixelgrade.com',
					'homepage' => 'https://pixelgrade.com',
					'role'     => 'Design and product development',
				],
				[
					'name'     => 'Răzvan Onofrei',
					'email'    => 'razvan@pixelgrade.com',
					'homepage' => 'https://pixelgrade.com',
					'role'     => 'Development and product development',
				],
				[
					'name'     => 'Mădălin Gorbănescu',
					'email'    => 'madalin@pixelgrade.com',
					'homepage' => 'https://pixelgrade.com',
					'role'     => 'Development',
				],
				[
					'name'     => 'Oana Filip',
					'email'    => 'oana@pixelgrade.com',
					'homepage' => 'https://pixelgrade.com',
					'role'     => 'Communication and community-growing',
				],
				[
					'name'     => 'Andrei Ungurianu',
					'email'    => 'andrei@pixelgrade.com',
					'homepage' => 'https://pixelgrade.com',
					'role'     => 'Marketing',
				],
				[
					'name'     => 'Alin Clamba',
					'email'    => 'alin@pixelgrade.com',
					'homepage' => 'https://pixelgrade.com',
					'role'     => 'Customer support',
				],
				[
					'name'     => 'Alex Teodorescu',
					'email'    => 'alex@pixelgrade.com',
					'homepage' => 'https://pixelgrade.com',
					'role'     => 'Customer support',
				],
			],
			'keywords'          => [
				'pixelgradelt',
				'bedrock',
				'composer',
				'roots',
				'wordpress',
				'wp',
				'wp-config',
			],
			'support'           => [
				'issues' => 'https://pixelgradelt.com',
				'forum'  => 'https://pixelgradelt.com',
			],
			'repositories'      => [
				[
					// Our very own Composer repo.
					'type'    => 'composer',
					'url'     => get_packages_permalink( [ 'base' => true ] ),
					'options' => [
						'ssl'  => [
							'verify_peer' => ! is_debug_mode(),
						],
						'http' => [
							'header' => ! empty( $_ENV['LTRECORDS_PHP_AUTH_USER'] ) ? [
								'Authorization: Basic ' . base64_encode( $_ENV['LTRECORDS_PHP_AUTH_USER'] . ':' . Server::AUTH_PWD ),
							] : [],
						],
					],
				],
				[
					// The Packagist repo.
					'type' => 'composer',
					'url'  => 'https://repo.packagist.org',
				],
			],
			'require'           => [],
			'require-dev'       => [],
			'config'            => [
				'optimize-autoloader' => true,
				'preferred-install'   => 'dist',
			],
			'minimum-stability' => 'dev',
			'prefer-stable'     => true,
			'extra'             => [
				'installer-paths'       => [
					'web/app/mu-plugins/{$name}/' => [ 'type:wordpress-muplugin' ],
					'web/app/plugins/{$name}/'    => [ 'type:wordpress-plugin' ],
					'web/app/themes/{$name}/'     => [ 'type:wordpress-theme' ],
				],
				'wordpress-install-dir' => 'web/wp',
			],
			'scripts'           => [
				'post-root-package-install' => [
					"php -r \"copy('.env.example', '.env');\"",
				],
				'test'                      => [
					'phpcs',
				],
			],
		];
	}

	/**
	 * Get the composition schema, conforming to JSON Schema.
	 *
	 * We will use the actual Composer JSON schema since we return the contents of a full composer.json file.
	 *
	 * @since 0.10.0
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = [];

		try {
			$schemaJson = new JsonFile( plugin()->get_path( 'vendor/composer/composer/res/composer-schema.json' ) );
			$schema     = $schemaJson->read();
		} catch ( \Exception $e ) {
			// Do nothing.
		}

		return $schema;
	}
}

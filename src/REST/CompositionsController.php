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
use Composer\Json\JsonValidationException;
use Composer\Package\Locker;
use JsonSchema\Constraints\BaseConstraint;
use JsonSchema\Validator;
use PixelgradeLT\Records\Authentication\ApiKey\Server;
use PixelgradeLT\Records\Capabilities;
use PixelgradeLT\Records\CrypterInterface;
use PixelgradeLT\Records\Exception\CrypterEnvironmentIsBrokenException;
use PixelgradeLT\Records\Repository\PackageRepository;
use PixelgradeLT\Records\Transformer\PackageRepositoryTransformer;
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
	 * Package repository.
	 *
	 * @var PackageRepository
	 */
	protected PackageRepository $repository;

	/**
	 * Repository transformer.
	 *
	 * @var PackageRepositoryTransformer
	 */
	protected PackageRepositoryTransformer $transformer;

	/**
	 * String crypter.
	 *
	 * @var CrypterInterface
	 */
	protected CrypterInterface $crypter;

	/**
	 * Constructor.
	 *
	 * @since 0.10.0
	 *
	 * @param string                       $namespace   The namespace for this controller's route.
	 * @param string                       $rest_base   The base of this controller's route.
	 * @param PackageRepository            $repository  Package repository.
	 * @param PackageRepositoryTransformer $transformer Package repository transformer.
	 * @param CrypterInterface             $crypter     String crypter.
	 */
	public function __construct(
		string $namespace,
		string $rest_base,
		PackageRepository $repository,
		PackageRepositoryTransformer $transformer,
		CrypterInterface $crypter
	) {

		$this->namespace   = $namespace;
		$this->rest_base   = $rest_base;
		$this->repository  = $repository;
		$this->transformer = $transformer;
		$this->crypter     = $crypter;
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
							'description' => esc_html__( 'The ID of the user the new composition is tied to.', 'pixelgradelt_records' ),
							'type'        => 'integer',
							'context'     => [ 'view', 'edit' ],
							'required'    => true,
						],
						'orderId'         => [
							'description' => esc_html__( 'The e-commerce order ID(s) the new composition is to be tied to.', 'pixelgradelt_records' ),
							'type'        => 'array',
							'items'       => [
								'type' => 'integer',
							],
							'context'     => [ 'view', 'edit' ],
							'required'    => false,
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

		// Add the user details.
		try {
			$composition = $this->add_user_details( $composition, $request );
		} catch ( CrypterEnvironmentIsBrokenException $e ) {
			return new WP_Error(
				'rest_unable_to_encrypt',
				esc_html__( 'We could not encrypt. Please contact the administrator and let them know that something is wrong. Thanks in advance!', 'pixelgradelt_records' ),
				[
					'status'  => 500,
					'details' => $e->getMessage(),
				]
			);
		}

		$composition = $this->standardize_to_object( $composition );

		// Fingerprint this composition. This should be run last!!!
		$composition = $this->add_fingerprint( $composition, $request );

		// Validate the composition according to composer-schema.json rules.
		try {
			$this->validate_schema( $composition, $request );
		} catch ( JsonValidationException $e ) {
			return new WP_Error(
				'rest_json_invalid',
				esc_html__( 'We could not produce a composition that would validate against the Composer JSON schema.', 'pixelgradelt_records' ),
				[
					'status'  => 500,
					'details' => $e->getErrors(),
				]
			);
		}

		// Return the composition.
		$response = rest_ensure_response( $composition );
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
	 * Add to the composition the user details available in the request.
	 *
	 * @param array           $composition The current composition details.
	 * @param WP_REST_Request $request     Full details about the request.
	 *
	 * @throws CrypterEnvironmentIsBrokenException
	 * @return array The updated composition details.
	 */
	protected function add_user_details( array $composition, WP_REST_Request $request ): array {
		if ( empty( $composition['extra'] ) ) {
			$composition['extra'] = [];
		}

		// Add the encrypted user details.
		$user_data = [
			'siteid'  => 0,
			'userid'  => 0,
			'orderid' => 0,
		];
		if ( isset( $request['siteId'] ) ) {
			$user_data['siteid'] = $request['siteId'];
		}
		if ( isset( $request['userId'] ) ) {
			$user_data['userid'] = $request['userId'];
		}
		if ( isset( $request['orderId'] ) ) {
			$user_data['orderid'] = $request['orderId'];
		}

		$composition['extra']['lt-user'] = $this->crypter->encrypt( json_encode( $user_data ) );

		return $composition;
	}

	/**
	 * Add to the composition the fingerprint to determine if a composition is to be trusted.
	 *
	 * @param object          $composition The current composition details.
	 * @param WP_REST_Request $request     Full details about the request.
	 *
	 * @return object The updated composition details.
	 */
	protected function add_fingerprint( object $composition, WP_REST_Request $request ): object {
		if ( empty( $composition->extra ) ) {
			$composition->extra = new \stdClass();
		}

		$key = "lt-fingerprint";

		// Make sure that we don't hash with the security details in place.
		if ( isset( $composition->extra->$key ) ) {
			unset( $composition->extra->$key );
		}

		$composition->extra->$key = $this->get_content_hash( $composition );

		return $composition;
	}

	/**
	 * Order the composition data to a standard to ensure hashes can be trusted.
	 *
	 * @param array $composition The current composition details.
	 *
	 * @return array The updated composition details.
	 */
	protected function standard_order( array $composition ): array {
		$targetKeys = array(
			'require',
			'require-dev',
			'conflict',
			'replace',
			'provide',
			'repositories',
			'extra',
		);
		foreach ( $targetKeys as $key ) {
			if ( ! empty( $composition[ $key ] ) && is_array( $composition[ $key ] ) ) {
				ksort( $composition[ $key ] );
			}
		}

		return $composition;
	}

	/**
	 * Returns the md5 hash of the sorted content of the composition details.
	 *
	 * Inspired by @see Locker::getContentHash()
	 *
	 * @param object|array $composition The current composition details.
	 *
	 * @return string
	 */
	protected function get_content_hash( $composition ): string {
		if ( ! is_array( $composition ) ) {
			$composition = json_decode( json_encode( $composition ), true );
		}

		$relevantKeys = array(
			'name',
			'version',
			'require',
			'require-dev',
			'conflict',
			'replace',
			'provide',
			'minimum-stability',
			'prefer-stable',
			'repositories',
			'extra',
		);

		$relevantContent = array();

		foreach ( array_intersect( $relevantKeys, array_keys( $composition ) ) as $key ) {
			$relevantContent[ $key ] = $composition[ $key ];
		}
		if ( isset( $composition['config']['platform'] ) ) {
			$relevantContent['config']['platform'] = $composition['config']['platform'];
		}

		ksort( $relevantContent );

		return md5( json_encode( $relevantContent ) );
	}

	/**
	 * Make sure that the composition details have the right type (especially empty ones).
	 *
	 * @param array $composition The current composition details.
	 *
	 * @return object The updated composition details.
	 */
	protected function standardize_to_object( array $composition ): object {
		// Ensure a standard order.
		$composition = $this->standard_order( $composition );

		$compositionObject = BaseConstraint::arrayToObjectRecursive( $composition );

		$objectsKeys = [
			'require',
			'require-dev',
			'config',
			'extra',
			'scripts',
			'support',
		];
		foreach ( $objectsKeys as $key ) {
			if ( empty( $compositionObject->$key ) ) {
				$compositionObject->$key = new \stdClass();
			}
		}

		return $compositionObject;
	}

	/**
	 * Validate the given composition against the composer-schema.json rules.
	 *
	 * @param object          $composition The current composition details in object.
	 * @param WP_REST_Request $request     Full details about the request.
	 *
	 * @throws JsonValidationException
	 * @return bool Success.
	 */
	protected function validate_schema( object $composition, WP_REST_Request $request ): bool {
		$validator = new Validator();
		$validator->check( $composition, $this->get_item_schema() );
		if ( ! $validator->isValid() ) {
			$errors = array();
			foreach ( (array) $validator->getErrors() as $error ) {
				$errors[] = ( $error['property'] ? $error['property'] . ' : ' : '' ) . $error['message'];
			}
			throw new JsonValidationException( 'The composition does not match the expected JSON schema', $errors );
		}

		return true;
	}

	protected function get_starter_composition(): array {
		return [
			'name'              => 'pixelgradelt/site',
			'type'              => 'project',
			'license'           => 'MIT',
			'description'       => 'A Pixelgrade LT WordPress site.',
			'homepage'          => 'https://pixelgradelt.com',
			'time'              => date( DATE_RFC3339 ),
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

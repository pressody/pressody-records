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
use InvalidArgumentException;
use JsonSchema\Validator;
use PixelgradeLT\Records\Capabilities;
use PixelgradeLT\Records\Exception\RestException;
use PixelgradeLT\Records\Repository\PackageRepository;
use PixelgradeLT\Records\Transformer\PackageRepositoryTransformer;
use PixelgradeLT\Records\Utils\ArrayHelpers;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Http as HTTP;
use function PixelgradeLT\Records\get_packages_permalink;
use function PixelgradeLT\Records\is_debug_mode;
use function PixelgradeLT\Records\is_dev_url;
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
	 * @since 0.10.0
	 *
	 * @var string
	 */
	const PACKAGE_NAME_PATTERN = '^[a-z0-9]([_.-]?[a-z0-9]+)*/[a-z0-9](([_.]?|-{0,2})[a-z0-9]+)*$';

	/**
	 * The key in composer.json `extra` used to store the encrypted LT details.
	 *
	 * @since 0.10.0
	 *
	 * @var string
	 */
	const LTDETAILS_KEY = 'lt-composition';

	/**
	 * The key in composer.json `extra` used to store the composer.json fingerprint.
	 *
	 * @since 0.10.0
	 *
	 * @var string
	 */
	const FINGERPRINT_KEY = 'lt-fingerprint';

	/**
	 * The key in composer.json `extra` used to store the composer.json LT version.
	 *
	 * We will use this in case we make breaking changes and wish to provide backwards compatibility.
	 *
	 * @since 0.10.0
	 *
	 * @var string
	 */
	const VERSION_KEY = 'lt-version';

	/**
	 * The version to be used for new compositions and to upgrade older ones.
	 *
	 * @since 0.11.0
	 *
	 * @var string
	 */
	const COMPOSITION_VERSION = '1.1.0';

	/**
	 * Package repository.
	 *
	 * @since 0.10.0
	 *
	 * @var PackageRepository
	 */
	protected PackageRepository $repository;

	/**
	 * Repository transformer.
	 *
	 * @since 0.10.0
	 *
	 * @var PackageRepositoryTransformer
	 */
	protected PackageRepositoryTransformer $transformer;

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
						'context'   => $this->get_context_param( [ 'default' => 'edit' ] ),
						'ltdetails' => [
							'description' => esc_html__( 'The encrypted composition LT details data to attach to the composition.', 'pixelgradelt_records' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'required'    => true,
						],
						'require'   => [
							'description' => esc_html__( 'A LT Records packages list (actual LT packages or LT parts) to include in the composition. All packages that don\'t exist will be ignored. These required packages will overwrite the same packages given through the "composer" param.', 'pixelgradelt_records' ),
							'type'        => 'array',
							'items'       => [
								'type'       => 'object',
								'properties' => [
									'name'    => [
										'description'       => esc_html__( 'The LT Records package\'s full Composer package name.', 'pixelgradelt_records' ),
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
						'composer'  => [
							'type'        => 'object',
							'description' => esc_html__( 'composer.json project (root) properties according to the Composer 2.0 JSON schema. The "repositories", "require", "require-dev", "config", "extra","scripts" root-properties will be merged. The rest will overwrite existing properties.', 'pixelgradelt_records' ),
							'default'     => [],
							'context'     => [ 'view', 'edit' ],
						],
					],
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/refresh',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'refresh_item' ],
					'permission_callback' => [ $this, 'update_item_permissions_check' ],
					'show_in_index'       => false,
					'args'                => [
						'context'  => $this->get_context_param( [ 'default' => 'edit' ] ),
						'composer' => [
							'description' => esc_html__( 'The full composer.json contents to attempt to refresh.', 'pixelgradelt_records' ),
							'type'        => 'object',
							'context'     => [ 'view', 'edit' ],
							'required'    => true,
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
	 * @since 0.10.0
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
	 * Check if a given request has access to update a resource.
	 *
	 * @since 0.10.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has access to create items, WP_Error object otherwise.
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! current_user_can( Capabilities::VIEW_PACKAGES ) ) {
			return new WP_Error(
				'rest_cannot_create',
				esc_html__( 'Sorry, you are not allowed to update compositions.', 'pixelgradelt_records' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		return true;
	}

	/**
	 * Create a new composition (the contents of composer.json).
	 *
	 * @since 0.10.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {
		// Start with the default composition contents.
		$composition = $this->get_starter_composition();

		// Update the default composition with the received details.
		try {
			$composition = $this->update_composition( $composition, $request->get_params() );
		} catch ( RestException $e ) {
			return new WP_Error(
				'rest_update_composition_errors',
				$e->getMessage(),
				[ 'status' => $e->getStatusCode(), ]
			);
		}

		$compositionObject = $this->standardize_to_object( $composition );

		// Fingerprint the created composition. This should be run last!!!
		$compositionObject = $this->add_fingerprint( $compositionObject );

		try {
			// Validate the created composition according to composer-schema.json rules.
			$this->validate_schema( $compositionObject );
		} catch ( JsonValidationException $e ) {
			return new WP_Error(
				'rest_json_invalid',
				esc_html__( 'We could not produce a composition that would validate against the Composer JSON schema.', 'pixelgradelt_records' ),
				[
					'status'  => HTTP::INTERNAL_SERVER_ERROR,
					'details' => $e->getErrors(),
				]
			);
		}

		// Return the created composition as the response.
		$response = rest_ensure_response( $compositionObject );
		$response->set_status( HTTP::CREATED );

		return $response;
	}

	/**
	 * Maybe refresh a composition (the contents of composer.json).
	 *
	 * @since 0.10.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function refresh_item( WP_REST_Request $request ) {
		$composition = $request['composer'];
		// Make sure we are dealing with an associative array.
		if ( is_object( $composition ) ) {
			$composition = $this->objectToArrayRecursive( $composition );
		}

		// First, validate the received composition's schema.
		try {
			// Validate the composition according to composer-schema.json rules.
			$this->validate_schema( $this->standardize_to_object( $composition ) );
		} catch ( JsonValidationException $e ) {
			return new WP_Error(
				'rest_json_invalid',
				esc_html__( 'Could not validate the received composition against the Composer JSON schema.', 'pixelgradelt_records' ),
				[
					'status'  => HTTP::NOT_ACCEPTABLE,
					'details' => $e->getErrors(),
				]
			);
		}

		// Second, check the fingerprint.
		try {
			// In case of invalid fingerprint, exceptions are thrown.
			$this->check_fingerprint( $composition );
		} catch ( RestException $e ) {
			return new WP_Error(
				'rest_invalid_fingerprint',
				$e->getMessage(),
				[ 'status' => $e->getStatusCode(), ]
			);
		}

		// Third, check if we have the encrypted LT details.
		if ( empty( $composition['extra'][ self::LTDETAILS_KEY ] ) ) {
			return new WP_Error(
				'rest_missing_lt_user',
				esc_html__( 'The provided composer JSON data is missing the LT details.', 'pixelgradelt_records' ),
				[ 'status' => HTTP::NOT_ACCEPTABLE ]
			);
		}

		// If we have made it thus far, the received composition is OK.
		// Proceed to determine if we need to update something to it.

		/**
		 * Provide instructions to update the composition by.
		 *
		 * @since 0.10.0
		 *
		 * @see   CompositionsController::refresh_item()
		 *
		 * Return an empty array if we should leave the composition unchanged.
		 * Return false or WP_Error if we should reject the refresh and error out.
		 *
		 * @param array $instructions_to_update The instructions to update the composition by.
		 * @param array $composition            The full composition data.
		 */
		$instructions_to_update = apply_filters( 'pixelgradelt_records/composition_instructions_to_update', $this->get_default_instructions_to_update( $composition ), $composition );
		if ( is_wp_error( $instructions_to_update ) ) {
			$message = esc_html__( 'Your attempt to refresh the composition was rejected. Here is what happened: ', 'pixelgradelt_records' ) . PHP_EOL;
			$message .= implode( ' ; ' . PHP_EOL, $instructions_to_update->get_error_messages() );

			return new WP_Error(
				'rest_rejected_refresh',
				$message,
				[
					'status'                                  => HTTP::NOT_ACCEPTABLE,
					$instructions_to_update->get_error_code() => $instructions_to_update->get_error_data(),
				]
			);
		} elseif ( false === $instructions_to_update ) {
			return new WP_Error(
				'rest_rejected_refresh',
				esc_html__( 'Your attempt to refresh the composition was rejected.', 'pixelgradelt_records' ),
				[ 'status' => HTTP::NOT_ACCEPTABLE, ]
			);
		}

		if ( ! is_array( $instructions_to_update ) || empty( $instructions_to_update ) ) {
			// There is nothing to update to the received composition. Respond accordingly.
			$response = rest_ensure_response( [] );
			$response->set_status( HTTP::NO_CONTENT );

			return $response;
		}

		// We have work to do.
		try {
			$updatedComposition = $this->update_composition( $composition, $instructions_to_update );
		} catch ( RestException $e ) {
			return new WP_Error(
				'rest_update_composition_errors',
				$e->getMessage(),
				[ 'status' => $e->getStatusCode(), ]
			);
		}

		$updatedCompositionObject = $this->standardize_to_object( $updatedComposition );

		// Fingerprint the updated composition. This should be run last!!!
		$updatedCompositionObject = $this->add_fingerprint( $updatedCompositionObject );

		try {
			// Validate the updated composition according to composer-schema.json rules.
			$this->validate_schema( $updatedCompositionObject );
		} catch ( JsonValidationException $e ) {
			return new WP_Error(
				'rest_json_invalid',
				esc_html__( 'We could not produce a composition that would validate against the Composer JSON schema.', 'pixelgradelt_records' ),
				[
					'status'  => HTTP::INTERNAL_SERVER_ERROR,
					'details' => $e->getErrors(),
				]
			);
		}

		// If the updated composition and the initial composition have the same fingerprint it means that nothing was updated.
		// Respond accordingly.
		if ( ! empty( $composition['extra'][ self::FINGERPRINT_KEY ] )
		     && $updatedCompositionObject->extra->{self::FINGERPRINT_KEY} === $composition['extra'][ self::FINGERPRINT_KEY ] ) {

			$response = rest_ensure_response( [] );
			$response->set_status( HTTP::NO_CONTENT );

			return $response;
		}

		// Return the refreshed composition as the response.
		return rest_ensure_response( $updatedCompositionObject );
	}

	/**
	 * Update composition by instructions.
	 *
	 * @since 0.10.0
	 *
	 * @param array $composition  The current composition data.
	 * @param array $instructions The instructions to update the composition by.
	 *
	 * @throws RestException
	 * @return array
	 */
	protected function update_composition( array $composition, array $instructions ): array {
		$initial_composition = $composition;

		// The order below is important!

		// First, add any composer.json properties received.
		if ( ! empty( $instructions['composer'] ) && is_array( $instructions['composer'] ) ) {
			$composition = $this->add_composer_properties( $composition, $instructions['composer'] );
		}

		// Second, remove required packages, including packages sent through the composer config.
		if ( ! empty( $instructions['remove'] ) && is_array( $instructions['remove'] ) ) {
			$composition = $this->remove_required_packages( $composition, $instructions['remove'] );
		}

		// Third, add the required LT packages.
		if ( ! empty( $instructions['require'] ) && is_array( $instructions['require'] ) ) {
			$composition = $this->add_required_packages( $composition, $instructions['require'] );
		}

		// We will add the new LT details if they are different than what we have.
		// Before adding the encrypted LT details, allow a third-party check.
		// We don't want to add data that is later found to be invalid.
		if ( isset( $instructions['ltdetails'] )
		     && ( ! isset( $composition['extra'][ self::LTDETAILS_KEY ] )
		          || $composition['extra'][ self::LTDETAILS_KEY ] !== $instructions['ltdetails'] )
		) {

			// We will validate only if we are given a non-empty string.
			// If we are given another value, we will just write it. Others should know better about what they are doing.
			if ( ! empty( $instructions['ltdetails'] ) && is_string( $instructions['ltdetails'] ) ) {
				/**
				 * Filter the validation of encrypted LT details.
				 *
				 * @since 0.10.0
				 *
				 * @see   CompositionsController::update_composition()
				 *
				 * Return true if the composition's LT details are valid, or a WP_Error in case we should reject them.
				 *
				 * @param bool   $valid       Whether the composition's LT details are valid.
				 * @param string $encrypted_ltdetails
				 * @param array  $composition The current composition data.
				 */
				$valid = apply_filters( 'pixelgradelt_records/validate_encrypted_ltdetails', true, $instructions['ltdetails'], $composition );
				if ( is_wp_error( $valid ) ) {
					$message = esc_html__( 'Third-party checks have found the encrypted LT composition details invalid. Here is what happened: ', 'pixelgradelt_records' ) . PHP_EOL;
					$message .= implode( ' ; ' . PHP_EOL, $valid->get_error_messages() );

					throw RestException::forInvalidCompositionLTDetails( $message );
				} elseif ( true !== $valid ) {
					throw RestException::forInvalidCompositionLTDetails();
				}
			}

			// Now we can add/replace the LT details in the composition.
			$composition = $this->add_ltdetails( $composition, $instructions );
		}

		// Update the timestamp
		$composition['time'] = date( DATE_RFC3339 );

		/**
		 * Filter the updated composition.
		 *
		 * @since 0.10.0
		 *
		 * @see   CompositionsController::update_composition()
		 *
		 * @param array $composition         The updated composition data.
		 * @param array $instructions        The composition instructions to update by.
		 * @param array $initial_composition The initial composition data.
		 */
		return apply_filters( 'pixelgradelt_records/update_composition', $composition, $instructions, $initial_composition );
	}

	/**
	 * Add/merge to the composition any composer.json properties received in the request
	 *
	 * @since 0.10.0
	 *
	 * @param array $composition     The current composition data.
	 * @param array $composer_config Composer.json properties.
	 *
	 * @return array The updated composition details.
	 */
	protected function add_composer_properties( array $composition, array $composer_config ): array {
		if ( empty( $composer_config ) ) {
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
		$allowed = array_unique( array_merge( $overwrite, $merge ) );

		foreach ( $composer_config as $property => $value ) {
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
	 * Add required packages to the composition.
	 *
	 * @since 0.10.0
	 *
	 * @param array $composition      The current composition data.
	 * @param array $require_packages Package details to require.
	 *
	 * @return array The updated composition details.
	 */
	protected function add_required_packages( array $composition, array $require_packages ): array {
		if ( empty( $require_packages ) ) {
			return $composition;
		}

		// Transform the repository into a Composer repository since we receive full package names (vendor and all).
		$repo_packages = $this->transformer->transform( $this->repository );

		foreach ( $require_packages as $package_details ) {
			if ( empty( $package_details['name'] ) ) {
				continue;
			}

			// Check that the LT packages exists (and has releases) in our repository.
			if ( empty( $repo_packages['packages'][ $package_details['name'] ] ) ) {
				continue;
			}

			if ( empty( $package_details['version'] ) ) {
				$package_details['version'] = '*';
			}

			// Add it to the `require` list.
			$composition['require'][ $package_details['name'] ] = $package_details['version'];

			// If we have other package details besides name and version, remember these details for future reference.
			if ( count( $package_details ) > 2 ) {
				if ( empty( $composition['extra'] ) ) {
					$composition['extra'] = [];
				}
				if ( empty( $composition['extra']['lt-required-packages'] ) ) {
					$composition['extra']['lt-required-packages'] = [];
				}

				$composition['extra']['lt-required-packages'][ $package_details['name'] ] = $package_details;
			}
		}

		return $composition;
	}

	/**
	 * Remove required packages from the composition.
	 *
	 * @since 0.10.0
	 *
	 * @param array $composition     The current composition data.
	 * @param array $remove_packages Package details to require.
	 *
	 * @return array The updated composition details.
	 */
	protected function remove_required_packages( array $composition, array $remove_packages ): array {
		if ( empty( $remove_packages ) ) {
			return $composition;
		}

		// For not, we are only interested in the package name and will remove it regardless of version constraints.
		foreach ( $remove_packages as $package_details ) {
			if ( is_string( $package_details ) ) {
				$package_details = [
					'name' => $package_details,
				];
			}

			if ( ! is_array( $package_details ) || empty( $package_details['name'] ) || ! isset( $composition['require'][ $package_details['name'] ] ) ) {
				continue;
			}

			// Remove it from the `require` list.
			unset( $composition['require'][ $package_details['name'] ] );

			// Maybe remove it from the extra details.
			if ( isset( $composition['extra']['lt-required-packages'][ $package_details['name'] ] ) ) {
				unset( $composition['extra']['lt-required-packages'][ $package_details['name'] ] );
			}
		}

		return $composition;
	}

	/**
	 * Add to the composition the LT details available in the request.
	 *
	 * @since 0.10.0
	 *
	 * @param array $composition The current composition data.
	 * @param array $data
	 *
	 * @return array The updated composition data.
	 */
	protected function add_ltdetails( array $composition, array $data ): array {
		if ( empty( $composition['extra'] ) ) {
			$composition['extra'] = [];
		}

		// Add the encrypted composition LT details.
		if ( isset( $data['ltdetails'] ) ) {
			$composition['extra'][ self::LTDETAILS_KEY ] = $data['ltdetails'];
		}

		return $composition;
	}

	/**
	 * Add to the composition the fingerprint to determine if a composition is to be trusted.
	 *
	 * @since 0.10.0
	 *
	 * @param object $composition The current composition details, in object format.
	 *
	 * @return object The updated composition details.
	 */
	protected function add_fingerprint( object $composition ): object {
		if ( empty( $composition->extra ) ) {
			$composition->extra = new \stdClass();
		}

		$composition->extra->{self::FINGERPRINT_KEY} = $this->fingerprint_composition( $composition );

		return $composition;
	}

	/**
	 * Generate a fingerprint of a full composition.
	 *
	 * @since 0.10.0
	 *
	 * @param object $composition The full composition in object format.
	 *
	 * @return string The composition fingerprint.
	 */
	protected function fingerprint_composition( object $composition ): string {
		if ( empty( $composition->extra ) ) {
			$composition->extra = new \stdClass();
		}

		// Make sure that we don't hash with the security details in place.
		if ( isset( $composition->extra->{self::FINGERPRINT_KEY} ) ) {
			unset( $composition->extra->{self::FINGERPRINT_KEY} );
		}

		return $this->get_content_hash( $composition );
	}

	/**
	 * Check the composition's fingerprint.
	 *
	 * @since 0.10.0
	 *
	 * @param array $composition The composition full details.
	 *
	 * @throws RestException
	 * @return bool True if it passes check.
	 */
	protected function check_fingerprint( array $composition ): bool {
		if ( empty( $composition['extra'][ self::FINGERPRINT_KEY ] ) ) {
			throw RestException::forMissingComposerFingerprint();
		}

		if ( $composition['extra'][ self::FINGERPRINT_KEY ] !== $this->fingerprint_composition( $this->standardize_to_object( $composition ) ) ) {
			throw RestException::forInvalidComposerFingerprint();
		}

		return true;
	}

	/**
	 * Order the composition data to a standard to ensure hashes can be trusted.
	 *
	 * @since 0.10.0
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
	 * Inspired by @since 0.10.0
	 *
	 * @see \Composer\Package\Locker::getContentHash()
	 *
	 * @param object|array $composition The current composition details.
	 *
	 * @return string The composition hash.
	 */
	protected function get_content_hash( $composition ): string {
		if ( is_object( $composition ) ) {
			$composition = $this->objectToArrayRecursive( $composition );
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

		ArrayHelpers::ksortRecursive( $relevantContent );

		return md5( json_encode( $relevantContent ) );
	}

	/**
	 * Make sure that the composition details have the right type (especially empty ones).
	 *
	 * @since 0.10.0
	 *
	 * @param array $composition The current composition details.
	 *
	 * @return object The standardized composition object.
	 */
	protected function standardize_to_object( array $composition ): object {
		// Ensure a standard order.
		$composition = $this->standard_order( $composition );

		// Convert to object.
		$compositionObject = $this->arrayToObjectRecursive( $composition );

		// Enforce empty properties that should be objects, not empty arrays.
		$objectsKeys = [
			'require',
			'require-dev',
			'conflict',
			'extra',
			'provide',
			'replace',
			'suggest',
			'config',
			'autoload',
			'autoload-dev',
			'scripts',
			'scripts-descriptions',
			'support',
		];
		foreach ( $objectsKeys as $key ) {
			if ( isset( $compositionObject->$key ) && empty( $compositionObject->$key ) ) {
				$compositionObject->$key = new \stdClass();
			}
		}

		/**
		 * Filter the standardized composition object.
		 *
		 * @since 0.10.0
		 *
		 * @see   CompositionsController::standardize_to_object()
		 *
		 * @param object $compositionObject The standardized composition object.
		 * @param array  $composition       The initial composition.
		 */
		return apply_filters( 'pixelgradelt_records/composition_standardize_to_object', $compositionObject, $composition );
	}

	/**
	 * Recursively cast an associative array to an object
	 *
	 * @since 0.10.0
	 *
	 * @param array $array
	 *
	 * @return object
	 */
	protected function arrayToObjectRecursive( array $array ): object {
		$json = json_encode( $array );
		if ( json_last_error() !== \JSON_ERROR_NONE ) {
			$message = 'Unable to encode schema array as JSON';
			if ( function_exists( 'json_last_error_msg' ) ) {
				$message .= ': ' . json_last_error_msg();
			}
			throw new InvalidArgumentException( $message );
		}

		return (object) json_decode( $json );
	}

	/**
	 * Recursively cast an object to an associative array.
	 *
	 * @since 0.10.0
	 *
	 * @param object $object
	 *
	 * @return array
	 */
	protected function objectToArrayRecursive( object $object ): array {
		$json = json_encode( $object );
		if ( json_last_error() !== \JSON_ERROR_NONE ) {
			$message = esc_html__( 'Unable to encode schema array as JSON', 'pixelgradelt_records' );
			if ( function_exists( 'json_last_error_msg' ) ) {
				$message .= ': ' . json_last_error_msg();
			}
			throw new InvalidArgumentException( $message );
		}

		return (array) json_decode( $json, true );
	}

	/**
	 * Validate the given composition against the composer-schema.json rules.
	 *
	 * @since 0.10.0
	 *
	 * @param object $composition The current composition details in object.
	 *
	 * @throws JsonValidationException
	 * @return bool Success.
	 */
	protected function validate_schema( object $composition ): bool {
		$validator       = new Validator();
		$composer_schema = $this->get_item_schema();
		if ( empty( $composer_schema ) ) {
			// If we couldn't read the schema, let things pass.
			return true;
		}

		$validator->validate( $composition, $composer_schema );
		if ( ! $validator->isValid() ) {
			$errors = array();
			foreach ( (array) $validator->getErrors() as $error ) {
				$errors[] = ( $error['property'] ? $error['property'] . ' : ' : '' ) . $error['message'];
			}
			throw new JsonValidationException( esc_html__( 'The composition does not match the expected JSON schema', 'pixelgradelt_records' ), $errors );
		}

		return true;
	}

	/**
	 * Get a composition to start populating.
	 *
	 * @since 0.10.0
	 *
	 * @return array
	 */
	protected function get_starter_composition(): array {
		// This is mostly the contents of the composer.json in our WPSite Starter (https://github.com/pixelgradelt/wpsite-starter)
		// We should keep important things in sync since these will overwrite the default composer.json in that repo.
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
						'ssl' => [
							'verify_peer' => ! ( is_debug_mode() || is_dev_url( get_packages_permalink( [ 'base' => true ] ) ) ),
						],
					],
				],
				[
					'type' => 'vcs',
					'url'  => 'https://github.com/pixelgradelt/pixelgradelt-conductor',
				],
				[
					// The Packagist repo.
					'type' => 'composer',
					'url'  => 'https://repo.packagist.org',
				],
			],
			'require'           => [
				'ext-json'                            => '*',
				'gordalina/cachetool'                 => '~6.3',
				'php'                                 => '>=7.1',
				'oscarotero/env'                      => '^2.1',
				'pixelgradelt/pixelgradelt-conductor' => 'dev-main',
				'roots/bedrock-autoloader'            => '^1.0',
				'roots/wordpress'                     => '*',
				'roots/wp-config'                     => '1.0.0',
				'roots/wp-password-bcrypt'            => '1.0.0',
				'vlucas/phpdotenv'                    => '^5.3',
			],
			'require-dev'       => [
				'squizlabs/php_codesniffer' => '^3.5.8',
				'roave/security-advisories' => 'dev-latest',
			],
			'config'            => [
				// Lock the vendor directory name so we don't get any surprises.
				'vendor-dir'          => 'vendor',
				'optimize-autoloader' => true,
				'preferred-install'   => 'dist',
				'sort-packages'       => true,
			],
			'minimum-stability' => 'dev',
			'prefer-stable'     => true,
			'extra'             => [
				// @see https://packagist.org/packages/composer/installers
				'installer-paths'       => [
					// Since the ActionScheduler is of the wordpress-plugin type, but we don't use it as such,
					// we want it placed in the vendor directory. This rule needs to come first to take priority.
					'vendor/{$vendor}/{$name}/'   => [ 'woocommerce/action-scheduler', ],
					'web/app/mu-plugins/{$name}/' => [ 'type:wordpress-muplugin' ],
					'web/app/plugins/{$name}/'    => [ 'type:wordpress-plugin' ],
					'web/app/themes/{$name}/'     => [ 'type:wordpress-theme' ],
				],
				// @see https://packagist.org/packages/roots/wordpress-core-installer
				'wordpress-install-dir' => 'web/wp',
				// LT Composition version
				self::VERSION_KEY       => self::COMPOSITION_VERSION,
			],
			'scripts'           => [
				'cache:schedule:clear'   => [
					'PixelgradeLT\Conductor\Cache\CacheDispatcher::schedule_cache_clear',
				],
				// CacheTool wrapper commands. See https://github.com/gordalina/cachetool
				'cache:opcache:status'   => [
					'./vendor/bin/cachetool opcache:status',
				],
				'cache:opcache:clear'    => [
					'./vendor/bin/cachetool opcache:reset',
				],
				'cache:opcache:warm'     => [
					'./vendor/bin/cachetool opcache:compile:scripts -q ./web/',
				],
				// Allow the CatchDispatcher to take action on package modifications.
				'pre-package-install'    => [
					'PixelgradeLT\Conductor\Cache\CacheDispatcher::handle_event',
				],
				'post-package-install'   => [
					'PixelgradeLT\Conductor\Cache\CacheDispatcher::handle_event',
				],
				'pre-package-update'     => [
					'PixelgradeLT\Conductor\Cache\CacheDispatcher::handle_event',
				],
				'post-package-update'    => [
					'PixelgradeLT\Conductor\Cache\CacheDispatcher::handle_event',
				],
				'pre-package-uninstall'  => [
					'PixelgradeLT\Conductor\Cache\CacheDispatcher::handle_event',
				],
				'post-package-uninstall' => [
					'PixelgradeLT\Conductor\Cache\CacheDispatcher::handle_event',
				],
			],
		];
	}

	/**
	 * We will use these default instructions to update the composition by in order to update past compositions and keep up with our development,
	 * especially changes related to our WPSite Starter (https://github.com/pixelgradelt/wpsite-starter).
	 *
	 * @since 0.11.0
	 *
	 * @param array $composition
	 *
	 * @return array
	 */
	protected function get_default_instructions_to_update( array $composition ): array {
		$default = [
			'composer' => [],
			'remove'   => [],
			'require'  => [],
		];

		if ( isset( $composition['extra'][ self::VERSION_KEY ] ) && \version_compare( (string) $composition['extra'][ self::VERSION_KEY ], '1.1.0', '<' ) ) {
			// Remove some packages.
			$default['remove'][] = 'roots/bedrock-disallow-indexing';

			// Update the version.
			$default['composer']['extra'][ self::VERSION_KEY ] = '1.1.0';
		}

		return $default;
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

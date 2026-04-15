<?php

namespace Tarosky\MakePostmetaFaster\Api;

use Tarosky\MakePostmetaFaster\IndexChecker\PostMetaIndexChecker;
use Tarosky\MakePostmetaFaster\Pattern\SingletonPattern;

/**
 * REST API for index management.
 */
class IndexApi extends SingletonPattern {

	const NAMESPACE = 'mpmf/v1';

	const ROUTE = '/index';

	/**
	 * {@inheritDoc}
	 */
	protected function init() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route( self::NAMESPACE, self::ROUTE, array(
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'handle_get' ),
				'permission_callback' => array( $this, 'permission_check' ),
			),
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_post' ),
				'permission_callback' => array( $this, 'permission_check' ),
				'args'                => array(
					'update' => array(
						'type'              => 'boolean',
						'default'           => false,
						'sanitize_callback' => 'rest_sanitize_boolean',
					),
				),
			),
			array(
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'handle_delete' ),
				'permission_callback' => array( $this, 'permission_check' ),
			),
		) );
	}

	/**
	 * Permission check for all endpoints.
	 *
	 * @return bool|\WP_Error
	 */
	public function permission_check() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to manage database indexes.', 'mpmf' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	/**
	 * Get index checker instance.
	 *
	 * @return PostMetaIndexChecker
	 */
	protected function checker() {
		return PostMetaIndexChecker::get_instance();
	}

	/**
	 * Handle GET request - return index status.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function handle_get( $request ) {
		$checker = $this->checker();
		return new \WP_REST_Response( array(
			'has_index'     => $checker->has_index(),
			'indices'       => $checker->get_indices( $checker->key_name() ),
			'explain'       => $checker->explain(),
			'explain_score' => $checker->explain_score(),
			'key_length'    => $this->get_key_length(),
		) );
	}

	/**
	 * Handle POST request - add index.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handle_post( $request ) {
		$checker = $this->checker();
		$update  = $request->get_param( 'update' );
		if ( $checker->has_index() ) {
			if ( ! $update ) {
				return new \WP_Error(
					'mpmf_index_exists',
					__( 'Index already exists. Set "update" to true to recreate.', 'mpmf' ),
					array( 'status' => 409 )
				);
			}
			if ( ! $checker->drop_index() ) {
				return new \WP_Error(
					'mpmf_drop_failed',
					__( 'Failed to remove existing index.', 'mpmf' ),
					array( 'status' => 500 )
				);
			}
		}
		if ( ! $checker->add() ) {
			return new \WP_Error(
				'mpmf_add_failed',
				__( 'Failed to add index.', 'mpmf' ),
				array( 'status' => 500 )
			);
		}
		return new \WP_REST_Response( array(
			'success'   => true,
			'message'   => __( 'Index successfully added.', 'mpmf' ),
			'has_index' => true,
		) );
	}

	/**
	 * Handle DELETE request - remove index.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handle_delete( $request ) {
		$checker = $this->checker();
		if ( ! $checker->has_index() ) {
			return new \WP_Error(
				'mpmf_no_index',
				__( 'No index found to remove.', 'mpmf' ),
				array( 'status' => 404 )
			);
		}
		if ( ! $checker->drop_index() ) {
			return new \WP_Error(
				'mpmf_drop_failed',
				__( 'Failed to remove index.', 'mpmf' ),
				array( 'status' => 500 )
			);
		}
		return new \WP_REST_Response( array(
			'success'   => true,
			'message'   => __( 'Index successfully removed.', 'mpmf' ),
			'has_index' => false,
		) );
	}

	/**
	 * Get current key length settings.
	 *
	 * @return array{meta_key: int, meta_value: int}
	 */
	protected function get_key_length() {
		$length = get_option( 'mpmf-postmeta-key-length', array( 255, 64 ) );
		if ( ! is_array( $length ) || count( $length ) !== 2 ) {
			return array(
				'meta_key'   => 255,
				'meta_value' => 64,
			);
		}
		list( $key_len, $val_len ) = array_map( 'intval', $length );
		return array(
			'meta_key'   => min( max( 32, $key_len ), 255 ),
			'meta_value' => max( 64, $val_len ),
		);
	}
}

<?php

namespace Tarosky\MakePostmetaFaster\Api;

use Tarosky\MakePostmetaFaster\IndexChecker\PostMetaIndexChecker;
use Tarosky\MakePostmetaFaster\IndexChecker\TermMetaIndexChecker;
use Tarosky\MakePostmetaFaster\IndexChecker\UserMetaIndexChecker;
use Tarosky\MakePostmetaFaster\Pattern\AbstractIndexChecker;
use Tarosky\MakePostmetaFaster\Pattern\SingletonPattern;

/**
 * REST API for index management.
 */
class IndexApi extends SingletonPattern {

	const NAMESPACE = 'mpmf/v1';

	/**
	 * Map of table identifier to checker class name.
	 *
	 * @var array<string, class-string<AbstractIndexChecker>>
	 */
	const TABLES = array(
		'postmeta' => PostMetaIndexChecker::class,
		'usermeta' => UserMetaIndexChecker::class,
		'termmeta' => TermMetaIndexChecker::class,
	);

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
		// List all tables' status.
		register_rest_route( self::NAMESPACE, '/index', array(
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'handle_list' ),
				'permission_callback' => array( $this, 'permission_check' ),
			),
		) );

		// Per-table operations.
		$table_pattern = '(?P<table>' . implode( '|', array_keys( self::TABLES ) ) . ')';
		register_rest_route( self::NAMESPACE, '/index/' . $table_pattern, array(
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
	 * Resolve a checker instance from table identifier.
	 *
	 * @param string $table Table identifier.
	 * @return AbstractIndexChecker|null Null if unknown.
	 */
	protected function checker( $table ) {
		if ( ! isset( self::TABLES[ $table ] ) ) {
			return null;
		}
		$class = self::TABLES[ $table ];
		return call_user_func( array( $class, 'get_instance' ) );
	}

	/**
	 * Build status payload for a single table.
	 *
	 * @param AbstractIndexChecker $checker Checker instance.
	 * @return array
	 */
	protected function build_status( AbstractIndexChecker $checker ) {
		return array(
			'has_index'     => $checker->has_index(),
			'indices'       => $checker->get_indices( $checker->key_name() ),
			'explain'       => $checker->explain(),
			'explain_score' => $checker->explain_score(),
			'key_length'    => $this->get_key_length( $checker ),
		);
	}

	/**
	 * Handle GET /index - return status for all tables.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function handle_list( $request ) {
		unset( $request );
		$result = array();
		foreach ( array_keys( self::TABLES ) as $table ) {
			$result[ $table ] = $this->build_status( $this->checker( $table ) );
		}
		return new \WP_REST_Response( $result );
	}

	/**
	 * Handle GET /index/{table} - return status for one table.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function handle_get( $request ) {
		$checker = $this->checker( $request->get_param( 'table' ) );
		return new \WP_REST_Response( $this->build_status( $checker ) );
	}

	/**
	 * Handle POST /index/{table} - add index.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handle_post( $request ) {
		$checker = $this->checker( $request->get_param( 'table' ) );
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
	 * Handle DELETE /index/{table} - remove index.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handle_delete( $request ) {
		$checker = $this->checker( $request->get_param( 'table' ) );
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
	 * Get current key length settings for the checker.
	 *
	 * @param AbstractIndexChecker $checker Checker instance.
	 * @return array{meta_key: int, meta_value: int}
	 */
	protected function get_key_length( AbstractIndexChecker $checker ) {
		list( $key_len, $val_len ) = $checker->key_length();
		return array(
			'meta_key'   => $key_len,
			'meta_value' => $val_len,
		);
	}
}

<?php
/**
 * The Jetpack Connection Rest Authentication file.
 *
 * @package automattic/jetpack-connection
 */

namespace Automattic\Jetpack\Connection;

/**
 * The Jetpack Connection Rest Authentication class.
 */
class Rest_Authentication {

	/**
	 * The rest authentication status.
	 *
	 * @var boolean
	 */
	private $rest_authentication_status = null;

	/**
	 * The Manager object.
	 *
	 * @var Object
	 */
	private $connection_manager = null;

	/**
	 * The constructor.
	 */
	public function __construct() {
		$this->connection_manager = new Manager();
	}

	/**
	 * Authenticates requests from Jetpack server to WP REST API endpoints.
	 * Uses the existing XMLRPC request signing implementation.
	 *
	 * @param int|bool $user User ID if one has been determined, false otherwise.
	 *
	 * @return int|null The user id or null if the request was not authenticated.
	 */
	public function wp_rest_authenticate( $user ) {
		if ( ! empty( $user ) ) {
			// Another authentication method is in effect.
			return $user;
		}

		if ( ! isset( $_GET['_for'] ) || $_GET['_for'] !== 'jetpack' ) {
			// Nothing to do for this authentication method.
			return null;
		}

		if ( ! isset( $_GET['token'] ) && ! isset( $_GET['signature'] ) ) {
			// Nothing to do for this authentication method.
			return null;
		}

		// Only support specific request parameters that have been tested and
		// are known to work with signature verification.  A different method
		// can be passed to the WP REST API via the '?_method=' parameter if
		// needed.
		if ( $_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
			$this->rest_authentication_status = new WP_Error(
				'rest_invalid_request',
				__( 'This request method is not supported.', 'jetpack' ),
				array( 'status' => 400 )
			);
			return null;
		}
		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] && ! empty( file_get_contents( 'php://input' ) ) ) {
			$this->rest_authentication_status = new WP_Error(
				'rest_invalid_request',
				__( 'This request method does not support body parameters.', 'jetpack' ),
				array( 'status' => 400 )
			);
			return null;
		}

		$verified = $this->connection_manager->verify_xml_rpc_signature();

		if (
			$verified &&
			isset( $verified['type'] ) &&
			'user' === $verified['type'] &&
			! empty( $verified['user_id'] )
		) {
			// Authentication successful.
			$this->rest_authentication_status = true;
			return $verified['user_id'];
		}

		// Something else went wrong.  Probably a signature error.
		$this->rest_authentication_status = new WP_Error(
			'rest_invalid_signature',
			__( 'The request is not signed correctly.', 'jetpack' ),
			array( 'status' => 400 )
		);
		return null;
	}

	/**
	 * Report authentication status to the WP REST API.
	 *
	 * @param  WP_Error|mixed $value Error from another authentication handler, null if we should handle it, or another value if not.
	 * @return WP_Error|boolean|null {@see WP_JSON_Server::check_authentication}
	 */
	public function wp_rest_authentication_errors( $value ) {
		if ( null !== $value ) {
			return $value;
		}
		return $this->rest_authentication_status;
	}

	/**
	 * Resets the saved authentication state in between testing requests.
	 */
	public function reset_saved_auth_state() {
		$this->rest_authentication_status = null;
		$this->connection_manager->reset_saved_auth_state();
	}
}

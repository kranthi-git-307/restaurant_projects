<?php
if (!defined('ABSPATH')) {
    exit;
}
class Pinaka_Privacy_Policy_API_Controller {

    protected $namespace = 'pinaka-restaurant-pos/v1';
    protected $rest_base = 'privacy-policy';

    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/privacy-policies',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_all_privacy_policies' ],
                'permission_callback' => [ $this, 'check_user_role_permission' ],
            ]
        );
    }

    public function check_user_role_permission( $request ) {

		$user_id = get_current_user_id();
		$user    = get_userdata( $user_id );

		if ( ! $user ) {
			return new WP_Error(
				'pinakapos_rest_cannot_view',
				esc_html__( 'Sorry, you cannot give permission.', 'pinaka-pos' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		// Get all registered roles
		global $wp_roles;
		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}
		$all_roles = array_keys( $wp_roles->get_names() );

		// Allow if user has any of the registered roles
		foreach ( $user->roles as $role ) {
			if ( in_array( $role, $all_roles, true ) ) {
				return true;
			}
		}

		return new WP_Error(
			'pinakapos_rest_cannot_view',
			esc_html__( 'Sorry, you cannot give permission.', 'pinaka-pos' ),
			[ 'status' => rest_authorization_required_code() ]
		);
	}

    public function get_all_privacy_policies( $request ) {
        return rest_ensure_response( [] );
    }
}   
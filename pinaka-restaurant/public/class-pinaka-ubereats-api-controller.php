<?php
if (!defined('ABSPATH')) {
    exit;
}

class Pinaka_Uber_Eats_API_Controller 
{
    protected $namespace = 'pinaka-restaurant-pos/v1';
    protected $rest_base = 'uber-eats';
    public function register_routes() 
    {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/webhook',
            [
                'methods'             => 'POST',
                'callback' =>       [$this, 'uber_eats_webhook_handler'],
                'permission_callback' => '__return_true',
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
		global $wp_roles;
		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}
		$all_roles = array_keys( $wp_roles->get_names() );
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
	public function uber_eats_webhook_handler($request)
    {
        $body = $request->get_json_params();

        error_log('Uber Webhook: ' . json_encode($body));

        return new WP_REST_Response([
            'success' => true
        ], 200);
    }
}
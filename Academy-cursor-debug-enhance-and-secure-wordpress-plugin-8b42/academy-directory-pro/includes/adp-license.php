<?php
/**
 * Simple license system.
 */
// class ADP_License {  // کامنت شده: کل کلاس غیرفعال
//     public function __construct() {
//         add_action( 'wp_ajax_adp_activate_license', array( $this, 'activate' ) );
//         add_filter( 'adp_is_licensed', array( $this, 'check' ) );
//     }

//     public function activate() {
//         check_ajax_referer( 'adp_filter_nonce', 'nonce' ); // Reuse nonce for simplicity
//         $key = sanitize_text_field( $_POST['key'] );
//         // Placeholder: Call remote API
//         $response = wp_remote_post( 'https://your-api.com/validate', array( 'body' => array( 'key' => $key, 'site' => home_url() ) ) );
//         if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
//             wp_send_json_error();
//         }
//         update_option( 'adp_license_key', $key );
//         update_option( 'adp_license_status', 'valid' );
//         wp_send_json_success();
//     }

//     public function check() {
//         return get_option( 'adp_license_status' ) === 'valid';
//     }
// }
<?php
/**
 * Security helpers: nonces, rate limiting, visitor IP and input sanitising.
 *
 * @package AzmayenLiveChat
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class ALC_Security {

    public static function create_nonce( $action = 'alc_action' ) {
        return wp_create_nonce( $action );
    }

    public static function verify_nonce( $nonce, $action = 'alc_action' ) {
        if ( ! wp_verify_nonce( $nonce, $action ) ) {
            wp_send_json_error( [ 'message' => 'Security check failed.' ], 403 );
        }
    }

    public static function verify_admin_nonce( $nonce, $action = 'alc_admin_action' ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Insufficient permissions.' ], 403 );
        }
        if ( ! wp_verify_nonce( $nonce, $action ) ) {
            wp_send_json_error( [ 'message' => 'Security check failed.' ], 403 );
        }
    }

    public static function check_rate_limit( $action, $max_requests = 10, $window = 60 ) {
        $ip  = self::get_visitor_ip();
        $key = 'alc_rl_' . md5( $action . $ip );
        $hit = (int) get_transient( $key );
        if ( $hit >= $max_requests ) { return false; }
        set_transient( $key, $hit + 1, $window );
        return true;
    }

    public static function get_visitor_ip() {
        foreach ( [ 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' ] as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = trim( explode( ',', $_SERVER[ $key ] )[0] );
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    return sanitize_text_field( $ip );
                }
            }
        }
        return '0.0.0.0';
    }

    public static function clean_string( $val )  { return sanitize_text_field( wp_unslash( $val ) ); }
    public static function clean_email( $val )   { return sanitize_email( wp_unslash( $val ) ); }
    public static function clean_int( $val )     { return absint( $val ); }
    public static function clean_message( $val ) { return wp_strip_all_tags( wp_unslash( $val ) ); }

    public static function post( $key, $type = 'string', $default = '' ) {
        if ( ! isset( $_POST[ $key ] ) ) { return $default; }
        switch ( $type ) {
            case 'int':     return self::clean_int( $_POST[ $key ] );
            case 'email':   return self::clean_email( $_POST[ $key ] );
            case 'message': return self::clean_message( $_POST[ $key ] );
            default:        return self::clean_string( $_POST[ $key ] );
        }
    }
}

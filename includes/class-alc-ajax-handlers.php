<?php
/**
 * AJAX endpoints for the visitor widget and the admin dashboard.
 *
 * @package AzmayenLiveChat
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class ALC_Ajax_Handlers {

    public function __construct() {
        $public_actions = [ 'alc_start_chat', 'alc_send_message', 'alc_poll_messages', 'alc_typing' ];
        foreach ( $public_actions as $action ) {
            $method = str_replace( 'alc_', '', $action );
            add_action( 'wp_ajax_nopriv_' . $action, [ $this, $method ] );
            add_action( 'wp_ajax_'        . $action, [ $this, $method ] );
        }

        $admin_actions = [
            'alc_admin_reply', 'alc_admin_poll', 'alc_admin_poll_list',
            'alc_admin_mark_read', 'alc_admin_delete_conv', 'alc_admin_typing',
            'alc_admin_save_settings', 'alc_admin_export_conv',
            'alc_admin_toggle_status', 'alc_admin_get_quick_replies',
            'alc_admin_save_quick_reply', 'alc_admin_delete_quick_reply',
            'alc_admin_get_auto_replies', 'alc_admin_save_auto_reply',
            'alc_admin_delete_auto_reply', 'alc_admin_toggle_auto_reply',
        ];
        foreach ( $admin_actions as $action ) {
            $method = str_replace( 'alc_', '', $action );
            add_action( 'wp_ajax_' . $action, [ $this, $method ] );
        }
    }

    // ── Visitor ───────────────────────────────────────────────────────────────

    public function start_chat() {
        ALC_Security::verify_nonce( ALC_Security::post( 'nonce' ), 'alc_visitor' );

        if ( ! ALC_Security::check_rate_limit( 'start_chat', 3, 600 ) ) {
            wp_send_json_error( [ 'message' => 'Too many requests. Please wait a moment.' ] );
        }

        $name    = ALC_Security::post( 'visitor_name' );
        $email   = ALC_Security::post( 'visitor_email', 'email' );
        $company = ALC_Security::post( 'visitor_company' );
        $phone   = ALC_Security::post( 'visitor_phone' );
        $subject = ALC_Security::post( 'visitor_subject' );

        if ( empty( $name ) || empty( $email ) ) {
            wp_send_json_error( [ 'message' => 'Name and email are required.' ] );
        }
        if ( ! is_email( $email ) ) {
            wp_send_json_error( [ 'message' => 'Please enter a valid email address.' ] );
        }

        $existing = ALC_Database::find_conversation_by_email( $email );
        if ( $existing ) {
            ALC_Database::touch_conversation( $existing->id, false );
            wp_send_json_success( [ 'conversation_id' => $existing->id, 'message' => 'Chat resumed.' ] );
            return;
        }

        $conv_id = ALC_Database::create_conversation( [
            'name'    => $name,
            'email'   => $email,
            'company' => $company,
            'phone'   => $phone,
            'subject' => $subject,
            'ip'      => ALC_Security::get_visitor_ip(),
        ] );

        if ( ! $conv_id ) {
            wp_send_json_error( [ 'message' => 'Could not start chat. Please try again.' ] );
        }

        $welcome = ALC_Settings::get( 'welcome_message' );
        if ( $welcome ) {
            ALC_Database::insert_message( $conv_id, 'admin', $welcome );
        }

        if ( ALC_Settings::get( 'admin_email_notify' ) === '1' ) {
            self::send_admin_email_notification( $conv_id, $name, $email, '' );
        }

        wp_send_json_success( [ 'conversation_id' => $conv_id, 'message' => 'Chat started.' ] );
    }

    public function send_message() {
        ALC_Security::verify_nonce( ALC_Security::post( 'nonce' ), 'alc_visitor' );

        if ( ! ALC_Security::check_rate_limit( 'send_message', 20, 60 ) ) {
            wp_send_json_error( [ 'message' => 'Sending too fast. Please slow down.' ] );
        }

        $conv_id = ALC_Security::post( 'conversation_id', 'int' );
        $message = ALC_Security::post( 'message', 'message' );

        if ( ! $conv_id || empty( $message ) ) {
            wp_send_json_error( [ 'message' => 'Invalid request.' ] );
        }
        if ( strlen( $message ) > 2000 ) {
            wp_send_json_error( [ 'message' => 'Message too long (max 2000 chars).' ] );
        }

        $conv = ALC_Database::get_conversation( $conv_id );
        if ( ! $conv ) { wp_send_json_error( [ 'message' => 'Conversation not found.' ] ); }

        $msg_id = ALC_Database::insert_message( $conv_id, 'visitor', $message );
        ALC_Database::touch_conversation( $conv_id, true );

        // ── Auto-reply check ────────────────────────────────────────────────
        $auto_reply_text = ALC_Database::match_auto_reply( $message );
        $auto_reply_id   = null;
        if ( $auto_reply_text ) {
            $auto_reply_id = ALC_Database::insert_message( $conv_id, 'admin', $auto_reply_text );
            ALC_Database::touch_conversation( $conv_id, false );
        }

        // ── Schedule follow-up email to admin if no human reply in 10 min ──
        if ( ALC_Settings::get( 'followup_enabled' ) === '1' ) {
            wp_schedule_single_event(
                time() + 600,
                'alc_visitor_followup',
                [ absint( $conv_id ), absint( $msg_id ), $auto_reply_id ? absint( $auto_reply_id ) : 0, sanitize_text_field( $message ) ]
            );
        }

        wp_send_json_success( [ 'message_id' => $msg_id, 'message' => 'Message sent.', 'auto_reply_id' => $auto_reply_id ] );
    }

    public function poll_messages() {
        $conv_id  = ALC_Security::post( 'conversation_id', 'int' );
        $since_id = ALC_Security::post( 'since_id', 'int' );

        if ( ! $conv_id ) { wp_send_json_error( [ 'message' => 'Invalid request.' ] ); }

        $conv = ALC_Database::get_conversation( $conv_id );
        if ( ! $conv ) { wp_send_json_error( [ 'message' => 'Conversation not found.' ] ); }

        $messages     = ALC_Database::get_messages( $conv_id, $since_id );
        $admin_typing = (int) get_transient( 'alc_admin_typing_' . $conv_id );

        wp_send_json_success( [ 'messages' => $messages, 'admin_typing' => $admin_typing > 0 ] );
    }

    public function typing() {
        $conv_id   = ALC_Security::post( 'conversation_id', 'int' );
        $is_typing = ALC_Security::post( 'is_typing' ) === '1';

        if ( $conv_id ) {
            $key = 'alc_visitor_typing_' . $conv_id;
            $is_typing ? set_transient( $key, 1, 5 ) : delete_transient( $key );
        }
        wp_send_json_success();
    }

    // ── Admin ─────────────────────────────────────────────────────────────────

    public function admin_reply() {
        ALC_Security::verify_admin_nonce( ALC_Security::post( 'nonce' ), 'alc_admin_action' );

        $conv_id = ALC_Security::post( 'conversation_id', 'int' );
        $message = ALC_Security::post( 'message', 'message' );

        if ( ! $conv_id || empty( $message ) ) { wp_send_json_error( [ 'message' => 'Invalid request.' ] ); }

        $conv = ALC_Database::get_conversation( $conv_id );
        if ( ! $conv ) { wp_send_json_error( [ 'message' => 'Conversation not found.' ] ); }

        $msg_id = ALC_Database::insert_message( $conv_id, 'admin', $message );
        ALC_Database::touch_conversation( $conv_id, false );
        ALC_Database::mark_conversation_read( $conv_id );
        ALC_Database::set_conversation_status( $conv_id, 'open' );
        delete_transient( 'alc_admin_typing_' . $conv_id );

        // ── Schedule follow-up email to visitor if no reply in 10 min ──────
        if ( ALC_Settings::get( 'followup_enabled' ) === '1' ) {
            wp_schedule_single_event(
                time() + 600,
                'alc_admin_followup',
                [ absint( $conv_id ), absint( $msg_id ), sanitize_text_field( $message ) ]
            );
        }

        wp_send_json_success( [ 'message_id' => $msg_id, 'message' => 'Reply sent.' ] );
    }

    public function admin_poll() {
        ALC_Security::verify_admin_nonce( ALC_Security::post( 'nonce' ), 'alc_admin_action' );

        $conv_id  = ALC_Security::post( 'conversation_id', 'int' );
        $since_id = ALC_Security::post( 'since_id', 'int' );

        if ( ! $conv_id ) { wp_send_json_error( [ 'message' => 'Invalid request.' ] ); }

        $messages       = ALC_Database::get_messages( $conv_id, $since_id );
        $visitor_typing = (int) get_transient( 'alc_visitor_typing_' . $conv_id );

        $conv = ALC_Database::get_conversation( $conv_id );
        wp_send_json_success( [ 'messages' => $messages, 'visitor_typing' => $visitor_typing > 0, 'conv_status' => $conv->status ?? 'open' ] );
    }

    public function admin_poll_list() {
        ALC_Security::verify_admin_nonce( ALC_Security::post( 'nonce' ), 'alc_admin_action' );

        $unread = ALC_Database::count_unread();
        $search = ALC_Security::post( 'search' );
        $status = ALC_Security::post( 'filter_status' );
        $convs  = ALC_Database::get_conversations( 30, 0, $search, $status );

        $formatted = array_map( function( $c ) {
            return [
                'id'             => $c->id,
                'visitor_name'   => esc_html( $c->visitor_name ),
                'visitor_email'  => esc_html( $c->visitor_email ),
                'visitor_company'=> esc_html( $c->visitor_company ),
                'is_read'        => (int) $c->is_read,
                'status'         => $c->status,
                'last_message'   => $c->last_message,
                'last_message_h' => human_time_diff( strtotime( $c->last_message ), current_time( 'timestamp' ) ) . ' ago',
                'created_at'     => $c->created_at,
            ];
        }, $convs );

        wp_send_json_success( [ 'unread' => $unread, 'conversations' => $formatted ] );
    }

    public function admin_mark_read() {
        ALC_Security::verify_admin_nonce( ALC_Security::post( 'nonce' ), 'alc_admin_action' );
        ALC_Database::mark_conversation_read( ALC_Security::post( 'conversation_id', 'int' ) );
        wp_send_json_success( [ 'message' => 'Marked as read.' ] );
    }

    public function admin_delete_conv() {
        ALC_Security::verify_admin_nonce( ALC_Security::post( 'nonce' ), 'alc_admin_action' );
        ALC_Database::delete_conversation( ALC_Security::post( 'conversation_id', 'int' ) );
        wp_send_json_success( [ 'message' => 'Conversation deleted.' ] );
    }

    public function admin_typing() {
        if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( [], 403 ); }
        $conv_id   = ALC_Security::post( 'conversation_id', 'int' );
        $is_typing = ALC_Security::post( 'is_typing' ) === '1';
        if ( $conv_id ) {
            $key = 'alc_admin_typing_' . $conv_id;
            $is_typing ? set_transient( $key, 1, 5 ) : delete_transient( $key );
        }
        wp_send_json_success();
    }

    public function admin_save_settings() {
        ALC_Security::verify_admin_nonce( ALC_Security::post( 'nonce' ), 'alc_admin_settings' );
        $allowed = array_keys( ALC_Settings::defaults() );
        $new     = [];
        foreach ( $allowed as $key ) {
            if ( isset( $_POST[ $key ] ) ) {
                $new[ $key ] = ALC_Security::clean_string( $_POST[ $key ] );
            }
        }
        ALC_Settings::save( $new );
        wp_send_json_success( [ 'message' => 'Settings saved successfully.' ] );
    }

    public function admin_export_conv() {
        ALC_Security::verify_admin_nonce( ALC_Security::post( 'nonce' ), 'alc_admin_action' );

        $conv_id  = ALC_Security::post( 'conversation_id', 'int' );
        $conv     = ALC_Database::get_conversation( $conv_id );
        if ( ! $conv ) { wp_send_json_error( [ 'message' => 'Conversation not found.' ] ); }

        $messages = ALC_Database::get_messages( $conv_id );
        $lines    = [
            "===================================",
            "  Azmayen Live Chat — Export",
            "===================================",
            "Visitor : {$conv->visitor_name}",
            "Email   : {$conv->visitor_email}",
            "Company : {$conv->visitor_company}",
            "Phone   : {$conv->visitor_phone}",
            "Subject : {$conv->visitor_subject}",
            "Started : {$conv->created_at}",
            "Status  : {$conv->status}",
            "-----------------------------------",
            "",
        ];
        foreach ( $messages as $msg ) {
            $who     = $msg->sender === 'admin' ? 'Azmayen' : $conv->visitor_name;
            $lines[] = "[{$msg->created_at}] {$who}: {$msg->message}";
        }
        $lines[] = "";
        $lines[] = "===================================";

        wp_send_json_success( [ 'text' => implode( "\n", $lines ) ] );
    }

    public function admin_toggle_status() {
        ALC_Security::verify_admin_nonce( ALC_Security::post( 'nonce' ), 'alc_admin_action' );
        $conv_id = ALC_Security::post( 'conversation_id', 'int' );
        $status  = ALC_Security::post( 'status' );
        $conv = ALC_Database::get_conversation( $conv_id );
        if ( ! $conv ) { wp_send_json_error( [ 'message' => 'Conversation not found.' ] ); }
        $new_status = ( $conv->status === 'open' ) ? 'closed' : 'open';
        ALC_Database::set_conversation_status( $conv_id, $new_status );
        wp_send_json_success( [ 'status' => $new_status ] );
    }

    public function admin_get_quick_replies() {
        if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( [], 403 ); }
        wp_send_json_success( [ 'quick_replies' => ALC_Database::get_quick_replies() ] );
    }

    public function admin_save_quick_reply() {
        ALC_Security::verify_admin_nonce( ALC_Security::post( 'nonce' ), 'alc_admin_action' );
        $data = [
            'id'      => ALC_Security::post( 'qr_id', 'int' ),
            'title'   => ALC_Security::post( 'qr_title' ),
            'message' => ALC_Security::post( 'qr_message', 'message' ),
        ];
        $id = ALC_Database::save_quick_reply( $data );
        wp_send_json_success( [ 'id' => $id, 'message' => 'Quick reply saved.' ] );
    }

    public function admin_delete_quick_reply() {
        ALC_Security::verify_admin_nonce( ALC_Security::post( 'nonce' ), 'alc_admin_action' );
        ALC_Database::delete_quick_reply( ALC_Security::post( 'qr_id', 'int' ) );
        wp_send_json_success( [ 'message' => 'Quick reply deleted.' ] );
    }

    // ── Auto-reply handlers ───────────────────────────────────────────────────

    public function admin_get_auto_replies() {
        if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( [], 403 ); }
        wp_send_json_success( [ 'auto_replies' => ALC_Database::get_auto_replies() ] );
    }

    public function admin_save_auto_reply() {
        ALC_Security::verify_admin_nonce( ALC_Security::post( 'nonce' ), 'alc_admin_action' );
        $data = [
            'id'         => ALC_Security::post( 'ar_id', 'int' ),
            'title'      => ALC_Security::post( 'ar_title' ),
            'keywords'   => ALC_Security::post( 'ar_keywords' ),
            'reply'      => ALC_Security::post( 'ar_reply', 'message' ),
            'is_enabled' => 1,
        ];
        if ( empty( $data['title'] ) || empty( $data['keywords'] ) || empty( $data['reply'] ) ) {
            wp_send_json_error( [ 'message' => 'Title, keywords and reply are all required.' ] );
        }
        $id = ALC_Database::save_auto_reply( $data );
        wp_send_json_success( [ 'id' => $id, 'message' => 'Auto-reply saved.' ] );
    }

    public function admin_delete_auto_reply() {
        ALC_Security::verify_admin_nonce( ALC_Security::post( 'nonce' ), 'alc_admin_action' );
        ALC_Database::delete_auto_reply( ALC_Security::post( 'ar_id', 'int' ) );
        wp_send_json_success( [ 'message' => 'Auto-reply deleted.' ] );
    }

    public function admin_toggle_auto_reply() {
        ALC_Security::verify_admin_nonce( ALC_Security::post( 'nonce' ), 'alc_admin_action' );
        $new_state = ALC_Database::toggle_auto_reply( ALC_Security::post( 'ar_id', 'int' ) );
        wp_send_json_success( [ 'is_enabled' => $new_state ] );
    }

    private static function send_admin_email_notification( $conv_id, $name, $email, $message ) {
        $admin_email = get_option( 'admin_email' );
        $subject     = "New live chat from {$name} — Azmayen Live Chat";
        $body        = "You have a new chat conversation.\n\n"
                     . "Visitor : {$name}\n"
                     . "Email   : {$email}\n\n"
                     . "View it here: " . admin_url( "admin.php?page=alc-conversations&conv={$conv_id}" );
        wp_mail( $admin_email, $subject, $body );
    }
}

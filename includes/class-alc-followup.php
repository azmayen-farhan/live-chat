<?php
/**
 * Follow-up email alerts (WP-Cron, 10 minutes after an unanswered message).
 *
 * @package AzmayenLiveChat
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * ALC_Followup
 *
 * Handles the two 10-minute follow-up email scenarios:
 *
 *  1. Visitor sends a message → admin has not replied (human reply only —
 *     auto-reply does NOT count) within 10 min → email goes to admin.
 *
 *  2. Admin sends a reply → visitor has not replied within 10 min →
 *     email goes to the visitor at their registered email.
 *
 * Both cron hooks are registered here.  The hooks themselves are scheduled
 * inside ALC_Ajax_Handlers::send_message() and ALC_Ajax_Handlers::admin_reply().
 */
class ALC_Followup {

    public function __construct() {
        add_action( 'alc_visitor_followup', [ $this, 'check_visitor_followup' ], 10, 4 );
        add_action( 'alc_admin_followup',   [ $this, 'check_admin_followup'   ], 10, 3 );
    }

    // ── Called 10 min after a visitor sends a message ─────────────────────────
    // Args: $conv_id, $visitor_msg_id, $auto_reply_id (0 if no auto-reply), $message_text
    public function check_visitor_followup( $conv_id, $visitor_msg_id, $auto_reply_id, $message_text ) {
        global $wpdb;

        $conv = ALC_Database::get_conversation( $conv_id );
        if ( ! $conv || $conv->status === 'closed' ) {
            return; // conversation gone or already closed — nothing to do
        }

        // Find any HUMAN admin message sent after the visitor's message.
        // Exclude the auto-reply (if any) by its ID.
        $table = ALC_Database::messages_table();

        if ( $auto_reply_id > 0 ) {
            $has_human_reply = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table}
                 WHERE conversation_id = %d
                   AND sender = 'admin'
                   AND id > %d
                   AND id != %d",
                absint( $conv_id ),
                absint( $visitor_msg_id ),
                absint( $auto_reply_id )
            ) );
        } else {
            $has_human_reply = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table}
                 WHERE conversation_id = %d
                   AND sender = 'admin'
                   AND id > %d",
                absint( $conv_id ),
                absint( $visitor_msg_id )
            ) );
        }

        if ( $has_human_reply > 0 ) {
            return; // admin already replied — no alert needed
        }

        // Determine recipient and sender addresses
        $to      = $this->get_admin_to_email();
        $from    = $this->get_from_email();
        $name    = $conv->visitor_name ?: 'A visitor';
        $snippet = wp_trim_words( $message_text, 40, '…' );

        $subject = "{$name} sent you a message on your website";
        $body    = "{$name} sent you a message on your website.\n\nMessage: {$snippet}\n\n"
                 . "Reply here: " . admin_url( "admin.php?page=alc-conversations&conv={$conv_id}" );

        $this->send( $to, $subject, $body, $from );
    }

    // ── Called 10 min after admin sends a reply ───────────────────────────────
    // Args: $conv_id, $admin_msg_id, $message_text
    public function check_admin_followup( $conv_id, $admin_msg_id, $message_text ) {
        global $wpdb;

        $conv = ALC_Database::get_conversation( $conv_id );
        if ( ! $conv || $conv->status === 'closed' ) {
            return;
        }

        if ( empty( $conv->visitor_email ) || ! is_email( $conv->visitor_email ) ) {
            return; // no valid visitor email to notify
        }

        // Check if visitor has replied since the admin's message
        $table = ALC_Database::messages_table();
        $visitor_replied = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table}
             WHERE conversation_id = %d
               AND sender = 'visitor'
               AND id > %d",
            absint( $conv_id ),
            absint( $admin_msg_id )
        ) );

        if ( $visitor_replied > 0 ) {
            return; // visitor already replied — no alert needed
        }

        $admin_name  = ALC_Settings::get( 'admin_name', 'Azmayen' );
        $site_name   = get_bloginfo( 'name' ) ?: $admin_name;
        $from        = $this->get_from_email();
        $to          = sanitize_email( $conv->visitor_email );
        $snippet     = wp_trim_words( $message_text, 40, '…' );

        $subject = "{$admin_name} replied to your message on the website";
        $body    = "{$admin_name} replied to your message on {$site_name}.\n\nMessage: {$snippet}\n\n"
                 . "Visit the website to continue the conversation: " . home_url();

        $this->send( $to, $subject, $body, $from );
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function get_admin_to_email() {
        $saved = ALC_Settings::get( 'followup_to_email' );
        if ( $saved && is_email( $saved ) ) {
            return $saved;
        }
        // Fall back to notify_email, then WP admin email
        $notify = ALC_Settings::get( 'notify_email' );
        if ( $notify && is_email( $notify ) ) {
            return $notify;
        }
        return get_option( 'admin_email' );
    }

    private function get_from_email() {
        $saved = ALC_Settings::get( 'followup_from_email' );
        if ( $saved && is_email( $saved ) ) {
            return $saved;
        }
        return get_option( 'admin_email' );
    }

    private function send( $to, $subject, $body, $from ) {
        if ( ! $to || ! is_email( $to ) ) {
            return;
        }
        $admin_name = ALC_Settings::get( 'admin_name', 'Azmayen' );
        $headers    = [
            'Content-Type: text/plain; charset=UTF-8',
            "From: {$admin_name} <{$from}>",
        ];
        wp_mail( $to, $subject, $body, $headers );
    }
}

<?php
/**
 * Database layer: table schema, CRUD helpers and auto-reply matching.
 *
 * @package AzmayenLiveChat
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class ALC_Database {

    public static function conversations_table() {
        global $wpdb;
        return $wpdb->prefix . 'alc_conversations';
    }

    public static function messages_table() {
        global $wpdb;
        return $wpdb->prefix . 'alc_messages';
    }

    public static function quick_replies_table() {
        global $wpdb;
        return $wpdb->prefix . 'alc_quick_replies';
    }

    public static function auto_replies_table() {
        global $wpdb;
        return $wpdb->prefix . 'alc_auto_replies';
    }

    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $conversations = "CREATE TABLE " . self::conversations_table() . " (
            id            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            visitor_name  VARCHAR(100)        NOT NULL DEFAULT '',
            visitor_email VARCHAR(150)        NOT NULL DEFAULT '',
            visitor_company VARCHAR(150)      NOT NULL DEFAULT '',
            visitor_phone VARCHAR(30)         NOT NULL DEFAULT '',
            visitor_subject VARCHAR(200)      NOT NULL DEFAULT '',
            visitor_ip    VARCHAR(45)         NOT NULL DEFAULT '',
            status        ENUM('open','closed') NOT NULL DEFAULT 'open',
            is_read       TINYINT(1)          NOT NULL DEFAULT 0,
            last_message  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_at    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY   (id),
            KEY status    (status),
            KEY is_read   (is_read)
        ) $charset;";

        $messages = "CREATE TABLE " . self::messages_table() . " (
            id              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            conversation_id BIGINT(20) UNSIGNED NOT NULL,
            sender          ENUM('visitor','admin') NOT NULL DEFAULT 'visitor',
            message         TEXT                NOT NULL,
            is_read         TINYINT(1)          NOT NULL DEFAULT 0,
            created_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY     (id),
            KEY conv_id     (conversation_id),
            KEY sender      (sender)
        ) $charset;";

        $quick_replies = "CREATE TABLE " . self::quick_replies_table() . " (
            id        BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title     VARCHAR(100)        NOT NULL DEFAULT '',
            message   TEXT                NOT NULL,
            sort_order INT(11)            NOT NULL DEFAULT 0,
            created_at DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;";

        dbDelta( $conversations );
        dbDelta( $messages );
        dbDelta( $quick_replies );

        $auto_replies = "CREATE TABLE " . self::auto_replies_table() . " (
            id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title       VARCHAR(200)        NOT NULL DEFAULT '',
            keywords    TEXT                NOT NULL,
            reply       TEXT                NOT NULL,
            is_enabled  TINYINT(1)          NOT NULL DEFAULT 1,
            sort_order  INT(11)             NOT NULL DEFAULT 0,
            created_at  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;";
        dbDelta( $auto_replies );

        update_option( 'alc_db_version', ALC_VERSION );
        ALC_Settings::seed_defaults();
    }

    public static function uninstall() {
        global $wpdb;
        $wpdb->query( "DROP TABLE IF EXISTS " . self::messages_table() );
        $wpdb->query( "DROP TABLE IF EXISTS " . self::conversations_table() );
        $wpdb->query( "DROP TABLE IF EXISTS " . self::quick_replies_table() );
        $wpdb->query( "DROP TABLE IF EXISTS " . self::auto_replies_table() );
        delete_option( 'alc_db_version' );
        delete_option( 'alc_settings' );
    }

    public static function create_conversation( $data ) {
        global $wpdb;
        $wpdb->insert(
            self::conversations_table(),
            [
                'visitor_name'    => sanitize_text_field( $data['name']    ?? '' ),
                'visitor_email'   => sanitize_email(      $data['email']   ?? '' ),
                'visitor_company' => sanitize_text_field( $data['company'] ?? '' ),
                'visitor_phone'   => sanitize_text_field( $data['phone']   ?? '' ),
                'visitor_subject' => sanitize_text_field( $data['subject'] ?? '' ),
                'visitor_ip'      => sanitize_text_field( $data['ip']      ?? '' ),
                'created_at'      => current_time( 'mysql' ),
                'last_message'    => current_time( 'mysql' ),
            ],
            [ '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
        );
        return $wpdb->insert_id;
    }

    public static function get_conversations( $limit = 50, $offset = 0, $search = '', $status = '' ) {
        global $wpdb;
        $table = self::conversations_table();
        $where = 'WHERE 1=1';
        $params = [];

        if ( $search ) {
            $like = '%' . $wpdb->esc_like( $search ) . '%';
            $where .= " AND (visitor_name LIKE %s OR visitor_email LIKE %s)";
            $params[] = $like;
            $params[] = $like;
        }
        if ( $status === 'open' || $status === 'closed' ) {
            $where .= ' AND status = %s';
            $params[] = $status;
        }

        $sql = "SELECT * FROM $table $where ORDER BY last_message DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;

        return $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) ) ?: [];
    }

    public static function get_conversation( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::conversations_table() . " WHERE id = %d", absint( $id ) ) );
    }

    public static function find_conversation_by_email( $email ) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM " . self::conversations_table() . " WHERE visitor_email = %s AND status = 'open' ORDER BY last_message DESC LIMIT 1",
                sanitize_email( $email )
            )
        );
    }

    public static function count_unread() {
        global $wpdb;
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . self::conversations_table() . " WHERE is_read = 0" );
    }

    public static function count_open() {
        global $wpdb;
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . self::conversations_table() . " WHERE status = 'open'" );
    }

    public static function mark_conversation_read( $id ) {
        global $wpdb;
        $wpdb->update( self::conversations_table(), [ 'is_read' => 1 ], [ 'id' => absint( $id ) ], [ '%d' ], [ '%d' ] );
        $wpdb->update( self::messages_table(), [ 'is_read' => 1 ], [ 'conversation_id' => absint( $id ), 'sender' => 'visitor' ], [ '%d' ], [ '%d', '%s' ] );
    }

    public static function set_conversation_status( $id, $status ) {
        global $wpdb;
        $wpdb->update( self::conversations_table(), [ 'status' => $status ], [ 'id' => absint( $id ) ], [ '%s' ], [ '%d' ] );
    }

    public static function delete_conversation( $id ) {
        global $wpdb;
        $wpdb->delete( self::messages_table(),     [ 'conversation_id' => absint( $id ) ], [ '%d' ] );
        $wpdb->delete( self::conversations_table(), [ 'id'             => absint( $id ) ], [ '%d' ] );
    }

    public static function touch_conversation( $id, $from_visitor = true ) {
        global $wpdb;
        $data = [ 'last_message' => current_time( 'mysql' ) ];
        if ( $from_visitor ) { $data['is_read'] = 0; }
        $wpdb->update( self::conversations_table(), $data, [ 'id' => absint( $id ) ], array_fill( 0, count( $data ), '%s' ), [ '%d' ] );
    }

    public static function insert_message( $conversation_id, $sender, $message ) {
        global $wpdb;
        $wpdb->insert(
            self::messages_table(),
            [
                'conversation_id' => absint( $conversation_id ),
                'sender'          => in_array( $sender, [ 'visitor', 'admin' ], true ) ? $sender : 'visitor',
                'message'         => wp_kses_post( $message ),
                'created_at'      => current_time( 'mysql' ),
            ],
            [ '%d', '%s', '%s', '%s' ]
        );
        return $wpdb->insert_id;
    }

    public static function get_messages( $conversation_id, $since_id = 0 ) {
        global $wpdb;
        $conv_id = absint( $conversation_id );
        $since   = absint( $since_id );
        if ( $since > 0 ) {
            return $wpdb->get_results(
                $wpdb->prepare( "SELECT * FROM " . self::messages_table() . " WHERE conversation_id = %d AND id > %d ORDER BY id ASC", $conv_id, $since )
            ) ?: [];
        }
        return $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM " . self::messages_table() . " WHERE conversation_id = %d ORDER BY id ASC", $conv_id )
        ) ?: [];
    }

    public static function count_messages( $conversation_id ) {
        global $wpdb;
        return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM " . self::messages_table() . " WHERE conversation_id = %d", absint( $conversation_id ) ) );
    }

    public static function count_total() {
        global $wpdb;
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . self::conversations_table() );
    }

    public static function count_today() {
        global $wpdb;
        $today = current_time( 'Y-m-d' );
        return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM " . self::conversations_table() . " WHERE DATE(created_at) = %s", $today ) );
    }

    // ── Quick Replies ────────────────────────────────────────────────────────
    public static function get_quick_replies() {
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM " . self::quick_replies_table() . " ORDER BY sort_order ASC, id ASC" ) ?: [];
    }

    public static function save_quick_reply( $data ) {
        global $wpdb;
        if ( ! empty( $data['id'] ) ) {
            $wpdb->update( self::quick_replies_table(), [
                'title'   => sanitize_text_field( $data['title'] ),
                'message' => wp_kses_post( $data['message'] ),
            ], [ 'id' => absint( $data['id'] ) ], [ '%s', '%s' ], [ '%d' ] );
            return absint( $data['id'] );
        }
        $wpdb->insert( self::quick_replies_table(), [
            'title'   => sanitize_text_field( $data['title'] ),
            'message' => wp_kses_post( $data['message'] ),
            'created_at' => current_time( 'mysql' ),
        ], [ '%s', '%s', '%s' ] );
        return $wpdb->insert_id;
    }

    public static function delete_quick_reply( $id ) {
        global $wpdb;
        $wpdb->delete( self::quick_replies_table(), [ 'id' => absint( $id ) ], [ '%d' ] );
    }

    // ── Auto Replies ─────────────────────────────────────────────────────────
    public static function get_auto_replies() {
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM " . self::auto_replies_table() . " ORDER BY sort_order ASC, id ASC" ) ?: [];
    }

    public static function save_auto_reply( $data ) {
        global $wpdb;
        $fields = [
            'title'      => sanitize_text_field( $data['title'] ),
            'keywords'   => sanitize_text_field( $data['keywords'] ),
            'reply'      => wp_kses_post( $data['reply'] ),
            'is_enabled' => isset( $data['is_enabled'] ) ? (int) $data['is_enabled'] : 1,
        ];
        if ( ! empty( $data['id'] ) ) {
            $wpdb->update( self::auto_replies_table(), $fields, [ 'id' => absint( $data['id'] ) ], [ '%s', '%s', '%s', '%d' ], [ '%d' ] );
            return absint( $data['id'] );
        }
        $fields['created_at'] = current_time( 'mysql' );
        $wpdb->insert( self::auto_replies_table(), $fields, [ '%s', '%s', '%s', '%d', '%s' ] );
        return $wpdb->insert_id;
    }

    public static function delete_auto_reply( $id ) {
        global $wpdb;
        $wpdb->delete( self::auto_replies_table(), [ 'id' => absint( $id ) ], [ '%d' ] );
    }

    public static function toggle_auto_reply( $id ) {
        global $wpdb;
        $current = $wpdb->get_var( $wpdb->prepare( "SELECT is_enabled FROM " . self::auto_replies_table() . " WHERE id = %d", absint( $id ) ) );
        $new = $current ? 0 : 1;
        $wpdb->update( self::auto_replies_table(), [ 'is_enabled' => $new ], [ 'id' => absint( $id ) ], [ '%d' ], [ '%d' ] );
        return $new;
    }

    /**
     * Match visitor message against all enabled auto-reply rules.
     * Returns the reply text of the first matching rule, or null.
     */
    public static function match_auto_reply( $message ) {
        $rules = self::get_auto_replies();
        $msg_lower = mb_strtolower( $message );
        foreach ( $rules as $rule ) {
            if ( ! $rule->is_enabled ) { continue; }
            $raw_keywords = $rule->keywords;
            $keywords = array_filter( array_map( 'trim', explode( ',', $raw_keywords ) ) );
            if ( empty( $keywords ) ) { continue; }
            foreach ( $keywords as $kw ) {
                if ( $kw === '' ) { continue; }
                if ( mb_strpos( $msg_lower, mb_strtolower( $kw ) ) !== false ) {
                    return $rule->reply;
                }
            }
        }
        return null;
    }
}

<?php
/**
 * Admin: menus, admin-bar badge, asset loading and the four dashboard pages.
 *
 * @package AzmayenLiveChat
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class ALC_Admin {

    public function __construct() {
        add_action( 'admin_menu',            [ $this, 'register_menus' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'admin_bar_menu',        [ $this, 'admin_bar_item' ], 999 );
    }

    public function register_menus() {
        $unread = ALC_Database::count_unread();
        $badge  = $unread ? ' <span class="alc-menu-badge awaiting-mod count-' . $unread . '">' . $unread . '</span>' : '';

        add_menu_page( 'Live Chat', 'Live Chat' . $badge, 'manage_options', 'alc-conversations', [ $this, 'page_conversations' ], 'dashicons-format-chat', 25 );
        add_submenu_page( 'alc-conversations', 'Conversations', 'Conversations' . $badge, 'manage_options', 'alc-conversations', [ $this, 'page_conversations' ] );
        add_submenu_page( 'alc-conversations', 'Quick Replies', 'Quick Replies', 'manage_options', 'alc-quick-replies', [ $this, 'page_quick_replies' ] );
        add_submenu_page( 'alc-conversations', 'Auto Replies', 'Auto Replies', 'manage_options', 'alc-auto-replies', [ $this, 'page_auto_replies' ] );
        add_submenu_page( 'alc-conversations', 'Settings', 'Settings', 'manage_options', 'alc-settings', [ $this, 'page_settings' ] );
    }

    public function admin_bar_item( $wp_admin_bar ) {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        $unread = ALC_Database::count_unread();
        $title  = 'Live Chat' . ( $unread ? " <span style='background:#dc2626;color:#fff;border-radius:10px;padding:1px 6px;font-size:10px;margin-left:4px;'>$unread</span>" : '' );
        $wp_admin_bar->add_node( [ 'id' => 'alc-admin-bar', 'title' => $title, 'href' => admin_url( 'admin.php?page=alc-conversations' ) ] );
    }

    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'alc-' ) === false ) { return; }

        // Enqueue media scripts for image upload
        wp_enqueue_media();

        wp_enqueue_style(
            'alc-google-fonts',
            'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
            [],
            null
        );

        wp_enqueue_style( 'alc-admin-css', ALC_PLUGIN_URL . 'assets/css/admin.css', [ 'alc-google-fonts' ], ALC_VERSION );

        wp_enqueue_script( 'alc-sounds-js', ALC_PLUGIN_URL . 'assets/js/sounds.js', [], ALC_VERSION, true );
        wp_enqueue_script( 'alc-admin-js', ALC_PLUGIN_URL . 'assets/js/admin.js', [ 'jquery', 'alc-sounds-js' ], ALC_VERSION, true );

        $s = ALC_Settings::all();
        $config = [
            'ajaxurl'           => admin_url( 'admin-ajax.php' ),
            'nonce'             => wp_create_nonce( 'alc_admin_action' ),
            'settingsNonce'     => wp_create_nonce( 'alc_admin_settings' ),
            'pollInterval'      => 3000,
            'listInterval'      => 5000,
            'notificationSound' => $s['notification_sound'],
            'strings'           => [
                'confirm_delete' => 'Delete this conversation? This cannot be undone.',
                'sending'        => 'Sending\u2026',
                'send'           => 'Send Reply',
                'copied'         => 'Copied!',
                'select_image'   => 'Select Avatar',
                'use_image'      => 'Use this image',
            ],
        ];
        wp_add_inline_script( 'alc-admin-js', 'var alcAdmin = ' . wp_json_encode( $config ) . ';', 'before' );
    }

    // =========================================================================
    // PAGE: CONVERSATIONS
    // =========================================================================
    public function page_conversations() {
        $conv_id = isset( $_GET['conv'] ) ? absint( $_GET['conv'] ) : 0;
        $search  = isset( $_GET['s'] )    ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

        $convs  = ALC_Database::get_conversations( 100, 0, $search );
        $unread = ALC_Database::count_unread();
        $open   = ALC_Database::count_open();
        $total  = ALC_Database::count_total();
        $today  = ALC_Database::count_today();

        $conv     = null;
        $messages = [];
        $last_id  = 0;
        if ( $conv_id ) {
            $conv = ALC_Database::get_conversation( $conv_id );
            if ( $conv ) {
                ALC_Database::mark_conversation_read( $conv_id );
                $messages = ALC_Database::get_messages( $conv_id );
                $last_id  = $messages ? end( $messages )->id : 0;
            }
        }
        include ALC_PLUGIN_DIR . 'admin/views/conversations.php';
    }

    // =========================================================================
    // PAGE: QUICK REPLIES
    // =========================================================================
    public function page_quick_replies() {
        $replies = ALC_Database::get_quick_replies();
        include ALC_PLUGIN_DIR . 'admin/views/quick-replies.php';
    }

    // =========================================================================
    // PAGE: AUTO REPLIES
    // =========================================================================
    public function page_auto_replies() {
        $rules = ALC_Database::get_auto_replies();
        include ALC_PLUGIN_DIR . 'admin/views/auto-replies.php';
    }

    // =========================================================================
    // PAGE: SETTINGS
    // =========================================================================
    public function page_settings() {
        $s = ALC_Settings::all();
        include ALC_PLUGIN_DIR . 'admin/views/settings.php';
    }

    // ── Template helpers ──────────────────────────────────────────────────────
    private function fr( $label, $desc = '' ) {
        echo '<div class="alc-field-row"><div class="alc-field-label-wrap"><span class="alc-label">' . esc_html( $label ) . '</span>';
        if ( $desc ) { echo '<span class="alc-label-desc">' . esc_html( $desc ) . '</span>'; }
        echo '</div><div>';
    }
    private function efr() { echo '</div></div>'; }

    private function color_field( $name, $value ) {
        echo '<div class="alc-color-row">';
        echo '<div class="alc-color-swatch-wrap"><input type="color" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="alc-color-picker"></div>';
        echo '<input type="text" class="alc-color-hex" value="' . esc_attr( $value ) . '" maxlength="7" data-color-for="' . esc_attr( $name ) . '" readonly>';
        echo '</div>';
    }

    private function save_bar() {
        echo '<div class="alc-settings-actions"><button type="submit" class="alc-btn alc-btn-primary alc-btn-lg" id="alc-save-settings-btn"><span class="dashicons dashicons-saved"></span> Save Settings</button></div>';
    }
}

<?php
/**
 * Front-end: enqueues the widget assets and prints the chat markup in the footer.
 *
 * @package AzmayenLiveChat
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class ALC_Public {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_footer',          [ $this, 'render_widget' ] );
    }

    public function enqueue_assets() {
        if ( ALC_Settings::get( 'chat_enabled' ) !== '1' ) { return; }

        wp_enqueue_style( 'alc-widget-css', ALC_PLUGIN_URL . 'assets/css/widget.css', [], ALC_VERSION );

        wp_enqueue_script( 'alc-sounds-js', ALC_PLUGIN_URL . 'assets/js/sounds.js', [], ALC_VERSION, true );
        wp_enqueue_script( 'alc-widget-js', ALC_PLUGIN_URL . 'assets/js/widget.js', [ 'alc-sounds-js' ], ALC_VERSION, true );

        $s = ALC_Settings::all();

        $config = [
            'ajaxurl'      => admin_url( 'admin-ajax.php' ),
            'nonce'        => wp_create_nonce( 'alc_visitor' ),
            'pollInterval' => 3000,
            'settings'     => [
                'chat_title'           => $s['chat_title'],
                'welcome_message'      => $s['welcome_message'],
                'online_message'       => $s['online_message'],
                'offline_message'      => $s['offline_message'],
                'response_time'        => $s['response_time'],
                'primary_color'        => $s['primary_color'],
                'position'             => $s['position'],
                'show_branding'        => $s['show_branding'],
                'sound_enabled'        => $s['sound_enabled'],
                'notification_sound'   => $s['notification_sound'],
                'start_btn_label'      => $s['start_btn_label'],
                'input_placeholder'    => $s['input_placeholder'],
                'max_message_length'   => $s['max_message_length'],
                'field_name_enabled'    => $s['field_name_enabled'],
                'field_name_required'   => $s['field_name_required'],
                'field_email_enabled'   => $s['field_email_enabled'],
                'field_email_required'  => $s['field_email_required'],
                'field_company_enabled' => $s['field_company_enabled'],
                'field_company_required'=> $s['field_company_required'],
                'field_phone_enabled'   => $s['field_phone_enabled'],
                'field_phone_required'  => $s['field_phone_required'],
                'field_subject_enabled' => $s['field_subject_enabled'],
                'field_subject_required'=> $s['field_subject_required'],
            ],
        ];
        wp_add_inline_script( 'alc-widget-js', 'var alcWidget = ' . wp_json_encode( $config ) . ';', 'before' );
    }

    public function render_widget() {
        if ( ALC_Settings::get( 'chat_enabled' ) !== '1' ) { return; }
        $s = ALC_Settings::all();
        $avatar_url = trim( $s['admin_avatar_url'] );
        $admin_name = $s['admin_name'];
        $initials   = strtoupper( substr( $admin_name, 0, 2 ) );
        include ALC_PLUGIN_DIR . 'public/views/widget.php';
    }
}

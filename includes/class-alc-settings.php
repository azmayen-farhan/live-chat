<?php
/**
 * Settings store: defaults, read/merge/save for the single `alc_settings` option.
 *
 * @package AzmayenLiveChat
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class ALC_Settings {

    const OPTION_KEY = 'alc_settings';

    private static $defaults = [
        'chat_enabled'          => '1',
        'admin_name'            => 'Azmayen',
        'admin_title'           => 'Support',
        'admin_avatar_url'      => '',
        'chat_title'            => 'Chat with Azmayen',
        'chat_subtitle'         => 'Typically replies in a few minutes',
        'welcome_message'       => "Hi there! \xF0\x9F\x91\x8B How can I help you today?",
        'online_message'        => 'We\'re online — ready to help',
        'offline_message'       => 'Currently offline — leave a message!',
        'response_time'         => 'Usually replies within a few minutes',
        'away_message'          => "I'm away right now. Leave your details and I'll get back to you soon!",
        'primary_color'         => '#dc2626',
        'header_text_color'     => '#ffffff',
        'chat_bg_color'         => '#ffffff',
        'bubble_admin_color'    => '#dc2626',
        'bubble_visitor_color'  => '#f3f4f6',
        'position'              => 'right',
        'widget_offset_x'       => '24',
        'widget_offset_y'       => '24',
        'window_width'          => '380',
        'window_height'         => '540',
        'border_radius'         => '16',
        'button_icon'           => 'chat',
        'button_label'          => '',
        'button_size'           => 'md',
        'show_branding'         => '1',
        'custom_css'            => '',
        'field_name_enabled'    => '1',
        'field_name_required'   => '1',
        'field_email_enabled'   => '1',
        'field_email_required'  => '1',
        'field_company_enabled' => '1',
        'field_company_required'=> '0',
        'field_phone_enabled'   => '0',
        'field_phone_required'  => '0',
        'field_subject_enabled' => '0',
        'field_subject_required'=> '0',
        'input_placeholder'     => "Type your message\xe2\x80\xa6",
        'start_btn_label'       => 'Start Chat',
        'sound_enabled'         => '1',
        'notification_sound'    => 'chime',
        'admin_email_notify'    => '1',
        'notify_email'          => '',
        'desktop_notify'        => '0',
        'business_hours_enabled'=> '0',
        'bh_timezone'           => 'UTC',
        'bh_mon'                => '1',
        'bh_tue'                => '1',
        'bh_wed'                => '1',
        'bh_thu'                => '1',
        'bh_fri'                => '1',
        'bh_sat'                => '0',
        'bh_sun'                => '0',
        'bh_open'               => '09:00',
        'bh_close'              => '18:00',
        'max_message_length'    => '2000',
        'auto_close_hours'      => '0',
        'rate_limit_messages'   => '20',
        'rate_limit_chats'      => '3',
        'conversation_retention'=> '90',
        // ── Follow-up email alerts ────────────────────────────────────────────
        'followup_enabled'      => '1',
        'followup_from_email'   => '',
        'followup_to_email'     => '',
    ];

    public static function seed_defaults() {
        if ( ! get_option( self::OPTION_KEY ) ) {
            add_option( self::OPTION_KEY, self::$defaults );
        }
    }

    public static function all() {
        $saved = get_option( self::OPTION_KEY, [] );
        return wp_parse_args( $saved, self::$defaults );
    }

    public static function get( $key, $fallback = null ) {
        $all = self::all();
        return isset( $all[ $key ] ) ? $all[ $key ] : ( $fallback ?? ( self::$defaults[ $key ] ?? '' ) );
    }

    public static function save( $new_values ) {
        $current = self::all();
        $merged  = array_merge( $current, $new_values );
        update_option( self::OPTION_KEY, $merged );
    }

    public static function defaults() {
        return self::$defaults;
    }
}

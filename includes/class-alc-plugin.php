<?php
/**
 * Plugin bootstrap: loads classes, creates/updates the schema and wires the front end or admin.
 *
 * @package AzmayenLiveChat
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class ALC_Plugin {

    /**
     * Classes needed on every request (AJAX handlers, cron callbacks, activation).
     */
    private static function load_core() {
        require_once ALC_PLUGIN_DIR . 'includes/class-alc-settings.php';
        require_once ALC_PLUGIN_DIR . 'includes/class-alc-database.php';
        require_once ALC_PLUGIN_DIR . 'includes/class-alc-security.php';
        require_once ALC_PLUGIN_DIR . 'includes/class-alc-ajax-handlers.php';
        require_once ALC_PLUGIN_DIR . 'includes/class-alc-followup.php';
    }

    /**
     * Activation: create the tables and seed the default settings.
     */
    public static function activate() {
        self::load_core();
        ALC_Database::create_tables();
    }

    /**
     * Deactivation: intentionally does nothing so conversations, settings and
     * rules survive. Data is only removed by uninstall.php.
     */
    public static function deactivate() {}

    /**
     * Runs on `plugins_loaded`.
     */
    public static function run() {
        self::load_core();

        // Bring the schema up to date after the plugin files were replaced.
        if ( get_option( 'alc_db_version' ) !== ALC_VERSION ) {
            ALC_Database::create_tables();
        }

        new ALC_Ajax_Handlers();
        new ALC_Followup();

        if ( is_admin() ) {
            require_once ALC_PLUGIN_DIR . 'admin/class-alc-admin.php';
            new ALC_Admin();
        } else {
            require_once ALC_PLUGIN_DIR . 'public/class-alc-public.php';
            new ALC_Public();
        }
    }
}

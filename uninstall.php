<?php
/**
 * Runs when the plugin is deleted from the Plugins screen.
 *
 * Removes ALL plugin data: the four custom tables, the plugin options and any
 * pending follow-up emails. Deactivating the plugin does not do this.
 *
 * @package AzmayenLiveChat
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }

require_once plugin_dir_path( __FILE__ ) . 'includes/class-alc-database.php';

ALC_Database::uninstall();

wp_unschedule_hook( 'alc_visitor_followup' );
wp_unschedule_hook( 'alc_admin_followup' );

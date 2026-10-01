<?php
/**
 * Plugin Name:       Azmayen Live Chat
 * Plugin URI:        https://azmayenfarhan.com
 * Description:       Premium real‑time human live chat system — floating bubble, messenger‑style UI, full admin dashboard.
 * Version:           1.4.0
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Author:            Azmayen Farhan
 * Author URI:        https://azmayenfarhan.com
 * Text Domain:       azmayen-live-chat
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package AzmayenLiveChat
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'ALC_VERSION',     '1.4.0' );
define( 'ALC_TEXT_DOMAIN', 'azmayen-live-chat' );
define( 'ALC_PLUGIN_FILE', __FILE__ );
define( 'ALC_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'ALC_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );

require_once ALC_PLUGIN_DIR . 'includes/class-alc-plugin.php';

register_activation_hook(   __FILE__, [ 'ALC_Plugin', 'activate'   ] );
register_deactivation_hook( __FILE__, [ 'ALC_Plugin', 'deactivate' ] );

add_action( 'plugins_loaded', [ 'ALC_Plugin', 'run' ] );

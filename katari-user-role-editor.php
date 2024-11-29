<?php
/**
 * Plugin Name: Katari User Role Editor
 * Plugin URI: https://zeedstudio.com/katari-user-role-editor
 * Description: A comprehensive WordPress user role and capability management plugin.
 * Version: 1.0.0
 * Author: Abdul Rauf Abdul Rahaman
 * Author URI: https://zeedstudio.com
 * Text Domain: katari-user-role-editor
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('KATARI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KATARI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('KATARI_PLUGIN_VERSION', '1.0.0');

// Include necessary files
require_once KATARI_PLUGIN_DIR . 'includes/class-katari-main.php';
require_once KATARI_PLUGIN_DIR . 'includes/class-katari-capabilities-manager.php';

/**
 * Initialize the plugin
 */
function katari_init() {
    $GLOBALS['katari_capabilities_manager'] = new Katari_Capabilities_Manager();
}
add_action('plugins_loaded', 'katari_init');

// Initialize the main plugin class
Katari_Main::get_instance();

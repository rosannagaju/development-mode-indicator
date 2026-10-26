<?php
/**
 * Plugin Name: Development Mode Indicator
 * Description: Visually identify WordPress sites that are in development, staging, testing, QA, maintenance, or another custom mode.
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Author: Roshan Nagaju
 * Author URI: https://rosannagaju.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: development-mode-indicator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DMI_VERSION', '1.0.0' );
define( 'DMI_FILE', __FILE__ );
define( 'DMI_DIR', plugin_dir_path( __FILE__ ) );
define( 'DMI_URL', plugin_dir_url( __FILE__ ) );

require_once DMI_DIR . 'includes/class-dmi-settings.php';
require_once DMI_DIR . 'includes/class-dmi-display-manager.php';
require_once DMI_DIR . 'includes/class-dmi-admin.php';
require_once DMI_DIR . 'includes/class-dmi-plugin.php';

register_activation_hook( __FILE__, array( 'DMI_Settings', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'DMI_Settings', 'deactivate' ) );

function dmi_run() {
	$plugin = new DMI_Plugin();
	$plugin->run();
}

dmi_run();

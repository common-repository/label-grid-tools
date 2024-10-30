<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://labelgrid.com
 * @since             1.0.0
 * @package           LabelGrid_Tools
 *
 * @wordpress-plugin
 * Plugin Name:       LabelGrid Tools
 * Description:       LabelGrid Tools is an advanced plugin for Record Labels, Artists, and Distributors that allow to showcase music releases with ease providing advanced promotional and pre-release tools.
 * Version:           1.3.60
 * Author:            LabelGrid
 * Author URI:        https://labelgrid.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       label-grid-tools
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die();
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('LGT_PLUGIN_VERSION', '1.3.60');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-labelgrid-tools-activator.php
 */
function activate_labelgrid_tools()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-labelgrid-tools-activator.php';
    $activator = new LabelGrid_Tools_Activator();
    $activator->activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-labelgrid-tools-deactivator.php
 */
function deactivate_labelgrid_tools()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-labelgrid-tools-deactivator.php';
    LabelGrid_Tools_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_labelgrid_tools');
register_deactivation_hook(__FILE__, 'deactivate_labelgrid_tools');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-labelgrid-tools.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_labelgrid_tools() {

	new LabelGrid_Tools();

}
run_labelgrid_tools();

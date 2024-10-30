<?php
/*
 * Plugin Name: Keap Landing Pages
 * Plugin URI: https://www.keap.com/
 * Version: 1.4.2
 * Description: Host multiple Keap Landing Pages in your WordPress site.
 * Author: Keap
 * Author URI: https://www.keap.com
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Infusionsoft\WordPress\LandingPages;

if (!defined('ABSPATH')) {
    die();
}

define('INFUSIONSOFT_ILP_PLUGIN_URI', plugins_url('', __FILE__));

// This should be the same as the plugin version number in the header.
define('INFUSIONSOFT_ILP_PLUGIN_VERSION', '1.3');

require_once dirname(__FILE__) . '/core.php';
if (is_admin()) {
    require_once dirname(__FILE__) . '/admin.php';
}

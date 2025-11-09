<?php

/**
 * Plugin Name: RugbyExplorer API
 * Description: RugbyExplorer API
 * Version: 1.0
 * Author: Jethrolanda
 * Author URI: jethrolanda.com
 * Text Domain: rugbyexplorer-api
 * Domain Path: /languages/
 * Requires at least: 5.7
 * Requires PHP: 7.2
 */

defined('ABSPATH') || exit;

// Path Constants ======================================================================================================

define('REA_PLUGIN_URL',             plugins_url() . '/rugbyexplorer-api/');
define('REA_PLUGIN_DIR',             plugin_dir_path(__FILE__));
define('REA_CSS_ROOT_URL',           REA_PLUGIN_URL . 'css/');
define('REA_JS_ROOT_URL',            REA_PLUGIN_URL . 'js/');
define('REA_JS_ROOT_DIR',            REA_PLUGIN_DIR . 'js/');
define('REA_TEMPLATES_ROOT_URL',     REA_PLUGIN_URL . 'templates/');
define('REA_TEMPLATES_ROOT_DIR',     REA_PLUGIN_DIR . 'templates/');
define('REA_BLOCKS_ROOT_URL',        REA_PLUGIN_URL . 'blocks/');
define('REA_BLOCKS_ROOT_DIR',        REA_PLUGIN_DIR . 'blocks/');
define('REA_VIEWS_ROOT_URL',         REA_PLUGIN_URL . 'views/');
define('REA_VIEWS_ROOT_DIR',         REA_PLUGIN_DIR . 'views/');

// Require autoloader
require_once 'inc/autoloader.php';

// Run
require_once 'rugbyexplorer-api.plugin.php';
$GLOBALS['rea'] = new RugbyExplorer_API();

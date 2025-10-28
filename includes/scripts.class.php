<?php

namespace REA\Plugin;

/**
 * Scripts class
 *
 * @since   1.0
 */

defined('ABSPATH') || exit;

class Scripts
{

  /**
   * The single instance of the class.
   *
   * @since 1.0
   */
  protected static $_instance = null;

  /**
   * Class constructor.
   *
   * @since 1.0.0
   */
  public function __construct()
  {

    // Load Backend CSS and JS
    add_action('admin_enqueue_scripts', array($this, 'backend_script_loader'));

    // Load Frontend CSS and JS
    add_action('wp_enqueue_scripts', array($this, 'frontend_script_loader'));
  }

  /**
   * Main Instance.
   *
   * @since 1.0
   */
  public static function instance()
  {
    if (is_null(self::$_instance)) {
      self::$_instance = new self();
    }
    return self::$_instance;
  }

  /**
   * Load wp admin backend scripts
   *
   * @since 1.0
   * @return bool
   */
  public function backend_script_loader()
  {
    $asset_file = REA_JS_ROOT_DIR . 'rugbyexplorer/build/index.asset.php';

    if (file_exists($asset_file) && isset($_GET['page']) && $_GET['page'] == "rugbyexplorer") {
      $asset = include $asset_file;
      wp_register_script('rugbyexplorer-js', REA_JS_ROOT_URL . 'rugbyexplorer/build/index.js', $asset['dependencies'], $asset['version'], true);
      wp_localize_script('rugbyexplorer-js', 'rugbyexplorer_params', array(
        'rest_url'   => esc_url_raw(get_rest_url()),
        'nonce' => wp_create_nonce('wp_rest'),
        'ajax_url' => admin_url('admin-ajax.php'),
        'settings' => get_option('rugbyexplorer_options') ?: array()
        // array(
        //   'rugbyexplorer_field_api_username' => get_option('rugbyexplorer_field_api_username') ?: "",
        //   'rugbyexplorer_field_api_password' => get_option('rugbyexplorer_field_api_password') ?: "",
        //   'rugbyexplorer_field_schedule_update' => get_option('rugbyexplorer_field_schedule_update') ?: "",
        //   'rugbyexplorer_field_club_teams' => get_option('rugbyexplorer_field_club_teams') ?: array()
        // )

      ));
      wp_register_style('rugbyexplorer-css', REA_JS_ROOT_URL . 'rugbyexplorer/build/style-index.css');

      wp_enqueue_style('rugbyexplorer-css');
      wp_enqueue_script('rugbyexplorer-js');
    }
  }

  /**
   * Load wp frontend scripts
   *
   * @since 1.0
   * @return bool
   */
  public function frontend_script_loader()
  {
    wp_register_style('rugbyexplorer-api-css', REA_CSS_ROOT_URL . 'style.min.css');
  }
}

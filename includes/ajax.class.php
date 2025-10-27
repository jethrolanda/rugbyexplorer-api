<?php

namespace REA\Plugin;

/**
 * Plugins custom settings page that adheres to wp standard
 * see: https://developer.wordpress.org/plugins/settings/custom-settings-page/
 *
 * @since   1.0
 */

defined('ABSPATH') || exit;

/**
 * WP Settings Class.
 */
class Ajax
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
    // Fetch fuel savings data items via ajax 
    add_action("wp_ajax_delete_events", array($this, 'delete_events'));
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
   * Get fuel savings data.
   * 
   * @since 1.0
   */
  public function delete_events()
  {

    if (!defined('DOING_AJAX') || !DOING_AJAX) {
      wp_die();
    }

    if (!is_user_logged_in()) {
      wp_die();
    }

    try {

      $post_type = 'sp_event';
      $batch_size = 100;

      while (true) {
        $posts = get_posts([
          'post_type'      => $post_type,
          'post_status'    => 'any',
          'numberposts'    => $batch_size,
          'fields'         => 'ids',
        ]);

        if (empty($posts)) break;

        foreach ($posts as $post_id) {
          wp_delete_post($post_id, true); // force delete
        }

        // optional: short sleep to prevent timeout
        // sleep(1);
      }

      wp_send_json(array(
        'status' => 'success',
        'data' => array(),
      ));
    } catch (\Exception $e) {

      wp_send_json(array(
        'status' => 'error',
        'message' => $e->getMessage()
      ));
    }
  }
}

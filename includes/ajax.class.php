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
    // Save settings via ajax 
    add_action("wp_ajax_save_settings", array($this, 'save_settings'));

    // Delete events via ajax 
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
  public function save_settings()
  {

    if (!defined('DOING_AJAX') || !DOING_AJAX) {
      wp_die();
    }

    if (!is_user_logged_in()) {
      wp_die();
    }

    try {

      $data = json_decode(stripslashes($_POST['data']), true);
      // error_log(print_r($data, true));

      // update_option('rugbyexplorer_field_api_username', $data['rugbyexplorer_field_api_username']);
      // update_option('rugbyexplorer_field_api_password', $data['rugbyexplorer_field_api_password']);
      // update_option('rugbyexplorer_field_schedule_update', $data['rugbyexplorer_field_schedule_update']);
      update_option('rugbyexplorer_options', $data);

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

  /**
   * Delete events
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

      $post_type = array('sp_event', 'sp_team');
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

      $taxonomies = array('sp_season', 'sp_league', 'sp_venue'); // your custom taxonomy name 

      foreach ($taxonomies as $taxonomy) {
        $terms = get_terms([
          'taxonomy'   => $taxonomy,
          'hide_empty' => false,
        ]);

        if (!empty($terms) && !is_wp_error($terms)) {
          foreach ($terms as $term) {
            wp_delete_term($term->term_id, $taxonomy);
          }
        }
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

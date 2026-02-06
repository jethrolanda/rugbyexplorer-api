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

    // Create Team via ajax 
    add_action("wp_ajax_create_team", array($this, 'create_team'));

    // Delete Team via ajax 
    add_action("wp_ajax_delete_team", array($this, 'delete_team'));

    // Cache total games played per player via ajax 
    add_action("wp_ajax_cache_total_games_played", array($this, 'cache_total_games_played_cb'));
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
   * Save settings ajax handler
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
   * Delete events ajax handler
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
      @set_time_limit(0);

      $post_type = array('sp_event', 'sp_team', 'sp_player', 'sp_staff', 'sp_official');
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

      $taxonomies = array('sp_season', 'sp_league', 'sp_venue', 'sp_role', 'sp_duty');

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

  /**
   * Create team ajax handler
   * 
   * @since 1.0
   */
  public function create_team()
  {

    if (!defined('DOING_AJAX') || !DOING_AJAX) {
      wp_die();
    }

    if (!is_user_logged_in()) {
      wp_die();
    }

    try {

      $unique = str_replace('.', '', microtime(true));

      $data = json_decode(stripslashes($_POST['data']), true);
      $options = get_option('rugbyexplorer_options');

      $data['key'] = $unique;
      $options['rugbyexplorer_field_club_teams'][] = $data;

      update_option('rugbyexplorer_options', $options);

      wp_send_json(array(
        'status' => 'success',
        'data' => $options,
      ));
    } catch (\Exception $e) {

      wp_send_json(array(
        'status' => 'error',
        'message' => $e->getMessage()
      ));
    }
  }

  /**
   * Delete team ajax handler
   * 
   * @since 1.0
   */
  public function delete_team()
  {

    if (!defined('DOING_AJAX') || !DOING_AJAX) {
      wp_die();
    }

    if (!is_user_logged_in()) {
      wp_die();
    }

    try {

      $key = (int)json_decode(stripslashes($_POST['key']), true);

      $options = get_option('rugbyexplorer_options');
      $index = '';

      foreach ($options['rugbyexplorer_field_club_teams'] as $i => $item) {
        if ($item['key'] == $key) {
          $index = $i;
          break;
        }
      }

      if ($index >= 0) {
        unset($options['rugbyexplorer_field_club_teams'][$index]);
      }

      update_option('rugbyexplorer_options', $options);

      wp_send_json(array(
        'status' => 'success',
        'data' => $options,
      ));
    } catch (\Exception $e) {

      wp_send_json(array(
        'status' => 'error',
        'message' => $e->getMessage()
      ));
    }
  }

  /**
   * Cache total games played per player ajax handler
   * 
   * @since 1.0
   */
  public function cache_total_games_played_cb()
  {

    if (!defined('DOING_AJAX') || !DOING_AJAX) {
      wp_die();
    }

    if (!is_user_logged_in()) {
      wp_die();
    }

    try {

      global $rea;

      $rea->helpers->cache_total_games_played_all_players();

      wp_send_json(array(
        'status' => 'success',
      ));
    } catch (\Exception $e) {

      wp_send_json(array(
        'status' => 'error',
        'message' => $e->getMessage()
      ));
    }
  }
}

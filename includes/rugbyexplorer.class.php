<?php

namespace REA\Plugin;

/**
 * @since   1.0
 */

defined('ABSPATH') || exit;

class RugbyExplorer
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
    // add_action('init', array($this, 'request'));
    add_action('wp_ajax_rugbyexplorer_api', array($this, 'request_data'));
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

  public function request_data()
  {

    if (!defined('DOING_AJAX') || !DOING_AJAX) {
      wp_die();
    }

    if (!is_user_logged_in()) {
      wp_die();
    }

    try {
      global $rea;
      $options = get_option('rugbyexplorer_options');

      $test = array(
        '25848'
      );
      // error_log(print_r(serialize($test), true));
      foreach ($options['rugbyexplorer_field_club_teams'] as $team) {
        $args = array(
          'season' => $team['season'],
          'competition' => $team['competition_id'],
          'team' => $team['team_id'],
          'entityId' => (int) $team['entity_id'],
          'type' =>  'results'
        );
        $rea->api->getData($args);
        // break;
      }


      // $rea->api->getResults();

      wp_send_json(array(
        'status' => 'success',
        'data' => array(),
      ));
    } catch (\Exception $e) {
      wp_send_json(array(
        'status' => 'error',
        'message' => $e->getMessage(),
      ));
    }
  }
}

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
      // Start the timer
      $time_start = microtime(true);

      global $rea;

      $team_name      = $_POST['team_name'] ?? '';
      $competition_id = $_POST['competition_id'] ?? '';
      $team_id        = $_POST['team_id'] ?? '';
      $season         = $_POST['season'] ?? '';
      $entity_id      = $_POST['entity_id'] ?? '';

      if (!empty($competition_id) && !empty($team_id) && !empty($season) && !empty($entity_id)) {
        // Upcoming Fixtures
        $args1 = array(
          'season' => $season,
          'competition' => $competition_id,
          'team' => $team_id,
          'entityId' => (int) $entity_id,
          'type' =>  'fixtures'
        );

        $res1 = $rea->api->getData($args1);
        $rea->sportspress->createEvents($res1, $args1);

        // Recent Results
        $args2 = array(
          'season' => $season,
          'competition' => $competition_id,
          'team' => $team_id,
          'entityId' => (int) $entity_id,
          'type' =>  'results'
        );
        $res2 = $rea->api->getData($args2);
        $status = $rea->sportspress->createEvents($res2, $args2);
      }

      // End the timer
      $time_end = microtime(true);

      // Calculate the execution time
      $execution_time = ($time_end - $time_start);


      wp_send_json(array(
        'status' => 'success',
        'data' => array(
          'event_status'  => array_merge($status, array('time' => round($execution_time, 2))),
        ),
      ));
    } catch (\Exception $e) {
      wp_send_json(array(
        'status' => 'error',
        'message' => $e->getMessage(),
      ));
    }
  }
  public function request_data2()
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

      foreach ($options['rugbyexplorer_field_club_teams'] as $team) {
        // Upcoming Fixtures
        $rea->api->getData(array(
          'season' => $team['season'],
          'competition' => $team['competition_id'],
          'team' => $team['team_id'],
          'entityId' => (int) $team['entity_id'],
          'type' =>  'fixtures'
        ));
        // Recent Results
        $rea->api->getData(array(
          'season' => $team['season'],
          'competition' => $team['competition_id'],
          'team' => $team['team_id'],
          'entityId' => (int) $team['entity_id'],
          'type' =>  'results'
        ));
      }

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

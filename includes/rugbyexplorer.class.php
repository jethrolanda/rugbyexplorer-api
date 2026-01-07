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
    // Ajax request for performing per team events import
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

  /**
   * Perform events import per team. 
   * Import fixtures and results.
   *  
   * @since 1.0
   */
  public function request_data()
  {

    if (!defined('DOING_AJAX') || !DOING_AJAX) {
      wp_die();
    }

    if (!is_user_logged_in()) {
      wp_die();
    }

    try {

      @set_time_limit(0);

      // Start the timer
      $time_start = microtime(true);

      global $rea;

      $competition_id = $_POST['competition_id'] ?? '';
      $team_id        = $_POST['team_id'] ?? '';
      $season         = $_POST['season'] ?? '';
      $entity_id      = $_POST['entity_id'] ?? '';
      $status = array();

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
        if (!empty($res1)) {
          $status = array_merge($status, $rea->sportspress->createEvents($res1, $args1));
        }

        // Recent Results
        $args2 = array(
          'season' => $season,
          'competition' => $competition_id,
          'team' => $team_id,
          'entityId' => (int) $entity_id,
          'type' =>  'results'
        );

        $res2 = $rea->api->getData($args2);
        if (!empty($res2)) {
          $status = array_merge($status, $rea->sportspress->createEvents($res2, $args2));
        }

        // Save team ladder
        $competition_data = $rea->api->getCompetitionLadderData(array(
          'competition_id' => $competition_id
        ));
        $term_id = $rea->sportspress->getTermLeagueIdByName($competition_id);
        if (!empty($competition_data)) {
          if ($term_id) {
            update_term_meta($term_id, 'ladder_data', $competition_data);
          }
          update_option('ladder_data_' . $competition_id, $competition_data);
        }
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

  /**
   * Get all team club team ids
   * 
   * @return array
   * @since 1.0
   */
  public function get_team_ids()
  {
    $settings = get_option('rugbyexplorer_options');
    $teams = isset($settings['rugbyexplorer_field_club_teams']) ? $settings['rugbyexplorer_field_club_teams'] : array();
    $club_ids = array();
    if (!empty($teams)) {
      foreach ($teams as $team) {
        $club_ids[] = $team['team_id'];
      }
    }

    return $club_ids;
  }

  /**
   * Get position name by its order number
   *
   * @param Int $position Oder Number
   * @return string
   * @since 1.0
   */
  public function get_position_name($position)
  {
    $terms = get_terms([
      'taxonomy'   => 'sp_position',
      'hide_empty' => false,
      'meta_query' => [
        [
          'key'     => 'sp_order',
          'value'   => $position,
          'compare' => '='
        ]
      ]
    ]);
    $position_name = "";
    if (! empty($terms) && ! is_wp_error($terms)) {
      $position_name = $terms[0]->name;
    }
    return $position_name;
  }
}

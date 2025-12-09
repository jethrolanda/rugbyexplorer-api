<?php

namespace REA\Plugin;


defined('ABSPATH') || exit;

class Cron
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
    // Action Scheduler hook
    add_action('rugbyexplorer_scheduled_events_update', array($this, 'rugbyexplorer_scheduled_events_update'));
    add_action('rugbyexplorer_update_club_events', array($this, 'rugbyexplorer_update_club_events'));
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
   * Queue action scheduler action events per team
   * 
   * @since 1.0
   */
  public function rugbyexplorer_scheduled_events_update()
  {
    try {

      $year = date('Y');
      $options = get_option('rugbyexplorer_options');

      foreach ($options['rugbyexplorer_field_club_teams'] as $team) {
        // Skip if not current season. Year today
        if ($year == $team['season']) {
          as_enqueue_async_action('rugbyexplorer_update_club_events', array($team));
        }
      }
    } catch (\Exception $e) {
      error_log('Action Scheduler Error: ' . $e->getMessage());
    }
  }

  /**
   * Action Scheduler hook that updates team events
   * 
   * @param array $team Team data
   * 
   * @since 1.0
   */
  public function rugbyexplorer_update_club_events($team)
  {
    try {
      global $rea;

      $competition_id = $team['competition_id'] ?? '';
      $team_id        = $team['team_id'] ?? '';
      $season         = $team['season'] ?? '';
      $entity_id      = $team['entity_id'] ?? '';

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
          $rea->sportspress->createEvents($res1, $args1);
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
          $rea->sportspress->createEvents($res2, $args2);
        }

        // Save team ladder
        $competition_data = $rea->api->getCompetitionLadderData(array(
          'competition_id' => $competition_id
        ));
        $term_id = $rea->sportspress->getTermLeagueIdByName($competition_id);
        if (!empty($competition_data) && $term_id) {
          update_term_meta($term_id, 'ladder_data', $competition_data);
          update_option('ladder_data_' . $competition_id, $competition_data);
        }
      }
    } catch (\Exception $e) {
      error_log('Update Club Events Error: ' . $e->getMessage());
    }
  }
}

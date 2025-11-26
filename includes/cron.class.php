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
    add_filter('cron_schedules', array($this, 'add_custom_cron_schedules'));

    add_action('rugbyexplorer_schedule_update', array($this, 'rugbyexplorer_schedule_update'));

    // add_filter('sportspress_list_data_event_args', array($this, 'sportspress_list_data_event_args'), 10);

    // Action Scheduler hook
    add_action('rugbyexplorer_scheduled_events_update', array($this, 'rugbyexplorer_scheduled_events_update'));
    add_action('update_club_events', array($this, 'update_club_events'));
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

  function add_custom_cron_schedules($schedules)
  {
    $schedules['weekly'] = [
      'interval' => 7 * DAY_IN_SECONDS, // 604800 seconds
      'display'  => __('Once Weekly'),
    ];
    $schedules['every_fifteen_minutes'] = array(
      'interval' => 15 * 60, // 15 minutes in seconds
      'display'  => __('Every 15 Minutes'),
    );
    return $schedules;
  }

  public function rugbyexplorer_schedule_update()
  {
    try {
      global $rea;
      $options = get_option('rugbyexplorer_options');
      $year = date('Y');
      foreach ($options['rugbyexplorer_field_club_teams'] as $team) {

        // Skip if not current season. Year today
        if ($year != $team['season']) continue;

        // Upcoming Fixtures
        $args1 = array(
          'season' => $team['season'],
          'competition' => $team['competition_id'],
          'team' => $team['team_id'],
          'entityId' => (int) $team['entity_id'],
          'type' =>  'fixtures'
        );
        $res1 = $rea->api->getData($args1);
        $rea->sportspress->createEvents($res1, $args1);

        // Recent Results
        $args2 = array(
          'season' => $team['season'],
          'competition' => $team['competition_id'],
          'team' => $team['team_id'],
          'entityId' => (int) $team['entity_id'],
          'type' =>  'results'
        );
        $rea->api->getData($args2);
        $res2 = $rea->api->getData($args2);
        $rea->sportspress->createEvents($res2, $args2);
      }
    } catch (\Exception $e) {
      error_log('Cron Error: ' . $e->getMessage());
    }
  }

  public function sportspress_list_data_event_args($args)
  {

    $args['meta_query'][] = array(
      array(
        'key'     => 'sp_team',
        'value'   => get_the_ID(),
        'compare' => 'IN',
      ),
    );


    return $args;
  }

  public function rugbyexplorer_scheduled_events_update()
  {
    try {

      $year = date('Y');
      $options = get_option('rugbyexplorer_options');

      foreach ($options['rugbyexplorer_field_club_teams'] as $team) {
        // Skip if not current season. Year today
        if ($year == $team['season']) {
          as_enqueue_async_action('update_club_events', $team);
        }
      }
    } catch (\Exception $e) {
      error_log('Action Scheduler Error: ' . $e->getMessage());
    }
  }

  public function update_club_events($team)
  {
    try {

      global $rea;
      // Upcoming Fixtures
      $args1 = array(
        'season' => $team['season'],
        'competition' => $team['competition_id'],
        'team' => $team['team_id'],
        'entityId' => (int) $team['entity_id'],
        'type' =>  'fixtures'
      );

      $res1 = $rea->api->getData($args1);
      $rea->sportspress->createEvents($res1, $args1);

      // Recent Results
      $args2 = array(
        'season' => $team['season'],
        'competition' => $team['competition_id'],
        'team' => $team['team_id'],
        'entityId' => (int) $team['entity_id'],
        'type' =>  'results'
      );

      $rea->api->getData($args2);
      $res2 = $rea->api->getData($args2);
      $rea->sportspress->createEvents($res2, $args2);
    } catch (\Exception $e) {
      error_log('Update Club Events Error: ' . $e->getMessage());
    }
  }
}

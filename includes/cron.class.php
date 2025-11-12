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
    error_log('test cron');

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
    } catch (\Exception $e) {
      error_log('Cron Error: ' . $e->getMessage());
    }
  }
}

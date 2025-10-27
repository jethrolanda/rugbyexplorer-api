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

    add_action('fusesport_schedule_update', array($this, 'fusesport_schedule_update'));
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

  function activation_schedule_event()
  {
    if (! wp_next_scheduled('wce_every_five_minutes_event')) {
      wp_schedule_event(time(), 'every_five_minutes', 'wce_every_five_minutes_event');
    }
  }

  function deactivation_clear_event()
  {
    $timestamp = wp_next_scheduled('wce_every_five_minutes_event');
    if ($timestamp) {
      wp_unschedule_event($timestamp, 'wce_every_five_minutes_event');
    }
  }

  public function fusesport_schedule_update()
  {
    error_log('test cron');

    global $rea;

    $options = get_option('fusesport_options');
    $season_id = $options['fusesport_field_season_id'];
    $requested_token = $rea->fusesport->request_token();

    if ($requested_token['status'] === 'success') {
      $fusesport_competition_ids = array_map('trim', explode(",", $options['fusesport_field_competition_ids']));

      foreach ($fusesport_competition_ids as $competition_id) {
        $url = 'https://rugbyresults.fusesport.com/api/rugby/main_detail/' . $season_id . '?competitionID=' . $competition_id;
        $token = $requested_token['token'];

        $response = wp_remote_get($url, array(
          'headers' => array(
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/json',
          ),
        ));

        if (is_wp_error($response)) {
          error_log(print_r(array(
            'status' => 'error',
            'message' => $response->get_error_message(),
          ), true));
        } else {

          $body = wp_remote_retrieve_body($response);
          $data = json_decode($body, true);
          $rea->sportspress->createEvents($data);
        }
      }
    }
  }
}

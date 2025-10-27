<?php

namespace REA\Plugin;


defined('ABSPATH') || exit;

class Shortcode
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
    add_shortcode('fusesports_fixtures', array($this, 'fusesports_fixtures'));
    add_shortcode('fusesports_ladders', array($this, 'fusesports_ladders'));
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

  function fusesports_fixtures($atts)
  {

    $atts = shortcode_atts(array(
      'season_id' => '',
      'competition_id' => ''
    ), $atts, 'fusesports_fixtures');

    wp_enqueue_style('fusesport-api-css');

    ob_start();

    $data = $this->get_fusesport_fixtures($atts['season_id'], $atts['competition_id']);
    $test = wp_remote_get('https://api.fusesport.com/comps/' . $atts['competition_id'] . '/get/');
    $body = wp_remote_retrieve_body($test);
    $fixtures = json_decode($body, true);
    // error_log(print_r($fixtures, true));
    require_once(REA_VIEWS_ROOT_DIR . 'fusesports-fixtures.php');

    // content
    return ob_get_clean();
  }

  function fusesports_ladders($atts)
  {
    $atts = shortcode_atts(array(
      'season_id' => '',
      'competition_id' => ''
    ), $atts, 'fusesports_fixtures');

    wp_enqueue_style('fusesport-api-css');

    ob_start();

    $data = $this->get_fusesport_ladders($atts['season_id'], $atts['competition_id']);
    $test = wp_remote_get('https://api.fusesport.com/comps/' . $atts['competition_id'] . '/ladder/');
    $body = wp_remote_retrieve_body($test);
    $ladder_data = json_decode($body, true);

    require_once(REA_VIEWS_ROOT_DIR . 'fusesports-ladders.php');

    // content
    return ob_get_clean();
  }


  public function get_fusesport_fixtures($season_id, $competition_id)
  {
    global $fsa;
    $requested_token = $fsa->fusesport->request_token();
    if ($requested_token['status'] === 'success') {

      // You can get scheduling, scores and ladder info from https://rugbyresults.fusesport.com/api/rugby/main_detail/
      // 1313 is the season_id for the whole 2025 Premiership Rugby season.
      // It’s recommended to filter the results by adding ?competitionID=1688636 for Chikarovski Cup or ?competitionID=1688637 for Women's Div 2

      $url = 'https://rugbyresults.fusesport.com/api/rugby/main_detail/' . $season_id . '?competitionID=' . $competition_id;
      $token = $requested_token['token'];

      $response = wp_remote_get($url, array(
        'headers' => array(
          'Authorization' => 'Bearer ' . $token,
          'Accept'        => 'application/json',
        ),
      ));

      if (is_wp_error($response)) {
        echo $response->get_error_message();
      } else {

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        $full_name = $data['rugby-schedule'][0]['competitions'][0]['full_name'];
        $name = $data['rugby-schedule'][0]['competitions'][0]['name'];
        $games = $data['rugby-schedule'][0]['competitions'][0]['games'];

        $grouped = [];

        foreach ($games as $game) {
          $round = $game['round_name']; // key to group by
          $grouped[$round][] = $game;
        }
        return array(
          'full_name' => $full_name,
          'name' => $name,
          'rounds' => $grouped
        );
      }
    }
  }

  public function get_fusesport_ladders($season_id, $competition_id)
  {
    global $fsa;
    $requested_token = $fsa->fusesport->request_token();
    if ($requested_token['status'] === 'success') {

      // You can get scheduling, scores and ladder info from https://rugbyresults.fusesport.com/api/rugby/main_detail/
      // 1313 is the season_id for the whole 2025 Premiership Rugby season.
      // It’s recommended to filter the results by adding ?competitionID=1688636 for Chikarovski Cup or ?competitionID=1688637 for Women's Div 2

      $url = 'https://rugbyresults.fusesport.com/api/rugby/main_detail/' . $season_id . '?competitionID=' . $competition_id;
      $token = $requested_token['token'];

      $response = wp_remote_get($url, array(
        'headers' => array(
          'Authorization' => 'Bearer ' . $token,
          'Accept'        => 'application/json',
        ),
      ));

      if (is_wp_error($response)) {
        echo $response->get_error_message();
      } else {

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        $full_name = $data['rugby-schedule'][0]['competitions'][0]['full_name'];
        $name = $data['rugby-schedule'][0]['competitions'][0]['name'];
        $ladder = $data['rugby-schedule'][0]['competitions'][0]['ladder'];
        $final_placings = isset($data['rugby-schedule'][0]['competitions'][0]['final_placings']) ? $data['rugby-schedule'][0]['competitions'][0]['final_placings'] : array();
        return array(
          'full_name' => $full_name,
          'name' => $name,
          'ladder' => $ladder,
          'final_placings' => $final_placings
        );
      }
    }
  }
}

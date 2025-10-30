<?php

namespace REA\Plugin;

/**
 * @since   1.0
 */

defined('ABSPATH') || exit;

/**
 * WP Settings Class.
 */
class Sportspress
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
  public function __construct() {}

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

  public function createEvents($games)
  {
    // @set_time_limit(0);

    $options = get_option('rugbyexplorer_options');

    // REST API endpoint
    $url = $this->getSiteUrl() . '/wp-json/sportspress/v2/events';

    // Replace with your WordPress application credentials
    $username =  $options['sportspress_field_api_username'];
    $app_password = $options['sportspress_field_api_password'];

    // $sportspressSeasonId = $this->getTermSeasonIdByName('2025');
    // $getLeagueId = $this->getTermLeagueIdByName($leagueName);


    foreach ($games as $key => $game) {

      // Skip if already added
      // if ($this->checkIfGameIdAlreadyExist($game['id'])) {
      //   continue;
      // }
      // $location = isset($game['location']) && !empty(trim($game['location'])) ? trim($game['location']) : '';
      // $venueTermId = empty($location) ? 0 : $this->createVenue($location);

      // $team_ids = $this->createTeams(
      //   array(
      //     $game['hmteam'],
      //     $game['awteam']
      //   )
      // );
      // Prepare event data (example: rugby match)
      $prepared_data = array(
        'title'        => $game['homeTeam']['name'] . ' vs ' . $game['awayTeam']['name'],
        'status'       => 'publish',
        // 'teams'        => $team_ids, // IDs of the teams
        'date'         => $game['dateTime'],
        // 'venue'        => $venueTermId, // venue id
        // 'competition'  => $getLeagueId, // competion id or league id??
        // 'season'       => $sportspressSeasonId // season id
      );

      $args = array(
        'headers' => array(
          'Authorization' => 'Basic ' . base64_encode("$username:$app_password"),
          'Content-Type'  => 'application/json'
        ),
        'body'    => wp_json_encode($prepared_data),
        'method'  => 'POST',
        'timeout' => 300,
      );

      // Send the request
      $response = wp_remote_post($url, $args);
      // error_log(print_r($response, true));
      if (is_wp_error($response)) {
        error_log('SportsPress Event Creation Failed: ' . $response->get_error_message());
      } else {
        $body = json_decode(wp_remote_retrieve_body($response), true);
        // error_log('SportsPress Event Created: ' . print_r($prepared_data['title'], true));
        // Assign venue
        // wp_set_object_terms($body['id'], $venueTermId, 'sp_venue');

        // Assign league
        // wp_set_object_terms($body['id'], $getLeagueId, 'sp_league');

        // Assign season
        // wp_set_object_terms($body['id'], $sportspressSeasonId, 'sp_season');

        // Mode
        // update_post_meta($body['id'], 'sp_format', 'league');

        // Format
        // update_post_meta($body['id'], 'sp_mode', 'team');

        // Results
        // if (isset($game['hmscore']) && $game['hmscore'] != null && $game['awscore'] && $game['awscore'] != null) {
        //   $result = array();
        //   $result[$team_ids[0]] = array(
        //     "tries" => 0,
        //     "conversions" => 0,
        //     "pg" => 0,
        //     "dg" => 0,
        //     "points" => $game['hmscore'],
        //     "outcome" => array(
        //       $game['hmscore'] > $game['awscore'] ? "win" : "loss"
        //     )
        //   );
        //   $result[$team_ids[1]] = array(
        //     "tries" => 0,
        //     "conversions" => 0,
        //     "pg" => 0,
        //     "dg" => 0,
        //     "points" => $game['awscore'],
        //     "outcome" => array(
        //       $game['awscore'] > $game['hmscore'] ? "win" : "loss"
        //     )
        //   );

        //   update_post_meta(
        //     $body['id'],
        //     'sp_results',
        //     $result
        //   );
        // }

        // Add game ID
        // update_post_meta(
        //   $body['id'],
        //   'game_id',
        //   $game['id']
        // );
      }
      // if ($key == 1) break;
    }
    // error_log(print_r($games, true));
  }

  public function createTeams($teams)
  {
    // error_log(print_r($teams, true));

    $options = get_option('fusesport_options');
    $url = $this->getSiteUrl() . '/wp-json/sportspress/v2/teams';
    $username =  $options['sportspress_field_api_username'];
    $app_password = $options['sportspress_field_api_password'];
    $team_ids = array();

    foreach ($teams as $team) {

      $team_name = isset($team['team_name']) ? trim($team['team_name']) : "";
      $team_id_api = isset($team['id']) ? $team['id'] : "";

      // Skip creating team
      if ($team_id_api == 0 || empty($team_name)) {
        $team_ids[] = 0;
        continue;
      }

      // Check if team exist
      $team_id = $this->checkIfTeamIdAlreadyExist($team_name);
      if ($team_id > 0) {
        $team_ids[] = $team_id;
        continue;
      }

      $data = array(
        'title'       => $team_name,
        'status'      => 'publish',
        'description' => ''
      );

      $args = array(
        'headers' => array(
          'Authorization' => 'Basic ' . base64_encode("$username:$app_password"),
          'Content-Type'  => 'application/json'
        ),
        'body'    => wp_json_encode($data),
        'timeout' => 60 // increase timeout for Docker or slow connections
      );


      $response = wp_remote_post($url, $args);

      if (is_wp_error($response)) {
        // error_log('SportsPress Team Creation Failed: ' . $response->get_error_message());
        $team_ids[] = 0;
      } else {
        $body = json_decode(wp_remote_retrieve_body($response), true);
        // error_log('SportsPress Team Created: ' . $team_name);
        $team_ids[] = $body['id'];

        // error_log('createTeams post id: ' . print_r($body['id'], true));
        // error_log('createTeams team id: ' . print_r($team_id_api, true));
        update_post_meta($body['id'], 'team_id', $team_id_api);

        $image_url = $team['logo_url'];

        $post_id   = $body['id']; // Optional – ID of post to attach to
        $attachment_id = $this->createAttachmentFromUrl($image_url, $post_id);
        error_log('Team Logo ID: ' . print_r($attachment_id, true));
        // if ($attachment_id) {
        //   echo 'Created attachment ID: ' . $attachment_id;
        // }
      }
    }

    return $team_ids;
  }

  public function createVenue($venue)
  {

    if (empty(trim($venue))) return 0;

    $options = get_option('fusesport_options');
    $url = $this->getSiteUrl() . '/wp-json/sportspress/v2/venues';
    $username =  $options['sportspress_field_api_username'];
    $app_password = $options['sportspress_field_api_password'];

    $args = array(
      'headers' => array(
        'Authorization' => 'Basic ' . base64_encode("$username:$app_password"),
        'Content-Type'  => 'application/json'
      ),
      'body'    => wp_json_encode(array(
        'name'       => $venue,
        'status'      => 'publish',
        'description' => ''
      )),
      'method'  => 'POST',
      'timeout' => 60
    );

    $response = wp_remote_post($url, $args);

    if (is_wp_error($response)) {
      // error_log('SportsPress Venue Creation Failed: ' . $response->get_error_message());
      return 0;
    } else {
      $data = json_decode(wp_remote_retrieve_body($response));
      // error_log('SportsPress Venue Created: ' . $venue);
      if (isset($data->code) && $data->code == 'term_exists')
        return $data->data->term_id;

      return $data->id;
    }
  }

  // HELPER FUNCTIONS
  public function getSiteUrl()
  {
    // If local env then use the docke host url else use regular url
    if (defined('WP_ENVIRONMENT_TYPE') && WP_ENVIRONMENT_TYPE === 'local') {
      if (defined('WP_DOCKER_HOST')) {
        $url = WP_DOCKER_HOST;
      } else {
        $url = site_url();
      }
    } else {
      $url = site_url();
    }

    return $url;
  }

  public function getTermSeasonIdByName($name)
  {
    $term = get_term_by('name', $name, 'sp_season');

    if ($term) {
      $term_id = $term->term_id;
      return $term_id;
    }

    return false;
  }

  public function getTermLeagueIdByName($name)
  {
    $term = get_term_by('name', $name, 'sp_league');

    if ($term) {
      $term_id = $term->term_id;
      return $term_id;
    }

    return false;
  }


  public function checkIfTeamIdAlreadyExist($team_name)
  {
    global $wpdb;

    $post_type = 'sp_team';

    // Try to find the post by title (exact match)
    $post_id = $wpdb->get_var($wpdb->prepare(
      "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = %s AND post_status != 'trash' LIMIT 1",
      $team_name,
      $post_type
    ));

    // check by the api team id
    // issue: there are 2 different id for the same team

    // $post_id = $wpdb->get_var($wpdb->prepare(
    //   "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s LIMIT 1",
    //   $meta_key,
    //   $team_id_api
    // ));

    // error_log('checkIfTeamIdAlreadyExist: ' . print_r($post_id, true));
    if ($post_id) {
      return (int) $post_id;
    }
    return false;
  }

  public function checkIfGameIdAlreadyExist($game_id)
  {
    global $wpdb;

    $meta_key = 'game_id';

    $post_id = $wpdb->get_var($wpdb->prepare(
      "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s LIMIT 1",
      $meta_key,
      $game_id
    ));

    // error_log('checkIfGameIdAlreadyExist: ' . print_r($post_id, true));
    if ($post_id) {
      // echo "✅ Found in post ID: $result";
      return $post_id;
    } else {
      // echo "❌ Not found";
      return false;
    }
  }

  public function getAllGames($data)
  {
    $games = array();
    foreach ($data['round_objects'] as $round) {
      foreach ($round['games'] as $game) {
        $games[] = $game;
      }
    }
    return $games;
  }

  public function createAttachmentFromUrl($image_url, $post_id = 0)
  {
    // Include required WordPress core files
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    // Download the image to a temporary file
    $tmp = download_url($image_url);

    // Check for download errors
    if (is_wp_error($tmp)) {
      error_log('Image download failed: ' . $tmp->get_error_message());
      return false;
    }

    // Set up a proper filename
    $file_array = array(
      'name'     => basename(parse_url($image_url, PHP_URL_PATH)),
      'tmp_name' => $tmp
    );

    // Do the sideload (upload to media library and create attachment)
    $attachment_id = media_handle_sideload($file_array, $post_id);

    // Clean up if something went wrong
    if (is_wp_error($attachment_id)) {
      @unlink($tmp);
      error_log('Attachment creation failed: ' . $attachment_id->get_error_message());
      return false;
    }

    // (Optional) Set as featured image
    set_post_thumbnail($post_id, $attachment_id);

    return $attachment_id;
  }
}

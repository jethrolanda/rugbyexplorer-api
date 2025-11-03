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

  public function createEvents($games, $args)
  {
    // args example
    // $args = array(
    //   'season' => '2025',
    //   'competition' => 'mLGoqgHnacX2AnmgD',
    //   'team' => 'DZJhdynaY4wSDBQpQ',
    //   'entityId' => '53371',
    //   'type' => 'fixtures' or 'results'
    // );
    extract($args);
    // error_log(print_r($games, true));
    // error_log(print_r($args, true));
    // return;
    // @set_time_limit(0);

    $options = get_option('rugbyexplorer_options');

    // REST API endpoint
    $url = $this->getSiteUrl() . '/wp-json/sportspress/v2/events';

    // Replace with your WordPress application credentials
    $username =  $options['sportspress_field_api_username'];
    $app_password = $options['sportspress_field_api_password'];

    $sportspressSeasonId = $this->getTermSeasonIdByName($season);
    $sportspressLeagueId = $this->getTermLeagueIdByName($competition);

    foreach ($games as $key => $game) {

      // Skip if already added
      if ($this->checkIfGameIdAlreadyExist($game['id'])) {
        continue;
      }

      // if no league id found then create one
      if ($sportspressLeagueId == false) {
        $sportspressLeagueId = $this->createLeague($game['compName'], $competition);
      }

      $venue = isset($game['venue']) && !empty(trim($game['venue'])) ? trim($game['venue']) : '';

      $sportsPressVenueId = empty($venue) ? 0 : $this->createVenue($venue);

      $team_ids = $this->createTeams(
        array(
          $game['homeTeam'],
          $game['awayTeam']
        )
      );
      // Prepare event data (example: rugby match)
      $prepared_data = array(
        'title'        => $game['homeTeam']['name'] . ' vs ' . $game['awayTeam']['name'],
        'status'       => 'publish',
        'teams'        => $team_ids, // IDs of the teams
        'date'         => $game['dateTime'],
        // 'venue'        => $sportsPressVenueId, // venue id
        // 'competition'  => $sportspressLeagueId, // competion id or league id??
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
        wp_set_object_terms($body['id'], $sportsPressVenueId, 'sp_venue');

        // Assign league
        wp_set_object_terms($body['id'], $sportspressLeagueId, 'sp_league');

        // Assign season
        wp_set_object_terms($body['id'], $sportspressSeasonId, 'sp_season');

        // Mode
        update_post_meta($body['id'], 'sp_format', 'league');

        // Format
        update_post_meta($body['id'], 'sp_mode', 'team');

        // Results 
        if (!empty($game['homeTeam']) && !empty($game['awayTeam'])) {
          $result = array();
          $result[$team_ids[0]] = array(
            "tries" => '',
            "conversions" => '',
            "bp" => '',
            "points" => $game['homeTeam']['score'],
            "outcome" => array(
              $game['homeTeam']['score'] > $game['awayTeam']['score'] ? "win" : "loss"
            )
          );
          $result[$team_ids[1]] = array(
            "tries" => '',
            "conversions" => '',
            "bp" => '',
            "points" => $game['awayTeam']['score'],
            "outcome" => array(
              $game['awayTeam']['score'] > $game['homeTeam']['score'] ? "win" : "loss"
            )
          );

          update_post_meta(
            $body['id'],
            'sp_results',
            $result
          );
        }

        // Players and scores
        // $this->createPlayers($game['id'], $team_ids);

        // Add game ID / Match ID
        update_post_meta(
          $body['id'],
          'fixture_id',
          $game['id']
        );
      }
    }
  }

  public function createTeams($teams)
  {
    // error_log(print_r($teams, true));

    $options = get_option('rugbyexplorer_options');
    $url = $this->getSiteUrl() . '/wp-json/sportspress/v2/teams';
    $username =  $options['sportspress_field_api_username'];
    $app_password = $options['sportspress_field_api_password'];
    $team_ids = array();

    foreach ($teams as $team) {

      $team_name = isset($team['name']) ? trim($team['name']) : "";
      $team_id_api = isset($team['teamId']) ? $team['teamId'] : "";

      // Skip creating team
      if ($team_id_api == 0 || empty($team_name)) {
        $team_ids[] = 0;
        continue;
      }

      // Check if team exist
      $team_id = $this->checkIfTeamIdAlreadyExist($team_id_api);
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
        update_post_meta($body['id'], 'team_id', $team_id_api);

        $image_url = $team['crest'];

        $post_id   = $body['id']; // Optional – ID of post to attach to
        $attachment_id = $this->createAttachmentFromUrl($image_url, $post_id);
      }
    }

    return $team_ids;
  }

  // If venue not exist then create new
  // If venue exist then return id
  public function createVenue($venue)
  {

    if (empty(trim($venue))) return 0;

    $options = get_option('rugbyexplorer_options');
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

  public function createPlayers($matchId, $team_ids)
  {
    global $rea;

    $data = $rea->shortcode->getPlayerLineUpData(array('fixture_id' => $matchId));
    $players = $data['allMatchStatsSummary']['lineUp']['players'];
    // error_log(print_r($players, true));
    if (!empty($players)) {
      foreach ($players as $player) {
        $options = get_option('rugbyexplorer_options');
        $url = $this->getSiteUrl() . '/wp-json/sportspress/v2/venues';
        $username =  $options['sportspress_field_api_username'];
        $app_password = $options['sportspress_field_api_password'];

        $args = array(
          'headers' => array(
            'Authorization' => 'Basic ' . base64_encode("$username:$app_password"),
            'Content-Type'  => 'application/json'
          ),
          'body'    => wp_json_encode(array(
            'title'       => $player['name'],
            'status'      => 'publish',
            "meta" => array(
              "sp_number" => $player['shirtNumber'],
              "sp_team" => $player['isHome'] ? $team_ids[0] : $team_ids[1],
              "sp_position" => "Forward"
            )
          )),
          'method'  => 'POST',
          'timeout' => 60
        );

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
          // error_log('SportsPress Player Creation Failed: ' . $response->get_error_message());
          return 0;
        } else {
          $data = json_decode(wp_remote_retrieve_body($response));
          error_log('SportsPress Player Created: ' . print_r($data, true));
          // if (isset($data->code) && $data->code == 'term_exists')
          //   return $data->data->term_id;

          // return $data->id;
        }
      }
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

    $new_term = wp_insert_term(
      $name,   // Term name
      'sp_season'              // Your custom taxonomy
    );

    return $new_term['term_id'];
  }

  public function getTermLeagueIdByName($competition)
  {
    $terms = get_terms([
      'taxonomy'   => 'sp_league', // your taxonomy slug
      'hide_empty' => false,
      'fields'     => 'ids',   // ✅ only return term IDs
      'meta_query' => [
        [
          'key'     => 'competition_id', // your term meta key
          'value'   => $competition,       // the meta value to match
          'compare' => '='
        ]
      ]
    ]);

    if (!empty($terms) && !is_wp_error($terms)) {
      $term_id = $terms[0]; // first match
      return $term_id;
    } else {
      return false;
    }
  }

  public function createLeague($name, $competition)
  {
    $new_term = wp_insert_term(
      $name,
      'sp_league',
      array(
        'description' => 'Competition ID: ' . $competition,
      )
    );

    if (is_wp_error($new_term)) {
      error_log('Error: ' . $new_term->get_error_message());
    } else {
      $term_id = $new_term['term_id'];
      update_term_meta($term_id, 'competition_id', sanitize_text_field($competition));

      return $term_id;
    }
  }


  public function checkIfTeamIdAlreadyExist($team_id)
  {

    $posts = get_posts([
      'post_type'      => 'sp_team',
      'posts_per_page' => 1,
      'fields'         => 'ids',
      'meta_query'     => [
        [
          'key'     => 'team_id',
          'value'   => $team_id,
          'compare' => '='
        ]
      ]
    ]);

    return !empty($posts) ? $posts[0] : false;
  }

  public function checkIfGameIdAlreadyExist($game_id)
  {

    $sp_event_ids = get_posts(array(
      'post_type'      => 'sp_event',
      'fields'         => 'ids',
      'posts_per_page' => -1,
      'meta_query'     => array(
        array(
          'key'     => 'game_id',
          'compare' => '=',
          'value' => $game_id
        )
      ),
    ));

    return !empty($sp_event_ids) ? $sp_event_ids[0] : false;
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

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

    foreach ($games as $game) {

      // Skip if already added
      if ($this->checkIfGameIdAlreadyExist($game['id'])) {
        continue;
      }

      // if no league id found then create one
      if ($sportspressLeagueId == false) {
        $sportspressLeagueId = $this->createLeague($game['compName'], $competition);
      }

      // Create venue
      $venue = isset($game['venue']) && !empty(trim($game['venue'])) ? trim($game['venue']) : '';
      $sportsPressVenueId = empty($venue) ? 0 : $this->createVenue($venue);

      // Create teams
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
        'timeout' => 600,
      );

      // Send the request
      $response = wp_remote_post($url, $args);
      // error_log(print_r($response, true));
      if (is_wp_error($response)) {
        error_log('SportsPress Event Creation Failed: ' . $response->get_error_message());
      } else {
        $body = json_decode(wp_remote_retrieve_body($response), true);

        // Add game ID / Match ID
        update_post_meta($body['id'], 'fixture_id', $game['id']);

        // Save rugby explorer match data to postmeta
        update_post_meta($body['id'], 'rugby_explorer_game_data', $game);

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

        // Create Players
        $this->createPlayers($game['id'], $team_ids, $sportspressSeasonId, $sportspressLeagueId);

        // Create staff
        $this->createStaff($game['id'], $team_ids, $sportspressSeasonId, $sportspressLeagueId);

        // Create staff
        $this->createOfficial($game['id'], $body['id']);



        // Box Scores
        $this->addPointsSummary($game['id'], $team_ids, $body['id'], $game);

        // Assign Players to teams
        $this->assignPlayers($game['id'], $team_ids, $body['id']);
      }
      // break;
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

  public function createPlayers($matchId, $team_ids, $sportspressSeasonId, $sportspressLeagueId)
  {
    global $rea;

    $data = $rea->shortcode->getPlayerLineUpData(array('fixture_id' => $matchId));
    $substitutes = $data['allMatchStatsSummary']['lineUp']['substitutes'];
    $players = $data['allMatchStatsSummary']['lineUp']['players'];
    $players = array_merge($substitutes, $players);

    if (!empty($players)) {
      foreach ($players as $player) {

        // Skip adding if player already exist
        if ($this->checkIfPlayerIdAlreadyExist($player['id'])) continue;

        $team_id = $player['isHome'] ? $team_ids[0] : $team_ids[1];

        $post_data = [
          'post_title'   => $player['name'],
          'post_status'  => 'publish',
          'post_author'  => get_current_user_id(),
          'post_type'    => 'sp_player'
        ];

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
          echo 'Error creating post: ' . $post_id->get_error_message();
          error_log('SportsPress Player Creation Failed: ' . $post_id->get_error_message());
        } else {

          // Save rugby explorer data to postmeta
          update_post_meta($post_id, 'rugby_explorer_player_data', $player);

          // Player ID
          update_post_meta($post_id, 'player_id', $player['id']);

          // Squad Number / shirtNumber
          update_post_meta($post_id, 'sp_number', $player['shirtNumber']); //shirtNumber

          // Team
          update_post_meta($post_id, 'sp_current_team', $team_id);
          update_post_meta($post_id, 'sp_team', $team_id);

          // League
          wp_set_object_terms($post_id, $sportspressLeagueId, 'sp_league');

          // Season
          wp_set_object_terms($post_id, $sportspressSeasonId, 'sp_season');

          // Position
          $terms = get_terms([
            'taxonomy'   => 'sp_position',
            'hide_empty' => false,
            'meta_query' => [
              [
                'key'     => 'sp_order',
                'value'   => $player['position'],
                'compare' => '='
              ]
            ]
          ]);

          if (! empty($terms) && ! is_wp_error($terms)) {
            $term_id = $terms[0]->term_id;
            wp_set_object_terms($post_id, $term_id, 'sp_position');
          } else {
            update_post_meta($post_id, 'sp_position', $player['position']);
          }
        }
      }
    }
  }


  public function createStaff($matchId, $team_ids, $sportspressSeasonId, $sportspressLeagueId)
  {

    global $rea;

    $data = $rea->shortcode->getPlayerLineUpData(array('fixture_id' => $matchId));
    $coaches = $data['allMatchStatsSummary']['lineUp']['coaches'];
    $job_id = $this->getTermId('Coach', 'sp_role');

    if (!empty($coaches)) {
      foreach ($coaches as $coach) {
        // Skip adding if player already exist
        if ($this->checkIfCoachIdAlreadyExist($coach['id'])) continue;

        $team_id = $coach['isHome'] ? $team_ids[0] : $team_ids[1];

        $post_data = [
          'post_title'   => $coach['name'],
          'post_status'  => 'publish',
          'post_author'  => get_current_user_id(),
          'post_type'    => 'sp_staff'
        ];

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
          echo 'Error creating post: ' . $post_id->get_error_message();
          error_log('SportsPress Player Creation Failed: ' . $post_id->get_error_message());
        } else {
          // Save rugby explorer data to postmeta
          update_post_meta($post_id, 'rugby_explorer_coach_data', $coach);

          // Coach ID
          update_post_meta($post_id, 'coach_id', $coach['id']);

          // Team
          update_post_meta($post_id, 'sp_current_team', $team_id);
          update_post_meta($post_id, 'sp_team', $team_id);

          // League
          wp_set_object_terms($post_id, $sportspressLeagueId, 'sp_league');

          // Season
          wp_set_object_terms($post_id, $sportspressSeasonId, 'sp_season');

          // Job as Coach
          wp_set_object_terms($post_id, $job_id, 'sp_role');
        }
      }
    }
  }

  public function createOfficial($matchId,  $event_id)
  {
    global $rea;

    $data = $rea->shortcode->getPlayerLineUpData(array('fixture_id' => $matchId));
    $referees = $data['allMatchStatsSummary']['referees'];
    $officials = array();

    if (!empty($referees)) {
      foreach ($referees as $referee) {

        // Create Duty / Get Duty ID
        $duty_id = $this->getTermId($referee['type'], 'sp_duty');

        // Skip adding if player already exist
        $official_id = $this->checkIfOfficialIdAlreadyExist($referee['refereeId']);
        if ($official_id == false) {

          $post_data = [
            'post_title'   => $referee['refereeName'],
            'post_status'  => 'publish',
            'post_author'  => get_current_user_id(),
            'post_type'    => 'sp_official'
          ];

          $post_id = wp_insert_post($post_data);

          if (is_wp_error($post_id)) {
            echo 'Error creating post: ' . $post_id->get_error_message();
            error_log('SportsPress Player Creation Failed: ' . $post_id->get_error_message());
          } else {
            // Save rugby explorer data to postmeta
            update_post_meta($post_id, 'rugby_explorer_referee_data', $referee);

            // Referee ID
            update_post_meta($post_id, 'referee_id', $referee['refereeId']);

            // Assign Duty
            wp_set_object_terms($post_id, $duty_id, 'sp_duty');

            // Assign officials to array
            $officials[$duty_id][] = $post_id;
          }
        } else {
          // Assign officials to array
          $officials[$duty_id][] = $official_id;
        }
      }

      // Assign Officials to match
      update_post_meta($event_id, 'sp_officials', $officials);
    }
  }

  // Box scores
  public function addPointsSummary($matchId, $team_ids = array(), $event_id = false, $game = null)
  {

    global $rea;
    $data = $rea->shortcode->getPlayerLineUpData(array('fixture_id' => $matchId));

    $substitutes = $data['allMatchStatsSummary']['lineUp']['substitutes'];
    $players = $data['allMatchStatsSummary']['lineUp']['players'];
    $players = array_merge($substitutes, $players);
    usort($players, function ($a, $b) {
      return $a['position'] <=> $b['position']; // ascending
    });

    $points = $data['allMatchStatsSummary']['pointsSummary'];
    $scores = array();

    // t = tries
    if (!empty($points['tries'])) {
      foreach ($points['tries'] as $tries) {
        // find the player details. need id
        $matches = array_filter(
          $players,
          fn($p) =>
          stripos($p['name'], $tries['playerName']) !== false
        );
        if (!empty($matches)) {
          $matches = array_values($matches);
          $player_id = $this->getPostIdByMetaValue('sp_player', 'player_id', $matches[0]['id']);
        }

        // Add player to match. Avoid duplicates
        if ($event_id && !in_array($player_id, get_post_meta($event_id, 'sp_player', false))) {
          // add_post_meta($event_id, 'sp_player', $player_id);
        }

        if ($tries['isHome']) {
          $scores[$team_ids[0]][$player_id] = array(
            'number' => 0,
            'position' => array(0),
            't' => 0,
            'c' => 0,
            'p' => 0,
            'dg' => 0,
            'status' => 'lineup',
            'sub' => '0'
          );
        } else {
          $scores[$team_ids[1]][$player_id] = array(
            'number' => 0,
            'position' => array(0),
            't' => 0,
            'c' => 0,
            'p' => 0,
            'dg' => 0,
            'status' => 'lineup',
            'sub' => '0'
          );
        }

        if ($tries['isHome']) {
          $scores[$team_ids[0]][$player_id]['number'] = $matches[0]['shirtNumber'];
          $scores[$team_ids[0]][$player_id]['position'] = array($matches[0]['position']);
          $scores[$team_ids[0]][$player_id]['t'] = count(array_filter($points['tries'], fn($p) => $p['playerName'] === $tries['playerName']));
        } else {
          $scores[$team_ids[1]][$player_id]['number'] = $matches[0]['shirtNumber'];
          $scores[$team_ids[1]][$player_id]['position'] = array($matches[0]['position']);
          $scores[$team_ids[1]][$player_id]['t'] = count(array_filter($points['tries'], fn($p) => $p['playerName'] === $tries['playerName']));
        }
      }
    }

    // c = conversion
    if (!empty($points['conversions'])) {
      foreach ($points['conversions'] as $conversion) {
        // find the player details. need id
        $matches = array_filter(
          $players,
          fn($p) =>
          stripos($p['name'], $conversion['playerName']) !== false
        );

        if (!empty($matches)) {
          $matches = array_values($matches);
          $player_id = $this->getPostIdByMetaValue('sp_player', 'player_id', $matches[0]['id']);
        }

        // Add player to match. Avoid duplicates
        if ($event_id && !in_array($player_id, get_post_meta($event_id, 'sp_player', false))) {
          // add_post_meta($event_id, 'sp_player', $player_id);
        }

        if ($tries['isHome']) {
          $scores[$team_ids[0]][$player_id] = array(
            'number' => 0,
            'position' => array(0),
            't' => 0,
            'c' => 0,
            'p' => 0,
            'dg' => 0,
            'status' => 'lineup',
            'sub' => '0'
          );
        } else {
          $scores[$team_ids[1]][$player_id] = array(
            'number' => 0,
            'position' => array(0),
            't' => 0,
            'c' => 0,
            'p' => 0,
            'dg' => 0,
            'status' => 'lineup',
            'sub' => '0'
          );
        }

        if ($conversion['isHome']) {
          $scores[$team_ids[0]][$player_id]['number'] = $matches[0]['shirtNumber'];
          $scores[$team_ids[0]][$player_id]['position'] = array($matches[0]['position']);
          $scores[$team_ids[0]][$player_id]['c'] = count(array_filter($points['conversions'], fn($p) => $p['playerName'] === $conversion['playerName']));
        } else {
          $scores[$team_ids[1]][$player_id]['number'] = $matches[0]['shirtNumber'];
          $scores[$team_ids[1]][$player_id]['position'] = array($matches[0]['position']);
          $scores[$team_ids[1]][$player_id]['c'] = count(array_filter($points['conversions'], fn($p) => $p['playerName'] === $conversion['playerName']));
        }
      }
    }

    // Box score
    update_post_meta($event_id, 'sp_players', $scores);
    // error_log(print_r($scores, true));

    // Results 
    if (!empty($game['homeTeam']) && !empty($game['awayTeam'])) {
      $result = array();
      $result[$team_ids[0]] = array(
        "tries" => array_sum(array_column($scores[$team_ids[0]], 't')),
        "conversions" => array_sum(array_column($scores[$team_ids[0]], 'c')),
        "bp" => '',
        "points" => $game['homeTeam']['score'],
        "outcome" => array(
          $game['homeTeam']['score'] > $game['awayTeam']['score'] ? "win" : "loss"
        )
      );
      $result[$team_ids[1]] = array(
        "tries" => array_sum(array_column($scores[$team_ids[1]], 't')),
        "conversions" => array_sum(array_column($scores[$team_ids[1]], 'c')),
        "bp" => '',
        "points" => $game['awayTeam']['score'],
        "outcome" => array(
          $game['awayTeam']['score'] > $game['homeTeam']['score'] ? "win" : "loss"
        )
      );

      update_post_meta(
        $event_id,
        'sp_results',
        $result
      );
    }
  }


  public function assignPlayers($game_id, $team_ids, $event_id)
  {
    global $rea;
    $data = $rea->shortcode->getPlayerLineUpData(array('fixture_id' => $game_id));
    $substitutes = $data['allMatchStatsSummary']['lineUp']['substitutes'];
    $players = $data['allMatchStatsSummary']['lineUp']['players'];
    $players = array_merge($substitutes, $players);
    usort($players, function ($a, $b) {
      return $a['position'] <=> $b['position']; // ascending
    });

    $tries = $data['allMatchStatsSummary']['pointsSummary']['tries'];
    $conversions = $data['allMatchStatsSummary']['pointsSummary']['conversions'];
    $scores = array_merge($tries, $conversions);

    $team_members = array(array(0), array(0));
    foreach ($scores as $score) {

      // find the player details. need id
      $matches = array_filter(
        $players,
        fn($p) =>
        stripos($p['name'], $score['playerName']) !== false
      );
      if (!empty($matches)) {
        $matches = array_values($matches);
        $player_id = $this->getPostIdByMetaValue('sp_player', 'player_id', $matches[0]['id']);
      }

      if ($player_id) {
        if ($score['isHome']) {
          $team_members[0][] = $player_id;
        } else {
          $team_members[1][] = $player_id;
        }
      }
    }
    $team_members[0] = array_values(array_unique($team_members[0]));
    $team_members[1] = array_values(array_unique($team_members[1]));

    sp_update_post_meta_recursive($event_id, 'sp_player', $team_members);
    // error_log(print_r($team_members, true));
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

    $new_term = wp_insert_term($name, 'sp_season');

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

    return !empty($sp_event_ids) ? true : false;
  }

  public function checkIfPlayerIdAlreadyExist($player_id)
  {

    $sp_player_ids = get_posts(array(
      'post_type'      => 'sp_player',
      'fields'         => 'ids',
      'posts_per_page' => -1,
      'meta_query'     => array(
        array(
          'key'     => 'player_id',
          'compare' => '=',
          'value' => $player_id
        )
      ),
    ));

    return !empty($sp_player_ids) ? true : false;
  }

  public function checkIfCoachIdAlreadyExist($coach_id)
  {

    $sp_player_ids = get_posts(array(
      'post_type'      => 'sp_staff',
      'fields'         => 'ids',
      'posts_per_page' => -1,
      'meta_query'     => array(
        array(
          'key'     => 'coach_id',
          'compare' => '=',
          'value' => $coach_id
        )
      ),
    ));

    return !empty($sp_player_ids) ? true : false;
  }

  public function checkIfOfficialIdAlreadyExist($official_id)
  {

    $sp_player_ids = get_posts(array(
      'post_type'      => 'sp_official',
      'fields'         => 'ids',
      'posts_per_page' => -1,
      'meta_query'     => array(
        array(
          'key'     => 'referee_id',
          'compare' => '=',
          'value' => $official_id
        )
      ),
    ));

    return !empty($sp_player_ids) ? $sp_player_ids[0] : false;
  }

  public function getPostIdByMetaValue($post_type, $meta_key, $meta_value)
  {
    $sp_player_ids = get_posts(array(
      'post_type'      => $post_type,
      'fields'         => 'ids',
      'posts_per_page' => -1,
      'meta_query'     => array(
        array(
          'key'     => $meta_key,
          'compare' => '=',
          'value' => $meta_value
        )
      ),
    ));

    return !empty($sp_player_ids) ? $sp_player_ids[0] : false;
  }

  public function getTermId($name, $taxonomy)
  {
    $term = get_term_by('name', $name, $taxonomy);

    if ($term) {
      $term_id = $term->term_id;
      return $term_id;
    }

    $new_term = wp_insert_term(
      $name,
      $taxonomy
    );

    if (! is_wp_error($term)) {
      return $new_term['term_id'];
    } else {
      error_log('Error getTermId: ' . $new_term->get_error_message());
    }
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

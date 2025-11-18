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
    global $rea;
    // args example
    // $args = array(
    //   'season' => '2025',
    //   'competition' => 'mLGoqgHnacX2AnmgD',
    //   'team' => 'DZJhdynaY4wSDBQpQ',
    //   'entityId' => '53371',
    //   'type' => 'fixtures' or 'results'
    // );
    extract($args);

    $sportspressSeasonId = $this->getTermSeasonIdByName($season);
    $sportspressLeagueId = $this->getTermLeagueIdByName($competition);

    $status = array('failed' => 0, 'created' => 0, 'updated' => 0);

    foreach ($games as $game) {

      // Skip if game is already added by checking fixture_id
      $fixture_id = $this->getPostIdByMetaValue('sp_event', 'fixture_id', $game['id']);
      $is_create = $fixture_id === false ? true : false;

      // if no league id found then create one
      if ($sportspressLeagueId == false) {
        $sportspressLeagueId = $this->createLeague($game['compName'], $competition);
      }

      // Create venue
      $venue = !empty($game['venue']) ? trim($game['venue']) : '';
      $sportsPressVenueId = empty($venue) ? 0 : $this->createVenue($venue);

      // Create teams
      $team_ids = $this->createTeams(
        array(
          $game['homeTeam'],
          $game['awayTeam']
        ),
        $sportspressSeasonId,
        $sportspressLeagueId
      );

      $home = !empty($game['homeTeam']['name']) ? $game['homeTeam']['name'] : "BYE";
      $away = !empty($game['awayTeam']['name']) ? $game['awayTeam']['name'] : "BYE";

      $post_data = array(
        'post_title'   => $home . ' vs ' . $away,
        'post_date'    => $game['dateTime'],
        'post_status'  => 'publish',
        'post_author'  => get_current_user_id(),
        'post_type'    => 'sp_event'
      );

      if ($is_create) {
        $post_id = wp_insert_post($post_data);
      } else {
        $post_data['ID'] = $fixture_id;
        $post_id = wp_update_post($post_data);
      }

      if (is_wp_error($post_id)) {
        $status['failed']++;
        error_log('SportsPress Event Creation Failed: ' . $response->get_error_message());
      } else {
        if ($is_create) {
          $status['created']++;
        } else {
          $status['updated']++;
        }

        // Add game ID / Match ID
        update_post_meta($post_id, 'fixture_id', $game['id']);

        // Save rugby explorer match data to postmeta
        update_post_meta($post_id, 'rugby_explorer_game_data', $game);

        // Assign venue
        wp_set_object_terms($post_id, $sportsPressVenueId, 'sp_venue');

        // Assign league
        wp_set_object_terms($post_id, $sportspressLeagueId, 'sp_league');

        // Assign season
        wp_set_object_terms($post_id, $sportspressSeasonId, 'sp_season');

        // Mode
        update_post_meta($post_id, 'sp_format', 'league');

        // Format
        update_post_meta($post_id, 'sp_mode', 'team');

        sleep(1); // Add 1 sec delay to avoid hitting API rate limits
        // GraphQL Request Error: cURL error 28: Failed to connect to rugby-au-cms.graphcdn.app port 443 after 10001 ms: Timeout was reached
        // GraphQL Error: cURL error 28: Operation timed out after 5000 milliseconds with 0 bytes received
        $fixture_data = $rea->shortcode->getPlayerLineUpData(array('fixture_id' => $game['id']));
        $substitutes = $fixture_data['allMatchStatsSummary']['lineUp']['substitutes'];
        $substitutes = is_array($substitutes) ? $substitutes : array();
        $players = $fixture_data['allMatchStatsSummary']['lineUp']['players'];
        $players = is_array($players) ? $players : array();
        $players = array_merge($substitutes, $players);
        $coaches = $fixture_data['allMatchStatsSummary']['lineUp']['coaches'];
        $referees = $fixture_data['allMatchStatsSummary']['referees'];

        // Create Players
        $this->createPlayers($team_ids, $sportspressSeasonId, $sportspressLeagueId, $players);

        // Create staff
        $this->createStaff($game['id'], $team_ids, $sportspressSeasonId, $sportspressLeagueId, $coaches);

        // Create official
        $this->createOfficial($game['id'], $post_id, $referees);

        // Add teams
        delete_post_meta($post_id, 'sp_team');
        foreach ($team_ids as $team_id) {
          add_post_meta($post_id, 'sp_team', $team_id);
        }

        // Box Scores
        $this->addPointsSummary($team_ids, $post_id, $game, $players, $fixture_data);

        // Assign Players to teams
        $this->assignPlayers($post_id, $players, $fixture_data);
      }
    }

    return $status;
  }

  public function createTeams($teams, $sportspressSeasonId, $sportspressLeagueId)
  {
    $team_ids = array();

    foreach ($teams as $team) {

      $team_name = !empty($team['name']) ? trim($team['name']) : "";
      $team_id_api = isset($team['teamId']) ? $team['teamId'] : "";

      // Skip creating team
      if ($team_id_api == 0 || empty($team_name)) {
        $team_ids[] = $this->createByeTeam();
        continue;
      }

      // Check if team exist
      $team_id = $this->getPostIdByMetaValue('sp_team', 'team_id', $team_id_api);

      if ($team_id > 0) {
        $team_ids[] = $team_id;
        continue;
      }

      $post_data = array(
        'post_title'   => $team_name,
        'post_status'  => 'publish',
        'post_author'  => get_current_user_id(),
        'post_type'    => 'sp_team'
      );

      $post_id = wp_insert_post($post_data);

      if (is_wp_error($post_id)) {
        echo 'Error creating post: ' . $post_id->get_error_message();
        error_log('SportsPress Player Creation Failed: ' . $post_id->get_error_message());
      } else {

        $team_ids[] = $post_id;

        // Team ID
        update_post_meta($post_id, 'team_id', $team_id_api);

        // Save rugby explorer team data to postmeta
        update_post_meta($post_id, 'rugby_explorer_game_data', $team);

        // League
        wp_set_object_terms($post_id, $sportspressLeagueId, 'sp_league');

        // Season
        wp_set_object_terms($post_id, $sportspressSeasonId, 'sp_season');

        $image_url = $team['crest'];

        $this->createAttachmentFromUrl($image_url, $post_id);
      }
    }

    return $team_ids;
  }

  public function createByeTeam()
  {

    global $wpdb;
    $post_title = 'BYE';
    $post_id = $wpdb->get_var($wpdb->prepare(
      "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = 'sp_team' LIMIT 1",
      $post_title
    ));

    if ($post_id) {
      return $post_id;
    }

    $post_data = array(
      'post_title'   => 'BYE',
      'post_status'  => 'publish',
      'post_author'  => get_current_user_id(),
      'post_type'    => 'sp_team'
    );

    $post_id = wp_insert_post($post_data);
    return $post_id;
  }

  // If venue not exist then create new
  // If venue exist then return id
  public function createVenue($venue)
  {

    if (empty($venue)) return 0;

    return $this->getTermId($venue, 'sp_venue');
  }

  public function createPlayers($team_ids, $sportspressSeasonId, $sportspressLeagueId, $players)
  {

    if (!empty($players)) {
      foreach ($players as $player) {

        $player_id = substr($player['id'], 0, 17);

        // Skip adding if player already exist
        if ($this->getPostIdByMetaValue('sp_player', 'player_id', $player_id)) continue;

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
          update_post_meta($post_id, 'player_id', $player_id);

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


  public function createStaff($matchId, $team_ids, $sportspressSeasonId, $sportspressLeagueId, $coahes)
  {
    $job_id = $this->getTermId('Coach', 'sp_role');

    if (!empty($coaches)) {
      foreach ($coaches as $coach) {
        // Skip adding if player already exist
        if ($this->getPostIdByMetaValue('sp_staff', 'coach_id', $coach['id'])) continue;

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

  public function createOfficial($matchId,  $event_id, $referees)
  {

    $officials = array();

    if (!empty($referees)) {
      foreach ($referees as $referee) {

        // Create Duty / Get Duty ID
        $duty_id = $this->getTermId($referee['type'], 'sp_duty');

        // Skip adding if player already exist 
        $official_id = $this->getPostIdByMetaValue('sp_official', 'referee_id', $referee['refereeId']);
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
  public function addPointsSummary($team_ids = array(), $event_id = false, $game = null, $players = array(), $data = array())
  {

    usort($players, function ($a, $b) {
      return $a['position'] <=> $b['position']; // ascending
    });

    $points = $data['allMatchStatsSummary']['pointsSummary'];
    $scores = array(
      $team_ids[0] => array(),
      $team_ids[1] => array()
    );

    // t = tries
    if (!empty($points['tries'])) {
      foreach ($points['tries'] as $tries) {
        // find the player details. need id
        $matches = array_filter(
          $players,
          fn($p) =>
          stripos($p['name'], $tries['playerName']) !== false
        );
        $player_id = null;
        if (!empty($matches)) {
          $matches = array_values($matches);
          $match_player_id = substr($matches[0]['id'], 0, 17);
          $player_id = $this->getPostIdByMetaValue('sp_player', 'player_id', $match_player_id);
        }

        // Add player to match. Avoid duplicates
        if ($event_id && $player_id && !in_array($player_id, get_post_meta($event_id, 'sp_player', false))) {
          // add_post_meta($event_id, 'sp_player', $player_id);
        }

        if ($player_id) {
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
        }

        if (!empty($matches) && $player_id) {
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
        $player_id = null;
        if (!empty($matches)) {
          $matches = array_values($matches);
          $match_player_id = substr($matches[0]['id'], 0, 17);
          $player_id = $this->getPostIdByMetaValue('sp_player', 'player_id', $match_player_id);
        }

        // Add player to match. Avoid duplicates
        if ($event_id && $player_id && !in_array($player_id, get_post_meta($event_id, 'sp_player', false))) {
          // add_post_meta($event_id, 'sp_player', $player_id);
        }

        if ($player_id) {
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
        }

        if (!empty($matches) && $player_id) {
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
    }

    // Box score
    update_post_meta($event_id, 'sp_players', $scores);

    // Results 
    if (!empty($game['homeTeam']) && !empty($game['awayTeam'])) {
      $result = array();
      $result[$team_ids[0]] = array(
        "tries" => is_array($scores[$team_ids[0]]) ? array_sum(array_column($scores[$team_ids[0]], 't')) : '',
        "conversions" => is_array($scores[$team_ids[0]]) ? array_sum(array_column($scores[$team_ids[0]], 'c')) : '',
        "bp" => '',
        "points" => $game['homeTeam']['score'],
        "outcome" => array(
          $game['homeTeam']['score'] > $game['awayTeam']['score'] ? "win" : "loss"
        )
      );
      $result[$team_ids[1]] = array(
        "tries" => is_array($scores[$team_ids[1]]) ? array_sum(array_column($scores[$team_ids[1]], 't')) : '',
        "conversions" => is_array($scores[$team_ids[1]]) ? array_sum(array_column($scores[$team_ids[1]], 'c')) : '',
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


  public function assignPlayers($event_id, $players, $data)
  {

    usort($players, function ($a, $b) {
      return $a['position'] <=> $b['position']; // ascending
    });

    $tries = $data['allMatchStatsSummary']['pointsSummary']['tries'] ?: array();
    $conversions = $data['allMatchStatsSummary']['pointsSummary']['conversions'] ?: array();
    $scores = array_merge($tries, $conversions);

    $team_members = array(array(0), array(0));
    foreach ($scores as $score) {

      // find the player details. need id
      $matches = array_filter(
        $players,
        fn($p) =>
        stripos($p['name'], $score['playerName']) !== false
      );
      $player_id = false;
      if (!empty($matches)) {
        $matches = array_values($matches);
        $match_player_id = substr($matches[0]['id'], 0, 17);
        $player_id = $this->getPostIdByMetaValue('sp_player', 'player_id', $match_player_id);
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

    if (is_wp_error($new_term)) {
      error_log('Error getTermSeasonIdByName: ' . $new_term->get_error_message());
      if ($new_term->get_error_code() === 'term_exists') {
        // Extract the existing term ID
        return $new_term->get_error_data('term_exists');
      }
      return "";
    } else {
      return $new_term['term_id'];
    }
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
      if ($new_term->get_error_code() === 'term_exists') {
        // Extract the existing term ID
        return $new_term->get_error_data('term_exists');
      }
      error_log('Error createLeague: ' . $new_term->get_error_message());
    } else {
      $term_id = $new_term['term_id'];
      update_term_meta($term_id, 'competition_id', sanitize_text_field($competition));

      return $term_id;
    }
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

  // Return term id if term name exist. If term not exist then create new and return id
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


    if (is_wp_error($new_term)) {
      error_log('Error getTermId: ' . $new_term->get_error_message());
      if ($new_term->get_error_code() === 'term_exists') {
        // Extract the existing term ID
        return $new_term->get_error_data('term_exists');
      }
      return "";
    } else {
      return $new_term['term_id'];
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

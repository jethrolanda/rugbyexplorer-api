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
    add_shortcode('player_lineup', array($this, 'player_lineup'));
    add_shortcode('team_ladder', array($this, 'team_ladder'));
    add_shortcode('team_events', array($this, 'team_events'));
    add_shortcode('points_summary', array($this, 'points_summary'));

    add_shortcode('top_scorer', array($this, 'top_scorer'));
    add_shortcode('player_games_played', array($this, 'player_games_played'));
    add_shortcode('player_matches_history', array($this, 'player_matches_history'));
    add_shortcode('player_stats', array($this, 'player_stats'));
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
   * Display player lineup.
   * The lineup is stored in event postmeta during events import.
   * 
   * @param array $atts
   * @since 1.0
   */
  public function player_lineup($atts)
  {
    global $rea;
    $atts = shortcode_atts(array(
      'season_id' => '',
      'competition_id' => ''
    ), $atts, 'player_lineup');

    wp_enqueue_style('fusesport-api-css');

    ob_start();

    $data = get_post_meta(get_the_ID(), 'rugby_explorer_match_details_data', true);

    if (!empty($data['allMatchStatsSummary'])) {
      $fixture_item = $data['getFixtureItem'];

      if (
        in_array($fixture_item['homeTeam']['teamId'], $rea->rugbyexplorer->get_team_ids()) ||
        in_array($fixture_item['awayTeam']['teamId'], $rea->rugbyexplorer->get_team_ids())
      ) {
        require(REA_VIEWS_ROOT_DIR . 'player-lineup-view.php');
      } else {
        require(REA_VIEWS_ROOT_DIR . 'competition-only/player-line-up-view.php');
      }
    }

    // content
    return ob_get_clean();
  }

  /**
   * Display team ladder data.
   * Pull data from rugbyexplorer via api call
   * 
   * @param array $atts
   * @since 1.0
   */
  public function team_ladder($atts)
  {
    global $rea;
    $atts = shortcode_atts(array(
      'id' => uniqid(),
      'season_id' => '',
      'competition_id' => ''
    ), $atts, 'team_ladder');

    $competition_id = esc_attr($atts['competition_id']);

    if (empty($competition_id)) {
      $terms = get_the_terms(get_the_ID(), 'sp_league');
      if (!empty($terms)) {
        $competition_id = get_term_meta($terms[0]->term_id, 'competition_id', true);
      }
    }

    // Get from option table
    $data = get_option('ladder_data_' . $competition_id);

    // If empty then fetch via api then save to option table
    if (empty($data)) {
      $data = $rea->api->getCompetitionLadderData(array(
        'competition_id' => $competition_id
      ));

      if (!empty($data)) {
        update_option('ladder_data_' . $competition_id, $data);
      }
    }

    ob_start();

    if (!empty($data['ladderPools'])) {
      require(REA_VIEWS_ROOT_DIR . 'team-ladder-view.php');
    }


    // content
    return ob_get_clean();
  }

  /**
   * Display team events data.
   * Pull data from rugbyexplorer via api call.
   * Temporary solution while working on the events import function.
   * NOTE: This is now not need since we already have the feature ready. 
   *       Just keeping this here just in case needed.
   * 
   * @param array $atts
   * @since 1.0
   */
  public function team_events($atts)
  {
    global $rea;
    $atts = shortcode_atts(array(
      'id' => uniqid(),
      'entity_id' => '',
      'season' => '',
      'competition_id' => '',
      'team_id' => '',
      'type' => 'results',
    ), $atts, 'team_events');

    ob_start();

    $data = $rea->api->getData(array(
      'season' => esc_attr($atts['season']),
      'entityId' => intVal($atts['entity_id']),
      'competition' => esc_attr($atts['competition_id']),
      'team' => esc_attr($atts['team_id']),
      'type' => esc_attr($atts['type']),
    ));

    if (!empty($data)) {
      require(REA_VIEWS_ROOT_DIR . 'team-events-view.php');
    }

    // content
    return ob_get_clean();
  }

  /**
   * Display points summary data.
   * The data is stored in event postmeta during events import.
   * 
   * @param array $atts
   * @return string
   * @since 1.0
   */
  public function points_summary($atts)
  {

    global $rea;
    $atts = shortcode_atts(array(
      'id' => uniqid(),
    ), $atts, 'points_summary');

    ob_start();

    $args = array(
      'fixture_id' => get_post_meta(get_the_ID(), 'fixture_id', true)
    );

    $data = get_post_meta(get_the_ID(), 'rugby_explorer_match_details_data', true);

    if (!empty($data)) {
      $fixture_item = $data['getFixtureItem'];
      if (
        in_array($fixture_item['homeTeam']['teamId'], $rea->rugbyexplorer->get_team_ids()) ||
        in_array($fixture_item['awayTeam']['teamId'], $rea->rugbyexplorer->get_team_ids())
      ) {
        require(REA_VIEWS_ROOT_DIR . 'points-summary-view.php');
      } else {
        require(REA_VIEWS_ROOT_DIR . 'competition-only/points-summary-view.php');
      }
    }

    // content
    return ob_get_clean();
  }

  /**
   * Display top scorer data.
   * 
   * @param array $atts
   * @return string
   * @since 1.0
   */
  public function top_scorer($atts)
  {
    $atts = shortcode_atts(array(
      'playerlist_id' => 0,
      'season' => null,
    ), $atts, 'top_scorer');

    $id = $atts['playerlist_id'];
    $season = $atts['season'];
    ob_start();

    if ($id) {
      echo "<div class='sportspress'>";
      require(REA_VIEWS_ROOT_DIR . 'player-list.php');
      echo "</div>";
    } else {
      echo 'Please provide a valid Player List ID. Go to Players -> Players Lists to create one.';
    }

    return ob_get_clean();
  }

  /**
   * Display player total games played.
   * Use sportspress calculation else use custom one.
   * 
   * @param array $atts
   * @return string
   * @since 1.0
   */
  public function player_games_played($atts)
  {

    global $rea;
    $atts = shortcode_atts(array(
      'player_id' => get_the_ID(),
    ), $atts, 'player_games_played');

    // Custom games played count
    $player_id = esc_attr($atts['player_id']);
    $games_played = $rea->helpers->cache_total_games_played_per_player($player_id);

    // From sportspress player data
    $player = new \SP_Player($player_id);
    $data = $player->data(0, false);

    ob_start();

    if ($player_id && $games_played) {
      echo "<div class='sportspress'>";
      // echo $data[-1]['a'] ?? $games_played;
      echo $games_played;
      echo "</div>";
    }


    return ob_get_clean();
  }

  /**
   * Helper function that perform a query to get all events the player
   * The data is stored in event postmeta during events import.
   * 
   * @param int $player_id
   * @param int|null $year
   * @return array
   * @since 1.0
   */
  public function get_player_games_played($player_id, $year = null)
  {
    global $wpdb;

    $player_id = intval($player_id);
    $player_code = get_post_meta($player_id, 'player_id', true);

    $where_year = '';
    $params = [
      'sp_event',
      'rugby_explorer_match_details_data',
      '%' . $player_code . '%'
    ];

    // Add optional year condition
    if (!empty($year) && is_numeric($year)) {
      $where_year = " AND YEAR(p.post_date) = %d";
      $params[] = (int) $year;
    }

    $sql = "
    SELECT p.ID
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm
        ON p.ID = pm.post_id
    WHERE p.post_type = %s
      AND p.post_status != 'trash'
      AND pm.meta_key = %s
      AND pm.meta_value LIKE %s
      $where_year
";

    $sp_player_ids = $wpdb->get_col(
      $wpdb->prepare($sql, $params)
    );

    return $sp_player_ids;
  }

  /**
   * Get player match history data. 
   * List down events the player played.
   * 
   * @param array $atts
   * @return string
   * @since 1.0
   */
  public function player_matches_history($atts)
  {
    $atts = shortcode_atts(array(
      'player_id' => get_the_ID(),
      'season' => null,
    ), $atts, 'player_matches_history');

    $player_id = esc_attr($atts['player_id']);
    $season = esc_attr($atts['season']);
    $games_played = $this->get_player_games_played($player_id, $season);
    ob_start();

    if ($player_id) {
      echo "<div class='sportspress'>";
      require(REA_VIEWS_ROOT_DIR . 'player-matches-played.php');
      echo "</div>";
    }


    return ob_get_clean();
  }

  /**
   * Get player statistics data per season.
   * Show player scores per league by season.
   * 
   * @param array $atts
   * @return string
   * @since 1.0
   */
  public function player_stats($atts)
  {
    $currentYear = (int)date('Y');
    $startYear = 2019;
    $atts = shortcode_atts(array(
      'player_id' => get_the_ID(),
      'season' => range($currentYear, $startYear),
    ), $atts, 'player_stats');

    if (!is_array($atts['season'])) {
      $atts['season'] = array(esc_attr($atts['season']));
    }

    $player_id = esc_attr($atts['player_id']);
    $seasons = $atts['season'];

    ob_start();

    foreach ($seasons as $season) {
      $events = $this->get_player_games_played($player_id, $season);
      $filtered_events = $this->get_player_event_filter_by_player_id($player_id, $events);
      $data = array();

      foreach ($filtered_events as $event_id) {
        $terms = get_the_terms($event_id, 'sp_league');
        $data[] = array(
          'event_id' => $event_id,
          'player_id' => $player_id,
          'league_id' => !empty($terms) ? $terms[0]->term_id : 0,
          'league_name' => !empty($terms) ? $terms[0]->name : '',
          'scores' => $this->get_player_scores($event_id, $player_id)
        );
      }

      // Sort by league data
      $league_stats = array();
      foreach ($data as $d) {
        $total_matches = isset($league_stats[$d['league_id']]['total_matches']) ? $league_stats[$d['league_id']]['total_matches'] : 0;

        $total_try = isset($league_stats[$d['league_id']]['total_try']) ? $league_stats[$d['league_id']]['total_try'] : 0;
        $total_try = $d['scores']['try'] + $total_try;

        $total_conversions = isset($league_stats[$d['league_id']]['total_conversions']) ? $league_stats[$d['league_id']]['total_conversions'] : 0;
        $total_conversions = $d['scores']['conversions'] + $total_conversions;

        $total_penalty_kicks = isset($league_stats[$d['league_id']]['total_penalty_kicks']) ? $league_stats[$d['league_id']]['total_penalty_kicks'] : 0;
        $total_penalty_kicks = $d['scores']['penalty_kicks'] + $total_penalty_kicks;

        $total_drop_goals = isset($league_stats[$d['league_id']]['total_drop_goals']) ? $league_stats[$d['league_id']]['total_drop_goals'] : 0;
        $total_drop_goals = $d['scores']['drop_goals'] + $total_drop_goals;

        $league_stats[$d['league_id']] = array(
          'league_name' => $d['league_name'],
          'total_matches' => $total_matches + 1,
          'total_points' => ($total_try * 5) + ($total_conversions * 2) + ($total_penalty_kicks * 3) + ($total_drop_goals * 3),
          'total_try' => $total_try,
          'total_conversions' => $total_conversions,
          'total_penalty_kicks' => $total_penalty_kicks,
          'total_drop_goals' => $total_drop_goals,
        );
      }

      if ($player_id && !empty($league_stats)) {
        echo "<div class='sportspress'>";
        require(REA_VIEWS_ROOT_DIR . 'player-statistics.php');
        echo "</div>";
      }
    }

    return ob_get_clean();
  }

  /**
   * Helper function: Find from the events played where the player scored points
   * 
   * @param int $player_id
   * @param array $events
   * @return array
   * @since 1.0
   */
  public function get_player_event_filter_by_player_id($player_id, $events)
  {
    global $wpdb;

    $player_id = intval($player_id);
    $event_ids = array_map('intval', (array)$events); // sanitize 
    // If event_ids empty → avoid invalid SQL
    if (empty($event_ids)) {
      return []; // or return early
    }
    $placeholders = implode(',', array_fill(0, count($event_ids), '%d'));

    $params = array(
      'sp_event',
      'sp_players',
      '%' . $player_id . '%'
    );
    $params = array_merge($params, $event_ids);

    $sql = "
    SELECT p.ID
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm
        ON p.ID = pm.post_id
    WHERE p.post_type = %s
      AND p.post_status != 'trash'
      AND pm.meta_key = %s
      AND pm.meta_value LIKE %s
       AND p.ID IN ($placeholders)
";

    $sp_player_ids = $wpdb->get_col(
      $wpdb->prepare($sql, $params)
    );

    return $sp_player_ids;
  }

  /**
   * Helper function: Tally player scores
   * 
   * @param int $event_id
   * @param int $player_id
   * @return array
   * @since 1.0
   */
  public function get_player_scores($event_id, $player_id)
  {
    $scores = get_post_meta($event_id, 'sp_players', true);
    $data = array(
      'try' => 0, // t
      'conversions' => 0, // c
      'penalty_kicks' => 0, // p
      'drop_goals' => 0, // dg
    );

    foreach ($scores as $score) {
      if (isset($score[$player_id])) {
        $data['try'] += isset($score[$player_id]['t']) ? intval($score[$player_id]['t']) : 0;
        $data['conversions'] += isset($score[$player_id]['c']) ? intval($score[$player_id]['c']) : 0;
        $data['penalty_kicks'] += isset($score[$player_id]['p']) ? intval($score[$player_id]['p']) : 0;
        $data['drop_goals'] += isset($score[$player_id]['dg']) ? intval($score[$player_id]['dg']) : 0;
      }
    }

    return $data;
  }
}

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


  public function player_lineup($atts)
  {

    $atts = shortcode_atts(array(
      'season_id' => '',
      'competition_id' => ''
    ), $atts, 'player_lineup');

    wp_enqueue_style('fusesport-api-css');

    ob_start();

    $args = array(
      'fixture_id' => get_post_meta(get_the_ID(), 'fixture_id', true)
    );
    $data = get_post_meta(get_the_ID(), 'rugby_explorer_match_details_data', true);

    if (!empty($data['allMatchStatsSummary'])) {
      require(REA_VIEWS_ROOT_DIR . 'player-lineup-view.php');
    }


    // content
    return ob_get_clean();
  }

  public function team_ladder($atts)
  {
    global $rea;
    $atts = shortcode_atts(array(
      'id' => uniqid(),
      'season_id' => '',
      'competition_id' => ''
    ), $atts, 'team_ladder');

    $competition_id = $atts['competition_id'];

    if (empty($competition_id)) {
      $terms = get_the_terms(get_the_ID(), 'sp_league');
      if (!empty($terms)) {
        $competition_id = get_term_meta($terms[0]->term_id, 'competition_id', true);
      }
    }

    ob_start();
    $term_id = 0;
    if (is_numeric($competition_id)) {
      $term_id = $competition_id;
    } else {
      $term_id = $rea->sportspress->getTermLeagueIdByName($competition_id);
    }

    // $data = get_term_meta($term_id, 'ladder_data', true);
    $data = $rea->api->getCompetitionLadderData(array(
      'competition_id' => $competition_id
    ));

    if (!empty($data['ladderPools'])) {
      require(REA_VIEWS_ROOT_DIR . 'team-ladder-view.php');
    }


    // content
    return ob_get_clean();
  }



  public function team_events($atts)
  {
    $atts = shortcode_atts(array(
      'id' => uniqid(),
      'entity_id' => '',
      'season' => '',
      'competition_id' => '',
      'team_id' => ''
    ), $atts, 'team_events');

    ob_start();

    $data = $this->getTeamCompetitionEventsData($atts);

    if (!empty($data)) {
      require(REA_VIEWS_ROOT_DIR . 'team-events-view.php');
    }

    // content
    return ob_get_clean();
  }

  public function getTeamCompetitionEventsData($args)
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

    $body = [
      "operationName" => "EntityFixturesAndResults",
      "variables" => [
        "season" => $season,
        "comps" => [
          [
            "id" => $competition_id,
            "sourceType" => "2"
          ]
        ],
        "teams" => [$team_id],
        "type" => 'results',
        "skip" => 0,
        "limit" => 100,
        "entityId" => (int)$entity_id,
        "entityType" => "club"
      ],
      "query" => "query EntityFixturesAndResults(\$entityId: Int, \$entityType: String, \$season: String, \$comps: [CompInput], \$teams: [String], \$type: String, \$skip: Int, \$limit: Int) {
      getEntityFixturesAndResults(
        season: \$season
        comps: \$comps
        teams: \$teams
        entityId: \$entityId
        entityType: \$entityType
        type: \$type
        limit: \$limit
        skip: \$skip
      ) {
        ...Fixtures_fixture
        __typename
      }
    }

    fragment Fixtures_fixture on FixtureItem {
      id
      compId
      compName
      dateTime
      group
      isLive
      isBye
      round
      roundType
      roundLabel
      season
      status
      venue
      sourceType
      matchLabel
      homeTeam {
        ...Fixtures_team
        __typename
      }
      awayTeam {
        ...Fixtures_team
        __typename
      }
      fixtureMeta {
        ...Fixtures_meta
        __typename
      }
      __typename
    }

    fragment Fixtures_team on Team {
      id
      name
      teamId
      score
      crest
      __typename
    }

    fragment Fixtures_meta on Fixture {
      id
      ticketURL
      ticketsAvailableDate
      isSoldOut
      radioURL
      radioStart
      radioEnd
      streamURL
      streamStart
      streamEnd
      broadcastPartners {
        ...Fixtures_broadcastPartners
        __typename
      }
      __typename
    }

    fragment Fixtures_broadcastPartners on BroadcastPartner {
      id
      name
      link
      photoId
      __typename
    }"
    ];

    $response = wp_remote_post('https://rugby-au-cms.graphcdn.app/', [
      'headers' => [
        'Content-Type' => 'application/json',
      ],
      'body' => wp_json_encode($body),
      'method' => 'POST',
      'data_format' => 'body',
    ]);

    if (is_wp_error($response)) {
      error_log('GraphQL Error: ' . $response->get_error_message());
    } else {
      $data = json_decode(wp_remote_retrieve_body($response), true);
      $results = $data['data']['getEntityFixturesAndResults'];
      return $results;
    }
  }

  public function points_summary($atts)
  {
    $atts = shortcode_atts(array(
      'id' => uniqid(),
    ), $atts, 'points_summary');

    ob_start();

    $args = array(
      'fixture_id' => get_post_meta(get_the_ID(), 'fixture_id', true)
    );

    $data = get_post_meta(get_the_ID(), 'rugby_explorer_match_details_data', true);

    if (!empty($data)) {
      require(REA_VIEWS_ROOT_DIR . 'points-summary-view.php');
    }

    // content
    return ob_get_clean();
  }

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

  public function player_games_played($atts)
  {
    $atts = shortcode_atts(array(
      'player_id' => get_the_ID(),
    ), $atts, 'player_games_played');

    $player_id = esc_attr($atts['player_id']);
    $games_played = count($this->get_player_games_played($player_id));
    ob_start();

    if ($player_id && $games_played) {
      echo "<div class='sportspress'>";
      echo $games_played;
      echo "</div>";
    }


    return ob_get_clean();
  }

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

  public function player_stats($atts)
  {
    $atts = shortcode_atts(array(
      'player_id' => get_the_ID(),
      'season' => null,
    ), $atts, 'player_stats');

    $player_id = esc_attr($atts['player_id']);
    $season = esc_attr($atts['season']);
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
    error_log(print_r($league_stats, true));
    ob_start();

    if ($player_id) {
      echo "<div class='sportspress'>";
      require(REA_VIEWS_ROOT_DIR . 'player-statistics.php');
      echo "</div>";
    }


    return ob_get_clean();
  }

  // find from the events played where the player scored points
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
        $data['penalty_kicks'] += isset($score[$player_id]['t']['p']) ? intval($score[$player_id]['t']['p']) : 0;
        $data['drop_goals'] += isset($score[$player_id]['dg']) ? intval($score[$player_id]['dg']) : 0;
      }
    }

    return $data;
  }
}

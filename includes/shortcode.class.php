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

    $data = get_term_meta($term_id, 'ladder_data', true);

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
}

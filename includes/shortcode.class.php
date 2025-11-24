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
    ), $atts, 'fusesports_fixtures');

    wp_enqueue_style('fusesport-api-css');

    ob_start();

    $args = array(
      'fixture_id' => get_post_meta(get_the_ID(), 'fixture_id', true)
    );
    $data = $this->getPlayerLineUpData($args);

    if (!empty($data['allMatchStatsSummary'])) {
      require(REA_VIEWS_ROOT_DIR . 'player-lineup-view.php');
    }


    // content
    return ob_get_clean();
  }

  public function getPlayerLineUpData($args = array())
  {
    //  fixture_id, match id, event id from api
    extract($args);

    // Rugby Xplorer GraphQL endpoint (update if different)
    $graphql_url = 'https://rugby-au-cms.graphcdn.app/';

    $body = [
      "operationName" => "matchdetailsRugbyComAu",
      "variables" => [
        "comp" => [
          "id" => null,
          "season" => null,
          "fixture" => $fixture_id,
          "sourceType" => "2"
        ]
      ],
      "query" => "query matchdetailsRugbyComAu(\$comp: CompInput) {
      getFixtureItem(comp: \$comp) {
        ...Fixtures_fixture
        __typename
      }
      allMatchCommentary(comp: \$comp) {
        ...Fixture_MatchCommentary
        __typename
      }
      allMatchStatsSummary(comp: \$comp) {
        id
        lineUp {
          id
          players {
            ...MatchLineup_matchplayer
            __typename
          }
          substitutes {
            ...MatchLineup_matchplayer
            __typename
          }
          coaches {
            ...MatchLineup_matchplayer
            __typename
          }
          __typename
        }
        referees {
          ...MatchStatsSummary_matchreferee
          __typename
        }
        pointsSummary {
          id
          tries {
            ...PointSummary_matchpoint
            __typename
          }
          conversions {
            ...PointSummary_matchpoint
            __typename
          }
          penaltyGoals {
            ...PointSummary_matchpoint
            __typename
          }
          fieldGoals {
            ...PointSummary_matchpoint
            __typename
          }
          __typename
        }
        playSummary {
          id
          attack {
            ...MatchPlaySummary_matchplaystat
            __typename
          }
          defence {
            ...MatchPlaySummary_matchplaystat
            __typename
          }
          kicking {
            ...MatchPlaySummary_matchplaystat
            __typename
          }
          breakdown {
            ...MatchPlaySummary_matchplaystat
            __typename
          }
          setPlay {
            ...MatchPlaySummary_matchplaystat
            __typename
          }
          possession {
            ...MatchPlaySummary_matchplaystat
            __typename
          }
          discipline {
            ...MatchPlaySummary_matchplaystat
            __typename
          }
          __typename
        }
        __typename
      }
      allSeasonStat(comp: \$comp) {
        ...MatchPlaySummary_matchplaystat
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
    }

    fragment Fixture_MatchCommentary on MatchCommentary {
      id
      minute
      type
      comment
      __typename
    }

    fragment MatchLineup_matchplayer on MatchPlayer {
      id
      name
      position
      shirtNumber
      isHome
      photo {
        id
        url
        alt
        __typename
      }
      link
      captainType
      frontRow
      __typename
    }

    fragment PointSummary_matchpoint on MatchPoint {
      id
      playerName
      isHome
      pointsMinute
      __typename
    }

    fragment MatchPlaySummary_matchplaystat on MatchPlayStat {
      id
      title
      homeValue
      awayValue
      __typename
    }

    fragment MatchStatsSummary_matchreferee on MatchReferee {
      refereeId
      type
      refereeName
      status
      notified
      private
      isActive
      __typename
    }"
    ];

    $response = wp_remote_post($graphql_url, [
      'headers' => [
        'Content-Type' => 'application/json',
      ],
      'body' => wp_json_encode($body),
      'method' => 'POST',
      'timeout' => 300,
      // Fix for some servers not resolving IPv6 properly
      'cookies' => array(),
      'sslverify' => false,         // GraphCDN sometimes fails if strict SSL
    ]);

    if (is_wp_error($response)) {
      error_log('GraphQL Request Error getPlayerLineUpData: ' . $response->get_error_message());
    } else {
      $data = json_decode(wp_remote_retrieve_body($response), true);

      return $data['data'];
      // echo '<pre>';
      // print_r($data);
      // echo '</pre>';
    }
  }



  public function team_ladder($atts)
  {
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

    $args = array(
      'competition_id' => $competition_id
    );
    $data = $this->getCompetitionLadderData($args);

    if (!empty($data['ladderPools'])) {
      require(REA_VIEWS_ROOT_DIR . 'team-ladder-view.php');
    }


    // content
    return ob_get_clean();
  }

  public function getCompetitionLadderData($args)
  {
    extract($args);

    $graphql_url = 'https://rugby-au-cms.graphcdn.app/';

    $body = [
      "operationName" => "CompLadderQuery",
      "variables" => [
        "comp" => [
          "id" => $competition_id,
          "sourceType" => "2"
        ]
      ],
      "query" => 'query CompLadderQuery($comp: CompInput) {
      compLadder(comp: $comp) {
        id
        hasPools
        ladderPools {
          id
          poolName
          teams {
            id
            name
            position
            totalMatchPoints
            matchesPlayed
            matchesWon
            matchesLost
            matchesDrawn
            pointsFor
            pointsAgainst
            pointsDifference
            crest
            bonusPoints4T
          }
        }
      }
    }'
    ];

    $args = [
      'body' => json_encode($body),
      'headers' => [
        'Content-Type' => 'application/json',
      ],
      'method' => 'POST',
      'data_format' => 'body',
    ];

    $response = wp_remote_post($graphql_url, $args);

    if (is_wp_error($response)) {
      error_log('GraphQL Request Error: ' . $response->get_error_message());
    } else {
      $data = json_decode(wp_remote_retrieve_body($response), true);

      return $data['data']['compLadder'];
      // echo '<pre>';
      // print_r($data);
      // echo '</pre>';
    }
  }

  public function team_events($atts)
  {
    $atts = shortcode_atts(array(
      'id' => uniqid(),
      'entity_id' => '',
      'season' => '',
      'competition_id' => '',
      'team_id' => ''
    ), $atts, 'team_ladder');

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
    $data = $this->getPlayerLineUpData($args);

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

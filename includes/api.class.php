<?php

namespace REA\Plugin;


defined('ABSPATH') || exit;

/**
 * WP Settings Class.
 */
class Api
{
  /**
   * The single instance of the class.
   *
   * @since 1.0
   */
  protected static $_instance = null;

  public $api_url = 'https://rugby-au-cms.graphcdn.app/';

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

  /**
   * Get fixtures or results Data from Rugby Xplorer GraphQL API
   *
   * @param array $args Arguments for the data request 
   *
   * @return array fixtures or results Data from the API
   * @since 1.0
   */
  public function getData($args = array())
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

    if ($team === 'All') {
      $team = '';
    }
    $body = [
      "operationName" => "EntityFixturesAndResults",
      "variables" => [
        "season" => $season,
        "comps" => [
          [
            "id" => $competition,
            "sourceType" => "2"
          ]
        ],
        "teams" => [$team],
        "type" => $type,
        "skip" => 0,
        "limit" => 50,
        "entityId" => $entityId,
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

    $response = wp_remote_post($this->api_url, [
      'headers' => [
        'Content-Type' => 'application/json',
      ],
      'body' => wp_json_encode($body),
      'method' => 'POST',
      'data_format' => 'body',
      // Fix for some servers not resolving IPv6 properly 
      'timeout' => 60,
    ]);

    if (is_wp_error($response)) {
      error_log('GraphQL Error getData: ' . $response->get_error_message());
    } else {
      $data = json_decode(wp_remote_retrieve_body($response), true);
      $results = $data['data']['getEntityFixturesAndResults'];
      return $results;
    }
  }

  /**
   * Get match details from Rugby Xplorer GraphQL API.
   *
   * @param array $args Arguments for the data request
   *
   * @return array Match details Data from the API
   * @since 1.0
   */
  public function getMatchDetails($args = array())
  {
    //  fixture_id, match id, event id from api
    extract($args);

    $body = array(
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
    );

    $response = wp_remote_post($this->api_url, array(
      'headers' => [
        'Content-Type' => 'application/json',
      ],
      'body' => wp_json_encode($body),
      'method' => 'POST',
      'timeout' => 300,
      // Fix for some servers not resolving IPv6 properly
      'cookies' => array(),
      'sslverify' => false,         // GraphCDN sometimes fails if strict SSL
    ));

    if (is_wp_error($response)) {
      error_log('GraphQL Request Error getPlayerLineUpData: ' . $response->get_error_message());
    } else {
      $data = json_decode(wp_remote_retrieve_body($response), true);

      return $data['data'];
    }
  }

  /**
   * Get ladder data from Rugby Xplorer GraphQL API.
   *
   * @param array $args Arguments for the data request
   *
   * @return array Ladder Data from the API
   * @since 1.0
   */
  public function getCompetitionLadderData($args)
  {
    extract($args);

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

    $response = wp_remote_post($this->api_url, $args);

    if (is_wp_error($response)) {
      error_log('GraphQL Request Error: ' . $response->get_error_message());
    } else {
      $data = json_decode(wp_remote_retrieve_body($response), true);

      return $data['data']['compLadder'];
    }
  }
}

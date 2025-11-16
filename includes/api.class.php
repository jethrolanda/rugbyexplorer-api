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
        "limit" => 20,
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

  public function getLadder($matchId)
  {
    $body = [
      "operationName" => "CompLadderQuery",
      "variables" => [
        "comp" => [
          "id" => $matchId, //"mLGoqgHnacX2AnmgD",
          "sourceType" => "2"
        ]
      ],
      "query" => "query CompLadderQuery(\$comp: CompInput) {
      compLadder(comp: \$comp) {
        ...LadderCard_ladder
        __typename
      }
    }

    fragment LadderCard_ladder on Ladder {
      id
      hasPools
      ladderPools {
        id
        poolName
        teams {
          ...LadderCard_ladderTeam
          __typename
        }
        __typename
      }
      sortingOptions
      overallSort
      __typename
    }

    fragment LadderCard_ladderTeam on LadderTeam {
      active
      bonusPoints3T
      bonusPoints4T
      bonusPoints7P
      byes
      crest
      id
      matchWinRatio
      matchesDrawn
      matchesLost
      matchesPlayed
      matchesWon
      name
      numberForfeitsLoss
      numberForfeitsWin
      numberOfForfeits
      pointsADJ
      pointsAgainst
      pointsAgainstADJ
      pointsDifference
      pointsFor
      pointsForADJ
      pointsRatio
      position
      scoreRatio
      totalBonusPoints
      totalMatchPoints
      totalTries
      tryDifference
      __typename
    }"
    ];

    $response = wp_remote_post($this->api_url, [
      'headers' => [
        'Content-Type' => 'application/json',
        // If the API requires authentication, uncomment below:
        // 'Authorization' => 'Bearer your_token_here'
      ],
      'body' => wp_json_encode($body),
      'method' => 'POST',
      'timeout' => 30,
    ]);

    if (is_wp_error($response)) {
      error_log('GraphQL Error getLadder: ' . $response->get_error_message());
    } else {
      $data = json_decode(wp_remote_retrieve_body($response), true);
      $results = $data['data']['getEntityFixturesAndResults'];
    }
  }
}

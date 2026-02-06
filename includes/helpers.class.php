<?php

namespace REA\Plugin;


defined('ABSPATH') || exit;

class Helpers
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

  /**
   * Get all players and then individually get games played then cache
   *  
   * @since 1.0
   */
  public function cache_total_games_played_all_players()
  {
    global $rea;

    $players = get_posts(array(
      'post_type'      => 'sp_player',
      'post_status'    => 'any',
      'numberposts'    => -1,
      'fields'         => 'ids',
    ));

    if (!empty($players)) {
      foreach ($players as $player_id) {
        $games_played = count($this->get_player_games_played($player_id));
        update_post_meta($player_id, 'games_played', $games_played);
      }
    }
  }

  /**
   * Cache games played per player
   * 
   * @param int $player_id 
   * @return int
   * @since 1.0
   */
  public function cache_total_games_played_per_player($player_id)
  {
    global $rea;

    $games_played = get_post_meta($player_id, 'games_played', true);

    if (empty($games_played)) {
      $games_played = count($this->get_player_games_played($player_id));
      update_post_meta($player_id, 'games_played', $games_played);
    }

    return !empty($games_played) ? $games_played : 0;
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
    $params = array(
      'sp_event',
      'rugby_explorer_players',
      '%' . $player_code . '%'
    );

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

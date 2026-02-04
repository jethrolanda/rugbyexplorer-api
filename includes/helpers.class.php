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
        $games_played = count($rea->shortcode->get_player_games_played($player_id));
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
      $games_played = count($rea->shortcode->get_player_games_played($player_id));
      update_post_meta($player_id, 'games_played', $games_played);
    }

    return !empty($games_played) ? $games_played : 0;
  }
}

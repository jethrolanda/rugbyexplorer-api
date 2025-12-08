<?php

namespace REA\Plugin;


defined('ABSPATH') || exit;

class Metabox
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
    // Add the meta box Game Fixture ID
    add_action('add_meta_boxes', array($this, 'add_game_fixture_id_metabox'));

    // Save the Fixture ID meta box data
    add_action('save_post_sp_event', array($this, 'save_game_fixture_id_metabox'));

    // Add the meta box Player ID
    add_action('add_meta_boxes', array($this, 'add_player_id_metabox'));

    // Save the Player ID meta box data
    add_action('save_post_sp_player', array($this, 'save_player_id_metabox'));

    // Add the meta box Team ID
    add_action('add_meta_boxes', array($this, 'add_team_id_metabox'));

    // Save the Team ID meta box data
    add_action('save_post_sp_team', array($this, 'save_team_id_metabox'));
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
   * Initialize custom metabox for fixture ID from rugbyexplorer
   * 
   * @since 1.0
   */
  public function add_game_fixture_id_metabox()
  {
    add_meta_box(
      'game_fixture_id',            // ID
      'Fixture ID',                 // Title
      array($this, 'render_game_fixture_id'),     // Callback
      'sp_event',                   // Post type slug (change this)
      'normal',                     // Context ('normal', 'side', 'advanced')
      'default'                     // Priority
    );
  }

  /**
   * Callback for rendering fixture ID from rugbyexplorer
   * 
   * @since 1.0
   */
  public function render_game_fixture_id()
  {
    // Security nonce
    wp_nonce_field('save_match_details', 'match_details_nonce');

    // Get saved values
    global $post;
    $fixture_id = get_post_meta($post->ID, 'fixture_id', true);
?>

    <p>
      <label for="fixture_id"><strong>Fixture ID:</strong></label><br>
      <input
        disabled
        disabled
        type="text"
        id="fixture_id"
        name="fixture_id"
        value="<?php echo esc_attr($fixture_id); ?>"
        style="width:100%;"
        placeholder="e.g. bd555ab34f689975d" />
    </p>

  <?php
  }

  /**
   * Callback for saving fixture ID from rugbyexplorer
   * 
   * @param int $post_id Event ID
   * 
   * @since 1.0
   */
  public function save_game_fixture_id_metabox($post_id)
  {

    // Verify nonce
    if (
      !isset($_POST['match_details_nonce']) ||
      !wp_verify_nonce($_POST['match_details_nonce'], 'save_match_details')
    ) {
      return;
    }

    // Check autosave or permissions
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;


    // Sanitize & save
    if (isset($_POST['fixture_id'])) {

      update_post_meta($post_id, 'fixture_id', sanitize_text_field($_POST['fixture_id']));
    }
  }

  /**
   * Initialize custom metabox for player ID from rugbyexplorer
   * 
   * @since 1.0
   */
  public function add_player_id_metabox()
  {
    add_meta_box(
      'player_id',            // ID
      'Player ID',                 // Title
      array($this, 'render_player_id'),     // Callback
      'sp_player',                   // Post type slug (change this)
      'normal',                     // Context ('normal', 'side', 'advanced')
      'default'                     // Priority
    );
  }

  /**
   * Callback for rendering player ID from rugbyexplorer
   * 
   * @since 1.0
   */
  public function render_player_id()
  {
    // Security nonce
    wp_nonce_field('save_player_id', 'player_id_nonce');

    // Get saved values
    global $post;
    $player_id = get_post_meta($post->ID, 'player_id', true);
  ?>

    <p>
      <label for="player_id"><strong>Player ID:</strong></label><br>
      <input
        disabled
        type="text"
        id="player_id"
        name="player_id"
        value="<?php echo esc_attr($player_id); ?>"
        style="width:100%;"
        placeholder="e.g. DkJm85qoaBLDopyiG__11" />
    </p>

  <?php
  }

  /**
   * Call back for saving player ID from rugbyexplorer
   * 
   * @param int $post_id Player post ID
   * 
   * @since 1.0
   */
  public function save_player_id_metabox($post_id)
  {

    // Verify nonce
    if (
      !isset($_POST['player_id_nonce']) ||
      !wp_verify_nonce($_POST['player_id_nonce'], 'save_player_id')
    ) {
      return;
    }

    // Check autosave or permissions
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;


    // Sanitize & save
    if (isset($_POST['player_id'])) {

      update_post_meta($post_id, 'player_id', sanitize_text_field($_POST['player_id']));
    }
  }

  /**
   * Initialize custom metabox for team ID from rugbyexplorer
   * 
   * @since 1.0
   */
  public function add_team_id_metabox()
  {
    add_meta_box(
      'team_id',            // ID
      'Team ID',                 // Title
      array($this, 'render_team_id'),     // Callback
      'sp_team',                   // Post type slug (change this)
      'normal',                     // Context ('normal', 'side', 'advanced')
      'default'                     // Priority
    );
  }

  /**
   * Callback for rendering team ID from rugbyexplorer
   * 
   * @since 1.0
   */
  public function render_team_id()
  {
    // Security nonce
    wp_nonce_field('save_team_id', 'team_id_nonce');

    // Get saved values
    global $post;
    $team_id = get_post_meta($post->ID, 'team_id', true);
  ?>

    <p>
      <label for="team_id"><strong>Team ID:</strong></label><br>
      <input
        disabled
        type="text"
        id="team_id"
        name="team_id"
        value="<?php echo esc_attr($team_id); ?>"
        style="width:100%;"
        placeholder="e.g. 7dTm6aLvdYu3HMGW3" />
    </p>

<?php
  }

  /**
   * Callback for saving team ID from rugbyexplorer
   * 
   * @param int $post_id Post Team ID
   * 
   * @since 1.0
   */
  public function save_team_id_metabox($post_id)
  {

    // Verify nonce
    if (
      !isset($_POST['team_id_nonce']) ||
      !wp_verify_nonce($_POST['team_id_nonce'], 'save_team_id')
    ) {
      return;
    }

    // Check autosave or permissions
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;


    // Sanitize & save
    if (isset($_POST['team_id'])) {

      update_post_meta($post_id, 'team_id', sanitize_text_field($_POST['team_id']));
    }
  }
}

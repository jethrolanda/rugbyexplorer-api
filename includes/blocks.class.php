<?php

namespace REA\Plugin;

/**
 * Plugins custom settings page that adheres to wp standard
 * see: https://developer.wordpress.org/plugins/settings/custom-settings-page/
 *
 * @since   1.0
 */

defined('ABSPATH') || exit;

/**
 * WP Settings Class.
 */
class Blocks
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
    add_action('init', array($this, 'create_block_blocks_block_init'));

    add_filter('block_categories_all', array($this, 'register_new_category'));

    add_action('enqueue_block_editor_assets', array($this, 'my_block_editor_styles'));

    // add_action('wp_loaded', array($this, 'register_patterns'));
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
   * Registers the block using the metadata loaded from the `block.json` file.
   * Behind the scenes, it registers also all assets so they can be enqueued
   * through the block editor in the corresponding context.
   *
   * @see https://developer.wordpress.org/reference/functions/register_block_type/
   */
  public function create_block_blocks_block_init()
  {
    // register_block_type(FSA_BLOCKS_ROOT_DIR . 'build');
  }

  public function register_new_category($categories)
  {

    // Adding a new category.
    $categories[] = array(
      'slug'  => 'fuel-logic-service-area-blocks',
      'title' => 'Fuel Logic Service Area'
    );

    return $categories;
  }
  public function my_block_editor_styles() {}

  /**
   * Register block pattern for Empty Cart Message to make it translatable.
   */
  // public function register_patterns()
  // {
  //   register_block_pattern(
  //     'fuel-logic-service-area/success-zipcode',
  //     array(
  //       'title'    => 'Success Zipcode',
  //       'description' => _x('Success Zipcode.', 'Block pattern description', 'wpdocs-my-plugin'),
  //       'inserter' => false,
  //       'content'  => '<!-- wp:heading {"align":"wide", "level":1} --><h1 class="wp-block-heading alignwide">' . esc_html__('Success Zipcode', 'woocommerce') . '</h1><!-- /wp:heading -->',
  //     )
  //   );
  //   register_block_pattern(
  //     'fuel-logic-service-area/invalid-zipcode',
  //     array(
  //       'title'    => 'Invalid Zipcode',
  //       'description' => _x('Invalid Zipcode.', 'Block pattern description', 'wpdocs-my-plugin'),
  //       'inserter' => false,
  //       'content'  => '<!-- wp:heading {"align":"wide", "level":1} --><h1 class="wp-block-heading alignwide">' . esc_html__('Invalid Zipcode', 'woocommerce') . '</h1><!-- /wp:heading -->',
  //     )
  //   );
  //   register_block_pattern(
  //     'fuel-logic-service-area/banned-zipcode',
  //     array(
  //       'title'    => 'Banned Zipcode',
  //       'description' => _x('Banned Zipcode.', 'Block pattern description', 'wpdocs-my-plugin'),
  //       'inserter' => false,
  //       'content'  => '<!-- wp:heading {"align":"wide", "level":1} --><h1 class="wp-block-heading alignwide">' . esc_html__('Banned Zipcode', 'woocommerce') . '</h1><!-- /wp:heading -->',
  //     )
  //   );
  // }
}

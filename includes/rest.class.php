<?php

// https://learn.wordpress.org/lesson/custom-database-tables/
// TODO: implement create, read, update, delete api

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
class Rest
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
    add_action('rest_api_init', array($this, 'rest_endpoint'));
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

  public function rest_endpoint()
  {
    register_rest_route('wppb/v1', '/reported', array(
      'methods' => 'GET',
      'callback' => array($this, 'get_reported_products'),
      'permission_callback' => '__return_true'
    ));

    register_rest_route('wppb/v1', '/reported', array(
      'methods' => 'POST',
      'callback' => array($this, 'create_reported_product'),
      // 'args' => [
      //   'product_id' => [
      //     'required' => true,
      //   ],
      //   'reason' => [
      //     'required' => true,
      //   ]
      // ],
      'permission_callback' => function () {
        return is_user_logged_in();
        // return current_user_can('edit_others_posts');
      }
    ));
  }

  public function get_reported_products($data)
  {

    global $wpdb;

    $table_name = $wpdb->prefix . 'reported_product';

    $results = $wpdb->get_results("SELECT * FROM $table_name");

    $response =  rest_ensure_response($results);
    // $response->header('X-WP-Total', (int) $query->found_posts);
    // $response->header('X-WP-TotalPages', (int) $query->max_num_pages);
    return $response;
  }

  public function create_reported_product($request)
  {
    // error_log(print_r(json_decode($request->get_body()), true));

    global $wpdb;

    $table_name = $wpdb->prefix . 'reported_product';
    $body = json_decode($request->get_body());
    $data = array(
      'product_id' => absint($body->product_id),
      'reason' => sanitize_textarea_field($body->reason),
      'created_at' => current_time('mysql', 1)
    );

    $wpdb->insert($table_name, $data);

    return rest_ensure_response($data);
  }
}

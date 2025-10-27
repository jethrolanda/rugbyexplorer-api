<?php

namespace REA\Plugin;


/** 
 * @since   1.0
 */

defined('ABSPATH') || exit;

/**
 * WP Settings Class.
 */
class Settings
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
    /**
     * Register our settings_init to the admin_init action hook.
     */
    add_action('admin_init', array($this, 'settings_init'));

    /**
     * Register our register_options_page to the admin_menu action hook.
     */
    add_action('admin_menu', array($this, 'register_options_page'));

    // add_action('update_option_fusesport_options', array($this, 'on_setting_page_update'), 10, 2);
    add_action('updated_option', array($this, 'on_update_option'), 10, 3);
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
   * custom option and settings
   */
  public function settings_init()
  {
    // Register a new setting for "fusesport" page.
    register_setting('fusesport', 'fusesport_options');

    // Register a new section in the "fusesport" page.
    add_settings_section(
      'fusesport_section_developers',
      '',
      '',
      'fusesport'
    );

    // Register a new field in the "fusesport_section_developers" section, inside the "fusesport" page.
    add_settings_field(
      'fusesport_field_api_username', // As of WP 4.6 this value is used only internally.
      // Use $args' label_for to populate the id inside the callback.
      __('FuseSport API Username', 'fusesport'),
      array($this, 'fusesport_field_api_username_cb'),
      'fusesport',
      'fusesport_section_developers',
      array(
        'label_for'         => 'fusesport_field_api_username',
        'class'             => 'fusesport_row',
      )
    );
    add_settings_field(
      'fusesport_field_api_password', // As of WP 4.6 this value is used only internally.
      // Use $args' label_for to populate the id inside the callback.
      __('FuseSport API Password', 'fusesport'),
      array($this, 'fusesport_field_api_password_cb'),
      'fusesport',
      'fusesport_section_developers',
      array(
        'label_for'         => 'fusesport_field_api_password',
        'class'             => 'fusesport_row',
      )
    );

    add_settings_field(
      'sportspress_field_api_username',
      // Use $args' label_for to populate the id inside the callback.
      __('SportsPress API Username', 'fusesport'),
      array($this, 'sportspress_field_api_username_cb'),
      'fusesport',
      'fusesport_section_developers',
      array(
        'label_for'         => 'sportspress_field_api_username',
        'class'             => 'fusesport_row',
      )
    );
    add_settings_field(
      'sportspress_field_api_password', // As of WP 4.6 this value is used only internally.
      // Use $args' label_for to populate the id inside the callback.
      __('SportsPress API Password', 'fusesport'),
      array($this, 'sportspress_field_api_password_cb'),
      'fusesport',
      'fusesport_section_developers',
      array(
        'label_for'         => 'sportspress_field_api_password',
        'class'             => 'fusesport_row',
      )
    );
    add_settings_field(
      'fusesport_field_season_id', // As of WP 4.6 this value is used only internally.
      // Use $args' label_for to populate the id inside the callback.
      __('FuseSport Season ID', 'fusesport'),
      array($this, 'fusesport_season_id_cb'),
      'fusesport',
      'fusesport_section_developers',
      array(
        'label_for'         => 'fusesport_field_season_id',
        'class'             => 'fusesport_row',
      )
    );

    add_settings_field(
      'fusesport_field_competition_ids', // As of WP 4.6 this value is used only internally.
      // Use $args' label_for to populate the id inside the callback.
      __('FuseSport Competition IDs', 'fusesport'),
      array($this, 'fusesport_competition_ids_cb'),
      'fusesport',
      'fusesport_section_developers',
      array(
        'label_for'         => 'fusesport_field_competition_ids',
        'class'             => 'fusesport_row',
      )
    );

    add_settings_field(
      'fusesport_field_schedule_update', // As of WP 4.6 this value is used only internally.
      // Use $args' label_for to populate the id inside the callback.
      __('Schedule Update', 'fusesport'),
      array($this, 'fusesport_schedule_update_cb'),
      'fusesport',
      'fusesport_section_developers',
      array(
        'label_for'         => 'fusesport_field_schedule_update',
        'class'             => 'fusesport_row',
      )
    );
  }



  /**
   * Pill field callbakc function.
   *
   * WordPress has magic interaction with the following keys: label_for, class.
   * - the "label_for" key value is used for the "for" attribute of the <label>.
   * - the "class" key value is used for the "class" attribute of the <tr> containing the field.
   * Note: you can add custom key value pairs to be used inside your callbacks.
   *
   * @param array $args
   */
  public function fusesport_field_api_username_cb($args)
  {
    // Get the value of the setting we've registered with register_setting()
    $options = get_option('fusesport_options'); ?>
    <input style="width: 400px" type="text" placeholder="Username" id="<?php echo esc_attr($args['label_for']); ?>" name="fusesport_options[<?php echo esc_attr($args['label_for']); ?>]" value="<?php echo isset($options[$args['label_for']]) ? esc_attr($options[$args['label_for']]) : ''; ?>" />
    <p>Fusesport API username.</p>
  <?php
  }

  public function fusesport_field_api_password_cb($args)
  {
    // Get the value of the setting we've registered with register_setting()
    $options = get_option('fusesport_options');
  ?>
    <input style="width: 400px" type="password" placeholder="Password" id="<?php echo esc_attr($args['label_for']); ?>" name="fusesport_options[<?php echo esc_attr($args['label_for']); ?>]" value="<?php echo isset($options[$args['label_for']]) ? esc_attr($options[$args['label_for']]) : ''; ?>" />
    <p>Fusesport API password.</p>
  <?php
  }

  public function sportspress_field_api_username_cb($args)
  {
    // Get the value of the setting we've registered with register_setting()
    $options = get_option('fusesport_options'); ?>
    <input style="width: 400px" type="text" placeholder="Username" id="<?php echo esc_attr($args['label_for']); ?>" name="fusesport_options[<?php echo esc_attr($args['label_for']); ?>]" value="<?php echo isset($options[$args['label_for']]) ? esc_attr($options[$args['label_for']]) : ''; ?>" />
    <p>Add your wordpress username here. Sportspress API requires username and app password to do an API request. The API will use basic authentication (WordPress username + application password).</p>

  <?php
  }

  public function sportspress_field_api_password_cb($args)
  {
    // Get the value of the setting we've registered with register_setting()
    $options = get_option('fusesport_options');
  ?>
    <input style="width: 400px" type="password" placeholder="Password" id="<?php echo esc_attr($args['label_for']); ?>" name="fusesport_options[<?php echo esc_attr($args['label_for']); ?>]" value="<?php echo isset($options[$args['label_for']]) ? esc_attr($options[$args['label_for']]) : ''; ?>" />
    <p>Create application password at WP Admin → Users → Your Profile → Application Passwords → Add New then add the generated password here.</p>
  <?php
  }

  public function fusesport_season_id_cb($args)
  {
    // Get the value of the setting we've registered with register_setting()
    $options = get_option('fusesport_options');
  ?>
    <input style="width: 400px" type="text" placeholder="Season ID" id="<?php echo esc_attr($args['label_for']); ?>" name="fusesport_options[<?php echo esc_attr($args['label_for']); ?>]" value="<?php echo isset($options[$args['label_for']]) ? esc_attr($options[$args['label_for']]) : ''; ?>" />
    <p>Add fusesport season ID here.</p>
  <?php
  }

  public function fusesport_competition_ids_cb($args)
  {
    // Get the value of the setting we've registered with register_setting()
    $options = get_option('fusesport_options');
  ?>
    <input style="width: 400px" type="text" placeholder="Competition IDs" id="<?php echo esc_attr($args['label_for']); ?>" name="fusesport_options[<?php echo esc_attr($args['label_for']); ?>]" value="<?php echo isset($options[$args['label_for']]) ? esc_attr($options[$args['label_for']]) : ''; ?>" />
    <p>Add fusesport competition ID's here. Separate by comma.</p>
  <?php
  }

  public function fusesport_schedule_update_cb($args)
  {
    // Get the value of the setting we've registered with register_setting()
    $options = get_option('fusesport_options');
    $value = isset($options[$args['label_for']]) ? esc_attr($options[$args['label_for']]) : '';
  ?>
    <select name="fusesport_options[<?php echo esc_attr($args['label_for']); ?>]" id="<?php echo esc_attr($args['label_for']); ?>">
      <option value="daily" <?php selected($value, 'daily'); ?>>Daily</option>
      <option value="weekly" <?php selected($value, 'weekly'); ?>>Weekly</option>
      <option value="every_fifteen_minutes" <?php selected($value, 'every_fifteen_minutes'); ?>>Every 15 Mins</option>
    </select>
    <p>Schedule auto import FuseSports API to SportsPress.</p>
  <?php
  }


  /**
   * Add the top level menu page.
   */
  public function register_options_page()
  {
    add_menu_page(
      'FuseSport Settings',
      'FuseSport',
      'manage_options',
      'fusesport',
      array($this, 'options_page')
    );
  }

  /**
   * Top level menu callback function
   */
  public function options_page()
  {
    // check user capabilities
    if (! current_user_can('manage_options')) {
      return;
    }

    // add error/update messages

    // check if the user have submitted the settings
    // WordPress will add the "settings-updated" $_GET parameter to the url
    if (isset($_GET['settings-updated'])) {
      // add settings saved message with the class of "updated"
      add_settings_error('fusesport_messages', 'fusesport_message', __('Settings Saved', 'fusesport'), 'updated');
    }

    // show error/update messages
    settings_errors('fusesport_messages');
  ?>
    <div class="wrap">
      <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
      <form action="options.php" method="post">
        <?php
        // output security fields for the registered setting "fusesport"
        settings_fields('fusesport');
        // output setting sections and their fields
        // (sections are registered for "fusesport", each field is registered to a specific section)
        do_settings_sections('fusesport');
        // output save settings button
        submit_button('Save Settings');
        ?>
      </form>
    </div>

    <div id="fusesport"></div>
<?php
  }

  // 3️⃣ React to setting change — reschedule event if needed
  public function on_setting_page_update($old, $new)
  {
    error_log('on_setting_page_update');
    // error_log(print_r($old, true));
    // error_log(print_r($new, true));

    if ($old['fusesport_field_schedule_update'] != $new['fusesport_field_schedule_update']) {
      // Remove old schedule
      wp_clear_scheduled_hook('fusesport_schedule_update');

      // Add new schedule
      wp_schedule_event(time(), $new['fusesport_field_schedule_update'], 'fusesport_schedule_update');
    }
  }

  // Fallback if above misses
  public function on_update_option($option, $old, $new)
  {
    if ($option === 'fusesport_options') {
      $hook = 'fusesport_schedule_update';
      while ($timestamp = wp_next_scheduled($hook)) {
        wp_unschedule_event($timestamp, $hook);
      }
      $frequency = $new['fusesport_field_schedule_update'];
      wp_schedule_event(time(), $frequency, $hook);
    }
  }
}

<?php
if (!defined('ABSPATH')) {
	exit;
}
// Exit if accessed directly


class RugbyExplorer_API
{

	/*
    |------------------------------------------------------------------------------------------------------------------
    | Class Members
    |------------------------------------------------------------------------------------------------------------------
     */
	private static $_instance;

	public $scripts;
	public $ajax;
	public $shortcode;
	public $sportspress;
	public $settings;
	public $rugbyexplorer;
	public $cron;
	public $api;
	public $metabox;
	public $helpers;

	const VERSION = '1.0';

	/*
  |------------------------------------------------------------------------------------------------------------------
  | Mesc Functions
  |------------------------------------------------------------------------------------------------------------------
  */

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct()
	{

		// $options = get_option('rugbyexplorer_options');

		// $count = 1;
		// $updated_teams = array();
		// $unique = str_replace('.', '', microtime(true));
		// foreach ($options['rugbyexplorer_field_club_teams'] as $key => $team) {

		// 	$updated_teams[$key] = $team;
		// 	$updated_teams[$key]['key'] = $unique + $count;
		// 	$count++;
		// }
		// $options['rugbyexplorer_field_club_teams'] = $updated_teams;

		// update_option('rugbyexplorer_options', $options);

		$this->scripts = REA\Plugin\Scripts::instance();
		$this->ajax = REA\Plugin\Ajax::instance();
		$this->shortcode = REA\Plugin\Shortcode::instance();
		$this->sportspress = REA\Plugin\Sportspress::instance();
		$this->settings = REA\Plugin\Settings::instance();
		$this->rugbyexplorer = \REA\Plugin\RugbyExplorer::instance();
		$this->cron = \REA\Plugin\Cron::instance();
		$this->api = \REA\Plugin\Api::instance();
		$this->metabox = \REA\Plugin\Metabox::instance();
		$this->helpers = \REA\Plugin\Helpers::instance();


		// Register Activation Hook
		register_activation_hook(REA_PLUGIN_DIR . 'rugbyexplorer.php', array($this, 'activate'));

		// Register Deactivation Hook
		register_deactivation_hook(REA_PLUGIN_DIR . 'rugbyexplorer.php', array($this, 'deactivate'));
	}

	/**
	 * Singleton Pattern.
	 *
	 * @since 1.0.0
	 */
	public static function instance()
	{

		if (!self::$_instance instanceof self) {
			self::$_instance = new self;
		}

		return self::$_instance;
	}


	/**
	 * Trigger on activation
	 *
	 * @since 1.0.0
	 */
	public function activate()
	{

		// Check if ActionSheduler class exists
		// This class exist in WooCommerce or ActionScheduler plugin
		if (class_exists('ActionScheduler')) {
			// Avoid scheduling duplicate recurring action
			if (!as_next_scheduled_action('rugbyexplorer_scheduled_events_update')) {
				as_schedule_recurring_action(time(), DAY_IN_SECONDS, 'rugbyexplorer_scheduled_events_update');
			}

			if (!as_next_scheduled_action('rugbyexplorer_cache_games_played_per_player')) {
				as_schedule_recurring_action(time(), DAY_IN_SECONDS, 'rugbyexplorer_cache_games_played_per_player');
			}
		}
	}

	/**
	 * Trigger on deactivation
	 *
	 * @since 1.0.0
	 */
	public function deactivate()
	{
		wp_clear_scheduled_hook('rugbyexplorer_schedule_update');
	}
}

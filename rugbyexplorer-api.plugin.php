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
	public $blocks;
	public $ajax;
	public $shortcode;
	public $rest;
	public $sportspress;
	public $settings;
	public $rugbyexplorer;
	public $cron;
	public $api;
	public $metabox;

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

		$this->scripts = REA\Plugin\Scripts::instance();
		$this->blocks = REA\Plugin\Blocks::instance();
		$this->ajax = REA\Plugin\Ajax::instance();
		$this->shortcode = REA\Plugin\Shortcode::instance();
		$this->rest = REA\Plugin\Rest::instance();
		$this->sportspress = REA\Plugin\Sportspress::instance();
		$this->settings = REA\Plugin\Settings::instance();
		$this->rugbyexplorer = \REA\Plugin\RugbyExplorer::instance();
		$this->cron = \REA\Plugin\Cron::instance();
		$this->api = \REA\Plugin\Api::instance();
		$this->metabox = \REA\Plugin\Metabox::instance();


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

		if (class_exists('ActionScheduler')) {
			// Avoid scheduling duplicate recurring action
			if (!as_next_scheduled_action('rugbyexplorer_scheduled_events_update')) {
				as_schedule_recurring_action(time(), DAY_IN_SECONDS, 'rugbyexplorer_scheduled_events_update');
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

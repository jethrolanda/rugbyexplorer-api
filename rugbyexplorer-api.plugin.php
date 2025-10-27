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
	public $fusesport;
	public $settings;
	public $sportspress;
	public $cron;

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
		$this->fusesport = REA\Plugin\Fusesport::instance();
		$this->settings = REA\Plugin\Settings::instance();
		$this->sportspress = \REA\Plugin\Sportspress::instance();
		$this->cron = \REA\Plugin\Cron::instance();


		// Register Activation Hook
		register_activation_hook(REA_PLUGIN_DIR . 'rugbyexplorer-api.php', array($this, 'activate'));

		// Register Deactivation Hook
		register_deactivation_hook(REA_PLUGIN_DIR . 'rugbyexplorer-api.php', array($this, 'deactivate'));
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
		// On activation, add event in cron to delete all cached json files. Trigger twice a day.
		if (!wp_next_scheduled('fusesport_schedule_update')) {
			wp_schedule_event(time(), 'twicedaily', 'fusesport_schedule_update');
		}
	}

	/**
	 * Trigger on deactivation
	 *
	 * @since 1.0.0
	 */
	public function deactivate()
	{
		wp_clear_scheduled_hook('fusesport_schedule_update');
	}
}

<?php
/**
 * Handles the update functionality of the plugin.
 *
 * @since 1.0.0
 *
 * @package Tribe\Extensions\Remove_Past_Events_Plus;
 */

namespace Tribe\Extensions\Remove_Past_Events_Plus;

use Tribe__PUE__Checker;

/**
 * Class PUE.
 *
 * @since 1.0.0
 *
 * @package Tribe\Extensions\Remove_Past_Events_Plus;
 */
class PUE extends \tad_DI52_ServiceProvider {

	/**
	 * The slug used for PUE.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private static $pue_slug = 'extension-remove-past-events-plus';

	/**
	 * Whether to load PUE or not.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public static $is_active = false;

	/**
	 * Plugin update URL.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $update_url = 'http://tri.be/';

	/**
	 * The PUE checker instance.
	 *
	 * @since 1.0.0
	 *
	 * @var Tribe__PUE__Checker
	 */
	private $pue_instance;

	/**
	 * Registers the filters required by the Plugin Update Engine.
	 *
	 * @since 1.0.0
	 */
	public function register() {
		$this->container->singleton( static::class, $this );
		$this->container->singleton( 'extension.remove_past_events_plus.pue', $this );

		// Bail to avoid notice.
		if ( ! static:: $is_active ) {
			return;
		}

		add_action( 'tribe_helper_activation_complete', [ $this, 'load_plugin_update_engine' ] );

		register_uninstall_hook( Plugin::FILE, [ static::class, 'uninstall' ] );
	}

	/**
	 * If the PUE Checker class exists, go ahead and create a new instance to handle
	 * update checks for this plugin.
	 *
	 * @since 1.0.0
	 */
	public function load_plugin_update_engine() {
		/**
		 * Filters whether Extension exists on PUE component should manage the plugin updates or not.
		 *
		 * @since 1.0.0
		 *
		 * @param bool   $pue_enabled Whether PUE component should manage the plugin updates or not.
		 * @param string $pue_slug    The plugin slug used to register it in the Plugin Update Engine.
		 */
		$pue_enabled = apply_filters( 'tribe_enable_pue', true, static::get_slug() );

		if ( ! ( $pue_enabled && class_exists( 'Tribe__PUE__Checker' ) ) ) {
			return;
		}

		$this->pue_instance = new Tribe__PUE__Checker(
			$this->update_url,
			static::get_slug(),
			[],
			plugin_basename( Plugin::FILE )
		);
	}

	/**
	 * Get the PUE slug for this plugin.
	 *
	 * @since 1.0.0
	 *
	 * @return string PUE slug.
	 */
	public static function get_slug() {
		return static::$pue_slug;
	}

	/**
	 * Handles the removal of PUE-related options when the plugin is uninstalled.
	 *
	 * @since 1.0.0
	 */
	public static function uninstall() {
		$slug = str_replace( '-', '_', static::get_slug() );

		delete_option( 'pue_install_key_' . $slug );
		delete_option( 'pu_dismissed_upgrade_' . $slug );
	}
}

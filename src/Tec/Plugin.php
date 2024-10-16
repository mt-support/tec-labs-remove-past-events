<?php
/**
 * Plugin Class.
 *
 * @since 1.0.0
 *
 * @package Tribe\Extensions\Remove_Past_Events
 */

namespace Tribe\Extensions\Remove_Past_Events;

use TEC\Common\Contracts\Service_Provider;

/**
 * Class Plugin
 *
 * @since 1.0.0
 *
 * @package Tribe\Extensions\Remove_Past_Events
 */
class Plugin extends Service_Provider {
	/**
	 * Stores the version for the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const VERSION = '1.2.2';

	/**
	 * Stores the base slug for the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const SLUG = 'remove-past-events';

	/**
	 * Stores the base slug for the extension.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const FILE = TRIBE_EXTENSION_REMOVE_PAST_EVENTS_FILE;

	/**
	 * @since 1.0.0
	 *
	 * @var string Plugin Directory.
	 */
	public $plugin_dir;

	/**
	 * @since 1.0.0
	 *
	 * @var string Plugin path.
	 */
	public $plugin_path;

	/**
	 * @since 1.0.0
	 *
	 * @var string Plugin URL.
	 */
	public $plugin_url;

	/**
	 * Set up the Extension's properties.
	 *
	 * This always executes even if the required plugins are not present.
	 *
	 * @since 1.0.0
	 */
	public function register() {
		// Set up the plugin provider properties.
		$this->plugin_path = trailingslashit( dirname( static::FILE ) );
		$this->plugin_dir  = trailingslashit( basename( $this->plugin_path ) );
		$this->plugin_url  = plugins_url( $this->plugin_dir, $this->plugin_path );

		// Register this provider as the main one and use a bunch of aliases.
		$this->container->singleton( static::class, $this );
		$this->container->singleton( 'extension.remove_past_events', $this );
		$this->container->singleton( 'extension.remove_past_events.plugin', $this );
		$this->container->register( PUE::class );

		if ( ! $this->check_plugin_dependencies() ) {
			// If the plugin dependency manifest is not met, then bail and stop here.
			return;
		}

		add_filter( 'tribe_general_settings_maintenance_section', [ $this, 'option_filter' ] );

		add_filter( 'tec_events_event_cleaner_trash_cron_frequency', [ $this, 'reschedule_crons' ] );

		$this->container->register( Hooks::class );
		$this->container->register( Assets::class );
	}

	/**
	 * Checks whether the plugin dependency manifest is satisfied or not.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether the plugin dependency manifest is satisfied or not.
	 */
	protected function check_plugin_dependencies() {
		$this->register_plugin_dependencies();

		return tribe_check_plugin( static::class );
	}

	/**
	 * Registers the plugin and dependency manifest among those managed by Tribe Common.
	 *
	 * @since 1.0.0
	 */
	protected function register_plugin_dependencies() {
		$plugin_register = new Plugin_Register();
		$plugin_register->register_plugin();

		$this->container->singleton( Plugin_Register::class, $plugin_register );
		$this->container->singleton( 'extension.remove_past_events', $plugin_register );
	}

	/**
	 * Adjusting the dropdown options for trashing and deleting past events.
	 *
	 * @since 1.2.1 Changing option values to a 'frequency|interval' format.
	 * @since 1.2.2 Adjusting option values to keep the default values compatible.
	 *              After enabling the extension, the setting dropdown will keep the current setting instead of
	 *              reverting to 'Disabled' while the saved setting would still be something else due to the
	 *              different option value (e.g. "9" vs. "9|month").
	 *
	 * @param array<string,string>   $fields The array of option values.
	 *
	 * @return array  The modified option values.
	 */
	function option_filter( $fields ) {
		$new_values = [
			null        => esc_html__( 'Disabled', 'the-events-calendar' ),
			'15|minute' => esc_html__( '15 minutes', 'tec-labs-remove-past-events' ),
			'1|hour'    => esc_html__( '1 hour', 'tec-labs-remove-past-events' ),
			'12|hour'   => esc_html__( '12 hours', 'tec-labs-remove-past-events' ),
			'1|day'     => esc_html__( '1 day', 'tec-labs-remove-past-events' ),
			'3|day'     => esc_html__( '3 days', 'tec-labs-remove-past-events' ),
			'1|week'    => esc_html__( '1 week', 'tec-labs-remove-past-events' ),
			'2|week'    => esc_html__( '2 weeks', 'tec-labs-remove-past-events' ),
			'1'         => esc_html__( '1 month', 'the-events-calendar' ),
			'3'         => esc_html__( '3 months', 'the-events-calendar' ),
			'6'         => esc_html__( '6 months', 'the-events-calendar' ),
			'9'         => esc_html__( '9 months', 'the-events-calendar' ),
			'12'        => esc_html__( '1 year', 'the-events-calendar' ),
			'24'        => esc_html__( '2 years', 'the-events-calendar' ),
			'36'        => esc_html__( '3 years', 'the-events-calendar' ),
		];

		$fields['trash-past-events']['options']  = $new_values;

		return $fields;
	}

	/**
	 * Rescheduling the crons handling the trashing and deleting.
	 *
	 * @since 1.2.2 Adjust cron frequency calculation.
	 *              Remove $cron parameter.
	 *
	 * @return string The frequency string how often the cron should run.
	 */
	function reschedule_crons() {
		// Get the setting, default to 1 month.
		$time = tribe_get_option( 'trash-past-events', 1 );

		/**
		 * The frequency we want to run the cron on.
		 *
		 * Possible values:
		 * - tribe-every15mins (recommended for removing "immediately")
		 * - hourly
		 * - twicedaily
		 * - daily (recommended for removing day-old and older)
		 * - weekly
		 * - fifteendays
		 * - monthly
		 */
		$frequency_struct = explode( '|', $time );
		$feq              = $frequency_struct[0];
		$interval         = $frequency_struct[1] ?? 'month';

		// For 15 minutes (and other minutes)
		if ( str_starts_with( $interval, 'minute' ) ) {
			return 'tribe-every15mins';
		}
		// For 1 hour and 12 hours (and other hours)
		elseif ( str_starts_with( $interval, 'hour' ) ) {
			return 'hourly';
		}

		// For 1 day and longer
		return 'twicedaily';
	}
}

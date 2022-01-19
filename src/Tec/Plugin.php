<?php
/**
 * Plugin Class.
 *
 * @since 1.0.0
 *
 * @package Tribe\Extensions\Remove_Past_Events_Plus
 */

namespace Tribe\Extensions\Remove_Past_Events_Plus;

/**
 * Class Plugin
 *
 * @since 1.0.0
 *
 * @package Tribe\Extensions\Remove_Past_Events_Plus
 */
class Plugin extends \tad_DI52_ServiceProvider {
	/**
	 * Stores the version for the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const VERSION = '1.0.0';

	/**
	 * Stores the base slug for the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const SLUG = 'remove-past-events-plus';

	/**
	 * Stores the base slug for the extension.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const FILE = TRIBE_EXTENSION_REMOVE_PAST_EVENTS_PLUS_FILE;

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
	 * @since 1.0.0
	 *
	 * @var Settings
	 *
	 * TODO: Remove if not using settings
	 */
	private $settings;

	/**
	 * Setup the Extension's properties.
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
		$this->container->singleton( 'extension.remove_past_events_plus', $this );
		$this->container->singleton( 'extension.remove_past_events_plus.plugin', $this );
		$this->container->register( PUE::class );

		if ( ! $this->check_plugin_dependencies() ) {
			// If the plugin dependency manifest is not met, then bail and stop here.
			return;
		}

		// Do the settings.
		// TODO: Remove if not using settings
		$this->get_settings();

		// Start binds.

		add_filter( 'tribe_events_delete_old_events_sql', [ $this, 'cleanup_query' ] );
		add_filter( 'tribe-event-general-settings-fields', [ $this, 'option_filter' ] );
		add_action( 'plugins_loaded', [ $this, 'reschedule_crons' ], 99 );

		// End binds.

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
		$this->container->singleton( 'extension.remove_past_events_plus', $plugin_register );
	}

	/**
	 * Get this plugin's options prefix.
	 *
	 * Settings_Helper will append a trailing underscore before each option.
	 *
	 * @return string
     *
	 * @see \Tribe\Extensions\Remove_Past_Events_Plus\Settings::set_options_prefix()
	 *
	 * TODO: Remove if not using settings
	 */
	private function get_options_prefix() {
		return (string) str_replace( '-', '_', 'tec-labs-remove-past-events-plus' );
	}

	/**
	 * Get Settings instance.
	 *
	 * @return Settings
	 *
	 * TODO: Remove if not using settings
	 */
	private function get_settings() {
		if ( empty( $this->settings ) ) {
			$this->settings = new Settings( $this->get_options_prefix() );
		}

		return $this->settings;
	}

	/**
	 * Get all of this extension's options.
	 *
	 * @return array
	 *
	 * TODO: Remove if not using settings
	 */
	public function get_all_options() {
		$settings = $this->get_settings();

		return $settings->get_all_options();
	}

	/**
	 * Get a specific extension option.
	 *
	 * @param $option
	 * @param string $default
	 *
	 * @return array
	 *
	 * TODO: Remove if not using settings
	 */
	public function get_option( $option, $default = '' ) {
		$settings = $this->get_settings();

		return $settings->get_option( $option, $default );
	}

	/**
	 * Adjusting the cleanup query to use minutes instead of months
	 *
	 * @param string $sql The cleanup query in SQL format.
	 *
	 * @return string     The modified cleanup query in SQL format.
	 */
	public function cleanup_query( $sql ) {
		global $wpdb;

		$posts_with_parents_sql = "
SELECT DISTINCT post_parent
FROM {$wpdb->posts}
WHERE
post_type= '$event_post_type'
AND post_parent <> 0
";

		$sql = "
SELECT post_id
FROM {$wpdb->posts} AS t1
INNER JOIN {$wpdb->postmeta} AS t2 ON t1.ID = t2.post_id
WHERE
t1.post_type = %s
AND t2.meta_key = '_EventEndDate'
AND t2.meta_value <= DATE_SUB( CURRENT_TIMESTAMP(), INTERVAL %d MINUTE )
AND t2.meta_value != 0
AND t2.meta_value != ''
AND t2.meta_value IS NOT NULL
AND t1.post_parent = 0
AND t1.ID NOT IN ( $posts_with_parents_sql )
";

		return $sql;
	}

	/**
	 * Adjusting the dropdown options for trashing and deleting past events.
	 *
	 * @param array   $fields The array of option values.
	 *
	 * @return array  The modified option values.
	 */
	function option_filter( $fields ) {
		$new_values                              = [
			null    => esc_html__( 'Disabled', 'the-events-calendar' ),
			1       => esc_html__( '1 minute', 'the-events-calendar' ),
			15      => esc_html__( '15 minutes', 'the-events-calendar' ),
			60      => esc_html__( '1 hour', 'the-events-calendar' ),
			720     => esc_html__( '12 hours', 'the-events-calendar' ),
			1440    => esc_html__( '1 day', 'the-events-calendar' ),
			4320    => esc_html__( '3 days', 'the-events-calendar' ),
			10080   => esc_html__( '1 week', 'the-events-calendar' ),
			20160   => esc_html__( '2 weeks', 'the-events-calendar' ),
			43200   => esc_html__( '1 month', 'the-events-calendar' ),
			129600  => esc_html__( '3 months', 'the-events-calendar' ),
			259200  => esc_html__( '6 months', 'the-events-calendar' ),
			388800  => esc_html__( '9 months', 'the-events-calendar' ),
			525600  => esc_html__( '1 year', 'the-events-calendar' ),
			1051200 => esc_html__( '2 years', 'the-events-calendar' ),
			1576800 => esc_html__( '3 years', 'the-events-calendar' ),
		];
		$fields['delete-past-events']['options'] = $new_values;
		$fields['trash-past-events']['options']  = $new_values;

		return $fields;
	}

	/**
	 * Triggering the rescheduling of crons
	 *
	 * @return void
	 */
	function reschedule_crons() {
		$this->reschedule_trash_or_del_event_cron( 'tribe_trash_event_cron' );
		$this->reschedule_trash_or_del_event_cron( 'tribe_del_event_cron' );
	}

	/**
	 * Recheduling the crons handling the trashing and deleting.
	 *
	 * @param string $cron The slug of the cron.
	 *
	 * @return void
	 */
	function reschedule_trash_or_del_event_cron( $cron ) {
		if ( 'tribe_trash_event_cron' == $cron ) {
			$time = tribe_get_option( 'trash-past-events', 43200 );
		} else {
			$time = tribe_get_option( 'delete-past-events', 43200 );
		}
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

		// For 1 minute and 15 minutes
		if ( $time < 60 ) {
			$frequency = 'tribe-every15mins';
		}
		// For 1 hour and 12 hours
		elseif ( $time < 1440 ) {
			$frequency = 'hourly';
		}
		// For 1 day and longer
		else {
			$frequency = 'daily';
		}

		$scheduled = wp_next_scheduled( $cron );

		if ( $scheduled && $frequency !== wp_get_schedule( $cron ) ) {
			wp_unschedule_event( $scheduled, $cron );
			wp_schedule_event( time(), $frequency, $cron );
		}
	}
}

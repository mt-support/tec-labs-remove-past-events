<?php
/**
 * Handles hooking all the actions and filters used by the module.
 *
 * To remove a filter:
 * ```php
 *  remove_filter( 'some_filter', [ tribe( Tribe\Extensions\Remove_Past_Events_Plus\Hooks::class ), 'some_filtering_method' ] );
 *  remove_filter( 'some_filter', [ tribe( 'extension.remove_past_events_plus.hooks' ), 'some_filtering_method' ] );
 * ```
 *
 * To remove an action:
 * ```php
 *  remove_action( 'some_action', [ tribe( Tribe\Extensions\Remove_Past_Events_Plus\Hooks::class ), 'some_method' ] );
 *  remove_action( 'some_action', [ tribe( 'extension.remove_past_events_plus.hooks' ), 'some_method' ] );
 * ```
 *
 * @since 1.0.0
 *
 * @package Tribe\Extensions\Remove_Past_Events_Plus;
 */

namespace Tribe\Extensions\Remove_Past_Events_Plus;

use Tribe__Main as Common;

/**
 * Class Hooks.
 *
 * @since 1.0.0
 *
 * @package Tribe\Extensions\Remove_Past_Events_Plus;
 */
class Hooks extends \tad_DI52_ServiceProvider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 1.0.0
	 */
	public function register() {
		$this->container->singleton( static::class, $this );
		$this->container->singleton( 'extension.remove_past_events_plus.hooks', $this );

		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Adds the actions required by the plugin.
	 *
	 * @since 1.0.0
	 */
	protected function add_actions() {
		add_action( 'tribe_load_text_domains', [ $this, 'load_text_domains' ] );
	}

	/**
	 * Adds the filters required by the plugin.
	 *
	 * @since 1.0.0
	 */
	protected function add_filters() {

	}

	/**
	 * Load text domain for localization of the plugin.
	 *
	 * @since 1.0.0
	 */
	public function load_text_domains() {
		$mopath = tribe( Plugin::class )->plugin_dir . 'lang/';
		$domain = '__TRIBE_DOMAIN__';

		// This will load `wp-content/languages/plugins` files first.
		Common::instance()->load_text_domain( $domain, $mopath );
	}
}

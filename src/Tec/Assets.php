<?php
/**
 * Handles registering all Assets for the Plugin.
 *
 * To remove a Asset you can use the global assets handler:
 *
 * ```php
 *  tribe( 'assets' )->remove( 'asset-name' );
 * ```
 *
 * @since 1.0.0
 *
 * @package Tribe\Extensions\Remove_Past_Events_Plus
 */

namespace Tribe\Extensions\Remove_Past_Events_Plus;

/**
 * Register Assets.
 *
 * @since 1.0.0
 *
 * @package Tribe\Extensions\Remove_Past_Events_Plus
 */
class Assets extends \tad_DI52_ServiceProvider {
	/**
	 * Binds and sets up implementations.
	 *
	 * @since 1.0.0
	 */
	public function register() {
		$this->container->singleton( static::class, $this );
		$this->container->singleton( 'extension.remove_past_events_plus.assets', $this );

		$plugin = tribe( Plugin::class );

	}
}

<?php
/*
Plugin Name: Google Analytics for AMP
Plugin URI: https://ethitter.com/plugins/
Description: Enable Google Analytics support for Automattic's AMP plugin
Author: Erick Hitter
Version: 0.2
Author URI: https://ethitter.com/

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class ETH_AMP_Analytics_WP {
	/**
	 * PLUGIN SETUP
	 */

	/**
	 * Singleton
	 */
	private static $instance = null;

	/**
	 * Instantiate singleton
	 */
	public static function get_instance() {
		if ( ! is_a( self::$instance, __CLASS__ ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Dummy magic methods
	 */
	public function __clone() {_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'eth-amp-analytics-wp' ), '0.1' ); }
	public function __wakeup() { _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'eth-amp-analytics-wp' ), '0.1' ); }
	public function __call( $name = '', $args = array() ) { unset( $name, $args ); return null; }

	/**
	 * Class properties
	 */
	private $plugin_option_name = 'eth_amp_analytics_wp';
	private $plugin_option_defaults = array(
		'property_id' => null,
	);

	private $options = null;

	/**
	 * Defer plugin action loading until all plugins are loaded
	 *
	 * @return null
	 */
	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'action_plugins_loaded' ) );
	}

	/**
	 * Register actions and filters
	 */
	public function action_plugins_loaded() {
		if ( ! defined( 'AMP__FILE__' ) ) {
			return;
		}

		// Front-end
		add_action( 'wp_loaded', array( $this, 'action_wp_loaded' ) );

		// Admin UI
		add_action( 'admin_init', array( $this, 'action_admin_init' ) );
		register_uninstall_hook( __FILE__, array( 'ETH_AMP_Analytics_WP', 'uninstall' ) );
	}

	/**
	 * Clean up plugin data when it's removed
	 */
	public static function uninstall() {
		delete_option( 'eth_amp_analytics_wp' );
	}

	/**
	 * Conditionally load front-end hooks
	 */
	public function action_wp_loaded() {
		if ( ! empty( $this->get_option( 'property_id' ) ) ) {
			add_filter( 'amp_post_template_analytics', array( $this, 'filter_amp_post_template_analytics' ) );
		}
	}

	/**
	 * TEMPLATING
	 */

	/**
	 * Set analytics properties
	 */
	public function filter_amp_post_template_analytics( $analytics ) {
		if ( ! is_array( $analytics ) ) {
			$analytics = array();
		}

		$analytics['eth-amp-googleanalytics'] = array(
			'type' => 'googleanalytics',
			'attributes' => array(
				// 'data-credentials' => 'include',
			),
			'config_data' => array(
				'vars' => array(
					'account' => $this->get_option( 'property_id' ),
				),
				'triggers' => array(
					'trackPageview' => array(
						'on' => 'visible',
						'request' => 'pageview',
					),
				),
			),
		);

		return $analytics;
	}

	/**
	 * ADMIN UI
	 */

	/**
	 * Register admin additions
	 */
	public function action_admin_init() {
		register_setting( 'reading', $this->plugin_option_name, array( $this, 'sanitize_options' ) );

		add_settings_section( 'eth-amp-analytics-wp', __( 'Google Analytics for AMP', 'eth-amp-analytics-wp' ), '__return_false', 'reading' );
		add_settings_field( 'eth-amp-analytics-wp-property-id', __( 'Property ID:', 'eth-amp-analytics-wp' ), array( $this, 'settings_field_property_id' ), 'reading', 'eth-amp-analytics-wp' );
	}

	/**
	 * Render Property ID input field
	 */
	public function settings_field_property_id() {
		?>
		<input type="text" name="<?php echo esc_attr( $this->plugin_option_name ); ?>[property_id]" value="<?php echo esc_attr( $this->get_option( 'property_id' ) ); ?>" class="medium-text" />

		<p class="description"><?php printf( __( 'Google Analytics property ID, in the format %s.', 'eth-amp-analytics-wp' ), '<strong>UA-XXXXX-Y</strong>' ); ?></p>

		<p class="description"><?php printf( __( 'Google recommends using a property ID specific to AMP, rather than re-using the site&#8217;s existing ID. See %s.', 'eth-amp-analytics-wp' ), '<a href="https://developers.google.com/analytics/devguides/collection/amp-analytics/" target="_blank">https://developers.google.com/analytics/devguides/collection/amp-analytics/</a>' ); ?></p>
		<?php
	}

	/**
	 * Sanitize options
	 */
	public function sanitize_options( $options ) {
		$sanitized_options = $this->plugin_option_defaults;

		foreach ( $options as $key => $value ) {
			switch( $key ) {
				case 'property_id' :
					$value = trim( $value );

					if ( preg_match( '#^UA-([\d]+)-([\d]+)$#i', $value ) ) {
						$sanitized_options[ $key ] = $value;
					}
					break;

				default :
					// Do nothing, this is unexpected. :)
					break;
			}
		}

		return $sanitized_options;
	}

	/**
	 * UTILITY FUNCTIONS
	 */

	/**
	 * Retrieve one of the plugin's options
	 */
	private function get_option( $name ) {
		// Prepare options if this is the first request
		if ( is_null( $this->options ) ) {
			$ga_options = get_option( $this->plugin_option_name );

			if ( is_array( $ga_options ) ) {
				$this->options = wp_parse_args( $ga_options, $this->plugin_option_defaults );
			} else {
				return false;
			}

			unset( $ga_options );
		}

		// Does the key exist?
		if ( isset( $this->options[ $name ] ) ) {
			return $this->options[ $name ];
		} else {
			return false;
		}
	}
}

ETH_AMP_Analytics_WP::get_instance();

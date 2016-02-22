<?php
/*
Plugin Name: Google Analytics for AMP
Plugin URI: https://ethitter.com/plugins/
Description: Enable Google Analytics support for Automattic's AMP plugin
Author: Erick Hitter
Version: 0.1
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
	 * Register actions and filters
	 *
	 * @return null
	 */
	private function __construct() {
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
	 *
	 */
	public static function uninstall() {
		delete_option( 'eth_amp_analytics_wp' );
	}

	/**
	 * Conditionally load front-end hooks
	 */
	public function action_wp_loaded() {
		if ( ! empty( $this->get_option( 'property_id' ) ) ) {
			add_filter( 'amp_component_scripts', array( $this, 'filter_amp_component_scripts' ) );
			add_action( 'amp_post_template_footer', array( $this, 'action_amp_post_template_footer' ) );
		}
	}

	/**
	 * TEMPLATING
	 */

	/**
	 *
	 */
	public function filter_amp_component_scripts( $scripts ) {
		$scripts['amp-analytics'] = 'https://cdn.ampproject.org/v0/amp-analytics-0.1.js';

		return $scripts;
	}

	/**
	 *
	 */
	public function action_amp_post_template_footer( $amp_template ) {
		$triggers = array(
			'trackPageview' => array(
				'on'      => 'visible',
				'request' => 'pageview',
			),
		);

		$triggers = apply_filters( 'eth_amp_analytics_wp_ga_triggers', $triggers, $amp_template );

		?>
		<amp-analytics type="googleanalytics" id="analytics1">
			<script type="application/json">
				{
					"vars": {
						"account": "<?php echo esc_js( $this->options['property_id'] ); ?>"
					},
					"triggers": <?php wp_json_encode( $triggers ); ?>
				}
			</script>
		</amp-analytics>
		<?php
	}

	/**
	 * ADMIN UI
	 */

	/**
	 *
	 */
	public function action_admin_init() {
		register_setting( 'reading', $this->option_name, array( $this, 'sanitize_options' ) );

		add_settings_section( 'eth-amp-analytics-wp', __( 'Google Analytics for AMP', 'eth-amp-analytics-wp' ), '__return_false', 'reading' );
		add_settings_field( 'eth-amp-analytics-wp-property-id', __( 'Property ID:', 'eth-amp-analytics-wp' ), array( $this, 'settings_field_property_id' ), 'reading', 'eth-amp-analytics-wp' );
	}

	/**
	 *
	 */
	public function settings_field_property_id() {
		?>
		<input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[property_id]" value="<?php echo esc_attr( $this->get_option( 'property_id' ) ); ?>" class="medium-text" />

		<p class="description"><?php printf( __( 'Google Analytics property ID, in the format %s.', 'eth-amp-analytics-wp' ), '<strong>UA-XXXXX-Y</strong>' ); ?></p>
		<?php
	}

	/**
	 *
	 */
	public function sanitize_options( $options ) {
		return $options;
	}

	/**
	 * UTILITY FUNCTIONS
	 */

	/**
	 *
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
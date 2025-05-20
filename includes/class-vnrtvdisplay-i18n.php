<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://hintergrundbewegung.de
 * @since      1.0.0
 *
 * @package    Vnrtvdisplay
 * @subpackage Vnrtvdisplay/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Vnrtvdisplay
 * @subpackage Vnrtvdisplay/includes
 * @author     Peter Mertzlin <peter.mertzlin@gmail.com>
 */
class Vnrtvdisplay_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'vnrtvdisplay',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}

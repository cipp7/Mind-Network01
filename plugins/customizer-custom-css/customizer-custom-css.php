<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;
/*
Plugin Name: Customizer Custom CSS
Description: Add Custom CSS from customizer to your WordPress website.
Version:     1.2.3
Author:      Bijay Yadav
Author URI:  http://bijayyadav.com.np
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: customizer-custom-css
*/

//loads the text domain for translation
function wp_custom_css_load_plugin_textdomain()
{
	load_plugin_textdomain('customizer-custom-css', FALSE, basename(dirname(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'wp_custom_css_load_plugin_textdomain');

//Include Plugin Info file
require dirname(__FILE__) . '/about/about.php';


//Custom CSS on Customizer
function wp_custom_css_register($wp_customize)
{

	$wp_customize->add_section('wp_custom_css_section', array(
		'priority' => 10,
		'capability' => 'edit_theme_options',
		'theme_supports' => '',
		'title' => __('Custom CSS', 'customizer-custom-css'),
		'description' => '',
	));

	$wp_customize->add_setting('wp_custom_css', array(
		'default' => '',
		'type' => 'theme_mod',
		'transport' => 'postMessage',
		'sanitize_callback'    => 'wp_kses',
	));

	$wp_customize->add_control(
		'wp_custom_css',
		array(
			'label'      => __('Add your custom CSS', 'customizer-custom-css'),
			'section'    => 'wp_custom_css_section',
			'settings'   => 'wp_custom_css',
			'type'       => 'textarea',
		)

	);

	$wp_customize->add_setting('wp_custom_css_mobile', array(
		'default' => '',
		'type' => 'theme_mod',
		'transport' => 'postMessage',
		'sanitize_callback'    => 'wp_kses',
	));

	$wp_customize->add_control(
		'wp_custom_css_mobile',
		array(
			'label'      => __('Custom CSS for Mobile', 'customizer-custom-css'),
			'description' => __('Add your custom css for mobile (< 768px)', 'customizer-custom-css'),
			'section'    => 'wp_custom_css_section',
			'settings'   => 'wp_custom_css_mobile',
			'type'       => 'textarea',
		)

	);

	$wp_customize->add_setting('wp_custom_css_tablet', array(
		'default' => '',
		'type' => 'theme_mod',
		'transport' => 'postMessage',
		'sanitize_callback'    => 'wp_kses',
	));

	$wp_customize->add_control(
		'wp_custom_css_tablet',
		array(
			'label'      => __('Custom CSS for Tablet', 'customizer-custom-css'),
			'description' => __('Add your custom css for tablet (>= 768px)', 'customizer-custom-css'),
			'section'    => 'wp_custom_css_section',
			'settings'   => 'wp_custom_css_tablet',
			'type'       => 'textarea',
		)

	);

	$wp_customize->add_setting('wp_custom_css_desktop', array(
		'default' => '',
		'type' => 'theme_mod',
		'transport' => 'postMessage',
		'sanitize_callback'    => 'wp_kses',
	));

	$wp_customize->add_control(
		'wp_custom_css_desktop',
		array(
			'label'      => __('Custom CSS for Desktop', 'customizer-custom-css'),
			'description' => __('Add your custom css for desktop (>= 1024px)', 'customizer-custom-css'),
			'section'    => 'wp_custom_css_section',
			'settings'   => 'wp_custom_css_desktop',
			'type'       => 'textarea',
		)

	);
}
add_action('customize_register', 'wp_custom_css_register');

// JS for live customizer preview

function wp_custom_css_script()
{
	wp_enqueue_script('custom_css_script', plugin_dir_url(__FILE__) . 'customizer-custom-css.js', array('customize-preview'), '20140804', true);
}
add_action('customize_preview_init', 'wp_custom_css_script');

//outputs custom css on frontend
if (!function_exists('wp_custom_css_add_custom_css')) :

	function wp_custom_css_add_custom_css()
	{

		echo '<style id="wp-custom-css">';
		if (!empty(get_theme_mod('wp_custom_css'))) {
			echo wp_kses_post(get_theme_mod('wp_custom_css'));
		}
		if (!empty(get_theme_mod('wp_custom_css'))) {
			echo '@media screen and (max-width: 767px) {';
			echo wp_kses_post(get_theme_mod('wp_custom_css_mobile'));
			echo '}';
		}
		if (!empty(get_theme_mod('wp_custom_css'))) {
			echo '@media screen and (min-width: 768px) {';
			echo wp_kses_post(get_theme_mod('wp_custom_css_tablet'));
			echo '}';
		}
		if (!empty(get_theme_mod('wp_custom_css'))) {
			echo '@media screen and (min-width: 1024px) {';
			echo wp_kses_post(get_theme_mod('wp_custom_css_desktop'));
			echo '}';
		}
		echo '</style>';
	}

endif;
add_action('wp_head', 'wp_custom_css_add_custom_css', 1000);

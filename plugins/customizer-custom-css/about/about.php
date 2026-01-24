<?php

/**
 * Added customizer Page.
 */

/**
 * Add a new page under Appearance
 */
function customizer_menu()
{
	add_menu_page(__('CSS Options', 'customizer-custom-css'), __('CSS Options', 'customizer-custom-css'), 'edit_theme_options', 'custom-css', 'bj_custom_css');
}
add_action('admin_menu', 'customizer_menu');

/**
 * Enqueue styles for the help page.
 */
function customizer_custom_css_admin_scripts()
{
	if (is_admin()) {
		wp_enqueue_style('customizer-custom-css-admin-style', plugin_dir_url(__FILE__) . 'about.css', array(), '');
	}
}
add_action('admin_enqueue_scripts', 'customizer_custom_css_admin_scripts');

/**
 * Add the plugin info page
 */
function bj_custom_css()
{
?>
	<div class="das-wrap">
		<div class="customizer-panel">
			<div class="customizer-title">
				<h2> <?php _e('Customizer Custom CSS', 'customizer-custom-css'); ?> </h2>
			</div>
			<p>
				<?php esc_html_e('A perfect simple and lightweight plugin to add CSS for all devices mobile, tablet or desktop. Preview the output as you write the CSS code.', 'customizer-custom-css'); ?></p>
			<a class="btn btn-primary" href="<?php echo esc_url(admin_url('/customize.php?'));
												?>"><?php esc_html_e('Plugin Settings - Click Here', 'customizer-custom-css'); ?></a>
		</div>
		<div class="customizer-panel">
			<div class="customizer-panel-content">
				<div class="customizer-title">
					<h3><?php esc_html_e('If you like the theme, please leave a review', 'customizer-custom-css'); ?></h3>
				</div>
				<a href="https://wordpress.org/plugins/customizer-custom-css/#reviews" target="_blank" class="btn btn-secondary"><?php esc_html_e('Rate this plugin', 'customizer-custom-css'); ?></a>
			</div>
		</div>
	</div>
<?php
}

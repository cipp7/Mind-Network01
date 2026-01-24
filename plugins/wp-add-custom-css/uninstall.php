<?php
// exit if accessed directly
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

$option_name = 'wpacc_settings';
delete_option($option_name);
delete_site_option($option_name);
delete_post_meta_by_key('_single_add_custom_css');

$upload_dir = wp_upload_dir();
$custom_css_dir = trailingslashit($upload_dir['basedir']) . 'wp-add-custom-css';
$custom_css_file = trailingslashit($custom_css_dir) . 'custom-css.css';
if ( file_exists( $custom_css_file ) ) {
    @unlink( $custom_css_file );
}
if ( is_dir( $custom_css_dir ) ) {
    @rmdir( $custom_css_dir );
}
?>

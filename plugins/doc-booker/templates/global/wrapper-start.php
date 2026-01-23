<?php
/**
 * Content wrappers
 *
 * @package     WpdDocBooker/Templates
 * @version     1.0.0
 */


use WpDreamers\WPDDB\Controllers\Helper\Helper;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
$template = Helper::get_theme_slug_for_templates();

switch ($template) {
    case 'twentyten' :
        echo '<div id="container"><div id="content" role="main">';
        break;
    case 'twentyeleven' :
        echo '<div id="primary"><div id="content" role="main" class="twentyeleven">';
        break;
    case 'twentytwelve' :
        echo '<div id="primary" class="site-content"><div id="content" role="main" class="twentytwelve">';
        break;
    case 'twentythirteen' :
        echo '<div id="primary" class="site-content"><div id="content" role="main" class="entry-content twentythirteen">';
        break;
    case 'twentyfourteen' :
        echo '<div id="primary" class="content-area"><div id="content" role="main" class="site-content twentyfourteen"><div class="tfwc">';
        break;
    case 'twentyfifteen' :
        echo '<div id="primary" role="main" class="content-area twentyfifteen"><div id="main" class="site-main t15wc">';
        break;
    case 'twentysixteen' :
        echo '<div id="primary" class="content-area twentysixteen"><main id="main" class="site-main" role="main">';
        break;
	case 'twentytwenty' :
		echo '<div id="primary" class="content-area section-inner"><main id="main" class="site-main" role="main">';
		break;
	case 'oceanwp' :
		echo '<div id="primary" class="content-area section-inner">';
		break;
    default :
        echo '<div id="primary" class="content-area container '.esc_attr($template).'-'.'theme"><main id="main" class="site-main" role="main">';
        break;
}
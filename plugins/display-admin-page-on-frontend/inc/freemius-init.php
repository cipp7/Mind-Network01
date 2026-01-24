<?php

// Create a helper function for easy SDK access.
if ( !function_exists( 'dapof_fs' ) ) {
    function wpfa_maybe_auto_activate_license(  $dapof_fs, $license_key  ) {
        $cache_key = 'wpfa_dont_activate_license' . md5( $license_key );
        if ( !get_site_transient( $cache_key ) ) {
            try {
                $site = $dapof_fs->get_site_info( array(
                    'blog_id' => get_current_blog_id(),
                ) );
                $results = $dapof_fs->opt_in(
                    false,
                    false,
                    false,
                    $license_key,
                    false,
                    false,
                    false,
                    null,
                    array($site)
                );
                if ( is_object( $results ) && property_exists( $results, 'error' ) && is_string( $results->error ) ) {
                    set_site_transient( $cache_key, $results->error, HOUR_IN_SECONDS * 6 );
                }
            } catch ( \Throwable $e ) {
                set_site_transient( $cache_key, $e->getMessage(), HOUR_IN_SECONDS * 6 );
            }
        }
    }

    function dapof_fs() {
        global $dapof_fs;
        if ( !isset( $dapof_fs ) ) {
            // Activate multisite network integration.
            if ( !defined( 'WP_FS__PRODUCT_1877_MULTISITE' ) ) {
                define( 'WP_FS__PRODUCT_1877_MULTISITE', true );
            }
            $dapof_fs = fs_dynamic_init( array(
                'id'              => '1877',
                'slug'            => 'display-admin-page-on-frontend',
                'type'            => 'plugin',
                'public_key'      => 'pk_64475c4417669fbcc17c076e31b38',
                'is_premium'      => false,
                'has_addons'      => false,
                'has_paid_plans'  => true,
                'trial'           => array(
                    'days'               => 7,
                    'is_require_payment' => true,
                ),
                'has_affiliation' => 'selected',
                'menu'            => array(
                    'slug'        => 'wpatof_welcome_page',
                    'first-path'  => 'admin.php?page=wpatof_welcome_page',
                    'support'     => false,
                    'affiliation' => false,
                    'network'     => true,
                ),
                'is_live'         => true,
            ) );
        }
        return $dapof_fs;
    }

    // Init Freemius.
    dapof_fs();
    // Signal that SDK was initiated.
    do_action( 'dapof_fs_loaded' );
}
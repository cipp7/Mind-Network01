<?php

namespace WpDreamers\WPDDB\Controllers;
// Do not allow directly accessing this file.
use WpDreamers\WPDDB\Controllers\Admin\CustomUserRole;
use WpDreamers\WPDDB\Controllers\Helper\Helper;
use WpDreamers\WPDDB\Controllers\Model\DatabaseTable;

if ( ! defined( 'ABSPATH' ) ) {
	exit( 'This script cannot be accessed directly.' );
}

class Installation {
	public static function activate() {
		if ( ! get_option( 'wpddb_version' ) ) {
			self::create_options();
		}
		DatabaseTable::create_custom_db_table();
		CustomUserRole::create_custom_user_roles();
		self::update_wpddb_version();
		do_action( 'wpddb_flush_rewrite_rules' );
		do_action( 'wpddb_installed' );
	}

	private static function update_wpddb_version() {
		update_option( 'wpddb_version', WPDDB_VERSION );

	}

	private static function create_options() {

		$options = [
			'wpddb_permalinks_settings' => [
				'doctor_base'          => 'wpddb_doctor',
				'doctor_category_base' => 'wpddb_doctor_category',
				'clinic_base'          => 'wpddb_clinic',
			],
			'wpddb_doctor_settings'     => [
				'doctor_archive_style'       => 'layout-1',
				'doctor_posts_per_page'      => '9',
				'doctor_grid_columns'        => '3',
				'doctor_page_layout'         => 'full-width',
				'doctor_single_page_layout'  => 'full-width',
				'doctor_orderBy'             => 'none',
				'doctor_order'               => 'ASC',
				'doctor_thumbnail_width'     => '570',
				'doctor_thumbnail_height'    => '400',
				'doctor_thumbnail_hard_crop' => 'on',
				'slider_autoplay'            => 'on',
				'slider_loop'                => 'on',
				'centered_slider'            => 'on',
				'slides_per_view'            => '3',
				'display_doctor_schedule'    => 'on',
				'call_for_booking'           => 'on',
				'showing_bookable_text'      => 'on',
			],
			'wpddb_clinic_settings'     => [
				'clinic_posts_per_page'      => '9',
				'clinic_grid_columns'        => '3',
				'clinic_archive_style'       => 'layout-1',
				'clinic_page_layout'         => 'full-width',
				'clinic_single_page_layout'  => 'full-width',
				'clinic_post_label'          => __( 'Clinic', 'doc-booker' ),
				'clinic_orderBy'             => 'none',
				'clinic_order'               => 'ASC',
				'clinic_thumbnail_width'     => '570',
				'clinic_thumbnail_height'    => '400',
				'clinic_thumbnail_hard_crop' => 'on',
			],
			'wpddb_style_settings'      => [
				'wpddb_primary_color'   => '#5d3dfd',
				'wpddb_secondary_color' => '#ebf3fc',
			],
            'wpddb_global_settings'      => [
                'online_booking_payment'   => 'on',
                'payment_gateway' => 'stripe',
                'stripe_mode' => 'test',
                'razorpay_mode' => 'test',
                'currency' => 'USD',
                'currency_position' => 'left',
            ],

		];

		foreach ( $options as $option_name => $defaults ) {
			if ( false === get_option( $option_name ) ) {
				add_option( $option_name, $defaults );
			}
		}

		$pages = Helper::insert_custom_pages();

		if ( is_array( $pages ) && ! empty( $pages ) ) {
			$pSettings = get_option( 'wpddb_page_settings' ) ?: [];
			foreach ( $pages as $pSlug => $pId ) {
				$pSettings[ $pSlug ] = $pId;
			}
			update_option( 'wpddb_page_settings', $pSettings );
		}

	}

	public static function deactivation() {
		flush_rewrite_rules();
	}

	public static function check_and_update_plugin_version() {
		$installed_version = get_option( 'wpddb_version' );

		if ( $installed_version !== WPDDB_VERSION ) {
            self::database_migration();
			update_option( 'wpddb_version', WPDDB_VERSION );
		}
	}
    public static function database_migration(  ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpddb_bookings';
        $transaction_id_column_exists = $wpdb->get_results("SHOW COLUMNS FROM `$table_name` LIKE 'transaction_id'");
        $amount_column_exists = $wpdb->get_results("SHOW COLUMNS FROM `$table_name` LIKE 'amount_paid'");
        $payment_column_exists = $wpdb->get_results("SHOW COLUMNS FROM `$table_name` LIKE 'payment_status'");
        $payment_by_column_exists = $wpdb->get_results("SHOW COLUMNS FROM `$table_name` LIKE 'payment_by'");

        if (empty($transaction_id_column_exists)) {
            $wpdb->query("ALTER TABLE `$table_name` ADD `transaction_id` VARCHAR(200) DEFAULT NULL");
        }
        if (empty($amount_column_exists)){
            $wpdb->query("ALTER TABLE `$table_name` 
            ADD `amount_paid` DECIMAL(10, 2) DEFAULT 0.00");
        }
        if (empty($payment_column_exists)) {
            $wpdb->query("ALTER TABLE `$table_name` ADD `payment_status` VARCHAR(50) DEFAULT NULL");
        }
        if (empty($payment_by_column_exists)) {
            $wpdb->query("ALTER TABLE `$table_name` ADD `payment_by` VARCHAR(50) DEFAULT NULL");
        }
    }
}
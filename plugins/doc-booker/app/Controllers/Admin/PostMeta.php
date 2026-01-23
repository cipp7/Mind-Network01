<?php

namespace WpDreamers\WPDDB\Controllers\Admin;

use WpDreamers\WPDDB\Traits\SingletonTrait;

if ( ! defined( 'ABSPATH' ) ) {
	exit( 'This script cannot be accessed directly.' );
}

class PostMeta {
	use SingletonTrait;

	private $nonce_action = 'wpddb_metabox_nonce';
	private $nonce_name = 'wpddb_metabox_nonce_secret';

	public function __construct() {
		add_action( 'add_meta_boxes', [ $this, 'add_wpddb_meta_box' ], 10 );
		add_action( 'save_post_wpddb_doctor', [ $this, 'save_wpddb_doctor_post_meta' ], 10, 2 );
		add_action( 'save_post_wpddb_clinic', [ $this, 'save_wpddb_clinic_post_meta' ], 10, 2 );
		add_action( 'save_post_wpd_doctor_shortcode', [ $this, 'save_wpddb_doctor_shortcode_post_meta' ], 10, 2 );
		add_action( 'save_post_wpd_clinic_shortcode', [ $this, 'save_wpddb_clinic_shortcode_post_meta' ], 10, 2 );
		add_filter( 'use_block_editor_for_post_type', function ( $use_block_editor, $post_type ) {
			if ( in_array( $post_type, [ wpddb()->post_type_doctor, wpddb()->post_type_clinic,wpddb()->post_type_doctor_shortcode,wpddb()->post_type_clinic_shortcode ], true ) ) {
				return false; // Disable Gutenberg for these post types
			}

			return $use_block_editor;
		}, 10, 2 );
	}

	public function add_wpddb_meta_box( $post ) {
		add_meta_box(
			'wpddb_doctor_post_meta',
			'Doctor Information',
			[ $this, 'render_wpddb_doctor_meta_box' ],
			[ wpddb()->post_type_doctor ],
			'normal',
			'high'
		);
		add_meta_box(
			'wpddb_clinic_post_meta',
			'Clinic Information',
			[ $this, 'render_wpddb_clinic_meta_box' ],
			[ wpddb()->post_type_clinic ],
			'normal',
			'high'
		);
        add_meta_box(
            'wpddb_doctor_shortcode_post_meta',
            'Shortcode Settings',
            [ $this, 'render_wpddb_doctor_shortcode_meta_box' ],
            [ wpddb()->post_type_doctor_shortcode ],
            'normal',
            'high'
        );
        add_meta_box(
            'wpddb_clinic_shortcode_post_meta',
            'Shortcode Settings',
            [ $this, 'render_wpddb_clinic_shortcode_meta_box' ],
            [ wpddb()->post_type_clinic_shortcode ],
            'normal',
            'high'
        );
	}

	public function render_wpddb_doctor_meta_box( $post ) {

		wp_nonce_field( $this->nonce_action, $this->nonce_name );

		echo '<div id="wpddb-doctor-meta-container"></div>';
	}
	public function render_wpddb_clinic_meta_box( $post ) {

		wp_nonce_field( $this->nonce_action, $this->nonce_name );

		echo '<div id="wpddb-clinic-meta-container"></div>';
	}

    public function render_wpddb_doctor_shortcode_meta_box($post) {
        wp_nonce_field( $this->nonce_action, $this->nonce_name );

        echo '<div id="wpddb-doctor-shortcode-meta-container"></div>';
    }
    public function render_wpddb_clinic_shortcode_meta_box($post) {
        wp_nonce_field( $this->nonce_action, $this->nonce_name );

        echo '<div id="wpddb-clinic-shortcode-meta-container"></div>';
    }

	public function save_wpddb_doctor_post_meta( $post_id, $post ) {

		// Verify nonce
		if ( ! isset( $_POST[ $this->nonce_name ] ) || ! wp_verify_nonce( $_POST[ $this->nonce_name ], $this->nonce_action ) ) {
			return $post_id;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}
		// Check user capabilities
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		if (  isset( $_POST['wpddb_doctor_designation'] ) ) {
			update_post_meta( $post_id, 'wpddb_doctor_designation', sanitize_text_field( $_POST['wpddb_doctor_designation'] ) );
		}
		if (  isset( $_POST['wpddb_doctor_speciality'] ) ) {
			update_post_meta( $post_id, 'wpddb_doctor_speciality', sanitize_text_field( $_POST['wpddb_doctor_speciality'] ) );
		}
		if (  isset( $_POST['wpddb_doctor_workplace'] ) ) {
			update_post_meta( $post_id, 'wpddb_doctor_workplace', sanitize_text_field( $_POST['wpddb_doctor_workplace'] ) );
		}
		if (  isset( $_POST['wpddb_doctor_degree'] ) ) {
			update_post_meta( $post_id, 'wpddb_doctor_degree', sanitize_text_field( $_POST['wpddb_doctor_degree'] ) );
		}
        $doctor_schedule = json_decode(stripslashes($_POST['wpddb_doctor_schedule']), true);
        if (!empty($doctor_schedule) && is_array($doctor_schedule)) {
            $processed_schedule = [];
            $doctor_available_clinic_ids = [];
            foreach ($doctor_schedule as $day_data) {
                if (empty($day_data['available']) || empty($day_data['clinics'])) {
                    continue;
                }

                $sanitized_clinics = [];

                foreach ($day_data['clinics'] as $clinic) {
                    if (empty($clinic['id']) || empty($clinic['name'])) {
                        continue;
                    }
                    $clinic_id = sanitize_text_field($clinic['id']);
                    $doctor_available_clinic_ids[] = $clinic_id;
                    $sanitized_clinics[] = [
                        'id'      => sanitize_text_field($clinic['id']),
                        'name'    => sanitize_text_field($clinic['name']),
                        'timings' => array_map(function ($timing) {
                            return [
                                'time'        => sanitize_text_field($timing['time']),
                                'is_bookable' => filter_var($timing['is_bookable'], FILTER_VALIDATE_BOOLEAN),
                            ];
                        }, $clinic['timings'] ?? [])
                    ];
                }

                $processed_schedule[] = [
                    'available' => true,
                    'day'       => sanitize_text_field($day_data['day']),
                    'clinics'   => $sanitized_clinics,
                ];
            }


            $doctor_available_clinic_ids = array_values(array_unique($doctor_available_clinic_ids));

            update_post_meta($post_id, 'wpddb_doctor_schedule', $processed_schedule);
            update_post_meta($post_id, 'wpddb_doctor_available_clinic', $doctor_available_clinic_ids);

        } else {
            delete_post_meta($post_id, 'wpddb_doctor_schedule');
            delete_post_meta($post_id, 'wpddb_doctor_available_clinic');
        }

        //  Sanitize and Save Clinic Info (Under Clinic ID)
        $clinic_info = json_decode(stripslashes($_POST['wpddb_clinics_info']), true);
        if (!empty($clinic_info) && is_array($clinic_info)) {
            $processed_clinic_info = [];
            foreach ($clinic_info as $key=>$clinic) {

                if (empty($key)) {
                    continue;
                }

                $clinic_id = sanitize_text_field($key);

                $processed_clinic_info[$clinic_id] = [
                    'phone_number'  => sanitize_text_field($clinic['phone_number']),
                    'is_holiday'    => filter_var($clinic['is_holiday'], FILTER_VALIDATE_BOOLEAN),
                    'holiday_dates' => [
                        'start_date' => sanitize_text_field($clinic['holiday_dates']['start_date'] ?? ''),
                        'end_date'   => sanitize_text_field($clinic['holiday_dates']['end_date'] ?? ''),
                    ],
                ];
            }
            update_post_meta($post_id, 'wpddb_clinics_info', $processed_clinic_info);
        } else {
            delete_post_meta($post_id, 'wpddb_clinics_info');
        }

        do_action( 'wpddb_save_doctor_post_meta_pro', $post_id, $_POST );

	}

    function save_wpddb_clinic_post_meta($post_id, $post) {
        // Verify nonce
        if (!isset($_POST[$this->nonce_name]) || !wp_verify_nonce($_POST[$this->nonce_name], $this->nonce_action)) {
            return $post_id;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        // Check user capabilities
        if (!current_user_can('edit_post', $post_id)) {
            return $post_id;
        }

        if (isset($_POST['wpddb_clinic_email'])) {
            update_post_meta($post_id, 'wpddb_clinic_email', sanitize_email($_POST['wpddb_clinic_email']));
        }
        if (isset($_POST['wpddb_clinic_hotline'])) {
            update_post_meta($post_id, 'wpddb_clinic_hotline', sanitize_text_field($_POST['wpddb_clinic_hotline']));
        }
        if (isset($_POST['wpddb_clinic_latitude'])) {
            update_post_meta($post_id, 'wpddb_clinic_latitude', sanitize_text_field($_POST['wpddb_clinic_latitude']));
        }
        if (isset($_POST['wpddb_clinic_longitude'])) {
            update_post_meta($post_id, 'wpddb_clinic_longitude', sanitize_text_field($_POST['wpddb_clinic_longitude']));
        }
        if (isset($_POST['wpddb_clinic_address'])) {
            update_post_meta($post_id, 'wpddb_clinic_address', sanitize_text_field($_POST['wpddb_clinic_address']));
        }

        do_action('wpddb_save_clinic_post_meta_pro', $post_id, $_POST);
    }



    public function save_wpddb_doctor_shortcode_post_meta( $post_id, $post ) {

        // Verify nonce
        if ( ! isset( $_POST[ $this->nonce_name ] ) || ! wp_verify_nonce( $_POST[ $this->nonce_name ], $this->nonce_action ) ) {
            return $post_id;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }

        // Check user capabilities
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return $post_id;
        }

        $fields = [
            'layout'                => 'sanitize_text_field',
            'post_limit'            => 'absint',
            'grid_columns'          => 'absint',
            'more_btn'              => 'sanitize_text_field',
            'btn_text'              => 'sanitize_text_field',
            'btn_url'               => 'sanitize_url',
            'img_width'             => 'absint',
            'img_height'            => 'absint',
            'img_hard_crop'         => 'sanitize_text_field',
            'order_by'              => 'sanitize_text_field',
            'order'                 => 'sanitize_text_field',
            'bg_color'              => 'sanitize_text_field',
            'title_color'           => 'sanitize_text_field',
            'department_color'      => 'sanitize_text_field',
            'content_color'         => 'sanitize_text_field',
        ];

        // Save normal fields
        foreach ( $fields as $field => $sanitize_callback ) {
            $field_key = 'wpddb_doctor_' . $field;
            if ( isset( $_POST[ $field_key ] ) ) {
                $value = call_user_func( $sanitize_callback, $_POST[ $field_key ] );
                update_post_meta( $post_id, $field_key, $value );
            }
        }

        $array_fields = [ 'include', 'exclude', 'categories' ];
        foreach ( $array_fields as $array_field ) {
            $field_key = 'wpddb_doctor_' . $array_field;
            if ( isset( $_POST[ $field_key ] ) ) {
                $raw_value = stripslashes( $_POST[ $field_key ] );
                $decoded_value = json_decode( $raw_value, true );

                if ( is_array( $decoded_value ) ) {
                    $sanitized_array = array_map( 'sanitize_text_field', $decoded_value );
                    update_post_meta( $post_id, $field_key, $sanitized_array );
                } else {
                    update_post_meta( $post_id, $field_key, [] );
                }
            }
        }

        do_action( 'wpddb_save_doctor_shortcode_post_meta_pro', $post_id, $_POST );

    }

    public function save_wpddb_clinic_shortcode_post_meta( $post_id, $post ) {

        // Verify nonce
        if ( ! isset( $_POST[ $this->nonce_name ] ) || ! wp_verify_nonce( $_POST[ $this->nonce_name ], $this->nonce_action ) ) {
            return $post_id;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }

        // Check user capabilities
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return $post_id;
        }

        $fields = [
            'layout'                => 'sanitize_text_field',
            'post_limit'            => 'absint',
            'grid_columns'          => 'absint',
            'more_btn'              => 'sanitize_text_field',
            'btn_text'              => 'sanitize_text_field',
            'btn_url'               => 'sanitize_url',
            'img_width'             => 'absint',
            'img_height'            => 'absint',
            'img_hard_crop'         => 'sanitize_text_field',
            'order_by'              => 'sanitize_text_field',
            'order'                 => 'sanitize_text_field',
            'bg_color'              => 'sanitize_text_field',
            'title_color'           => 'sanitize_text_field',
            'content_color'         => 'sanitize_text_field',
        ];

        // Save normal fields
        foreach ( $fields as $field => $sanitize_callback ) {
            $field_key = 'wpddb_clinic_' . $field;
            if ( isset( $_POST[ $field_key ] ) ) {
                $value = call_user_func( $sanitize_callback, $_POST[ $field_key ] );
                update_post_meta( $post_id, $field_key, $value );
            }
        }

        $array_fields = [ 'include', 'exclude' ];
        foreach ( $array_fields as $array_field ) {
            $field_key = 'wpddb_clinic_' . $array_field;
            if ( isset( $_POST[ $field_key ] ) ) {
                $raw_value = stripslashes( $_POST[ $field_key ] );
                $decoded_value = json_decode( $raw_value, true );

                if ( is_array( $decoded_value ) ) {
                    $sanitized_array = array_map( 'sanitize_text_field', $decoded_value );
                    update_post_meta( $post_id, $field_key, $sanitized_array );
                } else {
                    update_post_meta( $post_id, $field_key, [] );
                }
            }
        }
        do_action( 'wpddb_save_clinic_shortcode_post_meta_pro', $post_id, $_POST );

    }


}
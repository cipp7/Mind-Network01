<?php

namespace WpDreamers\WPDDB\Controllers\Admin\Api;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WpDreamers\WPDDB\Controllers\Helper\Helper;
use WpDreamers\WPDDB\Controllers\WpddbOptions;
use WpDreamers\WPDDB\Traits\Constants;
use WpDreamers\WPDDB\Traits\SingletonTrait;

if ( ! defined( 'ABSPATH' ) ) {
	exit( 'This script cannot be accessed directly.' );
}

class RestApi {
	use SingletonTrait, Constants;

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'wpddb_register_rest_api_endpoint' ] );

	}

	public function wpddb_register_rest_api_endpoint() {
		register_rest_route( $this->doctor_endpoint_namespace, '/posts', array(
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_all_doctors' ],
			'permission_callback' => '__return_true',
		) );
		register_rest_route( $this->doctor_endpoint_namespace, '/categories', array(
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_all_rest_doctor_categories' ],
			'permission_callback' => '__return_true',
		) );
		register_rest_route( $this->clinic_endpoint_namespace, '/posts', array(
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_all_rest_clinics' ],
			'permission_callback' => '__return_true',
		) );
		register_rest_route( $this->wpddb_endpoint_namespace, '/pages', array(
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_all_pages' ],
			'permission_callback' => '__return_true',
		) );
		register_rest_route( $this->wpddb_endpoint_namespace, '/options', array(
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_all_settings_options' ],
			'permission_callback' => array( $this, 'check_admin_permission' ),
		) );

		// Get departments
		register_rest_route( 'get-doctor/v1', '/departments', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_departments' ),
			'permission_callback' => '__return_true'
		) );

		// Get doctor by department
		register_rest_route( 'get-doctor-by-departments/v1', '/(?P<id>\d+)', array(
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_doctor_by_departments' ],
			'permission_callback' => '__return_true',
		) );

		// Get doctor schedule
		register_rest_route( 'doctor-details-booking/v1', '/doctors/(?P<id>\d+)/schedule', array(
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_doctor_schedule' ],
			'permission_callback' => array( $this, 'verify_doctor_schedule_access' ),
		) );

		// Create booking
		register_rest_route( 'doctor-details-booking/v1', '/bookings', array(
			'methods'             => 'POST',
			'callback'            => [ $this, 'create_doctor_details_booking' ],
			'permission_callback' => array( $this, 'verify_booking_creation' ),
		) );
		register_rest_route( $this->wpddb_endpoint_namespace, '/booking_details', array(
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_booking_details' ],
			'permission_callback' => array( $this, 'verify_get_booking_access' ),
		) );
	}

	public function get_all_doctors() {
		$doctor_list = [];
		$args        = array(
			'post_type'   => wpddb()->post_type_doctor,
			'post_status' => 'publish',
			'numberposts' => - 1,
		);
		$doctors     = get_posts( $args );
		if ( $doctors ) {
			foreach ( $doctors as $doctor ) {
				$doctor_list[] = [
					'value' => $doctor->ID,
					'label' => ! empty( $doctor->post_title ) ? $doctor->post_title : '#' . $doctor->ID
				];
			}
		}

		return wp_json_encode( $doctor_list );
	}

	public function get_departments() {
		$terms       = get_terms( array(
			'taxonomy' => wpddb()->doctor_category,
		) );
		$departments = array();
		if ( ! is_wp_error( $terms ) && $terms ) {
			foreach ( $terms as $term ) {
				$departments[] = array(
					'id'    => $term->term_id,
					'name'  => $term->name,
					'slug'  => $term->slug,
					'count' => $term->count
				);
			}
		}

		return wp_json_encode( $departments );
	}

	public function get_doctor_by_departments( WP_REST_Request $request ) {

		$department_id = intval( $request->get_param( 'id' ) );
		$args          = array(
			'post_type'      => wpddb()->post_type_doctor,
			'posts_per_page' => - 1,
			'tax_query'      => array(
				array(
					'taxonomy' => wpddb()->doctor_category,
					'field'    => 'term_id',
					'terms'    => $department_id,
				),
			),
		);

		$doctors     = get_posts( $args );

		$doctor_data = [];
		if ( $doctors ) {
			foreach ( $doctors as $doctor ) {
				$doctor_data[] = array(
					'id'          => $doctor->ID,
					'name'        => $doctor->post_title,
					'designation' => get_post_meta( $doctor->ID, 'wpddb_doctor_designation', true ),
					'speciality' => get_post_meta( $doctor->ID, 'wpddb_doctor_speciality', true ),
					'degree' => get_post_meta( $doctor->ID, 'wpddb_doctor_degree', true ),
					'image'       => get_the_post_thumbnail_url( $doctor->ID, 'medium' ),
					'work_place'  => get_post_meta( $doctor->ID, 'wpddb_doctor_workplace', true ),
				);
			}
		}

		return wp_json_encode( $doctor_data );
	}

	public function get_all_rest_doctor_categories() {
		$terms_list = [];
		$terms      = get_terms( [
			'taxonomy'   => wpddb()->doctor_category,
			'hide_empty' => false
		] );
		if ( ! is_wp_error( $terms ) && $terms ) {
			foreach ( $terms as $term ) {
				$terms_list[] = [
					'value' => $term->term_id,
					'label' => $term->name,
				];
			}
		}

		return wp_json_encode( $terms_list );

	}

	public function get_all_rest_clinics() {
		$clinic_list = [];
		$args        = array(
			'post_type'   => wpddb()->post_type_clinic,
			'post_status' => 'publish',
			'numberposts' => - 1,
		);
		$clinics     = get_posts( $args );
		if ( $clinics ) {
			foreach ( $clinics as $clinic ) {
				$clinic_list[] = [
					'value' => $clinic->ID,
					'label' => ! empty( $clinic->post_title ) ? $clinic->post_title : '#' . $clinic->ID
				];
			}
		}

		return wp_json_encode( $clinic_list );

	}

	public function get_all_pages( $data ) {
		$page_list = [];
		$pages     = get_pages(
			[
				'sort_column'  => 'menu_order',
				'sort_order'   => 'ASC',
				'hierarchical' => 0,
			]
		);
		if ( $pages ) {
			foreach ( $pages as $page ) {
				$page_list[] = [
					'value' => $page->ID,
					'label' => ! empty( $page->post_title ) ? $page->post_title : '#' . $page->ID
				];
			}
		}

		return wp_json_encode( $page_list );
	}

	public function get_all_settings_options() {
		return WpddbOptions::wpddb_get_all_settings_options();
	}

	public function check_admin_permission( WP_REST_Request $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );

		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) || ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'rest_forbidden', __( 'You are not allowed to access this endpoint.', 'doc-booker' ), array( 'status' => 403 ) );
		}

		return true;
	}

	function verify_doctor_schedule_access( WP_REST_Request $request ) {
		// Check if user is logged in
		if ( ! is_user_logged_in() ) {
			// For public access, we can implement nonce verification
			$nonce = $request->get_header( 'X-WP-Nonce' );
			if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
				return false;
			}
		}

		$doctor_id = intval( $request->get_param( 'id' ) );
		$doctor    = get_post( $doctor_id );


		if ( ! $doctor || $doctor->post_status !== 'publish' || $doctor->post_type !== wpddb()->post_type_doctor ) {
			return false;
		}

		return true;
	}

	public function verify_get_booking_access( WP_REST_Request $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) || ! current_user_can( 'wpddb_manage_booking_system' ) ) {
			return new WP_Error( 'rest_forbidden', __( 'You are not allowed to access this endpoint.', 'doc-booker' ), array( 'status' => 403 ) );
		}

		return true;
	}

	public function verify_booking_creation( WP_REST_Request $request ) {
		// Verify nonce
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return false;
		}

		// Additional verification as needed
		$params = $request->get_params();


		// Verify doctor exists and is active
		if ( isset( $params['doctorId'] ) ) {
			$doctor = get_post( $params['doctorId'] );
			if ( ! $doctor || $doctor->post_status !== 'publish' || $doctor->post_type !== wpddb()->post_type_doctor ) {
				return false;
			}
		}

		return true;
	}

	public function get_doctor_schedule( $request ) {
		$doctor_id = intval( $request->get_param( 'id' ) );

		// Validate doctor ID
		if ( ! get_post( $doctor_id ) || get_post_type( $doctor_id ) !== wpddb()->post_type_doctor ) {
			return new WP_REST_Response( array( 'error' => 'Doctor not found' ), 404 );
		}

		// Get doctor schedule from post meta
		$schedules = get_post_meta( $doctor_id, 'wpddb_doctor_schedule', true );

		if ( ! $schedules ) {
			return new WP_REST_Response( array( 'error' => 'No schedule found for this doctor' ), 404 );
		}
		foreach ( $schedules as &$day_schedule ) {
			foreach ( $day_schedule['clinics'] as &$clinic ) {
				usort( $clinic['timings'], function ( $a, $b ) {
					return strtotime( $a['time'] ) - strtotime( $b['time'] );
				} );
			}
		}

		return new WP_REST_Response( $schedules, 200 );
	}

	public function create_doctor_details_booking( $request ) {
		$params = $request->get_params();

		// Validate required fields
		if (
			! isset( $params['doctorId'] ) ||
			! isset( $params['day'] ) ||
			! isset( $params['clinicId'] ) ||
			! isset( $params['time'] ) ||
			! isset( $params['patient'] ) ||
			! isset( $params['patient']['name'] ) ||
			! isset( $params['patient']['email'] ) ||
			! isset( $params['patient']['phone'] )
		) {
			return new WP_REST_Response( array( 'error' => 'Missing required fields' ), 400 );
		}

		$doctor_id        = intval( $params['doctorId'] );
		$day              = sanitize_text_field( $params['day'] );
		$clinic_id        = intval( $params['clinicId'] );
		$time             = sanitize_text_field( $params['time'] );
        $online_booking_payment = WpddbOptions::get_option('online_booking_payment','wpddb_global_settings') ?:'on';
        $transaction_id = '';
        $amount_paid = 0.00;
        $payment_status = '';
        if ( wpddb()->has_pro() && $online_booking_payment === 'on' && ! empty( $params['transactionId'] ) ) {
            $transaction_id = sanitize_text_field( $params['transactionId'] );
            $payment_by = sanitize_text_field( $params['paymentGateway'] );
            $amount_paid = floatval( $params['amountPaid'] ?? 0 );
            $payment_status = 'completed';
        }

		$sanitize_patient = [
			'name'  => sanitize_text_field( $params['patient']['name'] ),
			'phone' => sanitize_text_field( $params['patient']['phone'] ),
			'email' => sanitize_email( $params['patient']['email'] ),
			'note'  => sanitize_text_field( $params['patient']['notes'] ) ?? ''
		];

		$booking_id       = Helper::generate_booking_id();

		// Validate doctor exists
		if ( ! get_post( $doctor_id ) || get_post_type( $doctor_id ) !== wpddb()->post_type_doctor ) {
			return new WP_REST_Response( array( 'error' => 'Doctor not found' ), 404 );
		}

		// Get doctor schedule
		$schedule = get_post_meta( $doctor_id, 'wpddb_doctor_schedule', true );

		if ( ! $schedule ) {
			return new WP_REST_Response( array( 'error' => 'No schedule found for this doctor' ), 404 );
		}

		// Find the day in the schedule
		$day_index    = - 1;
		$clinic_index = - 1;
		$time_index   = - 1;

		foreach ( $schedule as $index => $day_item ) {
			if ( $day_item['day'] === $day ) {
				$day_index = $index;

				// Find the clinic in the day's clinics
				foreach ( $day_item['clinics'] as $c_index => $clinic ) {
					if ( $clinic['id'] == $clinic_id ) {
						$clinic_index = $c_index;

						// Find the time in the clinic's timings
						foreach ( $clinic['timings'] as $t_index => $timing ) {
							if ( $timing['time'] === $time ) {
								$time_index = $t_index;
								break;
							}
						}

						break;
					}
				}

				break;
			}
		}
		// Validate time slot exists and is bookable
		if (
			$day_index === - 1 ||
			$clinic_index === - 1 ||
			$time_index === - 1 ||
			! $schedule[ $day_index ]['clinics'][ $clinic_index ]['timings'][ $time_index ]['is_bookable']
		) {
			return new WP_REST_Response( array( 'error' => 'Time slot not available' ), 400 );
		}

		global $wpdb;
		$booking_table_name = $wpdb->prefix . 'wpddb_bookings';
		$patients_table     = $wpdb->prefix . 'wpddb_patients';
		$inserted_booking   = false;

		$patient_id         = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $patients_table WHERE email = %s",
			$sanitize_patient['email']
		) );

		if ( ! $patient_id ) {
			$wpdb->insert(
				$patients_table,
				[
					'full_name' => $sanitize_patient['name'],
					'email'     => $sanitize_patient['email'],
					'phone'     => $sanitize_patient['phone'],
				],
				[ '%s', '%s', '%s' ]
			);
			$patient_id = $wpdb->insert_id;
		}

		if ( $patient_id ) {
			$inserted_booking = $wpdb->query( $wpdb->prepare(
				"INSERT INTO $booking_table_name (booking_id, patient_id, doctor_id, clinic_id, day, time, patient_note, status,booking_present_status, created_at, updated_at,transaction_id,amount_paid,payment_status,payment_by) 
    			VALUES (%s, %d, %d, %d, %s, %s, %s,%s, %s, %s, %s,%s,%f,%s,%s)",
				$booking_id, $patient_id, $doctor_id, $clinic_id, $day, Helper::convert_time_format( $time ), $sanitize_patient['note'], 'approved', 'upcoming', current_time( 'mysql' ), current_time( 'mysql' ), $transaction_id ?? '', $amount_paid ?? 0.00, $payment_status ?? 'pending', $payment_by ?? ''
			) );
		}

		if ( ! $inserted_booking ) {
			return new WP_REST_Response( array( 'error' => 'Failed to create booking' ), 500 );
		}


		// Update doctor schedule to mark the time slot as not bookable
		$schedule[ $day_index ]['clinics'][ $clinic_index ]['timings'][ $time_index ]['is_bookable'] = '';
		update_post_meta( $doctor_id, 'wpddb_doctor_schedule', $schedule );

		// Send notification emails
		Helper::send_booking_notifications( $booking_id, $doctor_id, $day, $time, $clinic_id, $sanitize_patient );

		return new WP_REST_Response( array(
			'success'    => true,
			'booking_id' => $booking_id,
			'message'    => 'Booking created successfully'
		), 201 );
	}

	public function get_booking_details( $request ) {
		global $wpdb;
		$booking_table  = $wpdb->prefix . 'wpddb_bookings';
		$patients_table = $wpdb->prefix . 'wpddb_patients';

		$page         = $request->get_param( 'page' ) ? (int) $request->get_param( 'page' ) : 1;
		$per_page     = $request->get_param( 'per_page' ) ? (int) $request->get_param( 'per_page' ) : 10;
		$offset       = ( $page - 1 ) * $per_page;
		$search       = $request->get_param( 'search' ) ? sanitize_text_field( $request->get_param( 'search' ) ) : '';
		$order_column = $request->get_param( 'order_by' ) ? esc_sql( $request->get_param( 'order_by' ) ) : 'created_at';
		$order        = $request->get_param( 'order' ) ? esc_sql( $request->get_param( 'order' ) ) : 'DESC';

		$sql = "
			    SELECT 
			        b.id,
			        b.booking_id,
			        b.doctor_id,
			        b.clinic_id,
			        b.patient_id,
			        b.day,
			        b.time,
			        b.status,
			        b.booking_present_status,
			        b.created_at,
			        b.patient_note,
			        b.payment_status,
			        b.transaction_id,
			        b.amount_paid,
			        b.payment_by,
			        p.id as patient_id,
			        p.full_name,
			        p.phone,
			        p.email
			    FROM {$booking_table} AS b
			    LEFT JOIN {$patients_table} AS p
			        ON b.patient_id = p.id";
		if ( ! empty( $search ) ) {
			$sql .= $wpdb->prepare(
				" WHERE b.booking_id LIKE %s OR p.phone LIKE %s",
				'%' . $search . '%',
				'%' . $search . '%'
			);
		}

		$sql .= " ORDER BY {$order_column} {$order}";

		$sql .= $wpdb->prepare( " LIMIT %d OFFSET %d", $per_page, $offset );

		$results = $wpdb->get_results( $sql, ARRAY_A );

		if ( $results ) {
			foreach ( $results as &$result ) {
				$doctor_id = $result['doctor_id'];
				$clinic_id = $result['clinic_id'];
				if ( $doctor_id ) {
					$doctor_name           = get_the_title( $doctor_id );
					$result['doctor_name'] = $doctor_name;
				}
				if ( $clinic_id ) {
					$clinic_name           = get_the_title( $clinic_id );
					$result['clinic_name'] = $clinic_name;
				}
				$result['time'] = Helper::convert_time_format( $result['time'], '12' );
			}
		}

		$total_sql = "SELECT COUNT(*)
					  FROM {$booking_table} AS b
					  LEFT JOIN {$patients_table} AS p
					  ON b.booking_id = p.id";

		if ( ! empty( $search ) ) {
			$total_sql .= $wpdb->prepare(
				" WHERE b.booking_id LIKE %s OR p.phone LIKE %s",
				'%' . $search . '%',
				'%' . $search . '%'
			);
		}

		$total = $wpdb->get_var( $total_sql );

		if ( $results ) {
			$response = new WP_REST_Response( $results );
			$response->set_status( 200 );
			$response->header( 'X-WP-Total', $total );
			$response->header( 'X-WP-TotalPages', ceil( $total / $per_page ) );
		} else {
			$response = new WP_REST_Response( [] );
			$response->set_status( 200 );
			$response->header( 'X-WP-Total', 0 );
			$response->header( 'X-WP-TotalPages', 0 );
		}

		return $response;
	}

}
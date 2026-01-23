<?php

namespace WpDreamers\WPDDB\Controllers\Model;
defined( 'ABSPATH' ) || exit;
class DatabaseTable{
    public static function create_custom_db_table() {
        global $wpdb;
        if ( ! function_exists( 'dbDelta' ) ) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }
        $charset_collate = $wpdb->get_charset_collate();
        $patients_table = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wpddb_patients` (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,                 
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(50) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_email (email)
        ) $charset_collate;";

        dbDelta($patients_table);
        $sql_query = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wpddb_bookings` (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        booking_id VARCHAR(20) NOT NULL, 
        patient_id BIGINT(20) UNSIGNED NOT NULL,  
        doctor_id BIGINT(20) UNSIGNED NOT NULL,  
        clinic_id BIGINT(20) UNSIGNED NOT NULL,
        transaction_id varchar(255),
        amount_paid decimal(10, 2) DEFAULT 0.00,
        payment_status VARCHAR(50) DEFAULT NULL,
        payment_by VARCHAR(50) DEFAULT NULL,
        booking_present_status VARCHAR(20)  DEFAULT 'upcoming',
        day VARCHAR(20) NOT NULL,              
        time VARCHAR(20) NOT NULL,             
        patient_note VARCHAR(255) NULL, 
        status ENUM('approved', 'cancelled') DEFAULT 'approved',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
        PRIMARY KEY (id),
        UNIQUE KEY unique_booking_id (booking_id), 
        INDEX idx_patient_id (patient_id),           
        INDEX idx_doctor_id (doctor_id), 
        INDEX idx_day (day),                  
        INDEX idx_created_at (created_at),     
        INDEX idx_status (status),            
        INDEX idx_booking_present_status (booking_present_status),            
        INDEX idx_patient_doctor_day (patient_id, doctor_id, day), 
        CONSTRAINT fk_patient FOREIGN KEY (patient_id) REFERENCES `{$wpdb->prefix}wpddb_patients` (id) ON DELETE CASCADE
        ) $charset_collate;";
        dbDelta( $sql_query );

    }

}


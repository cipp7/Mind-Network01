<?php
// Divi Child Theme DiviSugar 2024

function child_theme_enqueue_styles() {
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css');
    wp_enqueue_script('bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', array('jquery'), null, true);
}
add_action('wp_enqueue_scripts', 'child_theme_enqueue_styles');

add_action('login_enqueue_scripts', function () {
    wp_enqueue_style('plugin-login-style', plugin_dir_url(__FILE__) . 'path-to-plugin-style.css');
    wp_enqueue_script('plugin-login-script', plugin_dir_url(__FILE__) . 'path-to-plugin-script.js', array('jquery'), null, true);
});

function custom_login_redirect($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        if (!in_array('administrator', $user->roles)) {
            return home_url();
        }
    }
    return $redirect_to;
}
add_filter('login_redirect', 'custom_login_redirect', 10, 3);

add_filter('show_admin_bar', function($show) {
    return current_user_can('administrator') ? $show : false;
});

function enable_author_support_for_doctor() {
    add_post_type_support('wpddb_doctor', 'author');
}
add_action('init', 'enable_author_support_for_doctor');

/** Force all post thumbnails to load full size */
add_filter('post_thumbnail_size', function() {
    return 'full';
});

/** Disable WordPress image compression for maximum quality */
add_filter('jpeg_quality', function() { return 100; });
add_filter('wp_editor_set_quality', function() { return 100; });

function show_logged_in_doctor_profile() {
    if (!is_user_logged_in()) {
        return '<p>Please <a href="/login">login</a> to view your profile.</p>';
    }

    $current_user_id = get_current_user_id();
    $doctor_posts = get_posts(array(
        'post_type'   => 'wpddb_doctor',
        'author'      => $current_user_id,
        'post_status' => 'publish',
        'numberposts' => 1,
    ));

    if (!empty($doctor_posts)) {
        $doctor = $doctor_posts[0];
        $output = '<h2>' . esc_html($doctor->post_title) . '</h2>';
        $output .= apply_filters('the_content', $doctor->post_content);
        $output .= get_the_post_thumbnail($doctor->ID, 'full'); // Full size image
        $output .= '<p><strong>Designation:</strong> ' . get_post_meta($doctor->ID, 'wpddb_doctor_designation', true) . '</p>';
        $output .= '<p><strong>Speciality:</strong> ' . get_post_meta($doctor->ID, 'wpddb_doctor_speciality', true) . '</p>';
        $output .= '<p><strong>Work Place:</strong> ' . get_post_meta($doctor->ID, 'wpddb_doctor_workplace', true) . '</p>';
        $output .= '<p><strong>Degree:</strong> ' . get_post_meta($doctor->ID, 'wpddb_doctor_degree', true) . '</p>';
        return $output;
    } else {
        return '<p>No profile found. Please contact admin.</p>';
    }
}
add_shortcode('my_doctor_profile', 'show_logged_in_doctor_profile');

function doctor_admin_edit_link_shortcode() {
    if (!is_user_logged_in()) return '<p>Please <a href="' . esc_url(wp_login_url()) . '">log in</a>.</p>';

    $user_id = get_current_user_id();
    $doctor_post = get_posts(array(
        'post_type'   => 'wpddb_doctor',
        'author'      => $user_id,
        'post_status' => 'publish',
        'numberposts' => 1,
    ));

    if (empty($doctor_post)) return '<p>No doctor profile assigned.</p>';

    $post_id = $doctor_post[0]->ID;
    $url = admin_url("post.php?post=$post_id&action=edit");

    return '<a href="' . esc_url($url) . '" target="_blank" class="button button-primary">Edit My Profile</a>';
}
add_shortcode('doctor_edit_link', 'doctor_admin_edit_link_shortcode');

add_action('wp_ajax_save_medication_ajax', 'handle_save_medication_ajax');
add_action('wp_ajax_nopriv_save_medication_ajax', 'handle_save_medication_ajax');

function handle_save_medication_ajax() {
    global $wpdb;
    $table = 'fzil_patient_medications';

    $inserted = $wpdb->insert($table, [
        'patient_id'    => intval($_POST['patient_id']),
        'patient_name'  => sanitize_text_field($_POST['patient_name']),
        'patient_email' => sanitize_email($_POST['patient_email']),
        'patient_age'   => intval($_POST['patient_age']),
        'disease'       => sanitize_text_field($_POST['disease']),
        'medicine'      => sanitize_textarea_field($_POST['medicine']),
        'doctor_name'   => sanitize_text_field($_POST['doctor_name']),
        'created_at'    => current_time('mysql') // Added for consistency
    ]);

    if ($inserted) {
        wp_send_json_success(['message' => '✅ Medication saved successfully.']);
    } else {
        wp_send_json_error(['message' => '❌ Failed to save medication.']);
    }
    wp_die();
}

add_action('wp_ajax_get_patient_medications', 'handle_get_patient_medications');
add_action('wp_ajax_nopriv_get_patient_medications', 'handle_get_patient_medications');

function handle_get_patient_medications() {
    global $wpdb;
    $medications_table = 'fzil_patient_medications';
    $pid = intval($_GET['patient_id'] ?? 0);
    $medications = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $medications_table WHERE patient_id = %d ORDER BY created_at DESC", $pid)
    );
    wp_send_json($medications);
}

// Fetch patient notes
add_action('wp_ajax_get_patient_notes', function() {
    global $wpdb;
    $patient_id = intval($_GET['patient_id']);
    $table = 'fzil_wpddb_patient_notes';
    $notes = $wpdb->get_results($wpdb->prepare(
        "SELECT id, note_id, note_title, note_category, note_text, created_at, created_by FROM $table WHERE patient_id = %d ORDER BY created_at DESC",
        $patient_id
    ), ARRAY_A);
    wp_send_json($notes);
});

// Save patient note
add_action('wp_ajax_save_patient_note_ajax', 'save_patient_note_ajax_callback');

function save_patient_note_ajax_callback() {
    global $wpdb;
    $table_name = 'fzil_wpddb_patient_notes';

    $note_id_field = isset($_POST['id']) && !empty($_POST['id']) ? sanitize_text_field($_POST['id']) : null;
    $patient_id = isset($_POST['patient_id']) ? intval($_POST['patient_id']) : 0;
    $note_title = isset($_POST['note_title']) ? sanitize_text_field($_POST['note_title']) : '';
    $note_category = isset($_POST['note_category']) ? sanitize_text_field($_POST['note_category']) : '';
    $note_text = isset($_POST['note_text']) ? sanitize_textarea_field($_POST['note_text']) : '';
    $current_user = wp_get_current_user();
    $created_by = $current_user->user_login ? $current_user->user_login : 'unknown';

    if ($note_id_field) {
        $updated = $wpdb->update(
            $table_name,
            [
                'note_title' => $note_title,
                'note_category' => $note_category,
                'note_text' => $note_text,
                'created_by' => $created_by,
                'created_at' => current_time('mysql'),
            ],
            ['note_id' => $note_id_field]
        );

        if ($updated !== false) {
            wp_send_json_success(['message' => 'Note updated successfully.']);
        } else {
            wp_send_json_error(['message' => 'Failed to update note.']);
        }
    } else {
        if (!$patient_id || !$note_title || !$note_text || !$note_category) {
            wp_send_json_error(['message' => 'Missing required fields for adding a note.']);
        }

        $latest_note = $wpdb->get_var("SELECT note_id FROM $table_name ORDER BY id DESC LIMIT 1");
        $num = $latest_note ? intval(substr($latest_note, 5)) + 1 : 1;
        $new_note_id = 'NOTE-' . str_pad($num, 6, '0', STR_PAD_LEFT);

        $inserted = $wpdb->insert(
            $table_name,
            [
                'note_id' => $new_note_id,
                'patient_id' => $patient_id,
                'note_title' => $note_title,
                'note_category' => $note_category,
                'note_text' => $note_text,
                'created_by' => $created_by,
                'created_at' => current_time('mysql'),
            ]
        );

        if ($inserted) {
            wp_send_json_success(['message' => 'Note added successfully.']);
        } else {
            wp_send_json_error(['message' => 'Failed to add note.']);
        }
    }
}

// Delete patient note
add_action('wp_ajax_delete_patient_note', function() {
    global $wpdb;
    $note_id = sanitize_text_field($_POST['note_id']);
    $table = 'fzil_wpddb_patient_notes';
    $deleted = $wpdb->delete($table, ['note_id' => $note_id]);

    if ($deleted !== false && $deleted > 0) {
        wp_send_json_success(['message' => 'Note deleted successfully.']);
    } else {
        wp_send_json_error(['message' => 'Failed to delete note.']);
    }
});

// Enqueue jQuery and localize AJAX URL
function doctor_registration_enqueue_scripts() {
    wp_enqueue_script('jquery');
    wp_localize_script('jquery', 'doctorAjax', [
        'ajax_url' => admin_url('admin-ajax.php')
    ]);
}
add_action('wp_enqueue_scripts', 'doctor_registration_enqueue_scripts');

// Doctor Registration AJAX
add_action('wp_ajax_doctor_registration', 'handle_doctor_registration_ajax');
add_action('wp_ajax_nopriv_doctor_registration', 'handle_doctor_registration_ajax');

function handle_doctor_registration_ajax() {
    global $wpdb;

    // Prefix-safe table names (IMPORTANT)
    $table_doctors = 'wp_doctors';
    $table_clinics = 'wp_doctor_clinics';

    // NEW fields
    $email    = sanitize_email($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    // Existing fields
    $doctor_name = sanitize_text_field($_POST['doctor_name'] ?? '');
    $designation = sanitize_text_field($_POST['designation'] ?? '');
    $speciality  = sanitize_text_field($_POST['speciality'] ?? '');
    $workplace   = sanitize_text_field($_POST['workplace'] ?? '');
    $degree      = sanitize_text_field($_POST['degree'] ?? '');
    $department  = sanitize_text_field($_POST['department'] ?? '');
    $is_clinic_admin = isset($_POST['is_clinic_admin']) ? intval($_POST['is_clinic_admin']) : 0;

    // Basic validation
    if ($doctor_name === '' || $email === '' || $password === '') {
        wp_send_json_error(['message' => 'Doctor name, email and password are required.']);
    }

    if (!is_email($email)) {
        wp_send_json_error(['message' => 'Invalid email address.']);
    }

    if (strlen($password) < 6) {
        wp_send_json_error(['message' => 'Password must be at least 6 characters.']);
    }

    // Create WP user (writes into fzil_users automatically)
    if (email_exists($email)) {
        wp_send_json_error(['message' => 'This email is already registered.']);
    }

    // You asked: doctor_name => user_login. Reality: WP will sanitize it.
    $base_login = sanitize_user($doctor_name, true);
    if ($base_login === '') $base_login = 'doctor';

    $user_login = $base_login;
    $i = 1;
    while (username_exists($user_login)) {
        $user_login = $base_login . $i;
        $i++;
        if ($i > 2000) {
            wp_send_json_error(['message' => 'Could not generate unique username.']);
        }
    }

    $user_id = wp_insert_user([
        'user_login'   => $user_login,
        'user_pass'    => $password,          // WP hashes it
        'user_email'   => $email,
        'display_name' => $doctor_name,
        'nickname'     => $doctor_name,
               
    ]);

    if (is_wp_error($user_id)) {
        wp_send_json_error(['message' => $user_id->get_error_message()]);
    }

    // Add usermeta login_type=doctor
    update_user_meta($user_id, 'login_type', 'doctor');

    // Upload profile photo (your existing logic)
    $profile_photo_url = '';
    $profile_photo_id  = 0;

    if (!empty($_FILES['profile_photo']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $file = $_FILES['profile_photo'];
        $upload_overrides = ['test_form' => false];
        $movefile = wp_handle_upload($file, $upload_overrides);

        if ($movefile && !isset($movefile['error'])) {
            $file_url  = $movefile['url'];
            $file_path = $movefile['file'];
            $file_type = wp_check_filetype($file_path, null);

            $attachment = [
                'post_mime_type' => $file_type['type'],
                'post_title'     => sanitize_file_name($file['name']),
                'post_content'   => '',
                'post_status'    => 'inherit'
            ];

            $profile_photo_id = wp_insert_attachment($attachment, $file_path);
            $attach_data = wp_generate_attachment_metadata($profile_photo_id, $file_path);
            wp_update_attachment_metadata($profile_photo_id, $attach_data);

            $profile_photo_url = $file_url;
        } else {
            // Rollback user if upload fails (optional but cleaner)
            wp_delete_user($user_id);
            wp_send_json_error(['message' => 'Image upload failed: ' . ($movefile['error'] ?? 'Unknown')]);
        }
    }

    // Insert into doctors table
    // IMPORTANT: add wp_user_id column in your doctors table, otherwise remove it here.
    $inserted = $wpdb->insert($table_doctors, [
        'doctor_name'        => $doctor_name,
        'designation'        => $designation,
        'speciality'         => $speciality,
        'workplace'          => $workplace,
        'degree'             => $degree,
        'department'         => $department,
        'is_clinic_admin'    => $is_clinic_admin,
        'profile_photo_url'  => $profile_photo_url,
        'profile_photo_id'   => $profile_photo_id,
        'created_at'         => current_time('mysql'),
        'wp_user_id'         => $user_id,  // recommended link
    ]);

    if (!$inserted) {
        // If doctor insert fails, rollback user
        wp_delete_user($user_id);
        wp_send_json_error(['message' => 'Doctor insert failed. User creation rolled back.']);
    }

    $doctor_id = (int) $wpdb->insert_id;

    // Insert clinics
    if ($doctor_id && !empty($_POST['clinics']) && is_array($_POST['clinics'])) {
        foreach ($_POST['clinics'] as $clinic) {
            $clinic_name  = sanitize_text_field($clinic['clinicName'] ?? '');
            $clinic_phone = sanitize_text_field($clinic['clinicPhone'] ?? '');
            $is_holiday   = isset($clinic['holiday']) ? 1 : 0;

            if ($clinic_name === '') continue;

            $wpdb->insert($table_clinics, [
                'doctor_id'    => $doctor_id,
                'clinic_name'  => $clinic_name,
                'clinic_phone' => $clinic_phone,
                'is_holiday'   => $is_holiday,
                'created_at'   => current_time('mysql')
            ]);
        }
    }

    wp_send_json_success([
        'message'           => 'Doctor registered + user created successfully.',
        'wp_user_id'        => $user_id,
        'user_login'        => $user_login,
        'profile_photo_url' => $profile_photo_url,
        'profile_photo_id'  => $profile_photo_id
    ]);
}


// Save Bio Form AJAX
add_action('wp_ajax_save_bio_form_ajax',        'save_bio_form_ajax');
add_action('wp_ajax_nopriv_save_bio_form_ajax', 'save_bio_form_ajax');

function save_bio_form_ajax() {
    if ( ! defined('DOING_AJAX') || ! DOING_AJAX ) {
        wp_send_json_error(['message' => 'Invalid request'], 400);
    }

    global $wpdb;

    // Tables
    $bio_table      = 'wp_bio_form_data';
    $patients_table = 'fzil_wpddb_patients';
    $bookings_table = 'fzil_wpddb_bookings';

    // Nonce (optional but recommended)
    if ( isset($_POST['bio_nonce']) && ! wp_verify_nonce($_POST['bio_nonce'], 'bio_form_nonce') ) {
        wp_send_json_error(['message' => 'Security check failed. Please refresh the page.'], 403);
    }

    $now = current_time('mysql'); // WP local time

    // Mode + posted patient_id (optional)
    $mode       = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : 'new';
    $posted_pid = (isset($_POST['patient_id']) && $_POST['patient_id'] !== '') ? intval($_POST['patient_id']) : 0;

    // ====== INPUTS USED FOR PATIENTS TABLE ======
    $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
    $last_name  = isset($_POST['last_name'])  ? sanitize_text_field($_POST['last_name'])  : '';
    $email      = isset($_POST['email'])      ? sanitize_email($_POST['email']) : '';
    $home_phone = isset($_POST['home_phone']) ? sanitize_text_field($_POST['home_phone']) : '';

    $full_name = trim($first_name . ' ' . $last_name);
    $phone     = $home_phone;

    // You cannot upsert patient row without email (your matching key)
    if ( ! $email ) {
        wp_send_json_error(['message' => 'Email is required to save into fzil_wpddb_patients.'], 422);
    }

    // =========================================================
    // 1) UPSERT INTO fzil_wpddb_patients (full_name, email, phone)
    // =========================================================
    $patient_id = 0;

    // A) If posted patient_id exists, update that row
    if ($posted_pid > 0) {
        $exists = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM {$patients_table} WHERE id = %d LIMIT 1", $posted_pid)
        );

        if ($exists) {
            $patient_id = (int) $exists;

            $ok = $wpdb->update(
                $patients_table,
                [
                    'full_name' => $full_name,
                    'email'     => $email,
                    'phone'     => $phone,
                ],
                ['id' => $patient_id],
                ['%s','%s','%s'],
                ['%d']
            );

            if ($ok === false) {
                wp_send_json_error([
                    'message'   => 'Patients UPDATE failed (by patient_id)',
                    'sql_error' => $wpdb->last_error,
                    'sql'       => $wpdb->last_query,
                ], 500);
            }
        }
    }

    // B) Otherwise find by email -> update, else insert
    if ($patient_id <= 0) {
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT id FROM {$patients_table} WHERE email = %s ORDER BY id ASC LIMIT 1", $email)
        );

        if ($row) {
            $patient_id = (int) $row->id;

            $ok = $wpdb->update(
                $patients_table,
                [
                    'full_name' => $full_name,
                    'email'     => $email,
                    'phone'     => $phone,
                ],
                ['id' => $patient_id],
                ['%s','%s','%s'],
                ['%d']
            );

            if ($ok === false) {
                wp_send_json_error([
                    'message'   => 'Patients UPDATE failed (by email)',
                    'sql_error' => $wpdb->last_error,
                    'sql'       => $wpdb->last_query,
                ], 500);
            }

        } else {
            $ok = $wpdb->insert(
                $patients_table,
                [
                    'full_name' => $full_name,
                    'email'     => $email,
                    'phone'     => $phone,
                ],
                ['%s','%s','%s']
            );

            if (!$ok) {
                wp_send_json_error([
                    'message'   => 'Patients INSERT failed',
                    'sql_error' => $wpdb->last_error,
                    'sql'       => $wpdb->last_query,
                ], 500);
            }

            $patient_id = (int) $wpdb->insert_id;
        }
    }

    // =========================================================
    // 2) INSERT INTO fzil_wpddb_bookings (ONLY patient_id, day, time, created_at, updated_at)
    //    NOTE: booking_id is commonly NOT NULL. If your schema allows NULL, you can remove it.
    // =========================================================
    $day  = isset($_POST['day'])  ? sanitize_text_field($_POST['day'])  : '';
    $time = isset($_POST['time']) ? sanitize_text_field($_POST['time']) : '';

    // Only insert booking if day + time are present
    $booking_row_id = 0;
    $booking_id     = '';

    if ($day !== '' && $time !== '') {

        // Keep this unless your booking_id column allows NULL
        $booking_id = '#WPDB-' . wp_rand(1000, 9999);

        $ok = $wpdb->insert(
            $bookings_table,
            [
                'booking_id' => $booking_id,     // remove ONLY if nullable in DB
                'patient_id' => (int) $patient_id,
                'day'        => $day,
                'time'       => $time,
                
            ],
            ['%s','%d','%s','%s','%s','%s']
        );

        if (!$ok) {
            wp_send_json_error([
                'message'   => 'Bookings INSERT failed',
                'sql_error' => $wpdb->last_error,
                'sql'       => $wpdb->last_query,
                'hint'      => 'If error says some column cannot be null, your DB schema requires it. Either allow NULL/default, or pass a value.',
            ], 500);
        }

        $booking_row_id = (int) $wpdb->insert_id;
    }

    // =========================================================
    // 3) SAVE ALL FORM DATA INTO wp_bio_form_data (JSON blob)
    // =========================================================
    $exclude   = ['action','mode','patient_id','_wpnonce','_wp_http_referer','submitBtn','bio_nonce'];
    $form_data = [];

    foreach ($_POST as $k => $v) {
        if (in_array($k, $exclude, true)) continue;

        if ($k === 'email') {
            $form_data[$k] = $email;
            continue;
        }

        $form_data[$k] = is_array($v)
            ? array_map('sanitize_text_field', $v)
            : sanitize_text_field($v);
    }

    // Ensure email is in JSON
    if ($email && empty($form_data['email'])) {
        $form_data['email'] = $email;
    }

    // Optional: add resolved ids to JSON for debugging/traceability
    $form_data['_resolved_patient_id'] = $patient_id;
    if ($booking_row_id > 0) {
        $form_data['_booking_row_id'] = $booking_row_id;
        $form_data['_booking_id']     = $booking_id;
    }

    $json = wp_json_encode($form_data, JSON_UNESCAPED_UNICODE);

    // Latest snapshot per patient
    $existing = $wpdb->get_row(
        $wpdb->prepare("SELECT id FROM {$bio_table} WHERE patient_id = %d ORDER BY submitted_at DESC LIMIT 1", $patient_id)
    );

    if ($mode === 'edit' && $existing) {
        $ok = $wpdb->update(
            $bio_table,
            ['data' => $json, 'submitted_at' => $now],
            ['id' => $existing->id],
            ['%s','%s'],
            ['%d']
        );

        if ($ok !== false) {
            wp_send_json_success([
                'message' => 'Saved: patients + bio' . ($booking_row_id ? ' + booking' : ''),
                'debug'   => [
                    'patient_id'     => $patient_id,
                    'bio_row_id'     => (int) $existing->id,
                    'booking_row_id' => $booking_row_id,
                    'booking_id'     => $booking_id,
                ]
            ]);
        }

        wp_send_json_error([
            'message'   => 'Bio UPDATE failed',
            'sql_error' => $wpdb->last_error,
            'sql'       => $wpdb->last_query,
        ], 500);

    } else {
        $ok = $wpdb->insert(
            $bio_table,
            ['patient_id' => $patient_id, 'submitted_at' => $now, 'data' => $json],
            ['%d','%s','%s']
        );

        if ($ok) {
            wp_send_json_success([
                'message' => 'Saved: patients + bio' . ($booking_row_id ? ' + booking' : ''),
                'debug'   => [
                    'patient_id'     => $patient_id,
                    'bio_insert_id'  => (int) $wpdb->insert_id,
                    'booking_row_id' => $booking_row_id,
                    'booking_id'     => $booking_id,
                ],
                'logged_in' => is_user_logged_in()
            ]);
        }

        wp_send_json_error([
            'message'   => 'Bio INSERT failed',
            'sql_error' => $wpdb->last_error,
            'sql'       => $wpdb->last_query,
        ], 500);
    }
}


// Handle "Delete Patient" requests coming from admin-post.php
add_action('admin_post_delete_patient', 'handle_delete_patient');
// If (and only if) you need to allow deletion for logged-out users, also add:
// add_action('admin_post_nopriv_delete_patient', 'handle_delete_patient');

function handle_delete_patient() {
    // Require login + capability (adjust capability to your needs)
    if ( ! is_user_logged_in() || ! current_user_can('edit_posts') ) {
        wp_die(__('Unauthorized', 'textdomain'), 403);
    }

    if ( empty($_GET['patient_id']) ) {
        wp_safe_redirect( add_query_arg('deleted', '0', site_url('/patient-list')) );
        exit;
    }

    $patient_id = intval($_GET['patient_id']);

    // Nonce check (the action string must match what you generated in the link)
    check_admin_referer('delete_patient_' . $patient_id);

    global $wpdb;
    $patients_table = 'fzil_wpddb_patients';
    $bio_table      = 'wp_bio_form_data'; // optional: remove their bio snapshots too

    // Delete patient
    $deleted = $wpdb->delete($patients_table, ['id' => $patient_id], ['%d']);

    // OPTIONAL: cascade delete related data (comment out if you want to keep history)
    if ($deleted) {
        $wpdb->delete($bio_table, ['patient_id' => $patient_id], ['%d']);
        // If you have a notes table, do it here too:
        // $wpdb->delete('fzil_wpddb_notes', ['patient_id' => $patient_id], ['%d']);
    }

    // Redirect back to the list with a status flag
    $redirect = add_query_arg(['deleted' => $deleted ? '1' : '0'], site_url('/patient-list'));
    wp_safe_redirect($redirect);
    exit;
}


add_shortcode('doctor_hierarchy', 'wpddb_doctor_hierarchy_shortcode');

function wpddb_doctor_hierarchy_shortcode() {
    ob_start();

    if ( ! function_exists('get_current_user_id') ) {
        echo '<p>WordPress environment not detected.</p>';
        return ob_get_clean();
    }

    $SUPERADMIN_ID   = 4; // Shraddha
    $current_user_id = get_current_user_id();

    // ------------------------------
    // Helpers
    // ------------------------------

    // Get a user's "main" doctor post (no parent). Fallback to most recent if needed.
    $get_main_doctor_post_by_author = function($user_id) {
        $args = [
            'post_type'      => 'wpddb_doctor',
            'author'         => $user_id,
            'posts_per_page' => 1,
            'meta_query'     => [
                'relation' => 'OR',
                [ 'key' => 'wpddb_parent_doctor_id', 'compare' => 'NOT EXISTS' ],
                [ 'key' => 'wpddb_parent_doctor_id', 'value' => '', 'compare' => '=' ],
                [ 'key' => 'wpddb_parent_doctor_id', 'value' => '0', 'compare' => '=' ],
            ],
            'orderby'        => 'date',
            'order'          => 'DESC'
        ];
        $posts = get_posts($args);
        return $posts ? $posts[0] : null;
    };

    // Children under a parent doctor (kept for backward compatibility)
    $get_child_doctors = function($parent_doctor_id) {
        return new WP_Query([
            'post_type'      => 'wpddb_doctor',
            'posts_per_page' => -1,
            'meta_query'     => [[
                'key'     => 'wpddb_parent_doctor_id',
                'value'   => (string) $parent_doctor_id,
                'compare' => '='
            ]],
            'orderby'        => 'title',
            'order'          => 'ASC'
        ]);
    };

    // Read clinic IDs from doctor's meta: wpddb_clinics_info
    // Accepts array or serialized text; returns array of clinic IDs (ints).
    $get_clinic_ids_from_doctor = function($doctor_post_id){
        $raw = get_post_meta($doctor_post_id, 'wpddb_clinics_info', true);
        if (empty($raw)) return [];

        // If WP already gave us an array, use it. Otherwise try to unserialize.
        if (!is_array($raw)) {
            if (function_exists('maybe_unserialize')) {
                $raw = maybe_unserialize($raw);
            } else {
                $maybe = @unserialize($raw);
                $raw = ($maybe !== false && is_array($maybe)) ? $maybe : [];
            }
        }
        if (!is_array($raw) || empty($raw)) return [];

        // Keys are clinic post IDs like 384
        $ids = array_map('intval', array_keys($raw));
        // Filter valid positives
        return array_values(array_filter($ids, function($v){ return $v > 0; }));
    };

    // Is this doctor a clinic admin? (meta_key=clinic_admin_doctor_id, meta_value='1')
    $is_clinic_admin = function($doctor_post_id){
        $flag = get_post_meta($doctor_post_id, 'clinic_admin_doctor_id', true);
        return (string)$flag === '1';
    };

    // Resolve clinic names for a doctor (for badges if needed)
    $get_clinic_names = function($doctor_post_id) use ($get_clinic_ids_from_doctor){
        $ids = $get_clinic_ids_from_doctor($doctor_post_id);
        if (!$ids) return '';
        $names = [];
        foreach ($ids as $cid){
            $t = get_the_title($cid);
            if (!empty($t)) $names[] = $t;
        }
        return implode(', ', $names);
    };

    // Card renderer
    $render_doctor_card = function($post_id) {
        $name         = get_the_title($post_id);
        $designation  = get_post_meta($post_id, 'wpddb_doctor_designation', true);
        $speciality   = get_post_meta($post_id, 'wpddb_doctor_speciality', true);
        $thumb        = get_the_post_thumbnail_url($post_id, 'full');
        $thumb        = $thumb ? esc_url($thumb) : 'https://via.placeholder.com/600x600?text=Doctor';

        echo '
        <div class="doctor-card" style="width:260px;background:#fff;border-radius:14px;box-shadow:0 6px 18px rgba(0,0,0,0.08);padding-bottom:16px;text-align:center;overflow:hidden;">
            <div style="height:255px;width:100%;overflow:hidden;">
                <img src="'. $thumb .'" alt="'. esc_attr($name) .'" style="width:100%;height:100%;object-fit:cover;">
            </div>
            <div style="margin-top:12px;">'.
                ( $designation ? '<span style="background:#005a8d;color:#fff;padding:5px 12px;border-radius:999px;font-size:12px;">'. esc_html($designation) .'</span>' : '' ) .
            '</div>
            <h4 style="color:#9d0d00;margin:10px 10px 0;font-size:18px;line-height:1.2;">'. esc_html($name) .'</h4>'.
            ( $speciality ? '<p style="color:#333;font-size:13px;margin:6px 12px 0;">'. esc_html($speciality) .'</p>' : '' ) .'
        </div>';
    };

    // ------------------------------
    // Build Clinic → Admin + Members map
    // ------------------------------
    $clinic_admins  = []; // clinic_id => admin_doctor_id
    $clinic_members = []; // clinic_id => [doctor_ids...]

    $all_doctors = new WP_Query([
        'post_type'      => 'wpddb_doctor',
        'posts_per_page' => -1,
        'author__not_in' => [$SUPERADMIN_ID],
        'orderby'        => 'title',
        'order'          => 'ASC'
    ]);

    if ($all_doctors->have_posts()) {
        while ($all_doctors->have_posts()) {
            $all_doctors->the_post();
            $doc_id     = get_the_ID();
            $clinic_ids = $get_clinic_ids_from_doctor($doc_id);
            if (!$clinic_ids) continue;

            $admin_flag = $is_clinic_admin($doc_id);

            foreach ($clinic_ids as $cid) {
                if ($admin_flag) {
                    if (!isset($clinic_admins[$cid])) {
                        $clinic_admins[$cid] = $doc_id;
                    }
                } else {
                    $clinic_members[$cid] = $clinic_members[$cid] ?? [];
                    $clinic_members[$cid][] = $doc_id;
                }
            }
        }
        wp_reset_postdata();
    }

    // Render one clinic block
    $render_clinic_block = function($clinic_id) use ($clinic_admins, $clinic_members, $render_doctor_card) {
        $clinic_title = get_the_title($clinic_id);
        if (!$clinic_title) $clinic_title = 'Clinic #'.$clinic_id;

        echo '<div class="panel">';
        echo '<details open>';
        echo '<summary><strong>'. esc_html($clinic_title) .'</strong></summary>';

        // Admin
        echo '<div style="margin-top:10px;">';
        echo '<div class="muted">Clinic Admin:</div>';
        echo '<div class="flex-row" style="margin-top:10px;">';
        if (isset($clinic_admins[$clinic_id]) && $clinic_admins[$clinic_id]) {
            $render_doctor_card($clinic_admins[$clinic_id]);
        } else {
            echo '<p class="muted" style="margin-top:6px;">No admin flagged for this clinic.</p>';
        }
        echo '</div>';
        echo '</div>';

        // Members
        echo '<div style="margin-top:16px;">';
        echo '<div class="muted">Doctors in this clinic:</div>';
        if (!empty($clinic_members[$clinic_id])) {
            echo '<div class="flex-row" style="margin-top:10px;">';
            foreach ($clinic_members[$clinic_id] as $member_id) {
                if (isset($clinic_admins[$clinic_id]) && (int)$clinic_admins[$clinic_id] === (int)$member_id) continue;
                $render_doctor_card($member_id);
            }
            echo '</div>';
        } else {
            echo '<p class="muted" style="margin-top:6px;">No doctors listed for this clinic.</p>';
        }
        echo '</div>';

        echo '</details>';
        echo '</div>';
    };

    // ------------------------------
    // Output (UI + views)
    // ------------------------------
    ?>
    <div class="doctor-hierarchy-wrap" style="margin:10px 0 30px;">
        <style>
            .level-title{font-weight:700;margin:10px 0 12px;color:#1a1a1a}
            .flex-row{display:flex;flex-wrap:wrap;gap:18px}
            .panel{background:#f7fafc;border:1px solid #e6ecf1;border-radius:14px;padding:16px;margin-bottom:18px}
            details>summary{cursor:pointer;list-style:none;font-weight:600;padding:8px 0}
            details>summary::-webkit-details-marker{display:none}
            .muted{color:#6b7a90;font-size:13px}
        </style>
    <?php

    // SUPERADMIN VIEW — show her profile card + all clinics
    if ($current_user_id == $SUPERADMIN_ID) {

        // Superadmin profile card (NEW per your request)
        $shraddha_main = $get_main_doctor_post_by_author($SUPERADMIN_ID);
        echo '<h3 class="level-title">Superadmin</h3>';
        echo '<div class="panel">';
        if ($shraddha_main) {
            // Optional badge with her clinic names
            $sa_clinic_names = $get_clinic_names($shraddha_main->ID);
            if ($sa_clinic_names) {
                echo '<div class="muted" style="margin-bottom:8px;">Clinics: '. esc_html($sa_clinic_names) .'</div>';
            }
            echo '<div class="flex-row">';
            $render_doctor_card($shraddha_main->ID);
            echo '</div>';
        } else {
            echo '<p class="muted">No profile card found for Superadmin.</p>';
        }
        echo '</div>';

        // All clinics
        echo '<h3 class="level-title">All Clinics</h3>';

        // Collect every clinic ID we learned about
        $all_clinic_ids = array_unique(array_merge(array_keys($clinic_admins), array_keys($clinic_members)));
        sort($all_clinic_ids, SORT_NUMERIC);

        if ($all_clinic_ids) {
            foreach ($all_clinic_ids as $cid) {
                $render_clinic_block($cid);
            }
        } else {
            echo '<p class="muted">No clinics mapped yet. Ensure doctor posts have <code>wpddb_clinics_info</code> filled.</p>';
        }

    // NON-SUPERADMIN VIEW — show only clinics linked to current user
    } else {

        // Find current user's main doctor post (or fallback)
        $my_main = $get_main_doctor_post_by_author($current_user_id);
        if ( ! $my_main ) {
            $mine = get_posts([
                'post_type'      => 'wpddb_doctor',
                'author'         => $current_user_id,
                'numberposts'    => 1,
                'orderby'        => 'date',
                'order'          => 'DESC'
            ]);
            if ($mine) {
                $my_doctor_id = $mine[0]->ID;
                $parent_id = get_post_meta($my_doctor_id, 'wpddb_parent_doctor_id', true);
                $my_main = $parent_id ? get_post($parent_id) : get_post($my_doctor_id);
            }
        }

        if ($my_main) {
            $my_clinic_ids = $get_clinic_ids_from_doctor($my_main->ID);

            if ($my_clinic_ids) {
                echo '<h3 class="level-title">Your Clinics</h3>';
                foreach ($my_clinic_ids as $cid) {
                    $render_clinic_block($cid);
                }
            } else {
                echo '<p class="muted">No clinics linked to your profile. Please add <code>wpddb_clinics_info</code> on your doctor post.</p>';
            }
        } else {
            echo '<p class="muted">No hierarchy could be determined for your profile.</p>';
        }
    }

    echo '</div>'; // wrap

    return ob_get_clean();
}



// Inline update (AJAX)
add_action('wp_ajax_update_patient_inline', 'update_patient_inline');
add_action('wp_ajax_nopriv_update_patient_inline', 'update_patient_inline'); // remove this line if only logged-in should edit

function update_patient_inline() {
    // Nonce check
    check_ajax_referer('update_patient_inline', 'security');

    if ( ! defined('DOING_AJAX') || ! DOING_AJAX ) {
        wp_send_json_error(['message' => 'Invalid request context']);
    }

    // Optional: permission check
    if ( ! is_user_logged_in() || ! current_user_can('edit_posts') ) {
        wp_send_json_error(['message' => 'Unauthorized'], 403);
    }

    global $wpdb;
    $table = 'fzil_wpddb_patients';

    $patient_id = isset($_POST['patient_id']) ? intval($_POST['patient_id']) : 0;
    $full_name  = isset($_POST['full_name']) ? sanitize_text_field($_POST['full_name']) : '';
    $email      = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $phone      = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';

    if ( ! $patient_id || empty($full_name) || empty($email) || empty($phone) ) {
        wp_send_json_error(['message' => 'Missing fields']);
    }
    if ( ! is_email($email) ) {
        wp_send_json_error(['message' => 'Invalid email']);
    }

    $updated = $wpdb->update(
        $table,
        [
            'full_name' => $full_name,
            'email'     => $email,
            'phone'     => $phone
        ],
        [ 'id' => $patient_id ],
        [ '%s','%s','%s' ],
        [ '%d' ]
    );

    if ($updated === false) {
        wp_send_json_error(['message' => 'DB error', 'sql' => $wpdb->last_error]);
    }

    wp_send_json_success([
        'message' => 'Updated',
        'patient' => [
            'id'        => $patient_id,
            'full_name' => $full_name,
            'email'     => $email,
            'phone'     => $phone
        ]
    ]);
}


// ================================================================
// BIO FILE IMPORT AJAX HANDLER (Frontend + Admin)
// ================================================================

add_action('wp_ajax_bio_import_file',        'bio_import_file_handler');
add_action('wp_ajax_nopriv_bio_import_file', 'bio_import_file_handler');

function bio_import_file_handler() {
    // (Optional) require login
    // if (!is_user_logged_in()) wp_send_json_error(['message' => 'Login required'], 403);

    // ✅ Nonce security
    if (empty($_POST['security']) || ! wp_verify_nonce($_POST['security'], 'bio_import_nonce')) {
        wp_send_json_error(['message' => 'Security check failed.'], 400);
    }

    // ✅ Ensure file exists
    if (empty($_FILES['uploaded-file']['name'])) {
        wp_send_json_error(['message' => 'No file received. Field name must be uploaded-file.'], 400);
    }

    // ✅ Allow CSV, XLSX, XLS
    $uploaded = wp_handle_upload($_FILES['uploaded-file'], [
        'test_form' => false,
        'mimes' => [
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls'  => 'application/vnd.ms-excel',
            'csv'  => 'text/csv',
            'txt'  => 'text/plain'
        ],
    ]);
    if (isset($uploaded['error'])) {
        wp_send_json_error(['message' => 'Upload error: '.$uploaded['error']], 400);
    }

    $file = $uploaded['file'];
    $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    // ✅ Parse file → array of rows
    try {
        if (in_array($ext, ['xlsx','xls'], true)) {
            if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
                wp_send_json_error([
                    'message' => strtoupper($ext).' reading requires PhpSpreadsheet. Install via composer or upload CSV.'
                ], 400);
            }
            $rows = bio_parse_xlsx_rows($file);
        } elseif (in_array($ext, ['csv','txt'], true)) {
            $rows = bio_parse_csv_rows($file);
        } else {
            wp_send_json_error(['message' => 'Unsupported file type: '.$ext], 400);
        }
    } catch (Throwable $e) {
        wp_send_json_error(['message' => 'Read error: '.$e->getMessage()], 500);
    }

    if (empty($rows)) {
        wp_send_json_error(['message' => 'No data rows found.'], 400);
    }

    // ================================================================
    // ✅ Save to Database
    // ================================================================
    global $wpdb;
    $bio_table      = 'wp_bio_form_data';
    $patients_table = 'fzil_wpddb_patients';

    $inserted = 0; $updated = 0; $failed = 0; $details = [];

    foreach ($rows as $i => $row) {
    $row = array_change_key_case($row, CASE_LOWER);

    // --- Resolve patient_id (save exactly what you want in DB)
    $patient_id = 0;

    // 1) Prefer explicit patient_id column
    if (!empty($row['patient_id'])) {
        $patient_id = intval($row['patient_id']);
    }
    // 2) Backward compatible: patient_id_optional
    elseif (!empty($row['patient_id_optional'])) {
        $patient_id = intval($row['patient_id_optional']);
    }
    // 3) Fallback: lookup by email
    elseif (!empty($row['email'])) {
        $email = sanitize_email($row['email']);
        $patient = $wpdb->get_row(
            $wpdb->prepare("SELECT id FROM {$patients_table} WHERE email=%s ORDER BY id ASC LIMIT 1", $email)
        );
        if ($patient) $patient_id = intval($patient->id);
    }

    // --- Build JSON payload (exclude only helper columns)
    $exclude = ['patient_id_optional','patient_id']; // keep patient_id only in SQL column
    $form_data = [];
    foreach ($row as $k => $v) {
        if (in_array($k, $exclude, true)) continue;
        if ($k === 'email') { $form_data[$k] = sanitize_email($v); continue; }
        $form_data[$k] = is_array($v) ? array_map('sanitize_text_field',$v) : sanitize_text_field($v);
    }
    if (!empty($row['email']) && empty($form_data['email'])) {
        $form_data['email'] = sanitize_email($row['email']);
    }

    $json = wp_json_encode($form_data, JSON_UNESCAPED_UNICODE);
    $now  = current_time('mysql');

    // --- Always INSERT a new snapshot per row
    $ok = $wpdb->insert(
        $bio_table,
        [
            'patient_id'   => $patient_id,   // <- this now stores the patient_id from the file (or looked up)
            'submitted_at' => $now,
            'data'         => $json,
        ],
        ['%d','%s','%s']
    );

    if ($ok) {
        $inserted++;
        $details[] = "Row ".($i+2).": inserted (patient_id={$patient_id}, insert_id={$wpdb->insert_id})";
    } else {
        $failed++;
        $details[] = "Row ".($i+2).": insert failed – ".$wpdb->last_error;
    }
}


    wp_send_json_success([
        'summary' => compact('inserted','updated','failed'),
        'details' => $details
    ]);
}

// ================================================================
// ✅ HELPER FUNCTIONS
// ================================================================

function bio_parse_csv_rows($file) {
    $rows = [];
    if (($handle = fopen($file, 'r')) !== false) {
        $headers = [];
        $rowNum = 0;
        while (($data = fgetcsv($handle, 0)) !== false) {
            $rowNum++;
            if ($rowNum === 1) {
                foreach ($data as $h) $headers[] = sanitize_key(trim((string)$h));
                continue;
            }
            $row = [];
            foreach ($data as $idx => $val) {
                $header = $headers[$idx] ?? null;
                if ($header) $row[$header] = (string)$val;
            }
            if (implode('', $row) !== '') $rows[] = $row;
        }
        fclose($handle);
    }
    return $rows;
}

function bio_parse_xlsx_rows($file) {
    // Needs PhpSpreadsheet
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
    $sheet = $spreadsheet->getSheetByName('Data') ?: $spreadsheet->getActiveSheet();
    $data  = $sheet->toArray(null, true, true, true);

    $rows = [];
    if (!empty($data)) {
        $headers = [];
        $first = array_shift($data);
        foreach ($first as $v) $headers[] = sanitize_key(trim((string)$v));
        foreach ($data as $r) {
            $row = [];
            $i = 0;
            foreach ($r as $cell) {
                $h = $headers[$i] ?? null;
                if ($h) $row[$h] = is_null($cell) ? '' : (string)$cell;
                $i++;
            }
            if (implode('', $row) !== '') $rows[] = $row;
        }
    }
    return $rows;
}

// Cancel booking via AJAX
add_action('wp_ajax_cancel_booking', function () {
    // security & perms
    if ( ! current_user_can('edit_posts') ) {
        wp_send_json_error(['message' => 'No permission'], 403);
    }
    check_ajax_referer('cancel_booking_nonce', 'nonce');

    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if (!$id) wp_send_json_error(['message' => 'Invalid booking id'], 400);

    global $wpdb;
    $table = 'fzil_wpddb_bookings';

    $ok = $wpdb->update(
        $table,
        ['booking_present_status' => 'cancel', 'updated_at' => current_time('mysql')],
        ['id' => $id],
        ['%s','%s'],
        ['%d']
    );

    if ($ok === false) {
        wp_send_json_error(['message' => 'DB error']);
    }

    wp_send_json_success([
        'id'    => $id,
        'label' => 'Cancel',            // for UI
        'class' => 'cancel'             // CSS class to apply
    ]);
});


// === Shortcode: [patient_full_detail id="123"] === //
add_shortcode('patient_full_detail', function($atts) {
    global $wpdb;

    /* ----------------- Resolve patient id ----------------- */
    $atts = shortcode_atts(['id' => 0], $atts, 'patient_full_detail');
    $patient_id = intval($atts['id'] ? $atts['id'] : ($_GET['id'] ?? 0));
    if ($patient_id <= 0) return '<p style="color:red;">Invalid patient.</p>';

    /* ----------------- Tables ----------------- */
    $patients_table = $wpdb->prefix . 'wpddb_patients';
    $notes_table    = $wpdb->prefix . 'wpddb_patient_notes_new';
    $posts_table    = $wpdb->posts;
    $postmeta_table = $wpdb->postmeta;

    /* ----------------- Handle POST actions (edit/delete/recommend) ----------------- */
    // Delete note
    if (!empty($_POST['pfd_action']) && $_POST['pfd_action'] === 'delete_note' && !empty($_POST['note_id'])) {
        $note_id = intval($_POST['note_id']);
        if (isset($_POST['pfd_delete_nonce']) && wp_verify_nonce($_POST['pfd_delete_nonce'], 'pfd_delete_' . $note_id)) {
            // delete only if note belongs to this patient
            $wpdb->delete($notes_table, ['id' => $note_id, 'patient_id' => $patient_id], ['%d','%d']);
            // soft redirect to avoid resubmission
            wp_safe_redirect(esc_url(add_query_arg(['pfd' => 'deleted'], remove_query_arg(['pfd']))));
            exit;
        }
    }

    // Edit/Update note
    if (!empty($_POST['pfd_action']) && $_POST['pfd_action'] === 'edit_note' && !empty($_POST['edit_note_id'])) {
        if (isset($_POST['pfd_edit_nonce']) && wp_verify_nonce($_POST['pfd_edit_nonce'], 'pfd_edit')) {
            $note_id          = intval($_POST['edit_note_id']);
            $updated_category = sanitize_text_field($_POST['edit_category']);
            $updated_next     = sanitize_text_field($_POST['edit_next_visit_date']);
            $updated_title    = sanitize_text_field($_POST['edit_note_title']);
            $updated_note     = sanitize_textarea_field($_POST['edit_note_content']);
            $updated_file_url = sanitize_text_field($_POST['existing_note_file_url']);

            // Optional file upload
            if (!empty($_FILES['edit_note_file_upload']['name'])) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $movefile = wp_handle_upload($_FILES['edit_note_file_upload'], ['test_form' => false]);
                if ($movefile && !isset($movefile['error'])) {
                    $updated_file_url = $movefile['url'];
                }
            }

            // Update only if the note belongs to this patient
            $wpdb->update(
                $notes_table,
                [
                    'category'        => $updated_category,
                    'next_visit_date' => $updated_next,
                    'title'           => $updated_title,
                    'note_content'    => $updated_note,
                    'file_url'        => $updated_file_url,
                ],
                ['id' => $note_id, 'patient_id' => $patient_id],
                ['%s','%s','%s','%s','%s'],
                ['%d','%d']
            );

            wp_safe_redirect(esc_url(add_query_arg(['pfd' => 'updated'], remove_query_arg(['pfd']))));
            exit;
        }
    }

    // Save Recommendation (optional, unchanged behavior)
    if (!empty($_POST['save_recommendation'])) {
        if (!isset($_POST['recommend_doctor_nonce']) || !wp_verify_nonce($_POST['recommend_doctor_nonce'], 'recommend_doctor')) {
            // ignore silently in shortcode context
        } else {
            $doctor_post_id  = isset($_POST['recommended_doctor_id']) ? intval($_POST['recommended_doctor_id']) : 0;
            if ($doctor_post_id > 0) {
                $current_user_id = get_current_user_id();
                if ($current_user_id) {
                    $doctor = $wpdb->get_row($wpdb->prepare(
                        "SELECT ID, post_title FROM {$posts_table}
                         WHERE ID=%d AND post_type='wpddb_doctor' AND post_status='publish' LIMIT 1",
                        $doctor_post_id
                    ));
                    if ($doctor) {
                        // Create new NOTE-YYYY-0001 style id
                        $year   = current_time('Y');
                        $prefix = "NOTE-{$year}-";
                        $like   = $wpdb->esc_like($prefix) . '%';
                        $last   = $wpdb->get_var($wpdb->prepare(
                            "SELECT note_id FROM {$notes_table} WHERE note_id LIKE %s ORDER BY note_id DESC LIMIT 1",
                            $like
                        ));
                        $n = 1;
                        if ($last && preg_match('/^NOTE-\d{4}-(\d{4})$/', $last, $m)) $n = intval($m[1]) + 1;
                        $new_note_id = sprintf('NOTE-%s-%04d', $year, $n);

                        $wpdb->insert(
                            $notes_table,
                            [
                                'note_id'         => $new_note_id,
                                'patient_id'      => $patient_id,
                                'user_id'         => $current_user_id,
                                'category'        => 'Recommendation',
                                'note_content'    => 'Therapist recommended: ' . $doctor->post_title,
                                'next_visit_date' => '0000-00-00',
                                'created_at'      => current_time('mysql'),
                                'title'           => 'Recommended Therapist',
                                'file_url'        => ''
                            ],
                            ['%s','%d','%d','%s','%s','%s','%s','%s']
                        );
                        wp_safe_redirect(esc_url(add_query_arg(['pfd' => 'recommended'], remove_query_arg(['pfd']))));
                        exit;
                    }
                }
            }
        }
    }

    /* ----------------- Fetch patient + data ----------------- */
    $patient = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$patients_table} WHERE id = %d LIMIT 1", $patient_id)
    );
    if (!$patient) return '<p style="color:red;">Patient not found.</p>';

    $latest_note = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$notes_table} WHERE patient_id=%d ORDER BY created_at DESC LIMIT 1", $patient_id)
    );
    $notes = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM {$notes_table} WHERE patient_id=%d ORDER BY created_at DESC", $patient_id)
    );

    // Doctors for recommendation dropdown
    $doctors = $wpdb->get_results("
        SELECT ID, post_title, post_author
        FROM {$posts_table}
        WHERE post_type='wpddb_doctor' AND post_status='publish'
        ORDER BY post_title ASC
    ");

    // Assigned doctors (via note->user_id => doctor post by author)
    $assigned_doctors = [];
    if ($notes) {
        foreach ($notes as $note) {
            $user_id = intval($note->user_id);
            if (!$user_id) continue;

            $doctor_post = $wpdb->get_row($wpdb->prepare("
                SELECT ID, post_title
                FROM {$posts_table}
                WHERE post_author=%d AND post_type='wpddb_doctor'
                ORDER BY post_date DESC
                LIMIT 1", $user_id));

            if ($doctor_post) {
                $designation = $wpdb->get_var($wpdb->prepare("
                    SELECT meta_value
                    FROM {$postmeta_table}
                    WHERE post_id=%d AND meta_key='wpddb_doctor_designation'
                    LIMIT 1", $doctor_post->ID));
                if (!empty($designation) && !empty($doctor_post->post_title)) {
                    $assigned_doctors[$designation] = $doctor_post->post_title;
                }
            }
        }
    }

    ob_start(); ?>

    <?php if (!empty($_GET['pfd']) && $_GET['pfd'] === 'deleted'): ?>
        <div style="margin:10px 0; padding:10px; border-left:4px solid #d63638; background:#fff5f5;">
            Note deleted.
        </div>
    <?php elseif (!empty($_GET['pfd']) && $_GET['pfd'] === 'updated'): ?>
        <div style="margin:10px 0; padding:10px; border-left:4px solid #46b450; background:#f6fff6;">
            Note updated.
        </div>
    <?php elseif (!empty($_GET['pfd']) && $_GET['pfd'] === 'recommended'): ?>
        <div style="margin:10px 0; padding:10px; border-left:4px solid #46b450; background:#f6fff6;">
            Recommendation saved.
        </div>
    <?php endif; ?>

    <div class="patient-full-detail" style="margin-top:20px;">
        <h3>👤 Patient Summary</h3>
        <p><strong>Name:</strong> <?php echo esc_html($patient->full_name); ?></p>
        <p><strong>Email:</strong> <?php echo esc_html($patient->email); ?></p>
        <p><strong>Phone:</strong> <?php echo esc_html($patient->phone); ?></p>

        <h4 style="margin-top:20px;">📋 Latest Note Info</h4>
        <p><strong>Category:</strong>
            <?php echo ($latest_note && $latest_note->category) ? esc_html($latest_note->category) : '<em>None</em>'; ?></p>
        <p><strong>Next Visit Date:</strong>
            <?php echo ($latest_note && $latest_note->next_visit_date && $latest_note->next_visit_date !== '0000-00-00')
                ? esc_html($latest_note->next_visit_date)
                : '<em>Not set</em>'; ?></p>

        <h4 style="margin-top:20px;">🩺 Assigned Doctors</h4>
        <?php if (!empty($assigned_doctors)): ?>
            <ul>
                <?php foreach ($assigned_doctors as $designation => $name): ?>
                    <li><strong><?php echo esc_html($designation); ?>:</strong> <?php echo esc_html($name); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p><em>No doctors assigned yet.</em></p>
        <?php endif; ?>

        <h4 style="margin-top:25px;">💬 Recommend Therapist</h4>
        <form method="post">
            <?php wp_nonce_field('recommend_doctor', 'recommend_doctor_nonce'); ?>
            <select name="recommended_doctor_id" style="min-width:280px;padding:8px;border-radius:6px;border:1px solid #ccc;">
                <option value="">— Select Doctor —</option>
                <?php if (!empty($doctors)) : foreach ($doctors as $doc): ?>
                    <option value="<?php echo esc_attr($doc->ID); ?>">
                        <?php echo esc_html($doc->post_title); ?>
                    </option>
                <?php endforeach; endif; ?>
            </select>
            <button type="submit" name="save_recommendation" class="button button-primary" style="margin-left:10px;">Save</button>
        </form>

        <h4 style="margin-top:25px;">🗂 Session Notes</h4>
        <?php if (!empty($notes)): ?>
            <?php foreach ($notes as $note): ?>
                <div style="background:#f9f9f9; padding:12px; margin-bottom:12px; border-left:5px solid #0073aa;">
                    <strong>Date:</strong> <?php echo esc_html($note->next_visit_date ?: '-'); ?><br>
                    <strong>Category:</strong> <?php echo esc_html($note->category ?: '-'); ?><br>
                    <strong>Note:</strong> <?php echo nl2br(esc_html($note->note_content)); ?><br>
                    <?php if (!empty($note->file_url)): ?>
                        <strong>File:</strong> <a href="<?php echo esc_url($note->file_url); ?>" target="_blank" rel="noopener">Open</a><br>
                    <?php endif; ?>

                    <div style="margin-top:8px; display:flex; gap:8px; flex-wrap:wrap;">
                        <!-- VIEW -->
                        <button type="button"
                                class="button button-small pfd-view"
                                data-note='<?php echo htmlspecialchars(json_encode($note), ENT_QUOTES, 'UTF-8'); ?>'>
                            View
                        </button>

                        <!-- EDIT -->
                        <button type="button"
                                class="button button-small pfd-edit"
                                data-note='<?php echo htmlspecialchars(json_encode($note), ENT_QUOTES, 'UTF-8'); ?>'>
                            Edit
                        </button>

                        <!-- DELETE -->
                        <form method="post" onsubmit="return confirm('Delete this note?');" style="display:inline;">
                            <?php wp_nonce_field('pfd_delete_' . intval($note->id), 'pfd_delete_nonce'); ?>
                            <input type="hidden" name="pfd_action" value="delete_note">
                            <input type="hidden" name="note_id" value="<?php echo intval($note->id); ?>">
                            <button type="submit" class="button button-small" style="background:#d63638; color:#fff;">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p><em>No session notes found.</em></p>
        <?php endif; ?>

        <form action="<?php echo esc_url(site_url('/add-note-page/')); ?>" method="post" style="display:inline;">
  <input type="hidden" name="email" value="<?php echo esc_attr($patient->email); ?>">
  <button type="submit" class="btn-add-note">➕ Add Note</button>
</form>
    </div>

    <!-- VIEW MODAL -->
    <div id="pfdViewModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:10000;">
      <div style="background:#fff; max-width:560px; margin:8vh auto; padding:18px 16px; border-radius:10px; position:relative;">
        <button id="pfdViewClose" style="position:absolute; right:10px; top:10px; border:none; background:#d63638; color:#fff; width:32px; height:32px; border-radius:50%;">×</button>
        <h3 style="margin:0 0 10px;">Note Details</h3>
        <div id="pfdViewBody"></div>
      </div>
    </div>

    <!-- EDIT MODAL -->
    <div id="pfdEditModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:10000;">
      <div style="background:#fff; max-width:640px; margin:7vh auto; padding:20px 18px; border-radius:12px; position:relative;">
        <button id="pfdEditClose" style="position:absolute; right:10px; top:10px; border:none; background:#d63638; color:#fff; width:34px; height:34px; border-radius:50%;">×</button>
        <h3 style="margin:0 0 14px;">Edit Note</h3>
        <form method="post" id="pfdEditForm" enctype="multipart/form-data">
            <?php wp_nonce_field('pfd_edit', 'pfd_edit_nonce'); ?>
            <input type="hidden" name="pfd_action" value="edit_note">
            <input type="hidden" name="edit_note_id" id="pfd_edit_note_id">
            <input type="hidden" name="existing_note_file_url" id="pfd_existing_file_url">

            <p style="margin-bottom:10px;">
              <label style="display:block; font-weight:600;">Category</label>
              <input type="text" name="edit_category" id="pfd_edit_category" style="width:100%; padding:8px;" required>
            </p>

            <p style="margin-bottom:10px;">
              <label style="display:block; font-weight:600;">Next Visit Date</label>
              <input type="date" name="edit_next_visit_date" id="pfd_edit_next" style="width:100%; padding:8px;" required>
            </p>

            <p style="margin-bottom:10px;">
              <label style="display:block; font-weight:600;">Title</label>
              <input type="text" name="edit_note_title" id="pfd_edit_title" style="width:100%; padding:8px;">
            </p>

            <p style="margin-bottom:10px;">
              <label style="display:block; font-weight:600;">Note Content</label>
              <textarea name="edit_note_content" id="pfd_edit_content" rows="5" style="width:100%; padding:8px;" required></textarea>
            </p>

            <p style="margin-bottom:14px;">
              <label style="display:block; font-weight:600;">Upload File (optional)</label>
              <input type="file" name="edit_note_file_upload" id="pfd_edit_file">
            </p>

            <div style="text-align:right;">
              <button type="submit" class="button button-primary">Save</button>
            </div>
        </form>
      </div>
    </div>

    <script>
    (function(){
      // VIEW
      const vModal = document.getElementById('pfdViewModal');
      const vBody  = document.getElementById('pfdViewBody');
      const vClose = document.getElementById('pfdViewClose');

      document.querySelectorAll('.pfd-view').forEach(btn=>{
        btn.addEventListener('click', function(){
          const note = JSON.parse(this.getAttribute('data-note'));
          const html = `
            <p><strong>Note ID:</strong> ${note.id}</p>
            <p><strong>Ref:</strong> ${note.note_id || '-'}</p>
            <p><strong>Category:</strong> ${note.category || '-'}</p>
            <p><strong>Title:</strong> ${note.title || '-'}</p>
            <p><strong>Content:</strong><br>${(note.note_content || '').replace(/\\n/g,'<br>')}</p>
            <p><strong>Next Visit:</strong> ${note.next_visit_date || '-'}</p>
            <p><strong>Created:</strong> ${note.created_at || '-'}</p>
            ${note.file_url ? `<p><strong>File:</strong> <a href="${note.file_url}" target="_blank" rel="noopener">Open</a></p>` : ''}
          `;
          vBody.innerHTML = html;
          vModal.style.display = 'block';
        });
      });
      vClose.addEventListener('click', ()=> vModal.style.display='none');
      vModal.addEventListener('click', (e)=>{ if(e.target===vModal) vModal.style.display='none'; });

      // EDIT
      const eModal = document.getElementById('pfdEditModal');
      const eClose = document.getElementById('pfdEditClose');

      document.querySelectorAll('.pfd-edit').forEach(btn=>{
        btn.addEventListener('click', function(){
          const n = JSON.parse(this.getAttribute('data-note'));
          document.getElementById('pfd_edit_note_id').value = n.id || '';
          document.getElementById('pfd_edit_category').value = n.category || '';
          document.getElementById('pfd_edit_next').value = n.next_visit_date || '';
          document.getElementById('pfd_edit_title').value = n.title || '';
          document.getElementById('pfd_edit_content').value = n.note_content || '';
          document.getElementById('pfd_existing_file_url').value = n.file_url || '';
          eModal.style.display = 'block';
        });
      });
      eClose.addEventListener('click', ()=> eModal.style.display='none');
      eModal.addEventListener('click', (e)=>{ if(e.target===eModal) eModal.style.display='none'; });
    })();
    </script>

    <?php
    return ob_get_clean();
});

add_action('wp_ajax_save_doctor_payment_proof', 'save_doctor_payment_proof');

function save_doctor_payment_proof() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Not logged in.'], 401);
    }

    $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
    if (!wp_verify_nonce($nonce, 'doctor_payment_proof_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce.'], 403);
    }

    global $wpdb;

    $current_user_id = get_current_user_id();
    $table_doctors   = 'wp_doctors';
    $table_history   = 'payment_history';

    // Find doctor by wp_user_id (best)
    $doctor = $wpdb->get_row(
        $wpdb->prepare("SELECT id FROM {$table_doctors} WHERE wp_user_id = %d LIMIT 1", $current_user_id)
    );

    // Fallback doctor_id meta
    if (!$doctor) {
        $doctor_id_meta = (int) get_user_meta($current_user_id, 'doctor_id', true);
        if ($doctor_id_meta > 0) {
            $doctor = $wpdb->get_row(
                $wpdb->prepare("SELECT id FROM {$table_doctors} WHERE id = %d LIMIT 1", $doctor_id_meta)
            );
        }
    }

    if (!$doctor || empty($doctor->id)) {
        wp_send_json_error(['message' => 'Doctor not mapped to user.'], 404);
    }

    if (empty($_FILES['payment_proof']['name'])) {
        wp_send_json_error(['message' => 'No file uploaded.'], 400);
    }

    // Validate file type (image only)
    $file = $_FILES['payment_proof'];
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];

    // NOTE: $_FILES['type'] can be spoofed. This is "ok" for basic use,
    // but if you want stricter security, use wp_check_filetype_and_ext().
    if (empty($file['type']) || !in_array($file['type'], $allowed, true)) {
        wp_send_json_error(['message' => 'Only JPG, PNG, WEBP allowed.'], 400);
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    // Upload to wp-content/uploads
    $upload_overrides = ['test_form' => false];
    $movefile = wp_handle_upload($file, $upload_overrides);

    if (isset($movefile['error'])) {
        wp_send_json_error(['message' => $movefile['error']], 500);
    }

    $file_url  = $movefile['url'];
    $file_path = $movefile['file'];

    // Insert into Media Library
    $attachment = [
        'post_mime_type' => $movefile['type'],
        'post_title'     => 'Payment Proof - Doctor ID ' . (int)$doctor->id,
        'post_content'   => '',
        'post_status'    => 'inherit',
    ];

    $attach_id = wp_insert_attachment($attachment, $file_path);
    if (!is_wp_error($attach_id)) {
        $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
        wp_update_attachment_metadata($attach_id, $attach_data);
    } else {
        $attach_id = 0;
    }

    // OPTIONAL payment fields (if you send them from JS / form)
    $amount         = isset($_POST['amount']) ? (float) $_POST['amount'] : null;
    $currency       = isset($_POST['currency']) ? sanitize_text_field($_POST['currency']) : 'INR';
    $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : null;
    $transaction_id = isset($_POST['transaction_id']) ? sanitize_text_field($_POST['transaction_id']) : null;
    $notes          = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : null;

    $now = current_time('mysql');

    // 1) Update wp_doctors = latest snapshot
    $updated = $wpdb->update(
        $table_doctors,
        [
            'payment_proof_url' => $file_url,
            'payment_proof_attachment_id' => (int)$attach_id,
            'payment_updated_at' => $now,
        ],
        ['id' => (int)$doctor->id],
        ['%s', '%d', '%s'],
        ['%d']
    );

    if ($updated === false) {
        wp_send_json_error(['message' => 'DB update failed.'], 500);
    }

    // 2) Insert into history table = permanent log
    $inserted = $wpdb->insert(
        $table_history,
        [
            'doctor_id' => (int)$doctor->id,
            'wp_user_id' => (int)$current_user_id,

            'payment_proof_url' => $file_url,
            'payment_proof_attachment_id' => (int)$attach_id,

            'amount' => $amount,
            'currency' => $currency,
            'payment_method' => $payment_method,
            'transaction_id' => $transaction_id,

            'status' => 'uploaded',
            'notes' => $notes,

            'uploaded_file_name' => isset($file['name']) ? sanitize_file_name($file['name']) : null,
            'uploaded_mime' => isset($movefile['type']) ? sanitize_text_field($movefile['type']) : null,

            'ip_address' => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : null,
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? substr(sanitize_text_field($_SERVER['HTTP_USER_AGENT']), 0, 255) : null,

            'created_at' => $now,
            'updated_at' => null,
        ],
        [
            '%d','%d',
            '%s','%d',
            '%f','%s','%s','%s',
            '%s','%s',
            '%s','%s',
            '%s','%s',
            '%s','%s'
        ]
    );

    if ($inserted === false) {
        // Important: latest snapshot is already updated. History insert failed.
        // Log it so you can fix DB permissions/schema issues.
        wp_send_json_error(['message' => 'Payment history insert failed.'], 500);
    }

    wp_send_json_success([
        'message' => 'Payment proof uploaded & saved in history.',
        'url' => $file_url,
        'attachment_id' => (int)$attach_id,
        'history_id' => (int)$wpdb->insert_id
    ]);
}


add_filter('login_redirect', function($redirect_to, $requested, $user) {
  if (is_wp_error($user) || !$user) return $redirect_to;

  $login_type = get_user_meta($user->ID, 'login_type', true);
  if ($login_type === 'doctor') {
    return site_url('/doctor-details/');
  }
  return $redirect_to;
}, 10, 3);


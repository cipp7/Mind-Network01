<?php
/*
Template Name: Add Note Page (With Title & File Upload)
*/
get_header();

global $wpdb;

$table_name   = 'fzil_wpddb_patients';
$notes_table  = 'fzil_wpddb_patient_notes_new';

// Accept email via POST (primary) or GET as a fallback
$email = isset($_POST['email']) ? sanitize_email($_POST['email']) :
         (isset($_GET['email']) ? sanitize_email($_GET['email']) : '');

$patient = $email ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE email = %s LIMIT 1", $email)) : null;

// ===== Handle form submit early (so $patient is already available) =====
if ($patient && isset($_POST['save_note'])) {
    // Nonce check
    if (!isset($_POST['save_note_nonce']) || !wp_verify_nonce($_POST['save_note_nonce'], 'save_note_action')) {
        echo '<div class="notice notice-error"><p>Security check failed.</p></div>';
    } else {
        $note_title      = sanitize_text_field($_POST['note_title']);
        $note            = sanitize_textarea_field($_POST['note']);
        $next_visit_date = sanitize_text_field($_POST['next_visit_date']);
        $categories      = isset($_POST['category']) && is_array($_POST['category'])
                            ? array_map('sanitize_text_field', $_POST['category']) : [];
        $category_combined = implode(', ', array_filter($categories));

        $patient_id      = (int) $patient->id;
        $current_user_id = get_current_user_id();

        // Build next note_id (NOTE-YYYY-####)
        $latest_id   = (int) $wpdb->get_var("SELECT MAX(id) FROM {$notes_table}");
        $increment_id = $latest_id ? ($latest_id + 1) : 1;
        $note_id     = 'NOTE-' . date('Y') . '-' . str_pad($increment_id, 4, '0', STR_PAD_LEFT);

        // Handle upload (optional)
        $file_url = '';
        if (!empty($_FILES['note_file']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attachment_id = media_handle_upload('note_file', 0);
            if (!is_wp_error($attachment_id)) {
                $file_url = wp_get_attachment_url($attachment_id);
            } else {
                echo '<div class="notice notice-error"><p>File upload failed: ' . esc_html($attachment_id->get_error_message()) . '</p></div>';
            }
        }

        // Insert note
        $inserted = $wpdb->insert($notes_table, [
            'note_id'         => $note_id,
            'patient_id'      => $patient_id,
            'user_id'         => $current_user_id,
            'title'           => $note_title,
            'category'        => $category_combined,
            'note_content'    => $note,
            'file_url'        => $file_url,
            'next_visit_date' => $next_visit_date,
            'created_at'      => current_time('mysql'),
        ], ['%s','%d','%d','%s','%s','%s','%s','%s','%s']);

        if ($inserted) {
            echo '<div class="notice notice-success"><p>Note saved successfully with ID: ' . esc_html($note_id) . '</p></div>';
            // Optional: redirect to avoid resubmits
            // wp_safe_redirect( add_query_arg(['saved' => 1], site_url('/add-note-page/')) ); exit;
        } else {
            echo '<div class="notice notice-error"><p>Failed to save the note. Please try again.</p></div>';
        }
    }
}

// Doctor info (optional)
$doctor_info = '';
if ($patient) {
    $last_note_user_id = $wpdb->get_var($wpdb->prepare(
        "SELECT user_id FROM {$notes_table} WHERE patient_id = %d ORDER BY created_at DESC LIMIT 1",
        $patient->id
    ));
    if ($last_note_user_id) {
        $doctor_post = $wpdb->get_row($wpdb->prepare(
            "SELECT ID, post_title FROM fzil_posts WHERE post_author = %d AND post_type='wpddb_doctor' ORDER BY post_date DESC LIMIT 1",
            $last_note_user_id
        ));
        if ($doctor_post) {
            $designation = $wpdb->get_var($wpdb->prepare(
                "SELECT meta_value FROM fzil_postmeta WHERE post_id = %d AND meta_key = 'wpddb_doctor_designation'",
                $doctor_post->ID
            ));
            if ($designation) {
                $doctor_info = '<p><strong>' . esc_html($designation) . ':</strong> ' . esc_html($doctor_post->post_title) . '</p>';
            }
        }
    }
}
?>

<div class="wrap">
    <h2>Add Note for Patient</h2>

    <?php if ($patient) : ?>
        <p><strong>Patient Name:</strong> <?php echo esc_html($patient->full_name); ?></p>
        <p><strong>Email:</strong> <?php echo esc_html($patient->email); ?></p>
        <p><strong>Phone:</strong> <?php echo esc_html($patient->phone); ?></p>
        <p><strong>Last Visit:</strong> <?php echo !empty($patient->last_visit) ? esc_html($patient->last_visit) : 'Not Found'; ?></p>
        <?php echo $doctor_info ? $doctor_info : '<p><strong>Doctor Info:</strong> Not Found</p>'; ?>

        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('save_note_action', 'save_note_nonce'); ?>

            <!-- Keep context on submit -->
            <input type="hidden" name="email" value="<?php echo esc_attr($patient->email); ?>">
            <input type="hidden" name="patient_id" value="<?php echo (int) $patient->id; ?>">

            <p><strong>Select up to 3 Categories:</strong></p>
            <div id="category-container">
                <select name="category[]" required>
                    <option value="">Select Category</option>
                    <?php
                    $categories = ["Infants & Toddlers", "Children", "Adolescents & Teenagers", "Adults", "Elderly / Seniors", "Anxiety Disorders", "Depressive Disorders", "Trauma & Stress", "Personality Disorders", "Psychotic Disorders", "Mood Disorders"];
                    foreach ($categories as $cat) {
                        echo '<option value="' . esc_attr($cat) . '">' . esc_html($cat) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <button type="button" id="add-category-button" class="button">Add Another Category</button>

            <p style="margin-top:14px;"><strong>Title:</strong></p>
            <input type="text" name="note_title" placeholder="Enter note title here" style="width: 100%;" required>

            <p><strong>Add Note:</strong></p>
            <textarea name="note" rows="4" cols="50" placeholder="Write your note here..." required></textarea>

            <p><strong>Upload File (image, pdf, document):</strong></p>
            <input type="file" name="note_file" accept="image/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain">

            <p><strong>Update Next Visit Date:</strong></p>
            <input type="date" name="next_visit_date" required>

            <br><br>
            <input type="submit" name="save_note" class="button button-primary" value="Save Note">
        </form>

        <script>
        document.getElementById('add-category-button').addEventListener('click', function() {
            var container = document.getElementById('category-container');
            var selects = container.getElementsByTagName('select');
            if (selects.length < 3) {
                var clone = selects[0].cloneNode(true);
                // Reset the cloned select to blank
                clone.selectedIndex = 0;
                container.appendChild(clone);
            } else {
                alert('You can only select up to 3 categories.');
            }
        });
        </script>

    <?php else : ?>
        <p style="color:red;">Patient not found. Please ensure the email was posted to this page.</p>
    <?php endif; ?>
</div>

<?php get_footer(); ?>

<?php
/*
Template Name: Patient Details Page
*/
get_header();

global $wpdb;
$table_name  = 'fzil_wpddb_patients';
$notes_table = 'fzil_wpddb_patient_notes_new';
$patient_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

/** ===================== ADDED: helper to generate next note_id ===================== */
if (!function_exists('wpddb_next_note_id')) {
    function wpddb_next_note_id($wpdb, $notes_table) {
        $year   = current_time('Y');
        $prefix = "NOTE-{$year}-";
        $like   = $wpdb->esc_like($prefix) . '%';
        $last   = $wpdb->get_var($wpdb->prepare(
            "SELECT note_id FROM {$notes_table} WHERE note_id LIKE %s ORDER BY note_id DESC LIMIT 1",
            $like
        ));
        $n = 1;
        if ($last && preg_match('/^NOTE-\d{4}-(\d{4})$/', $last, $m)) {
            $n = intval($m[1]) + 1;
        }
        return sprintf('NOTE-%s-%04d', $year, $n);
    }
}
/** ================================================================================ */

$patient = $patient_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $patient_id)) : null;
$latest_note = $patient ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $notes_table WHERE patient_id = %d ORDER BY created_at DESC LIMIT 1", $patient->id)) : null;
$notes = $patient ? $wpdb->get_results($wpdb->prepare("SELECT * FROM $notes_table WHERE patient_id = %d ORDER BY next_visit_date DESC", $patient->id)) : [];
$assigned_doctors = [];

// Fetch all doctors once
$doctors = $wpdb->get_results("
    SELECT ID, post_title, post_author
    FROM fzil_posts
    WHERE post_type = 'wpddb_doctor' AND post_status = 'publish'
    ORDER BY post_title ASC
");

if ($notes) {
    foreach ($notes as $note) {
        $user_id = $note->user_id;

        // Get doctor post where post_author = user_id and post_type = 'wpddb_doctor'
        $doctor_post = $wpdb->get_row($wpdb->prepare("
            SELECT ID, post_title 
            FROM fzil_posts 
            WHERE post_author = %d AND post_type = 'wpddb_doctor' 
            ORDER BY post_date DESC 
            LIMIT 1", 
        $user_id));

        if ($doctor_post) {
            // Get designation from postmeta
            $designation = $wpdb->get_var($wpdb->prepare("
                SELECT meta_value 
                FROM fzil_postmeta 
                WHERE post_id = %d AND meta_key = 'wpddb_doctor_designation' 
                LIMIT 1", 
            $doctor_post->ID));

            // Only add if both values exist
            if ($designation && $doctor_post->post_title) {
                $assigned_doctors[$designation] = $doctor_post->post_title;
            }
        }
    }
}

?>

<div class="wrap">
    <h2>Patient Details</h2>

    <?php if ($patient) : ?>
        <p><strong>Name:</strong> <?php echo esc_html($patient->full_name); ?></p>

        <form method="post">
            <p><strong>Latest Category from Notes:</strong>
                <?php echo ($latest_note && !empty($latest_note->category)) ? esc_html($latest_note->category) : '<em>No category found in notes.</em>'; ?>
            </p>
            <p><strong>Latest Next Visit Date from Notes:</strong>
                <?php echo ($latest_note && !empty($latest_note->next_visit_date) && $latest_note->next_visit_date !== '0000-00-00') ? esc_html($latest_note->next_visit_date) : '<em>No next visit date found in notes.</em>'; ?>
            </p>
        </form>

        <h3>Assigned Doctors</h3>
        <?php if (!empty($assigned_doctors)) : ?>
            <?php foreach ($assigned_doctors as $designation => $name) : ?>
                <p><strong><?php echo esc_html($designation); ?>:</strong>  <?php echo esc_html($name); ?></p>
            <?php endforeach; ?>
        <?php else : ?>
            <p><em>No doctors assigned yet.</em></p>
        <?php endif; ?>

        <?php if ($patient) : ?>
            <h3>Recommend Therapist</h3>

            <form method="post" id="recommendTherapistForm" style="margin-bottom:20px;">
                <?php wp_nonce_field('recommend_doctor', 'recommend_doctor_nonce'); ?>

                <label for="recommended_doctor_id" style="font-weight:600;display:block;margin-bottom:6px;">
                    Recommend Therapist
                </label>

                <select name="recommended_doctor_id" id="recommended_doctor_id"
                        style="min-width:320px;padding:8px;border:1px solid #ccc;border-radius:6px;">
                    <option value="">— Select Doctor —</option>
                    <?php if (!empty($doctors)) : ?>
                        <?php foreach ($doctors as $doc) : ?>
                            <option value="<?php echo esc_attr($doc->ID); ?>"
                                    data-author="<?php echo esc_attr($doc->post_author); ?>">
                                <?php echo esc_html($doc->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>

                <!-- Hidden fields (preview / convenience only; author is re-fetched securely on submit) -->
                <input type="hidden" name="recommended_doctor_author" id="recommended_doctor_author" value="">
                <input type="hidden" name="recommended_doctor_title" id="recommended_doctor_title" value="">

                <div id="recommendedDoctorPreview" style="margin-top:10px;color:#444;"></div>

                <button type="submit" name="save_recommendation" class="button" style="margin-top:10px;">
                    Save Recommendation
                </button>
            </form>
        <?php endif; ?>

        <h3>Session Notes</h3>
        <?php if ($notes) : ?>
            <?php foreach ($notes as $note) : ?>
                <div style="background:#f9f9f9; padding:10px; margin-bottom:10px; border-left:5px solid #0073aa;">
                    <strong>Date:</strong> <?php echo esc_html($note->next_visit_date); ?><br>
                    <strong>Category:</strong> <?php echo esc_html($note->category); ?><br>
                    <strong>Note:</strong> <?php echo esc_html($note->note_content); ?><br>
                    <button class="button button-small view-note-btn"
                        data-note='<?php echo htmlspecialchars(json_encode($note), ENT_QUOTES, 'UTF-8'); ?>'>
                        View
                    </button>

                    <button 
                        class="button button-small edit-note-btn" 
                        data-note='<?php echo htmlspecialchars(json_encode($note), ENT_QUOTES, 'UTF-8'); ?>'>
                        Edit
                    </button>

                    <form method="post" onsubmit="return confirm('Are you sure you want to delete this note?');" style="display:inline;">
                        <input type="hidden" name="delete_note_id" value="<?php echo esc_attr($note->id); ?>">
                        <button type="submit" class="button button-small" style="background-color:#d9534f; color:white;">Delete</button>
                    </form>

                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <p>No session notes found.</p>
        <?php endif; ?>

        <a href="<?php echo site_url('/add-note-page/?email=' . urlencode($patient->email)); ?>" class="button button-primary">Add Note</a>

    <?php else : ?>
        <p style="color:red;">Patient not found.</p>
    <?php endif; ?>
</div>

<!-- Modal -->
<div id="noteModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999;">
    <div style="
        background:#fff; 
        max-width:500px; 
        margin:100px auto; 
        padding:20px; 
        position:relative; 
        border-radius:8px;
        max-height:80vh;
        overflow-y:auto;
    ">
        <button id="closeModal" style="
            position:absolute; 
            top:10px; 
            right:10px;
            background:#e53935;
            color:#fff;
            border:none;
            border-radius:50%;
            width:30px;
            height:30px;
            cursor:pointer;
            font-size:16px;
        ">×</button>
        <h3>Note Details</h3>
        <div id="modalContent"></div>
    </div>
</div>

<!-- Modal Overlay -->
<div id="editNoteModal" style="
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0, 0, 0, 0.4);
    z-index: 10000;
    backdrop-filter: blur(3px);
">
    <div style="
        background: #fff;
        max-width: 600px;
        margin: 80px auto;
        padding: 30px 25px;
        position: relative;
        border-radius: 12px;
        overflow-y: auto;
        max-height: 80vh;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        font-family: 'Segoe UI', sans-serif;
    ">
        <button id="closeEditModal" style="
            position: absolute;
            top: 15px;
            right: 15px;
            background: #e53935;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            font-size: 18px;
            cursor: pointer;
            transition: background 0.3s;
        " title="Close">&times;</button>

        <h2 style="margin-top: 0; margin-bottom: 25px; font-size: 22px; color: #333;">Edit Note</h2>

        <form method="post" id="editNoteForm" enctype="multipart/form-data" style="">
            <input type="hidden" name="edit_note_id" id="edit_note_id">
            <input type="hidden" name="recommended_doctor_id" id="edit_recommended_doctor_id" value="">
            <input type="hidden" name="recommended_doctor_author" id="edit_recommended_doctor_author" value="">
            <input type="hidden" name="recommended_doctor_title" id="edit_recommended_doctor_title" value="">

            <div style="margin-bottom: 15px;">
                <label for="edit_category" style="font-weight: 600;">Category:</label><br>
                <input type="text" name="edit_category" id="edit_category" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px;" required>
            </div>

            <div style="margin-bottom: 15px;">
                <label for="edit_next_visit_date" style="font-weight: 600;">Next Visit Date:</label><br>
                <input type="date" name="edit_next_visit_date" id="edit_next_visit_date" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px;" required>
            </div>

            <div style="margin-bottom: 15px;">
                <label for="edit_note_title" style="font-weight: 600;">Title:</label><br>
                <input type="text" name="edit_note_title" id="edit_note_title" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label for="edit_note_content" style="font-weight: 600;">Note Content:</label><br>
                <textarea name="edit_note_content" id="edit_note_content" rows="5" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px;" required></textarea>
            </div>

            <div style="margin-bottom: 20px;">
                <label for="edit_note_file_upload" style="font-weight: 600;">Upload File:</label><br>
                <input type="file" name="edit_note_file_upload" id="edit_note_file_upload">
                <input type="hidden" name="existing_note_file_url" id="existing_note_file_url">
            </div>

            <div style="text-align: right;">
                <button type="submit" class="button" style="
                    background-color: #0073aa;
                    color: white;
                    border: none;
                    padding: 10px 20px;
                    font-size: 16px;
                    border-radius: 6px;
                    cursor: pointer;
                    transition: background 0.3s ease;
                " onmouseover="this.style.backgroundColor='#005f8d'" onmouseout="this.style.backgroundColor='#0073aa'">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('noteModal');
    const modalContent = document.getElementById('modalContent');
    const closeModal = document.getElementById('closeModal');

    document.querySelectorAll('.view-note-btn').forEach(button => {
        button.addEventListener('click', function () {
            const note = JSON.parse(this.getAttribute('data-note'));

            let html = `
                <p><strong>Note ID:</strong> ${note.id}</p>
                <p><strong>Note Reference:</strong> ${note.note_id}</p>
                <p><strong>Category:</strong> ${note.category}</p>
                <p><strong>Note Content:</strong> ${note.note_content}</p>
                <p><strong>Next Visit Date:</strong> ${note.next_visit_date}</p>
                <p><strong>Created At:</strong> ${note.created_at}</p>
                ${note.title ? `<p><strong>Title:</strong> ${note.title}</p>` : ''}
                ${note.file_url ? `<p><strong>File:</strong> <a href="${note.file_url}" target="_blank">View File</a></p>` : ''}
            `;

            modalContent.innerHTML = html;
            modal.style.display = 'block';
        });
    });

    closeModal.addEventListener('click', function() {
        modal.style.display = 'none';
    });

    window.addEventListener('click', function(e) {
        if (e.target == modal) {
            modal.style.display = 'none';
        }
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const editModal = document.getElementById('editNoteModal');
    const closeEditModal = document.getElementById('closeEditModal');

    document.querySelectorAll('.edit-note-btn').forEach(button => {
        button.addEventListener('click', function () {
            const note = JSON.parse(this.getAttribute('data-note'));

            document.getElementById('edit_note_id').value = note.id;
            document.getElementById('edit_category').value = note.category || '';
            document.getElementById('edit_next_visit_date').value = note.next_visit_date || '';
            document.getElementById('edit_note_title').value = note.title || '';
            document.getElementById('edit_note_content').value = note.note_content || '';
            document.getElementById('existing_note_file_url').value = note.file_url || '';

            editModal.style.display = 'block';
        });
    });

    closeEditModal.addEventListener('click', function () {
        editModal.style.display = 'none';
    });

    window.addEventListener('click', function (e) {
        if (e.target === editModal) {
            editModal.style.display = 'none';
        }
    });
});
</script>

<!-- ===================== ADDED: keep select + hidden fields in sync & show preview ===================== -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectEl  = document.getElementById('recommended_doctor_id');
    const authorEl  = document.getElementById('recommended_doctor_author');
    const titleEl   = document.getElementById('recommended_doctor_title');
    const previewEl = document.getElementById('recommendedDoctorPreview');

    if (selectEl) {
        selectEl.addEventListener('change', function () {
            const opt    = selectEl.options[selectEl.selectedIndex] || {};
            const id     = opt.value || '';
            const author = opt.getAttribute ? (opt.getAttribute('data-author') || '') : '';
            const title  = opt.text || '';

            if (authorEl) authorEl.value = author;
            if (titleEl)  titleEl.value  = title;

            previewEl.innerHTML = id
                ? `<strong>Selected:</strong> ${title} &nbsp;|&nbsp; <strong>ID:</strong> ${id} &nbsp;|`
                : '';
        });
    }
});
</script>
<!-- ================================================================================= -->

<?php
if (isset($_POST['update_patient']) && $patient) {
    $next_visit_date = sanitize_text_field($_POST['next_visit_date']);
    $wpdb->update(
        $table_name,
        ['next_visit' => $next_visit_date],
        ['id' => $patient->id]
    );
    echo '<div class="notice notice-success"><p>Next visit date updated successfully.</p></div>';
    echo '<meta http-equiv="refresh" content="1">';
}

/** Save Recommendation -> insert note with user_id = LOGGED-IN USER */
if (isset($_POST['save_recommendation']) && $patient) {
    if (!isset($_POST['recommend_doctor_nonce']) || !wp_verify_nonce($_POST['recommend_doctor_nonce'], 'recommend_doctor')) {
        echo '<div class="notice notice-error"><p>Security check failed.</p></div>';
    } else {
        $doctor_post_id = isset($_POST['recommended_doctor_id']) ? intval($_POST['recommended_doctor_id']) : 0;

        if ($doctor_post_id <= 0) {
            echo '<div class="notice notice-error"><p>Please select a therapist.</p></div>';
        } else {
            // get logged-in user id
            $current_user_id = get_current_user_id();
            if (!$current_user_id) {
                echo '<div class="notice notice-error"><p>You must be logged in to save a recommendation.</p></div>';
            } else {
                // verify the selected doctor from DB (don’t trust the form)
                $doctor = $wpdb->get_row($wpdb->prepare(
                    "SELECT ID, post_title 
                     FROM fzil_posts 
                     WHERE ID = %d AND post_type = 'wpddb_doctor' AND post_status = 'publish'
                     LIMIT 1",
                    $doctor_post_id
                ));

                if (!$doctor) {
                    echo '<div class="notice notice-error"><p>Invalid therapist selection.</p></div>';
                } else {
                    // helper that already exists in your file
                    $new_note_id = wpddb_next_note_id($wpdb, $notes_table);

                    $ok = $wpdb->insert(
                        $notes_table,
                        array(
                            'note_id'         => $new_note_id,
                            'patient_id'      => intval($patient->id),
                            'user_id'         => intval($current_user_id), // <-- LOGGED-IN USER HERE
                            'category'        => 'Recommendation',
                            'note_content'    => 'Therapist recommended: ' . $doctor->post_title,
                            'next_visit_date' => '0000-00-00',
                            'created_at'      => current_time('mysql'),
                            'title'           => 'Recommended Therapist',
                            'file_url'        => ''
                        ),
                        array('%s','%d','%d','%s','%s','%s','%s','%s')
                    );

                    if ($ok) {
                        echo '<div class="notice notice-success"><p>Recommendation saved. user_id = ' . esc_html($current_user_id) . ' for patient_id = ' . esc_html($patient->id) . ' (note_id ' . esc_html($new_note_id) . ').</p></div>';
                        echo '<meta http-equiv="refresh" content="1">';
                    } else {
                        echo '<div class="notice notice-error"><p>Failed to save recommendation.</p></div>';
                    }
                }
            }
        }
    }
}

/** =============================================================================================================== */

if (isset($_POST['edit_note_id']) && $patient) {
    $note_id = intval($_POST['edit_note_id']);
    $updated_category = sanitize_text_field($_POST['edit_category']);
    $updated_next_visit = sanitize_text_field($_POST['edit_next_visit_date']);
    $updated_title = sanitize_text_field($_POST['edit_note_title']);
    $updated_note = sanitize_textarea_field($_POST['edit_note_content']);
    $updated_file_url = sanitize_text_field($_POST['existing_note_file_url']); // default to old file

    if (!empty($_FILES['edit_note_file_upload']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $uploaded_file = $_FILES['edit_note_file_upload'];
        $upload_overrides = ['test_form' => false];
        $movefile = wp_handle_upload($uploaded_file, $upload_overrides);

        if ($movefile && !isset($movefile['error'])) {
            $updated_file_url = $movefile['url'];
        } else {
            echo '<div class="notice notice-error"><p>File upload failed: ' . esc_html($movefile['error']) . '</p></div>';
        }
    }

    $wpdb->update(
        $notes_table,
        [
            'category' => $updated_category,
            'next_visit_date' => $updated_next_visit,
            'title' => $updated_title,
            'note_content' => $updated_note,
            'file_url' => $updated_file_url,
        ],
        ['id' => $note_id]
    );

    echo '<div class="notice notice-success"><p>Note updated successfully.</p></div>';
    echo '<meta http-equiv="refresh" content="1">';
}

if (isset($_POST['delete_note_id']) && $patient) {
    $delete_note_id = intval($_POST['delete_note_id']);

    // Delete the note
    $wpdb->delete($notes_table, ['id' => $delete_note_id]);

    echo '<div class="notice notice-success"><p>Note deleted successfully.</p></div>';
    echo '<meta http-equiv="refresh" content="1">';
}

?>

<?php get_footer(); ?>

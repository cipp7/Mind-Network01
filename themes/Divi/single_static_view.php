<?php

/**
 * Template Name: Doctors Single Static View (No foreach)
 */

if (! defined('ABSPATH')) exit;

get_header();
global $wpdb;

$doctors_table = 'wp_doctors';
$clinics_table = 'wp_doctor_clinics';

// Get ONE doctor (latest)
$doctor = $wpdb->get_row("SELECT * FROM {$doctors_table} ORDER BY id DESC LIMIT 1");
echo '<pre>';
print_r($doctor);
echo '</pre>';


// If no doctor found
if (! $doctor) {
    echo '<div style="max-width:1100px;margin:30px auto;font-family:Arial;"><p><strong>No doctors found.</strong></p></div>';
    get_footer();
    exit;
}

// Get ONE clinic for that doctor (latest clinic)
$clinic = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * FROM {$clinics_table} WHERE doctor_id = %d ORDER BY id DESC LIMIT 1",
        $doctor->id
    )
);
?>

<style>
    .container {
        max-width: 1100px;
        margin: 30px auto;
        font-family: Arial
    }

    .card {
        border: 1px solid #ddd;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 25px;
        background: #fff
    }

    .row {
        display: flex;
        gap: 20px;
        flex-wrap: wrap
    }

    .photo {
        width: 120px;
        height: 120px;
        border-radius: 10px;
        object-fit: cover;
        border: 1px solid #ccc
    }

    .title {
        font-size: 22px;
        font-weight: bold;
        margin: 0
    }

    .muted {
        color: #555;
        margin: 4px 0
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px
    }

    th,
    td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: left
    }

    th {
        background: #f5f5f5
    }

    .badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 20px;
        background: #eee;
        font-size: 12px
    }
</style>

<div class="container">
    <h1>Doctor & Clinic (Static View)</h1>

    <div class="card">

        <div class="row">
            <div>
                <?php if (! empty($doctor->profile_photo_url)): ?>
                    <img class="photo" src="<?php echo esc_url($doctor->profile_photo_url); ?>" alt="<?php echo esc_attr($doctor->doctor_name); ?>">
                <?php else: ?>
                    <div class="photo" style="display:flex;align-items:center;justify-content:center;color:#999">
                        No Photo
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <p class="title"><?php echo esc_html($doctor->doctor_name); ?></p>
                <p class="muted"><?php echo esc_html($doctor->designation); ?></p>
                <p class="muted"><strong>Speciality:</strong> <?php echo esc_html($doctor->speciality); ?></p>
                <p class="muted"><strong>Degree:</strong> <?php echo esc_html($doctor->degree); ?></p>
                <p class="muted"><strong>Department:</strong> <?php echo esc_html($doctor->department); ?></p>
                <p class="muted"><strong>Workplace:</strong> <?php echo esc_html($doctor->workplace); ?></p>

                <?php if (isset($doctor->is_clinic_admin) && intval($doctor->is_clinic_admin) === 1): ?>
                    <span class="badge">Clinic Admin</span>
                <?php endif; ?>
            </div>
        </div>

        <h3>Clinic</h3>

        <?php if (! $clinic): ?>
            <p>No clinics linked.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>#</th>
                    <th>Clinic Name</th>
                    <th>Phone</th>
                    <th>Created</th>
                </tr>
                <tr>
                    <td>1</td>
                    <td><?php echo esc_html($clinic->clinic_name); ?></td>
                    <td><?php echo esc_html($clinic->clinic_phone); ?></td>
                    <td><?php echo esc_html($clinic->created_at); ?></td>
                </tr>
            </table>
        <?php endif; ?>

    </div>
</div>

<?php get_footer(); ?>
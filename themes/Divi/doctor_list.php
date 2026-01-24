<?php

/**
 * Template Name: Doctors Static View
 */

if (! defined('ABSPATH')) exit;

get_header();
global $wpdb;

// Table names (prefix-safe)
$doctors_table = 'wp_doctors';
$clinics_table = 'wp_doctor_clinics';

// Fetch all doctors
$doctors = $wpdb->get_results("SELECT * FROM {$doctors_table} ORDER BY id DESC");
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

    <h1>Doctors & Clinics</h1>

    <?php if (empty($doctors)): ?>
        <p>No doctors found.</p>
    <?php endif; ?>

    <?php foreach ($doctors as $doctor): ?>

        <?php
        // Fetch clinics for this doctor
        $clinics = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$clinics_table} WHERE doctor_id = %d",
                $doctor->id
            )
        );
        ?>

        <div class="card">

            <div class="row">
                <div>
                    <?php if (! empty($doctor->profile_photo_url)): ?>
                        <img class="photo" src="<?php echo esc_url($doctor->profile_photo_url); ?>">
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

                    <?php if (intval($doctor->is_clinic_admin) === 1): ?>
                        <span class="badge">Clinic Admin</span>
                    <?php endif; ?>
                </div>
            </div>

            <h3>Clinics</h3>

            <?php if (empty($clinics)): ?>
                <p>No clinics linked.</p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Clinic Name</th>
                        <th>Phone</th>

                        <th>Created</th>
                    </tr>

                    <?php $sr = 1;
                    foreach ($clinics as $clinic): ?>
                        <tr>
                            <td><?php echo $sr++; ?></td>
                            <td><?php echo esc_html($clinic->clinic_name); ?></td>
                            <td><?php echo esc_html($clinic->clinic_phone); ?></td>

                            <td><?php echo esc_html($clinic->created_at); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>

        </div>

    <?php endforeach; ?>

</div>

<?php get_footer(); ?>
<?php
/*
Template Name: Doctor Dashboard
*/

get_header();

$current_user = wp_get_current_user();

if ( array_intersect(['doctor', 'administrator'], (array) $current_user->roles) ) {

    // Only for doctor role
    ?>
    <div class="doctor-dashboard">
        <h2>Welcome, Dr. <?php echo esc_html($current_user->display_name); ?></h2>

        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('update_doctor_profile'); ?>

            <p>
                <label for="doctor_bio">Bio:</label><br>
                <textarea name="doctor_bio" rows="5" cols="50"><?php echo esc_textarea(get_user_meta($current_user->ID, 'doctor_bio', true)); ?></textarea>
            </p>

            <p>
                <label for="doctor_speciality">Speciality:</label><br>
                <input type="text" name="doctor_speciality" value="<?php echo esc_attr(get_user_meta($current_user->ID, 'doctor_speciality', true)); ?>" />
            </p>

            <p>
                <input type="submit" name="doctor_profile_submit" value="Update Profile" />
            </p>
        </form>
    </div>
    <?php

    // Handle form submission
    if ( isset($_POST['doctor_profile_submit']) && check_admin_referer('update_doctor_profile') ) {
        update_user_meta($current_user->ID, 'doctor_bio', sanitize_textarea_field($_POST['doctor_bio']));
        update_user_meta($current_user->ID, 'doctor_speciality', sanitize_text_field($_POST['doctor_speciality']));
        echo "<p style='color:green;'>Profile updated successfully!</p>";
    }

} else {
    // Not a doctor
    echo "<p>Access Denied.</p>";
}

get_footer();

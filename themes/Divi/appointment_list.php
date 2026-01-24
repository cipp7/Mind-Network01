<?php
/*
Template Name: Doctor Booking Page
*/
get_header();

$current_user = wp_get_current_user();

echo "<h2>Welcome, " . esc_html($current_user->display_name) . "</h2>";

global $wpdb;

// Updated table name
$table_name = 'fzil_wpddb_bookings';

// Fetch bookings for the logged-in user (assuming doctor_id maps to user ID)
$bookings = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM $table_name ORDER BY created_at DESC",
        $current_user->ID
    )
);

if ($bookings) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr>
            <th>Booking ID</th>
            <th>Day</th>
            <th>Time</th>
            <th>Status</th>
            <th>Present Status</th>
            <th>Created At</th>
            <th>Updated At</th>
          </tr>";
    foreach ($bookings as $booking) {
        echo "<tr>";
        echo "<td>" . esc_html($booking->booking_id) . "</td>";
        echo "<td>" . esc_html($booking->day) . "</td>";
        echo "<td>" . esc_html($booking->time) . "</td>";
        echo "<td>" . esc_html($booking->status) . "</td>";
        echo "<td>" . esc_html($booking->booking_present_status) . "</td>";
        echo "<td>" . esc_html($booking->created_at) . "</td>";
        echo "<td>" . esc_html($booking->updated_at) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No bookings found.</p>";
}

get_footer();
?>
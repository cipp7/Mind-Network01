<?php
/* Template Name: Frontend Booking Management */
get_header();

// Access control
if (!current_user_can('edit_posts')) {
    echo "<p>You do not have access to view this page.</p>";
    get_footer();
    exit;
}

global $wpdb, $bookings_table, $patients_table, $posts_table;;
$bookings_table = 'fzil_wpddb_bookings';
$patients_table = 'fzil_wpddb_patients';
$posts_table    = $wpdb->prefix . 'posts';

// Fetch bookings
// Pagination
$per_page = 4; // change to 10/25/50 as you like
$paged    = isset($_GET['bp']) ? max(1, intval($_GET['bp'])) : 1;
$offset   = ($paged - 1) * $per_page;

// Total rows
$total_bookings = (int) $wpdb->get_var("SELECT COUNT(*) FROM $bookings_table");
$total_pages    = (int) ceil($total_bookings / $per_page);

// Fetch bookings for current page only
$bookings = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM $bookings_table ORDER BY created_at DESC LIMIT %d OFFSET %d",
        $per_page,
        $offset
    )
);


// Helpers
function get_patient_info($id) {
    global $wpdb, $patients_table;
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $patients_table WHERE id = %d", $id));
}
function get_post_title_by_id($post_id) {
    global $wpdb, $posts_table;
    return $wpdb->get_var($wpdb->prepare("SELECT post_title FROM $posts_table WHERE ID = %d AND post_status = 'publish'", $post_id));
}

// Nonce + ajax URL for cancel action
$cancel_nonce = wp_create_nonce('cancel_booking_nonce');
$ajax_url     = admin_url('admin-ajax.php');

echo '<style>
.booking-tools { display:flex; gap:8px; align-items:center; margin-top:10px; }
.booking-tools input[type="text"] { padding:8px 10px; min-width:280px; border:1px solid #ddd; border-radius:6px; }
.booking-tools button { padding:8px 12px; border:1px solid #0073aa; background:#0073aa; color:#fff; border-radius:6px; cursor:pointer; }
.booking-tools button.secondary { background:#fff; color:#0073aa; }
.booking-tools small { color:#666; margin-left:auto; }

.booking-table { width: 100%; border-collapse: collapse; margin-top: 12px; font-family: Arial; }
.booking-table th, .booking-table td { border: 1px solid #eee; padding: 12px; text-align: left; }
.booking-table th { background-color: #f9f9f9; font-weight: bold; }
.booking-table .status { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 13px; color: white; }
.status.approved { background-color: #28a745; }
.status.expired  { background:#dc3545; }
.status.cancel   { background:#6c757d; }
.status.upcoming  { background:greenyellow; }
.actions button { background: none; border: none; cursor: pointer; font-size: 18px; }
.actions .btn-cancel { color: red; }
.actions .btn-top { color: #0073aa; margin-left: 8px; }
.actions button:disabled { opacity:.6; cursor:not-allowed; }

.booking-footer { background-color: #fafafa; padding: 10px 20px; border-top: 1px solid #eee; font-size: 14px; }
.booking-footer i { margin-right: 6px; color: #555; }
.toggle-footer-btn { cursor: pointer; font-weight: bold; font-size: 16px; color: #0073aa; border: none; background: none; margin-left: 10px; }
.booking-footer-row { display: none; }
.booking-footer-row.visible-row { display: table-row !important; }
.hidden-row { display: none !important; }
</style>';

echo '<h2>Booking Management</h2>';

/* Search tools */
echo '<div class="booking-tools">
        <input type="text" id="bookingSearch" placeholder="Search bookings (name, booking ID, clinic, doctor, day, status)..." />
        <button type="button" onclick="filterBookings()">Search</button>
        <button type="button" class="secondary" onclick="clearSearch()">Clear</button>
        <small>Tip: Type and press Enter</small>
      </div>';

echo '<table class="booking-table" id="bookingTable">';
echo '<thead><tr>
    <th>Booking ID</th>
    <th>Patient Name</th>
    <th>Clinic Name</th>
    <th>Doctor Name</th>
    <th>Day</th>
    <th>Time</th>
    <th>Status</th>
    <th>Actions</th>
</tr></thead><tbody>';

$counter = 0;
foreach ($bookings as $booking) {
    $patient     = get_patient_info($booking->patient_id);
    $doctor_name = get_post_title_by_id($booking->doctor_id) ?: '—';
    $clinic_name = get_post_title_by_id($booking->clinic_id) ?: '—';
    $row_id      = 'footer-' . $counter;
    $btn_id      = 'toggle-btn-' . $counter;

    echo '<tr class="booking-main-row" data-id="' . esc_attr($booking->id) . '" data-pair="' . esc_attr($row_id) . '">';
    echo '<td>' . esc_html($booking->booking_id) . '</td>';
    echo '<td>' . esc_html($patient->full_name ?? '—') . ' <button id="' . esc_attr($btn_id) . '" class="toggle-footer-btn" onclick="toggleFooter(\'' . esc_js($row_id) . '\', \'' . esc_js($btn_id) . '\')">+</button></td>';
    echo '<td>' . esc_html($clinic_name) . '</td>';
    echo '<td>' . esc_html($doctor_name) . '</td>';
    echo '<td>' . esc_html($booking->day) . '</td>';
    echo '<td>' . esc_html(date("h:i A", strtotime($booking->time))) . '</td>';
    echo '<td><span class="status ' . esc_attr(strtolower($booking->booking_present_status)) . '">' . esc_html(ucfirst($booking->booking_present_status)) . '</span></td>';
    echo '<td class="actions" style="display:flex; justify-content:center;">
            <button type="button" class="btn-cancel" title="Cancel booking">❌</button>
            <button type="button" class="btn-top" title="Go to patient list" style="display:none;">🔝</button>
          </td>';
    echo '</tr>';

    echo '<tr id="' . esc_attr($row_id) . '" class="booking-footer-row"><td colspan="8" class="booking-footer">';
    echo '<i class="dashicons dashicons-phone"></i> Phone: ' . esc_html($patient->phone ?? '-') . ' &nbsp;&nbsp;';
    echo '<i class="dashicons dashicons-email"></i> Email: ' . esc_html($patient->email ?? '-') . '';
    echo '</td></tr>';

    $counter++;
}

echo '</tbody></table>';
// Pagination UI
if ($total_pages > 1) {
    $base_url = remove_query_arg('bp');

    echo '<div style="margin-top:14px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">';

    // Prev
    if ($paged > 1) {
        echo '<a style="padding:8px 12px;border:1px solid #ddd;border-radius:6px;text-decoration:none;" href="' .
            esc_url(add_query_arg('bp', $paged - 1, $base_url)) . '">← Prev</a>';
    }

    // Page numbers
    $window = 2;
    $start  = max(1, $paged - $window);
    $end    = min($total_pages, $paged + $window);

    if ($start > 1) {
        echo '<a style="padding:8px 12px;border:1px solid #ddd;border-radius:6px;text-decoration:none;" href="' .
            esc_url(add_query_arg('bp', 1, $base_url)) . '">1</a>';
        if ($start > 2) echo '<span style="padding:8px 6px;">…</span>';
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = ($i === $paged);
        echo '<a style="padding:8px 12px;border:1px solid #ddd;border-radius:6px;text-decoration:none;' .
            ($active ? 'background:#0073aa;color:#fff;border-color:#0073aa;' : '') .
            '" href="' . esc_url(add_query_arg('bp', $i, $base_url)) . '">' . esc_html($i) . '</a>';
    }

    if ($end < $total_pages) {
        if ($end < $total_pages - 1) echo '<span style="padding:8px 6px;">…</span>';
        echo '<a style="padding:8px 12px;border:1px solid #ddd;border-radius:6px;text-decoration:none;" href="' .
            esc_url(add_query_arg('bp', $total_pages, $base_url)) . '">' . esc_html($total_pages) . '</a>';
    }

    // Next
    if ($paged < $total_pages) {
        echo '<a style="padding:8px 12px;border:1px solid #ddd;border-radius:6px;text-decoration:none;" href="' .
            esc_url(add_query_arg('bp', $paged + 1, $base_url)) . '">Next →</a>';
    }

    // Info
    $from = $offset + 1;
    $to   = min($offset + $per_page, $total_bookings);
    echo '<span style="margin-left:auto;color:#666;">Showing ' . esc_html($from) . '–' . esc_html($to) . ' of ' . esc_html($total_bookings) . '</span>';

    echo '</div>';
}

?>

<script>
const BOOKING_AJAX = {
  url: <?php echo json_encode($ajax_url); ?>,
  nonce: <?php echo json_encode($cancel_nonce); ?>
};
const PATIENT_LIST_URL = "https://newapp2025.shrradhasidhwani.com/patient-list-page/";

(function() {
  const input = document.getElementById('bookingSearch');
  if (input) {
    input.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') filterBookings();
      if (e.key === 'Escape') clearSearch();
    });
  }

  // Handle Cancel & Top buttons
  document.addEventListener('click', async function(e){
    const cancelBtn = e.target.closest('.btn-cancel');
    const topBtn = e.target.closest('.btn-top');

    // Cancel booking
    if (cancelBtn) {
      const row = cancelBtn.closest('tr.booking-main-row');
      const id  = row ? row.getAttribute('data-id') : null;
      if (!id) return;
      if (!confirm('Cancel this booking?')) return;

      cancelBtn.disabled = true;
      const original = cancelBtn.textContent;
      cancelBtn.textContent = '…';

      try {
        const form = new FormData();
        form.append('action', 'cancel_booking');
        form.append('id', id);
        form.append('nonce', BOOKING_AJAX.nonce);

        const res  = await fetch(BOOKING_AJAX.url, { method: 'POST', body: form, credentials: 'same-origin' });
        const json = await res.json();

        if (!res.ok || !json?.success) throw new Error(json?.data?.message || 'Failed to cancel');

        const statusEl = row.querySelector('td:nth-child(7) .status');
        if (statusEl) {
          statusEl.className = 'status ' + json.data.class;
          statusEl.textContent = json.data.label;
        }
        row.style.opacity = 0.6;

      } catch (err) {
        alert(err.message || 'Error cancelling booking');
      } finally {
        cancelBtn.disabled = false;
        cancelBtn.textContent = original;
      }
      return;
    }

    // 🔝 redirect to patient list
    if (topBtn) {
      window.open(PATIENT_LIST_URL, '_blank');
    }
  });
})();

function toggleFooter(rowId, btnId) {
  const row = document.getElementById(rowId);
  const btn = document.getElementById(btnId);
  if (row && btn) {
    row.classList.toggle('visible-row');
    btn.innerText = row.classList.contains('visible-row') ? '-' : '+';
  }
}

function filterBookings() {
  const q = (document.getElementById('bookingSearch').value || '').toLowerCase().trim();
  const table = document.getElementById('bookingTable');
  if (!table) return;
  const mainRows = table.querySelectorAll('tbody tr.booking-main-row');

  mainRows.forEach(function(main) {
    const pairId = main.getAttribute('data-pair');
    const footer = pairId ? document.getElementById(pairId) : null;
    let hay = '';
    main.querySelectorAll('td').forEach(td => { hay += ' ' + (td.innerText || td.textContent || ''); });
    hay = hay.toLowerCase();
    const match = !q || hay.indexOf(q) !== -1;
    if (match) {
      main.classList.remove('hidden-row');
      if (footer) footer.classList.remove('hidden-row');
    } else {
      main.classList.add('hidden-row');
      if (footer) footer.classList.add('hidden-row');
    }
  });
}

function clearSearch() {
  const input = document.getElementById('bookingSearch');
  if (input) input.value = '';
  filterBookings();
}
</script>

<?php get_footer(); ?>

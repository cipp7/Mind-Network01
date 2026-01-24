<?php
/*
Template Name: Client Detail (Frontend)
*/
get_header();

if ( ! function_exists('esc_html') ) { exit; }

global $wpdb;

/** Tables **/
$patients_table = 'fzil_wpddb_patients';
$notes_table    = 'fzil_wpddb_patient_notes_new'; // <- your actual notes table
$wp_users_table = $wpdb->users; // wp_users with prefix

/** Get patient id from querystring **/
$patient_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

/** Fetch patient row **/
$patient = null;
if ($patient_id > 0) {
  $patient = $wpdb->get_row(
    $wpdb->prepare(
      "SELECT id, full_name, email, phone
       FROM {$patients_table}
       WHERE id = %d
       LIMIT 1",
      $patient_id
    )
  );
}

/** Safe values **/
$full_name = $patient && !empty($patient->full_name) ? $patient->full_name : '—';
$email     = $patient && !empty($patient->email)     ? $patient->email     : '—';
$phone     = $patient && !empty($patient->phone)     ? $patient->phone     : '—';

/** Pull notes from fzil_wpddb_patient_notes_new
 *  Expected columns: id, note_id, patient_id, user_id, category, note_content, next_visit_date, created_at, title, file_url
 *  Also join wp_users to display author name.
 */
$patient_notes_rows = [];
if ($patient_id > 0) {
  $patient_notes_rows = $wpdb->get_results(
    $wpdb->prepare(
      "SELECT n.id,
              n.note_id,
              n.category,
              n.note_content,
              n.next_visit_date,
              n.created_at,
              n.title,
              n.file_url,
              u.display_name AS author_name
       FROM {$notes_table} AS n
       LEFT JOIN {$wp_users_table} AS u ON u.ID = n.user_id
       WHERE n.patient_id = %d
       ORDER BY n.created_at DESC, n.id DESC",
      $patient_id
    )
  ) ?: [];
}

/** Page text **/
$page_title   = ($full_name !== '—') ? "{$full_name}'s Detail" : "Client Detail";
$page_subhead = "Shradha Sidhwani / Client Detail";

/** Back link **/
$back_url = site_url('/patient-list-page/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Client Detail | Datalogix Clinic</title>
<style>
  body{margin:0;padding:0;background:#f9fafb;font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial}
  .client-wrap{max-width:1060px;margin:24px auto;padding:0 16px;}
  .crumb{font-size:13px;color:#6b7280;margin-bottom:8px}
  .crumb a{color:#2563eb;text-decoration:none}
  .page-h{font-size:26px;font-weight:700;color:#d35400;margin:2px 0 14px}
  .sub-h{color:#9ca3af;font-size:13px;margin-bottom:16px}

  .tabs{display:flex;gap:8px;border-bottom:1px solid #eee;margin:10px 0 18px}
  .tab-btn{border:none;background:#f5f6f8;color:#555;padding:10px 14px;border-top-left-radius:8px;border-top-right-radius:8px;cursor:pointer;font-weight:600}
  .tab-btn.active{background:#fff;border:1px solid #e5e7eb;border-bottom-color:#fff;border-bottom:1px solid #fff}
  .tab-panel{display:none;background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:18px}
  .tab-panel.active{display:block}

  .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:18px}
  @media(max-width:800px){.grid-2{grid-template-columns:1fr}}

  .kv{background:#fff;border:1px dashed #e5e7eb;border-radius:12px;padding:14px 16px}
  .kv .label{font-weight:700;color:#111827;position:relative;padding-left:14px}
  .kv .label:before{content:"•";position:absolute;left:0;color:#111827}
  .kv .value{margin-top:6px;color:#374151;white-space:pre-wrap}

  .info-bar{display:flex;gap:14px;align-items:center;margin:10px 0 6px;color:#6b7280;font-size:14px;flex-wrap:wrap}
  .pill{display:inline-block;padding:4px 10px;border-radius:999px;background:#f3f4f6;color:#374151;font-size:12px}

  table.clean{width:100%;border-collapse:separate;border-spacing:0 8px}
  table.clean thead th{font-size:13px;text-transform:uppercase;letter-spacing:.02em;color:#6b7280;text-align:left;padding:6px 10px}
  table.clean tbody tr{background:#fff}
  table.clean tbody td{padding:12px 10px;border-top:1px solid #eee;border-bottom:1px solid #eee}
  table.clean tbody tr td:first-child{border-left:1px solid #eee;border-top-left-radius:10px;border-bottom-left-radius:10px}
  table.clean tbody tr td:last-child{border-right:1px solid #eee;border-top-right-radius:10px;border-bottom-right-radius:10px}
  .status-badge{padding:4px 10px;border-radius:999px;font-size:12px;color:#fff;background:#9ca3af}
  .footer-actions{display:flex;justify-content:space-between;align-items:center;margin-top:18px}
  .btn-link{color:#2563eb;text-decoration:none;font-weight:600}
</style>
</head>
<body>

<div class="client-wrap">
  <!-- Breadcrumb -->
  <div class="crumb">
    <a href="<?php echo esc_url(home_url('/')); ?>">Home</a> / Client Detail
  </div>

  <!-- Title -->
  <div class="page-h"><?php echo esc_html($page_title); ?></div>
  <div class="sub-h"><?php echo esc_html($page_subhead); ?></div>

  <!-- Contact quick pills -->
  <div class="info-bar">
    <span class="pill">📞 <?php echo esc_html($phone); ?></span>
    <span class="pill">✉️ <?php echo esc_html($email); ?></span>
  </div>

  <!-- Tabs -->
  <div class="tabs" role="tablist">
    <button class="tab-btn active" data-tab="about">About</button>
    <button class="tab-btn" data-tab="appointments">Appointments</button>
    <button class="tab-btn" data-tab="notes">Client’s Notes</button>
  </div>

  <!-- Panel: About -->
  <section id="tab-about" class="tab-panel active">
    <div class="grid-2">
      <div class="kv"><div class="label">First Name</div><div class="value"><?php echo esc_html($full_name); ?></div></div>
      <div class="kv"><div class="label">Middle Name</div><div class="value">—</div></div>
      <div class="kv"><div class="label">Last Name</div><div class="value">—</div></div>

      <div class="kv"><div class="label">Email Address</div><div class="value"><?php echo esc_html($email); ?></div></div>
      <div class="kv"><div class="label">Phone</div><div class="value"><?php echo esc_html($phone); ?></div></div>
      <div class="kv"><div class="label">Age</div><div class="value">—</div></div>
    </div>

    <div class="footer-actions">
      <a class="btn-link" href="<?php echo esc_url($back_url); ?>">← Back</a>
      <span></span>
    </div>
  </section>

  <!-- Panel: Appointments (static demo) -->
  <section id="tab-appointments" class="tab-panel">
    <table class="clean">
      <thead>
        <tr>
          <th>Day</th>
          <th>Time</th>
          <th>Clinic</th>
          <th>Doctor</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <tr><td>-</td><td>-</td><td>-</td><td>-</td><td><span class="status-badge">-</span></td></tr>
        <tr><td>-</td><td>-</td><td>-</td><td>-</td><td><span class="status-badge">-</span></td></tr>
      </tbody>
    </table>
  </section>

  <!-- Panel: Client Notes -->
  <section id="tab-notes" class="tab-panel">
  <?php echo do_shortcode('[patient_full_detail id="' . intval($patient_id) . '"]'); ?>
</section>

</div>

<script>
(function(){
  const btns = document.querySelectorAll('.tab-btn');
  const panels = document.querySelectorAll('.tab-panel');
  btns.forEach(btn=>{
    btn.addEventListener('click', ()=>{
      btns.forEach(b=>b.classList.remove('active'));
      panels.forEach(p=>p.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
    });
  });
})();
</script>

</body>
</html>

<?php get_footer(); ?>

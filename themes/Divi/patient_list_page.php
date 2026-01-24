<?php
/*
Template Name: Patient List Page
*/
get_header();

global $wpdb;

$table_name = 'fzil_wpddb_patients';
$patients = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id ASC");
?>

<div class="wrap">
    <h2>Patient List</h2>
   <?php if (isset($_GET['deleted'])): ?>
  <?php if ($_GET['deleted'] === '1'): ?>
    <div id="delete-notice" style="margin:10px 0; padding:10px; border-left:4px solid #46b450; background:#f6fff6;">
      ✅ Patient deleted successfully.
    </div>
  <?php else: ?>
    <div id="delete-notice" style="margin:10px 0; padding:10px; border-left:4px solid #dc3232; background:#fff5f5;">
      ❌ Failed to delete patient.
    </div>
  <?php endif; ?>
<?php endif; ?>

<script>
(function() {
  var el = document.getElementById('delete-notice');
  // Auto-hide after 3 seconds (optional)
  if (el) {
    setTimeout(function(){ el.style.display = 'none'; }, 3000);
  }
  // Remove ?deleted=... from the URL so it doesn't reappear on refresh
  var url = new URL(window.location);
  if (url.searchParams.has('deleted')) {
    url.searchParams.delete('deleted');
    window.history.replaceState({}, document.title, url.toString());
  }
})();
</script>


    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
  <input type="text" id="patientSearch" placeholder="Search by name, email or phone..." style="padding:8px; width:300px;">
  
  <a href="<?php echo site_url('/user-metadata'); ?>" 
     class="button button-primary" 
     style="padding:8px 16px; background:#2271b1; color:#fff; border-radius:4px; text-decoration:none;">
    Register Client
  </a>
</div>

    <table class="wp-list-table ">
        <thead>
            <tr>
                <th>Patient ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody id="patientTable">
            <?php if ($patients) : ?>
                  <?php $count = 1; // Start counter ?>
                <?php foreach ($patients as $patient) : ?>
                    <tr>
                        <!-- <td><?php echo esc_html($patient->id); ?></td> -->
                         <td><?php echo $count++; ?></td>
                        <td>
  <span class="cell-with-edit">
    <span class="cell-text cell-text-full_name"><?php echo esc_html($patient->full_name); ?></span>
    <button type="button"
            class="cell-edit-btn"
            data-id="<?php echo intval($patient->id); ?>"
            title="Edit name/email/phone" aria-label="Edit">
      <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25z"
              stroke="currentColor" stroke-width="1.6" fill="currentColor"/>
        <path d="M20.71 7.04a1 1 0 0 0 0-1.41L18.37 3.3a1 1 0 0 0-1.41 0l-1.34 1.34 3.75 3.75 1.34-1.35z"
              fill="currentColor"/>
      </svg>
    </button>
  </span>
</td>

<td>
  <span class="cell-with-edit">
    <span class="cell-text cell-text-email"><?php echo esc_html($patient->email); ?></span>
    <button type="button"
            class="cell-edit-btn"
            data-id="<?php echo intval($patient->id); ?>"
            title="Edit name/email/phone" aria-label="Edit">
      <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25z"
              stroke="currentColor" stroke-width="1.6" fill="currentColor"/>
        <path d="M20.71 7.04a1 1 0 0 0 0-1.41L18.37 3.3a1 1 0 0 0-1.41 0l-1.34 1.34 3.75 3.75 1.34-1.35z"
              fill="currentColor"/>
      </svg>
    </button>
  </span>
</td>

<td>
  <span class="cell-with-edit">
    <span class="cell-text cell-text-phone"><?php echo esc_html($patient->phone); ?></span>
    <button type="button"
            class="cell-edit-btn"
            data-id="<?php echo intval($patient->id); ?>"
            title="Edit name/email/phone" aria-label="Edit">
      <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25z"
              stroke="currentColor" stroke-width="1.6" fill="currentColor"/>
        <path d="M20.71 7.04a1 1 0 0 0 0-1.41L18.37 3.3a1 1 0 0 0-1.41 0l-1.34 1.34 3.75 3.75 1.34-1.35z"
              fill="currentColor"/>
      </svg>
    </button>
  </span>
</td>


                        <td>
    <div class="action-row">
       <a href="<?php echo esc_url( site_url('/client_details/?id=' . intval($patient->id)) ); ?>"
   class="button add-note-btn">
  Add Note
</a>

        
  <a href="<?php echo esc_url( 'https://newapp2025.shrradhasidhwani.com/patient-details/?id=' . intval($patient->id) ); ?>"
     class="button view-details-btn">
     View Details
  </a>
</div>

    <div class="action-row">
        <a href="<?php echo site_url('/user-metadata/?id=' . urlencode($patient->id)); ?>" 
           class="button edit-patient-btn">Usermeta</a>
       <?php
$delete_url = wp_nonce_url(
    admin_url('admin-post.php?action=delete_patient&patient_id=' . intval($patient->id)),
    'delete_patient_' . intval($patient->id) // must match check_admin_referer()
);
?>
<a href="<?php echo esc_url($delete_url); ?>"
   class="button delete-patient-btn"
   onclick="return confirm('Are you sure you want to delete this patient?');">Delete</a>
    </div>
</td>

                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="5">No patients found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Inline edit modal -->
<div class="inline-edit-overlay" id="inlineEditOverlay" aria-hidden="true">
  <div class="inline-edit-modal" role="dialog" aria-modal="true" aria-labelledby="inlineEditTitle">
    <div class="inline-edit-header">
      <h3 id="inlineEditTitle">Edit Patient</h3>
      <button class="inline-edit-close" type="button" aria-label="Close">&times;</button>
    </div>
    <form id="inlineEditForm" class="inline-edit-form">
      <input type="hidden" name="patient_id" id="ie_patient_id">
      <div class="field">
        <label for="ie_full_name">Full Name</label>
        <input type="text" name="full_name" id="ie_full_name" required>
      </div>
      <div class="field">
        <label for="ie_email">Email</label>
        <input type="email" name="email" id="ie_email" required>
      </div>
      <div class="field">
        <label for="ie_phone">Phone</label>
        <input type="text" name="phone" id="ie_phone" required>
      </div>
      <div class="inline-edit-actions">
        <button type="button" class="btn-pill btn-cancel" id="ie_cancel">Cancel</button>
        <button type="submit" class="btn-pill btn-save">Save</button>
      </div>
    </form>
  </div>
</div>
<script>
  const ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
  const inlineEditNonce = '<?php echo wp_create_nonce('update_patient_inline'); ?>';

  const overlay = document.getElementById('inlineEditOverlay');
  const form    = document.getElementById('inlineEditForm');

  function openInlineEdit(row, patientId) {
    // Read from the row
    const nameEl  = row.querySelector('.cell-text-full_name');
    const emailEl = row.querySelector('.cell-text-email');
    const phoneEl = row.querySelector('.cell-text-phone');

    document.getElementById('ie_patient_id').value = patientId;
    document.getElementById('ie_full_name').value  = (nameEl?.textContent || '').trim();
    document.getElementById('ie_email').value      = (emailEl?.textContent || '').trim();
    document.getElementById('ie_phone').value      = (phoneEl?.textContent || '').trim();

    overlay.style.display = 'flex';
    overlay.setAttribute('aria-hidden', 'false');
    document.getElementById('ie_full_name').focus();
  }
  function closeInlineEdit() {
    overlay.style.display = 'none';
    overlay.setAttribute('aria-hidden', 'true');
    form.reset();
  }

  // Open from any pencil in the row
  document.addEventListener('click', function(e){
    const btn = e.target.closest('.cell-edit-btn');
    if (!btn) return;
    e.preventDefault();
    const row = btn.closest('tr');
    const id  = btn.getAttribute('data-id');
    openInlineEdit(row, id);
  });

  // Close handlers
  document.querySelector('.inline-edit-close').addEventListener('click', closeInlineEdit);
  document.getElementById('ie_cancel').addEventListener('click', closeInlineEdit);
  overlay.addEventListener('click', function(e){ if (e.target === overlay) closeInlineEdit(); });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && overlay.style.display === 'flex') closeInlineEdit(); });

  // Submit (AJAX)
  form.addEventListener('submit', function(e){
    e.preventDefault();

    const patient_id = document.getElementById('ie_patient_id').value;
    const full_name  = document.getElementById('ie_full_name').value.trim();
    const email      = document.getElementById('ie_email').value.trim();
    const phone      = document.getElementById('ie_phone').value.trim();

    if (!full_name || !email || !phone) {
      alert('Please fill all fields.');
      return;
    }

    const fd = new FormData();
    fd.append('action',   'update_patient_inline');
    fd.append('security', inlineEditNonce);
    fd.append('patient_id', patient_id);
    fd.append('full_name',  full_name);
    fd.append('email',      email);
    fd.append('phone',      phone);

    fetch(ajaxurl, { method:'POST', body: fd, credentials: 'same-origin' })
      .then(r => r.json())
      .then(resp => {
        if (!resp || !resp.success) {
          alert('Failed to update.' + (resp && resp.data && resp.data.message ? (' ' + resp.data.message) : ''));
          return;
        }
        // Update the row in the table
        const row = document.querySelector('tr td .cell-edit-btn[data-id="'+patient_id+'"]').closest('tr');
        row.querySelector('.cell-text-full_name').textContent = full_name;
        row.querySelector('.cell-text-email').textContent      = email;
        row.querySelector('.cell-text-phone').textContent      = phone;

        closeInlineEdit();
      })
      .catch(err => {
        console.error(err);
        alert('Request error.');
      });
  });
</script>



</div>

<script>




document.getElementById('patientSearch').addEventListener('keyup', function() {
    var filter = this.value.toUpperCase();
    var rows = document.querySelector("#patientTable").rows;

    for (var i = 0; i < rows.length; i++) {
        var nameCol = rows[i].cells[1].textContent.toUpperCase();
        var emailCol = rows[i].cells[2].textContent.toUpperCase();
        var phoneCol = rows[i].cells[3].textContent.toUpperCase();
        if (nameCol.indexOf(filter) > -1 || emailCol.indexOf(filter) > -1 || phoneCol.indexOf(filter) > -1) {
            rows[i].style.display = "";
        } else {
            rows[i].style.display = "none";
        }
    }
});
</script>

<?php get_footer(); ?>

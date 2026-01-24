<?php
/* Template Name: Patient Dropdown Page */
get_header();
global $wpdb;

$patients_table = 'fzil_wpddb_patients';
$patients = $wpdb->get_results("SELECT id, full_name, email, phone FROM $patients_table ORDER BY full_name ASC");
?>

<div class="patient-container">
  <h2>Search a Patient</h2>
  <input type="text" id="patientSearch" placeholder="Search patient by name or email..." class="search-bar">
  <ul id="searchResults" class="search-results"></ul>

  <div id="patientDetails" class="patient-details" style="display:none;">
    <p><strong>Name:</strong> <span id="detailsName"></span></p>
    <p><strong>Email:</strong> <span id="detailsEmail"></span></p>
    <p><strong>Phone:</strong> <span id="detailsPhone"></span></p>
    <div id="existingMedications"></div>
    <button type="button" id="checkMedicationBtn" onclick="openModal()">Add Medication</button>

    <div id="notesSection" style="display:none; margin-top:20px;">
      <h3>Notes</h3>
      <div id="existingNotes"></div>
      <h4 id="noteFormHeading">Add Note</h4>
      <form id="noteForm">
        <input type="hidden" name="action" value="save_patient_note_ajax">
        <input type="hidden" name="id" id="note_id">
        <input type="hidden" name="patient_id" id="patient_id">
        <p><label>Title:</label> <input type="text" name="note_title" id="note_title" required></p>
        <p><label>Category:</label> <input type="text" name="note_category" id="note_category" required></p>
        <p style="margin-bottom:0;"><label>Note:</label> <textarea name="note_text" id="note_text" rows="4" required></textarea></p>
        <button type="submit" id="saveNoteBtn">Save Note</button>
        <button type="button" id="cancelEditBtn" style="display:none;" onclick="cancelEdit()">Cancel Edit</button>
        <p id="noteAjaxMessage"></p>
      </form>
    </div>
  </div>
</div>

<script>
const patients = <?php echo json_encode($patients); ?>;
const searchInput = document.getElementById('patientSearch');
const searchResults = document.getElementById('searchResults');
const notesSection = document.getElementById('notesSection');
const existingNotes = document.getElementById('existingNotes');
const noteForm = document.getElementById('noteForm');
const noteAjaxMessage = document.getElementById('noteAjaxMessage');
const noteIdInput = document.getElementById('note_id');
const noteTitleInput = document.getElementById('note_title');
const noteCategoryInput = document.getElementById('note_category');
const noteTextInput = document.getElementById('note_text');
const saveNoteBtn = document.getElementById('saveNoteBtn');
const cancelEditBtn = document.getElementById('cancelEditBtn');
const noteFormHeading = document.getElementById('noteFormHeading');
const patientIdInput = document.getElementById('patient_id');

let currentPatientId = null;

searchInput.addEventListener('input', () => {
    const query = searchInput.value.toLowerCase();
    searchResults.innerHTML = '';
    if (query.length < 2) {
        searchResults.style.display = 'none';
        return;
    }
    searchResults.style.display = 'block';
    patients.forEach(patient => {
        const nameEmail = (patient.full_name + ' ' + patient.email).toLowerCase();
        if (nameEmail.includes(query)) {
            const li = document.createElement('li');
            li.textContent = `${patient.full_name} (${patient.email})`;
            li.className = 'search-results';
            li.style.cursor = 'pointer';
            li.onclick = () => {
                showPatientDetails(patient);
                searchResults.style.display = 'none';
                searchInput.value = '';
            };
            searchResults.appendChild(li);
        }
    });
});

function showPatientDetails(patient) {
    document.getElementById('detailsName').innerText = patient.full_name;
    document.getElementById('detailsEmail').innerText = patient.email;
    document.getElementById('detailsPhone').innerText = patient.phone;
    document.getElementById('patientDetails').style.display = 'block';
    document.getElementById('checkMedicationBtn').style.display = 'inline-block';
    notesSection.style.display = 'block';

    currentPatientId = patient.id;
    patientIdInput.value = patient.id;

    loadPatientNotes(patient.id);

    fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=get_patient_medications&patient_id=' + patient.id)
    .then(res => res.json())
    .then(data => {
        let html = '<h4>Previous Medications:</h4>';
        if (data.length > 0) {
            html += '<table class="table-med">';
            html += '<thead><tr><th>Disease</th><th>Doctor</th><th>Medicine</th></tr></thead><tbody>';
            data.forEach(med => {
                html += `<tr><td>${med.disease}</td><td>${med.doctor_name}</td><td>${med.medicine}</td></tr>`;
            });
            html += '</tbody></table>';
        } else {
            html += '<p>No medications found.</p>';
        }
        document.getElementById('existingMedications').innerHTML = html;
    });
}

function loadPatientNotes(patient_id) {
    fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=get_patient_notes&patient_id=' + patient_id)
    .then(res => res.json())
    .then(data => {
        let html = '<h4>Previous Notes:</h4>';
        if (data.length > 0) {
            html += '<ul>';
            data.forEach(note => {
                if(note.note_id){
                    html += `<li><strong>${note.note_id}</strong> - <strong>${note.note_title}</strong> [${note.note_category ?? ''}]: ${note.note_text} <button type="button" onclick="editNote('${note.note_id}','${note.note_title.replace(/'/g,"\\'")}','${note.note_category ? note.note_category.replace(/'/g,"\\'") : ''}','${note.note_text.replace(/'/g,"\\'")}')">Edit</button> <button type="button" onclick="deleteNote('${note.note_id}')">Delete</button></li>`;
                }
            });
            html += '</ul>';
        } else {
            html += '<p>No notes found.</p>';
        }
        existingNotes.innerHTML = html;
    });
}

function editNote(note_id, title, category, text) {
    noteIdInput.value = note_id;
    noteTitleInput.value = title;
    noteCategoryInput.value = category;
    noteTextInput.value = text;
    noteFormHeading.innerText = 'Edit Note';
    saveNoteBtn.innerText = 'Update Note';
    cancelEditBtn.style.display = 'inline-block';
    noteAjaxMessage.innerText = 'Editing Note ID: ' + note_id;
}

function cancelEdit() {
    noteForm.reset();
    noteIdInput.value = '';
    noteFormHeading.innerText = 'Add Note';
    saveNoteBtn.innerText = 'Save Note';
    cancelEditBtn.style.display = 'none';
    noteAjaxMessage.innerText = '';
}

noteForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        noteAjaxMessage.innerText = data.message;
        if (data.success) {
            noteForm.reset();
            cancelEdit();
            loadPatientNotes(currentPatientId);
        }
    });
});

function deleteNote(note_id) {
    if (confirm('Are you sure you want to delete this note?')) {
        const formData = new FormData();
        formData.append('action', 'delete_patient_note');
        formData.append('note_id', note_id);

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data && data.data && data.data.message) {
                alert(data.data.message);
            } else {
                alert("Unexpected response. Please try again.");
            }
            loadPatientNotes(currentPatientId);
        })
        .catch(err => {
            alert("An error occurred while deleting the note. Please try again.");
        });
    }
}


</script>

<?php get_footer(); ?>

<?php 
/* Template Name: File Upload Page */
get_header();
?>

<style>
/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.4);
}
.modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
}
#close-modal {
    background-color: #4CAF50;
    color: white;
    padding: 10px 20px;
    cursor: pointer;
}
#close-modal:hover { background-color: #45a049; }

/* General Styles */
:root {
  --primary: #2d59fa;
  --primary-hover: #0037ff;
  --bg: #f7f8f9;
  --secondary: #ffffff;
  --dropzone-bg: #fff;
  --gray: #767676;
  --border: #edf1f3;
  --dropzone-border: #04dce6;
  --headline: #211e30;
  --text: #0a090c;
  --primary-text: #f2f7fe;
  --dropzone-over: #f2f7fe;
}
.file-upload-container {
  font-family: 'Work Sans', sans-serif;
  background: transparent;
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
  padding: 1rem;
  color: var(--text);
}
.dropzone-box {
  border-radius: 1rem;
  min-width: 25rem;
  padding: 2rem;
  display: flex;
  justify-content: center;
  flex-direction: column;
  max-width: 30rem;
  border: 1px solid var(--border);
  width: 100%;
  background: var(--dropzone-bg);
}
.dropzone-box h2 { font-size: 1.4rem; margin-bottom: 0.6rem; color: var(--headline); }
.dropzone-box p { font-size: 1.15rem; color: var(--gray); }
.dropzone-area {
  padding: 1rem;
  position: relative;
  margin-top: 1.5rem;
  min-height: 16rem;
  display: flex;
  text-align: center;
  justify-content: center;
  align-items: center;
  flex-direction: column;
  border: 2px dashed var(--dropzone-border);
  border-radius: 1rem;
  cursor: pointer;
  transition: all 0.3s ease;
}
.dropzone-area input[type="file"] {
  cursor: pointer;
  position: absolute;
  opacity: 0;
  top: 0; right: 0; bottom: 0; left: 0;
}
.dropzone-area:hover { background: var(--dropzone-over); }
.dropzone--over { border: 2px solid var(--primary); background: var(--dropzone-over); }
.dropzone-actions {
  display: flex; justify-content: space-between; align-items: center;
  margin-top: 2rem; gap: 1rem;
}
.dropzone-actions button {
  flex-grow: 1; min-height: 3rem; font-size: 1.2rem; border: none;
  transition: background 0.3s ease;
}
.dropzone-actions button[type='reset'] {
  border-radius: 0.5rem; padding: 0.5rem 1rem; color: var(--text);
  background: var(--secondary); cursor: pointer;
  border: 1px solid var(--dropzone-border);
}
.dropzone-actions button[type='reset']:hover {
  border: 1px solid var(--primary); color: var(--primary);
}
.dropzone-actions button[type='submit'] {
  background: var(--primary); border-radius: 0.5rem;
  padding: 0.5rem 1rem; color: var(--primary-text);
  cursor: pointer;
}
.dropzone-actions button[type='submit']:hover { background: var(--primary-hover); }
</style>

<div class="file-upload-container">
  <form class="dropzone-box" id="file-upload-form">
    <h2>Upload file</h2>
    <p>Click to upload or drag and drop</p>
    <div class="dropzone-area">
      <div class="file-upload-icon">
        <img src="https://newapp2025.shrradhasidhwani.com/wp-content/uploads/2025/10/cloud-computing.png" width="25%" height="25%">
      </div>
      <input type="file" required id="upload-file" name="uploaded-file" accept=".csv, .xls, .xlsx">
    </div>
    <p class="file-info">No Files Selected</p>
    <div class="dropzone-description">
      <span>Max file size: 25MB</span>
    </div>
    <div class="dropzone-actions">
      <button type="reset">Cancel</button>
      <button id="submit-button" type="submit">Save</button>
    </div>
    <input type="hidden" name="security" value="<?php echo esc_attr( wp_create_nonce('bio_import_nonce') ); ?>">
  </form>
</div>

<!-- Modal -->
<div id="modal" class="modal">
  <div class="modal-content">
    <p>Uploading...</p>
    <button id="close-modal">Close</button>
  </div>
</div>

<script>
const dropzoneBox = document.getElementsByClassName("dropzone-box")[0];
const inputElement = document.getElementById("upload-file");
const dropzoneElement = inputElement.closest(".dropzone-area");
const dropzoneFileMessage = document.querySelector(".file-info");
const uploadIcon = document.querySelector(".file-upload-icon img");
const filelimit = 25000000;
const ajaxurl = "<?php echo esc_url(admin_url('admin-ajax.php')); ?>";

const updateDropzoneFilelist = (file) => {
  if (dropzoneFileMessage) dropzoneFileMessage.textContent = file.name;
};

// File change
inputElement.addEventListener("change", () => {
  if (inputElement.files[0].size > filelimit) {
    inputElement.setCustomValidity("File is over 25MB!");
    inputElement.reportValidity();
    return;
  } else inputElement.setCustomValidity("");
  if (inputElement.files.length) {
    updateDropzoneFilelist(inputElement.files[0]);
    if (uploadIcon) uploadIcon.style.display = 'none';
  }
});

// Reset
dropzoneBox.addEventListener("reset", () => {
  if (dropzoneFileMessage) dropzoneFileMessage.textContent = "No Files Selected";
  if (uploadIcon) uploadIcon.style.display = 'block';
});

// Drop handler
dropzoneElement.addEventListener("drop", (e) => {
  e.preventDefault();
  if (e.dataTransfer.files[0].size > filelimit) {
    inputElement.setCustomValidity("File is over 25MB!");
    inputElement.reportValidity();
    dropzoneElement.classList.remove("dropzone--over");
    return;
  } else inputElement.setCustomValidity("");
  if (e.dataTransfer.files.length) {
    inputElement.files = e.dataTransfer.files;
    updateDropzoneFilelist(e.dataTransfer.files[0]);
    if (uploadIcon) uploadIcon.style.display = 'none';
  }
  dropzoneElement.classList.remove("dropzone--over");
});

// === SUBMIT AJAX ===
const formEl = document.getElementById('file-upload-form');
const modal = document.getElementById('modal');
const modalContent = document.querySelector('#modal .modal-content');

formEl.addEventListener("submit", async (e) => {
  e.preventDefault();
  const myFile = inputElement.files[0];
  if (!myFile) return;
  if (myFile.size > filelimit) {
    inputElement.setCustomValidity("File is over 25MB!");
    inputElement.reportValidity();
    return;
  }

  inputElement.setCustomValidity("");
  modal.style.display = 'block';
  modalContent.innerHTML = '<p>⏳ Uploading & processing file...</p>';

  const fd = new FormData(formEl);
  fd.append('action', 'bio_import_file');

  try {
    const res = await fetch(ajaxurl, { method: 'POST', body: fd });
    const json = await res.json();

    if (json.success) {
      const s = json.data.summary || {};
      const details = (json.data.details || []).map(d => `<li>${d}</li>`).join('');
      modalContent.innerHTML = `
        <h3>✅ Import Completed</h3>
        <p><strong>Inserted:</strong> ${s.inserted || 0} &nbsp; 
           <strong>Updated:</strong> ${s.updated || 0} &nbsp; 
           <strong>Failed:</strong> ${s.failed || 0}</p>
        <details><summary>Details</summary><ul>${details}</ul></details>
        <button id="close-modal">Close</button>`;
    } else {
      modalContent.innerHTML = `
        <h3>❌ Upload Failed</h3>
        <p style="color:red">${(json.data && json.data.message) ? json.data.message : 'Unknown error'}</p>
        <button id="close-modal">Close</button>`;
    }
    document.getElementById('close-modal').addEventListener('click', () => modal.style.display = 'none');
  } catch (err) {
    modalContent.innerHTML = `
      <h3>⚠️ Error</h3>
      <p style="color:red">${err.message}</p>
      <button id="close-modal">Close</button>`;
    document.getElementById('close-modal').addEventListener('click', () => modal.style.display = 'none');
  }
});
</script>

<?php get_footer(); ?>

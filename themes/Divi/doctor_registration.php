<?php
/*
Template Name: Doctor Registration
*/
get_header();
?>

<style>
  body {
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(to right, #e0f7fa, #ffffff);
    margin: 0;
  }

  form {
    background: #ffffff;
    padding: 30px;
    border-radius: 15px;
    max-width: 850px;
    margin: 30px auto;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
  }

  h2 {
    color: #0d47a1;
    margin-top: 20px;
    margin-bottom: 20px;
    border-bottom: 2px solid #eeeeee;
    padding-bottom: 5px;
  }

  .form-group {
    margin-bottom: 20px;
  }

  label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
  }

  input[type="text"],
  input[type="tel"] {
    width: 100%;
    padding: 12px;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 15px;
    transition: 0.3s;
  }

  input:focus {
    outline: none;
    border-color: #42a5f5;
    box-shadow: 0 0 0 2px rgba(66, 165, 245, 0.2);
  }

  .clinic-box {
    background: #f1faff;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 15px;
    position: relative;
    border: 1px solid #d6e9f8;
  }

  .remove-btn {
    background: #e53935;
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 5px;
    float: right;
    margin-top: -13px;
    font-size: 13px;
    cursor: pointer;
  }

  .switch {
    display: flex;
    align-items: center;
    margin-top: 10px;
    gap: 10px;
  }

  button {
    margin-top: 15px;
    padding: 12px 20px;
    border: none;
    font-size: 15px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.3s ease;
  }

  .add-btn {
    background-color: #43a047;
    color: white;
  }

  .add-btn:hover {
    background-color: #2e7d32;
  }

  .submit-btn {
    background-color: #1976d2;
    color: white;
    width: 100%;
  }

  .submit-btn:hover {
    background-color: #0d47a1;
  }
</style>

<form id="registrationForm">
  <h2>Doctor/Therapist Information</h2>
  <div class="form-group">
    <label for="doctor_name">Doctor/Therapist Name</label>
    <input type="text" id="doctor_name" name="doctor_name" required>
  </div>
  <div class="form-group">
    <label for="designation">Doctor/Therapist Designation</label>
    <input type="text" id="designation" name="designation" required>
  </div>
  <div class="form-group">
    <label for="speciality">Doctor/Therapist Speciality</label>
    <input type="text" id="speciality" name="speciality" required>
  </div>
  <div class="form-group">
    <label for="workplace">Doctor/Therapist Work Place</label>
    <input type="text" id="workplace" name="workplace" required>
  </div>
  <div class="form-group">
    <label for="degree">Doctor/Therapist Degree</label>
    <input type="text" id="degree" name="degree" required>
  </div>

  <div class="form-group">
    <label for="department">Doctor/Therapist Department</label>
    <input type="text" id="department" name="department" required>
  </div>

  <div class="form-group">
    <label for="email">Email ID</label>
    <input type="email" id="email" name="email" required>
  </div>

  <div class="form-group">
    <label for="password">Password</label>
    <input type="password" id="password" name="password" required minlength="6">
  </div>

  <div class="form-group">
    <label>Is Clinic Admin?</label>
    <label><input type="radio" name="is_clinic_admin" value="1" required> Yes</label>
    <label><input type="radio" name="is_clinic_admin" value="0"> No</label>
  </div>

  <div class="form-group">
    <label for="profile_photo">Upload Profile Photo</label>
    <input type="file" id="profile_photo" name="profile_photo" accept="image/*">
  </div>


  <h2>Clinic Info</h2>
  <div id="clinicContainer"></div>
  <button type="button" class="add-btn" id="addClinicBtn">+ Add Clinic Info</button>

  <button type="submit" class="submit-btn">Submit</button>
</form>

<!-- Thank You Modal -->
<div id="thankYouModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999;">
  <div style="background:#fff; max-width:400px; margin:100px auto; padding:30px; position:relative; border-radius:12px; text-align:center;">
    <button id="closeModal" style="
    position: absolute;
    top: 10px;
    right: 10px;
    width: 32px;
    height: 32px;
    background: #e53935;
    color: white;
    border: none;
    border-radius: 50%;
    font-size: 20px;
    font-weight: bold;
    line-height: 32px;
    text-align: center;
    cursor: pointer;
    padding: 0;
">×</button>
    <h3 style="color:#0d47a1; margin-bottom:15px;">Thank You!</h3>
    <p>Thank you for registration.<br>We are verifying your details and will connect with you shortly.</p>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    let clinicIndex = 0;
    const clinicContainer = document.getElementById("clinicContainer");
    const addClinicBtn = document.getElementById("addClinicBtn");
    const form = document.getElementById("registrationForm");
    const modal = document.getElementById("thankYouModal");
    const closeModal = document.getElementById("closeModal");

    addClinicBtn.addEventListener("click", addClinic);

    function addClinic() {
      const div = document.createElement("div");
      div.className = "clinic-box";
      div.id = `clinic-${clinicIndex}`;
      div.innerHTML = `
            <button type="button" class="remove-btn" onclick="removeClinic(${clinicIndex})">Remove</button>
            <label>Clinic Name</label>
            <input type="text" name="clinics[${clinicIndex}][clinicName]" placeholder="Enter clinic name" required>
            <label>Clinic Booking Phone</label>
            <input type="tel" name="clinics[${clinicIndex}][clinicPhone]" placeholder="Enter phone number">
            <div class="switch">
                <label>Set as Holiday</label>
                <input type="checkbox" name="clinics[${clinicIndex}][holiday]">
            </div>
        `;
      clinicContainer.appendChild(div);
      clinicIndex++;
    }
    window.removeClinic = function(index) {
      const div = document.getElementById(`clinic-${index}`);
      if (div) div.remove();
    }

    closeModal.addEventListener("click", () => modal.style.display = "none");
    window.addEventListener("click", (e) => {
      if (e.target == modal) modal.style.display = "none";
    });

    form.addEventListener("submit", function(e) {
      e.preventDefault();

      const formData = new FormData(form);
      formData.append('action', 'doctor_registration'); // WordPress AJAX action

      console.log("Sending to:", doctorAjax.ajax_url);
      for (var pair of formData.entries()) {
        console.log(pair[0] + ": " + pair[1]);
      }

      fetch(doctorAjax.ajax_url, {
          method: "POST",
          body: formData
        })
        .then(res => res.json())
        .then(data => {
          console.log("Server response:", data);
          if (data.success) {
            modal.style.display = "block";
            form.reset();
            clinicContainer.innerHTML = "";
            clinicIndex = 0;
          } else {
            alert("Error: " + (data.data?.message ?? "Unknown error."));
          }
        })
        .catch(err => {
          console.error("AJAX error:", err);
          alert("Network error. Please try again.");
        });
    });
  });
</script>

<?php get_footer(); ?>
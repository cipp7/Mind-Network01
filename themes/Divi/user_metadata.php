<?php
/**
 * Template Name: Biographical Form
 */
get_header();

if ( ! defined('ABSPATH') ) exit;
global $wpdb;

// CHANGE this to your real bio table if different
$bio_table = 'wp_bio_form_data';

$edit_patient_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$initial_json = '{}';
if ($edit_patient_id) {
  $row = $wpdb->get_row(
    $wpdb->prepare("SELECT data FROM {$bio_table} WHERE patient_id = %d ORDER BY submitted_at DESC LIMIT 1", $edit_patient_id)
  );
  if ($row && !empty($row->data)) {
    $initial_json = $row->data; // already JSON
  }
}
?>

<style>
  .form-wrapper {
    max-width: 700px;
    margin: auto;
    font-family: Arial, sans-serif;
    padding: 30px;
    border: 1px solid #ddd;
    border-radius: 12px;
    background: #fff;
  }

  .progress-bar {
    display: flex;
    height: 10px;
    background: #eee;
    border-radius: 5px;
    overflow: hidden;
    margin-bottom: 25px;
  }

  .progress-bar > div {
    height: 100%;
    background: #0073aa;
    transition: width 0.3s ease-in-out;
  }

  .form-step {
    display: none;
  }

  .form-step.active {
    display: block;
  }

  .form-group {
    margin-bottom: 15px;
  }

  label {
    font-weight: bold;
  }

  input[type="text"], input[type="email"], input[type="number"], input[type="date"], select {
    width: 100%;
    padding: 8px;
    margin-top: 4px;
    box-sizing: border-box;
  }

  .btns {
    display: flex;
    justify-content: space-between;
    margin-top: 20px;
  }

  button {
    padding: 10px 20px;
    background: #0073aa;
    border: none;
    color: white;
    border-radius: 5px;
    cursor: pointer;
  }

/* Progress bar */
.step-indicator {
  display: flex;
  align-items: center;
  justify-content: space-between;
  max-width: 350px;
  margin: 30px auto;
}

.step {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #fff;
  border: 2px solid #ccc;
  color: #ccc;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  transition: 0.3s ease-in-out;
}

.step.active {
  border-color: #0073aa;
  color: #0073aa;
}

.line {
  flex: 1;
  height: 2px;
  background: #ccc;
  margin: 0 4px;
}

.step.completed {
  background: #0073aa;
  color: #fff;
  border-color: #0073aa;
}

</style>

<div class="form-wrapper">
  <h2>Biographical Information Form</h2>
  <div class="step-indicator">
  <div class="step active">1</div>
  <div class="line"></div>
  <div class="step">2</div>
  <div class="line"></div>
  <div class="step">3</div>
  <div class="line"></div>
  <div class="step">4</div>
  <div class="line"></div>
  <div class="step">5</div>
   <div class="line"></div>
  <div class="step">6</div>
</div>


  <form id="biographicalForm">
    <input type="hidden" name="bio_nonce" value="<?php echo wp_create_nonce('bio_form_nonce'); ?>">
    <input type="hidden" name="patient_id" id="patient_id" value="<?php echo $edit_patient_id ? intval($edit_patient_id) : ''; ?>">
<input type="hidden" name="mode" value="<?php echo $edit_patient_id ? 'edit' : 'new'; ?>">

    <!-- Step 1 -->
    <div class="form-step active" id="step-1">
      <div class="form-group"><label>Email *</label><input type="email" name="email" required></div>
      <div class="form-group"><label>First Name *</label><input type="text" name="first_name" required></div>
      <div class="form-group"><label>Middle Name</label><input type="text" name="middle_name"></div>
      <div class="form-group"><label>Last Name *</label><input type="text" name="last_name" required></div>
      <div class="form-group"><label>Date of Birth *</label><input type="date" name="dob" required></div>
      <div class="form-group"><label>Age *</label><input type="number" name="age" required></div>
      <div class="form-group"><label>Gender *</label>
        <select name="gender" required>
          <option value="">Select</option>
          <option>Male</option>
          <option>Female</option>
          <option>Transgender</option>
          <option>Non Binary</option>
          <option>Gender Fluid</option>
          <option>Prefer Not to Say</option>
          <option>Other</option>
        </select>
      </div>
      <div class="form-group"><label>Sexual Orientation</label>
        <select name="sexual_orientation">
          <option value="">Select</option>
          <option>Straight/Heterosexual</option>
          <option>Gay or Lesbian</option>
          <option>Bisexual</option>
          <option>Queer</option>
          <option>Prefer Not to Say</option>
          <option>Other</option>
        </select>
      </div>
      <div class="form-group"><label>Street</label><input type="text" name="street"></div>
      <div class="form-group"><label>City</label><input type="text" name="city"></div>
      <div class="form-group"><label>State</label><input type="text" name="state"></div>
      <div class="form-group"><label>Zip Code</label><input type="text" name="zip"></div>
      <div class="form-group"><label>Home Phone *</label><input type="text" name="home_phone" required></div>
      <div class="form-group"><label>Business Phone</label><input type="text" name="business_phone"></div>
      <div class="form-group"><label>Permanent Address</label><input type="text" name="permanent_address"></div>

      <div class="btns">
        <span></span>
        <button type="button" onclick="nextStep()">Next</button>
      </div>
    </div>

    <!-- Step 2 -->
    <div class="form-step" id="step-2">
      <div class="form-group"><label>Relational Status</label>
        <select name="relational_status">
          <option value="">Select</option>
          <option>Single</option>
          <option>Married</option>
          <option>Engaged</option>
          <option>Divorced</option>
          <option>Separated</option>
          <option>In a Relationship</option>
          <option>Widow</option>
        </select>
      </div>
      <div class="form-group"><label>If Married</label>
        <select name="marriage_type">
          <option>Not Applicable</option>
          <option>Arranged</option>
          <option>Love</option>
        </select>
      </div>
      <div class="form-group"><label>Years in Current Relationship</label><input type="number" name="relationship_years"></div>
      <div class="form-group"><label>Number of Children</label><input type="number" name="children_count"></div>
      <div class="form-group"><label>Children’s Age</label><input type="text" name="children_age"></div>
      <div class="form-group"><label>Years of Formal Education Completed</label>
        <select name="education_years">
          <?php for ($i = 1; $i <= 20; $i++) { echo "<option>$i</option>"; } ?>
          <option>More than 20</option>
        </select>
      </div>
      <div class="form-group"><label>Employment Status</label>
        <select name="employment_status">
          <option>Employed for wages</option>
          <option>Self-employed</option>
          <option>Out of work and looking for work</option>
          <option>Out of work but not currently looking for work</option>
          <option>A homemaker</option>
          <option>Military</option>
          <option>Retired</option>
          <option>Unable to work</option>
        </select>
      </div>
      <div class="form-group"><label>How religious are you? (1–9)</label><input type="number" min="1" max="9" name="religious_self"></div>
      <div class="form-group"><label>Religiosity of 1st Caretaker (1–9)</label><input type="number" min="1" max="9" name="religious_1"></div>
      <div class="form-group"><label>Religiosity of 2nd Caretaker (1–9)</label><input type="number" min="1" max="9" name="religious_2"></div>

      <div class="btns">
        <button type="button" onclick="prevStep()">Previous</button>
        <button type="button" onclick="nextStep()">Next</button>
      </div>
    </div>

    <!-- Step 3 -->
<div class="form-step" id="step-3">
  <div class="form-group"><label>Mother's Age</label><input type="number" name="mother_age"></div>
  <div class="form-group"><label>If deceased, how old were you when she died?</label><input type="number" name="age_when_mother_died"></div>
  
  <div class="form-group"><label>Father's Age</label><input type="number" name="father_age"></div>
  <div class="form-group"><label>If deceased, how old were you when he died?</label><input type="number" name="age_when_father_died"></div>

  <div class="form-group"><label>If parents separated, how old were you?</label><input type="number" name="age_at_separation"></div>
  <div class="form-group"><label>If parents divorced, how old were you?</label><input type="number" name="age_at_divorce"></div>
  
  <div class="form-group"><label>Total number of times mother divorced</label><input type="number" name="mother_divorces"></div>
  <div class="form-group"><label>Total number of times father divorced</label><input type="number" name="father_divorces"></div>

  <div class="form-group"><label>Ages of living brothers</label><input type="text" name="brothers_ages"></div>
  <div class="form-group"><label>Ages of living sisters</label><input type="text" name="sisters_ages"></div>

  <div class="form-group"><label>Significant medical history</label><textarea name="medical_history"></textarea></div>

  <div class="form-group"><label>Were you adopted?</label><br>
    <label><input type="radio" name="adopted" value="Yes"> Yes</label>
    <label><input type="radio" name="adopted" value="No"> No</label>
  </div>

  <div class="form-group"><label>Have you had individual/group/couples therapy?</label><br>
    <label><input type="radio" name="therapy_history" value="Yes"> Yes</label>
    <label><input type="radio" name="therapy_history" value="No"> No</label>
  </div>

  <div class="form-group"><label>Dates & length of treatment (if any)</label><textarea name="therapy_dates"></textarea></div>

  <div class="form-group"><label>Ever hospitalized for emotional problems?</label><textarea name="emotional_hospitalization"></textarea></div>

  <div class="form-group"><label>Currently under treatment elsewhere?</label><textarea name="current_treatment"></textarea></div>

  <div class="form-group"><label>History/use of addictive substances?</label><textarea name="addiction_history"></textarea></div>

  <div class="form-group"><label>Have you taken medication for emotional problems?</label><br>
    <label><input type="radio" name="emotional_medication" value="Yes"> Yes</label>
    <label><input type="radio" name="emotional_medication" value="No"> No</label>
  </div>

  <div class="form-group"><label>If yes, specify name, status, prescriber</label><textarea name="emotional_meds_detail"></textarea></div>

  <div class="form-group"><label>Type of psychotherapy received</label><textarea name="psychotherapy_type" placeholder="e.g., cognitive therapy, psychodynamic, etc."></textarea></div>

  <div class="form-group"><label>Have you ever attempted suicide?</label><textarea name="suicide_attempt"></textarea></div>
  <div class="form-group"><label>Are you currently having suicidal thoughts?</label><textarea name="suicidal_thoughts"></textarea></div>

  <div class="form-group"><label>Past complaints/symptoms/problems</label><textarea name="past_symptoms"></textarea></div>

  <div class="form-group"><label>When are your problems worse?</label><textarea name="problem_worsen_condition"></textarea></div>

  <div class="form-group"><label>When are your problems improved?</label><textarea name="problem_improve_condition"></textarea></div>

  <div class="btns">
    <button type="button" onclick="prevStep()">Previous</button>
    <button type="button" onclick="nextStep()">Next</button>
    
  </div>
</div>

<!-- Step 4 -->
<div class="form-step" id="step-4">
  <div class="form-group"><label>List the activities you like to do most, the kinds of activities and pleasure</label>
    <textarea name="favorite_activities"></textarea>
  </div>

  <div class="form-group"><label>List your main strengths</label>
    <textarea name="main_strengths"></textarea>
  </div>

  <div class="form-group"><label>List your main weaknesses</label>
    <textarea name="main_weaknesses"></textarea>
  </div>

  <div class="form-group"><label>List your main social difficulties</label>
    <textarea name="social_difficulties"></textarea>
  </div>

  <div class="form-group"><label>List your main love and sex difficulties</label>
    <textarea name="love_sex_difficulties"></textarea>
  </div>

  <div class="form-group"><label>List your main school or work difficulties</label>
    <textarea name="school_work_difficulties"></textarea>
  </div>

  <div class="form-group"><label>List your main life goals</label>
    <textarea name="life_goals"></textarea>
  </div>

  <div class="form-group"><label>List the behaviors and emotions about yourself you would most like to change</label>
    <textarea name="change_behaviors_emotions"></textarea>
  </div>

  <div class="form-group"><label>Mother's occupation</label>
    <input type="text" name="mother_occupation">
  </div>

  <div class="form-group"><label>Father's occupation</label>
    <input type="text" name="father_occupation">
  </div>

  <div class="form-group"><label>If your mother and father did not raise you when you were young, who did?</label>
    <input type="text" name="other_guardian">
  </div>

  <div class="form-group"><label>Describe how each of your parents/caretakers treated you when you were a child and the relationship you have with them now</label>
    <textarea name="caretaker_relationships"></textarea>
  </div>

  <div class="form-group"><label>If there were any unusually disturbing features in your relationship to any of your siblings briefly describe them</label>
    <textarea name="sibling_issues"></textarea>
  </div>

  <div class="btns">
    <button type="button" onclick="prevStep()">Previous</button>
    <button type="button" onclick="nextStep()">Next</button>
    
  </div>
</div>

<!-- Step 5 -->
<div class="form-step" id="step-5">
  <h3>Childhood Experiences</h3>

  <div class="form-group"><label>Emotional abuse</label><br>
    <label><input type="radio" name="emotional_abuse" value="Yes"> Yes</label>
    <label><input type="radio" name="emotional_abuse" value="No"> No</label>
  </div>

  <div class="form-group"><label>Sexual abuse</label><br>
    <label><input type="radio" name="sexual_abuse" value="Yes"> Yes</label>
    <label><input type="radio" name="sexual_abuse" value="No"> No</label>
  </div>

  <div class="form-group"><label>Drug abuse</label><br>
    <label><input type="radio" name="drug_abuse" value="Yes"> Yes</label>
    <label><input type="radio" name="drug_abuse" value="No"> No</label>
  </div>

  <div class="form-group"><label>Household drug abuse</label><br>
    <label><input type="radio" name="household_drug_abuse" value="Yes"> Yes</label>
    <label><input type="radio" name="household_drug_abuse" value="No"> No</label>
  </div>

  <div class="form-group"><label>Parental separation or divorce</label><br>
    <label><input type="radio" name="parental_divorce" value="Yes"> Yes</label>
    <label><input type="radio" name="parental_divorce" value="No"> No</label>
  </div>

  <div class="form-group"><label>Incarcerated household member</label><br>
    <label><input type="radio" name="incarcerated_member" value="Yes"> Yes</label>
    <label><input type="radio" name="incarcerated_member" value="No"> No</label>
  </div>

  <div class="form-group"><label>Emotional neglect</label><br>
    <label><input type="radio" name="emotional_neglect" value="Yes"> Yes</label>
    <label><input type="radio" name="emotional_neglect" value="No"> No</label>
  </div>

  <div class="form-group"><label>Physical neglect</label><br>
    <label><input type="radio" name="physical_neglect" value="Yes"> Yes</label>
    <label><input type="radio" name="physical_neglect" value="No"> No</label>
  </div>

  <div class="form-group"><label>Mother treated violently</label><br>
    <label><input type="radio" name="mother_violence" value="Yes"> Yes</label>
    <label><input type="radio" name="mother_violence" value="No"> No</label>
  </div>

  <div class="form-group"><label>Close relatives seriously emotionally disturbed (specify)</label>
    <textarea name="relatives_disturbed"></textarea>
  </div>

  <div class="form-group"><label>Close relatives hospitalized for emotional problems or attempted suicide (specify)</label>
    <textarea name="relatives_hospitalized"></textarea>
  </div>

  <div class="form-group"><label>Any additional information you think might be helpful</label>
    <textarea name="additional_info"></textarea>
  </div>

  <div class="form-group"><label>How did you hear about us? *</label>
    <select name="referral_source" required>
      <option value="">Select</option>
      <option>Friend/Family</option>
      <option>Colleague</option>
      <option>Our Website</option>
      <option>Social Media</option>
      <option>Google Search</option>
      <option>Doctor</option>
      <option>Other</option>
    </select>
  </div>

  <div class="form-group"><label>Name of the Doctor *</label>
    <input type="text" name="doctor_name" required>
  </div>

  

  <div class="btns">
    <button type="button" onclick="prevStep()">Previous</button>
     <button type="button" onclick="nextStep()">Next</button>
  </div>
</div>

<div class="form-step" id="step-6">
  <h3>Emergency Contact & Additional Info</h3>
  <div class="form-group"><label>Emergency Contact Name *</label><input type="text" name="emergency_name" required></div>
  <div class="form-group"><label>Relationship *</label><input type="text" name="emergency_relationship" required></div>
  <div class="form-group"><label>Phone Number *</label><input type="text" name="emergency_phone" required></div>

  <div class="form-group"><label>Ethnicity *</label><input type="text" name="ethnicity" required></div>

  <div class="form-group"><label>Languages Spoken *</label>
    <select name="languages_spoken" required>
      <option value="">Select</option>
      <option>English</option><option>Hindi</option><option>Gujarati</option><option>Marathi</option>
      <option>Sindhi</option><option>Tamil</option><option>Telugu</option><option>Punjabi</option>
      <option>Urdu</option><option>Malyalam</option><option>Kashmiri</option><option>Nepali</option>
      <option>Manipuri</option><option>Sanskrit</option><option>Odia</option><option>Maithili</option>
      <option>Bengali</option><option>Kannada</option><option>Assamese</option><option>Konkani</option>
      <option>Bihari</option><option>Dogri</option><option>Spanish</option><option>French</option>
      <option>Other</option>
    </select>
  </div>

  <div class="form-group">
    <label>
      <input type="checkbox" id="final_consent_checkbox" required>
      <strong>I agree to the terms of online therapy.</strong><br>
      I understand that cancellations must be made 24 hours in advance to avoid fees.
    </label>
  </div>

  <div class="form-group">
    <label><input type="checkbox" id="consent_checkbox" required>
    <strong>By submitting this form</strong>, you agree to the counselling consent terms and conditions. You also confirm that you have read all information, understand the risks and benefits of counselling and the limits of confidentiality, and give permission to use your data anonymously for research purposes.
    <br><br>*Please hit the submit button only once. You will receive a confirmation email.</label>
  </div>

  <div class="btns">
    <button type="button" onclick="prevStep()">Previous</button>
    <button type="submit" id="submitBtn" disabled>Submit</button>
  </div>
</div>
 </form>
</div>
<!-- 1) Consent enable/disable -->
<script>
const consentBox = document.getElementById("consent_checkbox");
const finalBox   = document.getElementById("final_consent_checkbox");
const submitBtn  = document.getElementById("submitBtn");

function updateSubmitButtonState() {
  if (!submitBtn) return;
  const a = consentBox ? consentBox.checked : false;
  const b = finalBox ? finalBox.checked : false;
  submitBtn.disabled = !(a && b);
}
if (consentBox) consentBox.addEventListener("change", updateSubmitButtonState);
if (finalBox)   finalBox.addEventListener("change", updateSubmitButtonState);
</script>



<!-- 2) Step navigation / validation -->
<script>
let currentStep = 1;

function updateProgressUI() {
  const steps = document.querySelectorAll('.step');
  steps.forEach((step, index) => {
    step.classList.remove('active', 'completed');
    if (index < currentStep - 1) step.classList.add('completed');
    if (index === currentStep - 1) step.classList.add('active');
  });
}

function nextStep() {
  const currentFormStep = document.getElementById("step-" + currentStep);
  const inputs = currentFormStep.querySelectorAll("input, select, textarea");
  let isValid = true;

  inputs.forEach(input => {
    const existingError = input.parentElement.querySelector('.error-message');
    if (existingError) existingError.remove();

    if (input.hasAttribute("required") && !String(input.value || '').trim()) {
      isValid = false;
      const error = document.createElement("div");
      error.className = "error-message";
      error.innerText = "Please fill this required field.";
      error.style.color = "red";
      error.style.fontSize = "13px";
      error.style.marginTop = "4px";
      input.style.borderColor = "red";
      input.parentElement.appendChild(error);
    } else {
      input.style.borderColor = "";
    }
  });

  if (!isValid) { inputs[0]?.focus(); return; }

  currentFormStep.classList.remove("active");
  currentStep++;
  document.getElementById("step-" + currentStep).classList.add("active");
  updateProgressUI();
  scrollToFormTop();
}

function prevStep() {
  document.getElementById("step-" + currentStep).classList.remove("active");
  currentStep--;
  document.getElementById("step-" + currentStep).classList.add("active");
  updateProgressUI();
  scrollToFormTop();
}

function scrollToFormTop() {
  const formTop = document.querySelector(".form-wrapper").getBoundingClientRect().top + window.pageYOffset;
  window.scrollTo({ top: formTop, behavior: 'smooth' });
}
</script>

<!-- 3) PREFILL SCRIPT (runs after DOM and after updateSubmitButtonState exists) -->
<script>
// Make PHP JSON available to JS
const initialData = <?php echo $initial_json ?: '{}'; ?>;

// Fill controls from JSON by matching "name" attributes
function prefillFormFromJSON(data) {
  if (!data || typeof data !== 'object') return;
  const form = document.getElementById('biographicalForm');
  const elements = form.querySelectorAll('input[name], select[name], textarea[name]');

  elements.forEach(el => {
    const name = el.getAttribute('name');
    if (!(name in data)) return;

    const val = data[name];

    if (el.type === 'radio') {
      if (el.value == val) el.checked = true;
    } else if (el.type === 'checkbox') {
      el.checked = !!val && (val === true || val === 'on' || val === 'yes' || val === '1');
    } else {
      el.value = val ?? '';
    }
  });

  if (typeof updateSubmitButtonState === 'function') updateSubmitButtonState();
}

document.addEventListener('DOMContentLoaded', () => {
  try {
    const isEdit = <?php echo $edit_patient_id ? 'true' : 'false'; ?>;
    if (isEdit && Object.keys(initialData || {}).length) {
      prefillFormFromJSON(initialData);
    }
  } catch (e) {
    console.warn('Prefill error:', e);
  }
});
</script>

<!-- 4) AJAX submit -->
<script>
const ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

document.getElementById("biographicalForm").addEventListener("submit", function (e) {
  e.preventDefault();

  const form = this;
  const formData = new FormData(form);
  formData.append("action", "save_bio_form_ajax");

  fetch(ajaxurl, {
    method: "POST",
    body: formData
  })
  .then(res => res.json())
  .then(response => {
    console.log("🔵 AJAX Response:", response);

    if (response.success) {
      alert("✅ Your data has been saved successfully!");
      // Optional redirect back to patient list:
      // window.location.href = "<?php echo site_url('/patient-list'); ?>";
    } else {
      alert("❌ Failed to save form.");
      console.error("🛑 Error Info:", response.data?.debug || response);
    }
  })
  .catch(error => {
    console.error("⚠️ Fetch Error:", error);
  });
});
</script>

<?php get_footer(); ?>

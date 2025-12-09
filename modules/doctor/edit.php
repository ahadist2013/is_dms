<?php
include '../../includes/header.php'; // ⚠️ main.js লোড হবে না


// dropdown data (create.php-এর মতোই)
$titles         = $conn->query("SELECT title_id, title_name FROM master_titles ORDER BY title_name ASC");
$disciplines    = $conn->query("SELECT discipline_id, discipline_name FROM master_disciplines ORDER BY discipline_name ASC");
$degrees        = $conn->query("SELECT degree_id, degree_name FROM master_degrees ORDER BY degree_name ASC");
$zones          = $conn->query("SELECT zone_id, zone_name FROM zones ORDER BY zone_name ASC");
$units          = $conn->query("SELECT unit_id, unit_name FROM master_units ORDER BY unit_name ASC");
$designations   = $conn->query("SELECT designation_id, designation_name FROM master_designations ORDER BY designation_name ASC");
$doctor_grades  = $conn->query("SELECT grade_id, grade_code FROM master_doctor_grades WHERE is_active=1 ORDER BY grade_id ASC");

$visit_types = ['Regular visit','Ex. Headquarter','Outstation'];
$doctor_id   = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
if ($doctor_id <= 0) {
  echo "<div style='padding:20px;color:#b00;font-weight:700'>Invalid doctor_id.</div>";
  include '../../includes/footer_without_mainjs.php'; exit;
}
?>

<script>
    window.IS_DOCTOR_EDIT = true;
</script>
<script src="<?php echo BASE_PATH; ?>assets/js/doctor_edit.js?v=5" defer></script>

<div class="doctor-entry-container">
  <h2>✏️ Edit Doctor Information</h2>

  <!-- action → save API -->
  <form id="doctor_edit_form" method="POST" action="<?php echo BASE_PATH; ?>api/doctor_edit_save.php">
    <input type="hidden" name="doctor_id" id="doctor_id" value="<?php echo $doctor_id; ?>">
    <input type="hidden" name="updated_by" value="<?php echo $_SESSION['user_id']; ?>">

    <div class="form-layout">
      <div class="form-column">
        <div class="card">
          <h3>Primary Information</h3>

          <div class="form-group">
            <label for="mobile_number">Doctor's Mobile Number <span class="required">*</span></label>
            <input type="text" id="mobile_number" name="mobile_number" maxlength="11" placeholder="01XXXXXXXXX"
                   <?php if($_SESSION['role']!=='Admin') echo 'readonly style="background:#f3f3f3"'; ?> required>
          </div>

          <div class="form-group">
            <label for="bmdc_number">BMDC Number</label>
            <div style="display:flex;align-items:center;gap:5px;">
              <span><b>A-</b></span>
              <input type="text" id="bmdc_number" name="bmdc_number" pattern="\d{1,6}" maxlength="6"
                     oninput="this.value=this.value.replace(/[^0-9]/g,'');" placeholder="e.g. 12345" style="flex:1;">
            </div>
            <small class="hint">Example: A-12345</small>
          </div>

          <div class="form-group">
            <label for="title_id">Title <span class="required">*</span></label>
            <select id="title_id" name="title_id" required>
              <option value="">Select Title</option>
              <?php while($t=$titles->fetch_assoc()){ echo "<option value='{$t['title_id']}'>{$t['title_name']}</option>"; } ?>
            </select>
          </div>

          <div class="form-group">
            <label for="name">Doctor Name <span class="required">*</span></label>
            <input type="text" id="name" name="name" style="text-transform:uppercase;" required>
          </div>

          <div class="form-group">
            <label for="email">Doctor's Email ID</label>
            <input type="email" id="email" name="email">
          </div>

          <div class="form-group">
            <label for="date_of_birth">Doctor's Date of Birth</label>
            <input type="date" id="date_of_birth" name="date_of_birth">
          </div>

          <div class="form-group">
            <label for="designation">Designation <span class="required">*</span></label>
            <select id="designation" name="designation" required>
              <option value="">Select Designation</option>
              <?php while($d=$designations->fetch_assoc()){ echo "<option value='{$d['designation_id']}'>{$d['designation_name']}</option>"; } ?>
            </select>
          </div>

          <div class="form-group">
            <label>Designation Status *</label>
            <div class="radio-group">
              <label><input type="radio" name="designation_status" value="Current" required> Current</label>
              <label><input type="radio" name="designation_status" value="Ex."> Ex.</label>
            </div>
          </div>

          <div class="form-group">
            <label for="specialization_id">Specialization/Department <span class="required">*</span></label>
            <select id="specialization_id" name="specialization_id" required>
              <option value="">Select Specialization</option>
              <?php while($sp=$disciplines->fetch_assoc()){ echo "<option value='{$sp['discipline_id']}'>{$sp['discipline_name']}</option>"; } ?>
            </select>
          </div>

          <div class="form-group">
            <label for="institute_id">Working Institute <span class="required">*</span></label>
            <select id="institute_id" name="institute_id" style="width:100%;" required></select>
          </div>

          <div class="form-group">
            <label>Institute Status *</label>
            <div class="radio-group">
              <label><input type="radio" name="is_ex_institute" value="0" required> Current</label>
              <label><input type="radio" name="is_ex_institute" value="1"> Ex.</label>
            </div>
          </div>
        </div>

        <div class="card">
          <h3>Qualifications</h3>
          <!-- create.php-এর মতোই ডাইনামিক এরিয়া -->
          <div class="qualification-item" id="qual_0">
            <button type="button" class="remove-qual" data-index="0">X</button>
            <div class="form-group">
              <label for="degree_id_0">Main Degree <span class="required">*</span></label>
              <select id="degree_id_0" name="degree_ids[]" data-index="0" required>
                <option value="">Select Main Degree</option>
                <?php $degrees->data_seek(0); while($deg=$degrees->fetch_assoc()){ echo "<option value='{$deg['degree_id']}'>{$deg['degree_name']}</option>"; } ?>
              </select>
            </div>
            <div class="form-group">
              <label for="q_specialization_0">Specialization/Discipline</label>
              <select id="q_specialization_0" name="q_specializations[]">
                <option value="">Select Degree First</option>
              </select>
            </div>
          </div>

          <div id="additional_qualifications"></div>
          <button type="button" class="btn btn-secondary btn-small" id="add_qualification_btn">➕ Add More Degree</button>
        </div>
<div id="assignment_area" style="display:none;"> 
        <div class="card">
          <h3>Assignment Details</h3>
          <div class="form-group">
            <label for="zone_id">Zone <span class="required">*</span></label>
            <select id="zone_id" name="zone_id" required>
              <option value="">Select Zone</option>
              <?php $zones->data_seek(0); while($z=$zones->fetch_assoc()){ echo "<option value='{$z['zone_id']}'>{$z['zone_name']}</option>"; } ?>
            </select>
          </div>

          <div class="form-group">
            <label for="territory_id">Territory <span class="required">*</span></label>
            <select id="territory_id" name="territory_id" required>
              <option value="">Select Zone First</option>
            </select>
          </div>

          <div class="form-group"><div id="officer_info" class="hint">Officer: N/A | ID: N/A</div></div>

          <div class="form-group">
            <label>Doctor Type *</label>
            <div class="radio-group">
              <label><input type="radio" name="doctor_type" value="in_house" required> In-House</label>
              <label><input type="radio" name="doctor_type" value="outside"> Outside</label>
            </div>

            <div id="unit_container" style="display:none; margin-top:10px;">
              <div class="form-group">
                <label for="unit_id">Unit *</label>
                <select id="unit_id" name="unit_id">
                  <option value="">Select Unit</option>
                  <?php $units->data_seek(0); while($u=$units->fetch_assoc()){ echo "<option value='{$u['unit_id']}'>{$u['unit_name']}</option>"; } ?>
                </select>
              </div>

              <div class="form-group">
                <label for="room_number">Room Number *</label>
                <input type="text" id="room_number" name="room_number">
              </div>
            </div>

            <div id="visit_type_container" style="display:none; margin-top:10px;">
              <label for="visit_type">Visit Type *</label>
              <select id="visit_type" name="visit_type">
                <option value="">Select Visit Type</option>
                <?php foreach($visit_types as $v){ echo "<option value='{$v}'>{$v}</option>"; } ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label for="doctor_grade">Doctor Grading *</label>
            <select id="doctor_grade" name="doctor_grade" required>
              <option value="">Select Grade</option>
              <?php $doctor_grades->data_seek(0); while($g=$doctor_grades->fetch_assoc()){ echo "<option value='{$g['grade_id']}'>{$g['grade_code']}</option>"; } ?>
            </select>
          </div>

          <div class="form-group">
            <label for="chamber_id">Chamber/Clinic Name <span class="required">*</span></label>
            <select id="chamber_id" name="chamber_id" style="width:100%;" required></select>
          </div>
        </div>
</div>
        <button type="submit" class="btn btn-primary">💾 Update Doctor Information</button>
      </div>
    </div>
  </form>
</div>

<!-- Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- শুধু edit পেজের JS -->
<script>
    window.IS_DOCTOR_EDIT = true;
</script>

<script>
$(document).ready(function () {

    $("#doctor_edit_form").on("submit", function(e){
        e.preventDefault(); // normal submit বন্ধ

        let formData = new FormData(this);

        $.ajax({
            url: "<?php echo BASE_PATH; ?>api/doctor_edit_save.php",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,

            success: function(res) {
                if(res.success){
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: res.msg,
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = "<?php echo BASE_PATH; ?>pages/my_doctors_2.php";
                        }
                    });

                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Update Failed',
                        text: res.msg
                    });
                }
            },

            error: function(xhr, status, error){
                Swal.fire({
                    icon: 'error',
                    title: 'Request Error',
                    text: error
                });
            }
        });
    });

});
</script>

<?php include '../../includes/footer_without_mainjs.php'; ?>

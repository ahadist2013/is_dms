<?php
include '../../includes/header.php'; // Includes session_start, db_connection, and BASE_PATH

// Check if user has permission to add doctor (e.g., all users except public)
// if ($_SESSION['role'] == 'Public') {
//     header("Location: " . BASE_PATH . "index.php");
//     exit();
// }

// Fetching initial data for dropdowns
$titles = $conn->query("SELECT title_id, title_name FROM master_titles ORDER BY title_name ASC");
$disciplines = $conn->query("SELECT discipline_id, discipline_name FROM master_disciplines ORDER BY discipline_name ASC");
$degrees = $conn->query("SELECT degree_id, degree_name FROM master_degrees ORDER BY degree_name ASC");
$zones = $conn->query("SELECT zone_id, zone_name FROM zones ORDER BY zone_name ASC");
$institute_types = $conn->query("SELECT type_id, type_name FROM master_institute_types ORDER BY type_name ASC");
$regions = $conn->query("SELECT region_id, region_name FROM regions ORDER BY region_name ASC");

// Assuming a master_units table for Unit field
$units = $conn->query("SELECT unit_id, unit_name FROM master_units ORDER BY unit_name ASC");
$designations = $conn->query("SELECT designation_id, designation_name FROM master_designations ORDER BY designation_name ASC");
$visit_types = ['Regular visit', 'Ex. Headquarter', 'Outstation'];

// Fetch Doctor Grades (for dropdown)
$doctor_grades = $conn->query("SELECT grade_id, grade_code FROM master_doctor_grades WHERE is_active = 1 ORDER BY grade_id ASC");
?>

<div class="doctor-entry-container">
    <h2>➕ Add New Doctor Entry</h2>
    <form id="doctor_entry_form" method="POST" action="save_doctor.php">
        <input type="hidden" name="existing_doctor_id" id="existing_doctor_id">
        <input type="hidden" name="created_by" value="<?php echo $_SESSION['user_id']; ?>">
        
        <div class="form-layout">
            
            <div class="form-column">
                <div class="card">
                    <h3>Primary Information</h3>

                    <!-- 🟢 Always visible -->
                    <div class="form-group">
                        <label for="mobile_number">Doctor's Mobile Number <span class="required">*</span></label>
                        <input type="text" id="mobile_number" name="mobile_number" maxlength="11" placeholder="01XXXXXXXXX" required>
                        <div id="mobile_suggestion_box" class="suggestion-box"></div>
                        <small class="hint">Please input the personal mobile number of the doctor</small>
                    </div>
                    
                    

                    <!-- 🔵 Hidden when doctor exists -->
                    <div id="new_doctor_fields">
                        <!-- Optional BMDC Number -->
                    <div class="form-group">
                        <label for="bmdc_number">BMDC Number</label>
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <span style="font-weight: bold;">A-</span>
                            <input 
                                type="text" 
                                id="bmdc_number" 
                                name="bmdc_number" 
                                maxlength="6" 
                                pattern="\d{1,6}" 
                                placeholder="Enter BMDC Number"
                                oninput="this.value=this.value.replace(/[^0-9]/g,'');"
                                style="flex: 1;"
                            >
                        </div>
                        <small class="hint">Example: A-12345</small>
                    </div>
                        <div class="form-group">
                            <label for="title_id">Title <span class="required">*</span></label>
                            <select id="title_id" name="title_id" required>
                                <option value="">Select Title</option>
                                <?php while($title = $titles->fetch_assoc()): ?>
                                    <option value="<?php echo $title['title_id']; ?>"><?php echo $title['title_name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="name">Doctor Name <span class="required">*</span></label>
                           <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            style="text-transform: uppercase;" 
                            oninput="this.value = this.value.toUpperCase();" 
                            placeholder="Enter Doctor Name" 
                            required>
                            <div id="name_suggestion_box" class="suggestion-box"></div>
                            <small class="hint">Input only name: Example: MD. ABDULLAH</small>
                        </div>

                        <!-- Optional Email -->
                        <div class="form-group">
                            <label for="email">Doctor's Email ID</label>
                            <input type="email" id="email" name="email" placeholder="Enter valid email address">
                        </div>

                        <!-- Optional Date of Birth -->
                        <div class="form-group">
                            <label for="dob">Doctor's Date of Birth</label>
                            <input type="date" id="dob" name="dob">
                        </div>

                         <div class="card">
                            <h3>Professional Details</h3>
                            <div class="form-group">
                                <label for="designation">Designation <span class="required">*</span></label>
                                <select name="designation" id="designation" required>
                                    <option value="">Select Designation</option>
                                    <?php
                                    // Assuming $designations is an array of designation data from the database
                                    if ($designations->num_rows > 0) {
                                        while ($row = $designations->fetch_assoc()) {
                                            echo '<option value="' . $row['designation_id'] . '">' . htmlspecialchars($row['designation_name']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>


                            <div class="form-group">
                                <label>Designation Status  <span class="required">*</span></label>
                                <div class="radio-group" style="display: flex; gap: 20px;">
                                    <div class="radio-option"> 
                                        <input type="radio" id="designation_status_current" name="designation_status" value="Current" required checked>
                                        <label for="designation_status_current">Current</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="designation_status_ex" name="designation_status" value="Ex." required>
                                        <label for="designation_status_ex">Ex.</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
								<label for="specialization_id">Specialization/Department <span class="required">*</span></label>
								<select name="specialization_id" id="specialization_id" required>
									<option value="">Select Specialization</option>
									<?php
									// master_disciplines টেবিল থেকে specialization গুলো আনো
									$specializations = $conn->query("SELECT discipline_id, discipline_name FROM master_disciplines ORDER BY discipline_name ASC");
									if ($specializations && $specializations->num_rows > 0) {
										while ($sp = $specializations->fetch_assoc()) {
											echo '<option value="' . htmlspecialchars($sp['discipline_id']) . '">' . htmlspecialchars($sp['discipline_name']) . '</option>';
										}
									}
									?>
								</select>
							</div>

                            
                            <div class="form-group">
                              <label for="institute_id">Working Institute <span class="required">*</span></label>
                              <select name="institute_id" id="institute_id" style="width:100%;" required></select>
                            </div>



                            <div class="form-group">
                                <label>Institute Status *</label>
                                <div class="radio-group" style="display: flex; gap: 20px;">
                                    <div class="radio-option"> 
                                        <input type="radio" id="institute_status_current" name="is_ex_institute" value="0" required checked>
                                        <label for="institute_status_current">Current</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="institute_status_ex" name="is_ex_institute" value="1" required>
                                        <label for="institute_status_ex">Ex.</label>
                                    </div>
                                </div>
                            </div>
                            
                        </div>

                        <div class="card">
                            <h3>Qualifications</h3>
                            <div class="qualification-item" id="qual_0">
                                <div class="form-group">
                                    <label for="degree_id_0">Main Degree <span class="required">*</span></label>
                                    <select id="degree_id_0" name="degree_ids[]" data-index="0" required>
                                        <option value="">Select Main Degree</option>
                                        <?php $degrees->data_seek(0); // Reset pointer for degrees ?>
                                        <?php while($degree = $degrees->fetch_assoc()): ?>
                                            <option value="<?php echo $degree['degree_id']; ?>"><?php echo $degree['degree_name']; ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="q_specialization_0">Specialization/Discipline</label>
                                    <select id="q_specialization_0" name="q_specializations[]">
                                        <option value="">Select Degree First</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div id="additional_qualifications">
                                </div>
                            
                            <button type="button" class="btn btn-secondary btn-small" id="add_qualification_btn">➕ Add More Degree</button>
                        </div>
                    </div>
                </div>
                
                <div class="card" id="assignment_section">
                    <h3>Assignment Details</h3>
                    
                    <div class="form-group">
                        <label for="zone_id">Zone <span class="required">*</span></label>
                        <select id="zone_id" name="zone_id" required>
                            <option value="">Select Zone</option>
                            <?php $zones->data_seek(0); ?>
                            <?php while($zone = $zones->fetch_assoc()): ?>
                                <option value="<?php echo $zone['zone_id']; ?>"><?php echo $zone['zone_name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="territory_id">Territory <span class="required">*</span></label>
                        <select id="territory_id" name="territory_id" required>
                            <option value="">Select Zone First</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <div id="officer_info" class="hint">Officer: N/A | ID: N/A</div>
                    </div>
                    
                   <div class="form-group">
                        <label>Doctor Type *</label>
                        <div class="radio-group">

                            <div class="radio-option"> 
                                <input type="radio" id="doctor_type_inhouse" name="doctor_type" value="in_house" required>
                                <label for="doctor_type_inhouse">In-House</label>
                            </div>

                            <div class="radio-option">
                                <input type="radio" id="doctor_type_outside" name="doctor_type" value="outside" required>
                                <label for="doctor_type_outside">Outside</label>
                            </div>

                        </div>

                        <!-- ✅ In-House details: Unit + Room Number side by side -->
                        <div id="inhouse_fields" style="display:none; margin-top:15px;">

                            <div style="display:flex; gap:15px; align-items:flex-end; flex-wrap:wrap;">
                                <!-- Unit -->
                                <div class="form-group" style="flex:1;">
                                    <label for="unit_id">Unit *</label>
                                    <select id="unit_id" name="unit_id" class="form-control">
                                        <option value="">Select Unit</option>
                                        <?php 
                                        if ($units->num_rows > 0) {
                                            $units->data_seek(0); 
                                            while ($row = $units->fetch_assoc()) {
                                                echo '<option value="' . $row['unit_id'] . '">' . htmlspecialchars($row['unit_name']) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>

                                <!-- Room Number -->
                                <div class="form-group" style="flex:1;">
                                    <label for="room_number">Room Number <span class="text-danger">*</span></label>
                                    <input type="text" id="room_number" name="room_number" class="form-control" placeholder="Enter Consultant Room Number">
                                </div>
                            </div>
                        </div>

                        <!-- Outside Visit Type -->
                        <div class="form-group" id="visit_type_container" style="display:none; margin-top:15px;">
                            <label for="visit_type">Visit Type *</label>
                            <select id="visit_type" name="visit_type" class="form-control">
                                <option value="">Select Visit Type</option>
                                <?php 
                                foreach ($visit_types as $type) {
                                    echo '<option value="' . htmlspecialchars($type) . '">' . htmlspecialchars($type) . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group" style="margin-top:15px;">
                            <label for="doctor_grade">Doctor Grading *</label>
                            <select id="doctor_grade" name="doctor_grade" required class="form-control">
                                <option value="">Select Doctor Grade *</option>
                                <?php 
                                if (isset($doctor_grades) && $doctor_grades->num_rows > 0) {
                                    $doctor_grades->data_seek(0);
                                    while ($row = $doctor_grades->fetch_assoc()) {
                                        echo '<option value="' . $row['grade_id'] . '">' . htmlspecialchars($row['grade_code']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                      <label for="chamber_id">Chamber/Clinic Name <span class="required">*</span></label>
                      <select name="chamber_id" id="chamber_id" style="width:100%;" required></select>
                    </div>
                    

                </div>

                <button type="submit" class="btn btn-primary">💾 Save Doctor Information</button>

            </div>
            
            <div class="form-column">

            </div>
        </div>
    </form>
</div>

<div id="modal_institute_chamber" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close-btn">&times;</span>
        <h3 id="modal_title">Add New Master Data</h3>
        <div id="modal_body">
            </div>
    </div>
</div>
<!-- ✅ Select2 CSS & JS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {

  // ✅ Institute Dropdown with AJAX Search
  $('#institute_id').select2({
      placeholder: 'Search Institute...',
      ajax: {
          url: '../../modules/master_data/ajax_handlers.php',
          type: 'GET',
          dataType: 'json',
          delay: 300,
          data: function (params) {
              return {
                  action: 'search_institutes',
                  query: params.term || '' // search term
              };
          },
          processResults: function (data) {
              return {
                  results: $.map(data.data, function (item) {
                      return { id: item.id, text: item.name };
                  })
              };
          },
          cache: true
      },
      minimumInputLength: 1
  });

  // ✅ Chamber Dropdown with AJAX Search
  $('#chamber_id').select2({
      placeholder: 'Search Chamber/Clinic...',
      ajax: {
          url: '../../modules/master_data/ajax_handlers.php',
          type: 'GET',
          dataType: 'json',
          delay: 300,
          data: function (params) {
              return {
                  action: 'search_chambers',
                  query: params.term || ''
              };
          },
          processResults: function (data) {
              return {
                  results: $.map(data.data, function (item) {
                      return { id: item.id, text: item.name };
                  })
              };
          },
          cache: true
      },
      minimumInputLength: 1
  });

});
</script>

<?php 
// Ensure all database resources are freed before closing the included file
if ($titles->num_rows > 0) $titles->free();
if ($disciplines->num_rows > 0) $disciplines->free();
if ($degrees->num_rows > 0) $degrees->free();
if ($designations && $designations->num_rows > 0) $designations->free();
if ($zones->num_rows > 0) $zones->free();
if ($institute_types->num_rows > 0) $institute_types->free();
if ($regions->num_rows > 0) $regions->free();
if ($units->num_rows > 0) $units->free();
if (isset($doctor_grades) && $doctor_grades !== false && $doctor_grades->num_rows > 0) $doctor_grades->free(); 
?>
<?php include '../../includes/footer.php'; ?>
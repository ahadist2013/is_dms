<?php
// ফাইল লোকেশন: /is_dms/modules/doctor/doctor_edit.php

include '../../includes/header.php'; // Includes session_start, db_connection, and BASE_PATH

// Helper function to escape data for HTML output
function e($value) {
    return htmlspecialchars($value ?? '');
}

// ==========================================================
// 🔴 NEW: User Role Check
// ==========================================================
// Check if the current user is an Admin
$is_admin_user = (isset($_SESSION['role']) && $_SESSION['role'] == 'Admin');
// Admin না হলে, ফিল্ডটিকে শুধু 'readonly' করা হবে, যাতে ডেটা POST হয়
$mobile_field_modifier = $is_admin_user ? '' : 'readonly';

// ==========================================================
// 🔴 EDIT MODE: Existing Doctor Data Fetch
// ==========================================================
$is_edit_mode = false;
$doctor_data = [];
$doctor_degrees = [];
$assignment_data = [];

$doctor_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
$assignment_id = isset($_GET['assignment_id']) ? (int)$_GET['assignment_id'] : 0;
$page_title = "❌ Invalid Request"; // Default title

if ($doctor_id > 0 && $assignment_id > 0) {
    $is_edit_mode = true;
    
    // --- Existing Doctor Data Fetch Query (Assuming this part is correct) ---
    $sql_doctor = "
        SELECT 
            d.*, d.name AS doctor_name, d.institute_id AS initial_institute_id,
            a.*,
            md.discipline_name AS specialization_name,
            mi.institute_name AS initial_institute_name,
            mc.chamber_name AS initial_chamber_name,
            z.zone_name, t.territory_name
        FROM doctors d
        LEFT JOIN doctor_assignments a ON d.doctor_id = a.doctor_id AND a.assignment_id = ?
        LEFT JOIN master_disciplines md ON d.specialization_id = md.discipline_id
        LEFT JOIN master_institutes mi ON d.institute_id = mi.institute_id
        LEFT JOIN master_chambers mc ON a.chamber_id = mc.chamber_id
        LEFT JOIN territories t ON a.territory_id = t.territory_id
        LEFT JOIN zones z ON t.zone_id = z.zone_id
        WHERE d.doctor_id = ? AND a.is_primary = 1
    ";

    $stmt = $conn->prepare($sql_doctor);
    $stmt->bind_param("ii", $assignment_id, $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $doctor_data = $result->fetch_assoc();
        $assignment_data = $doctor_data; // Assignment details are merged with doctor data
        $page_title = "Edit Doctor: ".e($doctor_data['doctor_name']);
        
        // Fetch existing degrees (Qualifications)
        $sql_degrees = "
            SELECT 
                dd.degree_id, 
                md.degree_name, 
                dd.detail_id, 
                mdd.detail_value 
            FROM doctor_degrees dd
            JOIN master_degrees md ON dd.degree_id = md.degree_id
            LEFT JOIN master_degree_details mdd ON dd.detail_id = mdd.detail_id
            WHERE dd.doctor_id = ?
            ORDER BY dd.doctor_degree_id ASC
        ";
        $stmt_degrees = $conn->prepare($sql_degrees);
        $stmt_degrees->bind_param("i", $doctor_id);
        $stmt_degrees->execute();
        $degrees_result = $stmt_degrees->get_result();
        
        if ($degrees_result) {
            while($row = $degrees_result->fetch_assoc()) {
                $doctor_degrees[] = $row;
            }
            $degrees_result->free();
        }
        $stmt_degrees->close();
        
    } else {
        $page_title = "Doctor Not Found";
    }
    
    $result->free();
    $stmt->close();
}


// ==========================================================
// 💡 FIX 1.1: Fetching Master Data for Qualification (Degrees)
// ==========================================================
// সমস্ত ডিগ্রি ডেটা ফেচ করে $all_degrees_data ভেরিয়েবলে রাখা (AJAX এবং PHP লুপের জন্য)
$all_degrees_result = $conn->query("SELECT degree_id, degree_name FROM master_degrees ORDER BY degree_name ASC");
$all_degrees_data = [];

if ($all_degrees_result) {
    if ($all_degrees_result->num_rows > 0) {
        while ($row = $all_degrees_result->fetch_assoc()) {
            $all_degrees_data[] = $row;
        }
    }
    // ✅ FIX 1.2: রেজাল্ট সেট সাথে সাথে ফ্রি করে দেওয়া, যাতে শেষের free() কল করলে error না আসে।
    $all_degrees_result->free(); 
}

// Fetching initial data for other dropdowns
$titles = $conn->query("SELECT title_id, title_name FROM master_titles ORDER BY title_name ASC");
$disciplines = $conn->query("SELECT discipline_id, discipline_name FROM master_disciplines ORDER BY discipline_name ASC");
$zones = $conn->query("SELECT zone_id, zone_name FROM zones ORDER BY zone_name ASC");
$institute_types = $conn->query("SELECT type_id, type_name FROM master_institute_types ORDER BY type_name ASC");
$regions = $conn->query("SELECT region_id, region_name FROM regions ORDER BY region_name ASC");

// Assuming a master_units table for Unit field
$units = $conn->query("SELECT unit_id, unit_name FROM master_units ORDER BY unit_name ASC");
$designations = $conn->query("SELECT designation_id, designation_name FROM master_designations ORDER BY designation_name ASC");
$visit_types = ['Regular visit', 'Ex. Headquarter', 'Outstation'];

// ==========================================================
// HTML Structure Starts Here
// ==========================================================
?>

<div class="container">
    <h2><?php echo $page_title; ?></h2>

    <?php if ($is_edit_mode && !empty($doctor_data)): ?>
    <form id="doctor_entry_form" action="../doctor/update_doctor.php" method="POST">
        <input type="hidden" name="doctor_id" value="<?php echo e($doctor_id); ?>">
        <input type="hidden" name="assignment_id" value="<?php echo e($assignment_id); ?>">
        
        <div class="row">
            
            <div class="col-md-6">
                <div class="card">
                    <h3>Doctor Information</h3>
                    <div class="form-group">
                        <label for="name">Doctor Name <span class="required">*</span></label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo e($doctor_data['doctor_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="mobile_number">Mobile Number <span class="required">*</span></label>
                        <input type="text" id="mobile_number" name="mobile_number" class="form-control" value="<?php echo e($doctor_data['mobile_number']); ?>" <?php echo $mobile_field_modifier; ?> required>
                        <?php if (!$is_admin_user): ?>
                            <small class="text-muted">Mobile number is read-only for non-admin users.</small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo e($doctor_data['email']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="bmdc_number">BMDC Number</label>
                        <input type="text" id="bmdc_number" name="bmdc_number" class="form-control" value="<?php echo e($doctor_data['bmdc_number']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="dob">Date of Birth</label>
                        <input type="date" id="dob" name="dob" class="form-control" value="<?php echo e($doctor_data['dob']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="title_id">Title <span class="required">*</span></label>
                        <select id="title_id" name="title_id" class="form-control" required>
                            <option value="">Select Title</option>
                            <?php 
                            if ($titles):
                                while ($row = $titles->fetch_assoc()): 
                                    $selected = ($row['title_id'] == $doctor_data['title_id']) ? 'selected' : '';
                            ?>
                                <option value="<?php echo e($row['title_id']); ?>" <?php echo $selected; ?>><?php echo e($row['title_name']); ?></option>
                            <?php 
                                endwhile;
                            endif;
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="specialization_id">Specialization (Main) <span class="required">*</span></label>
                        <select id="specialization_id" name="specialization_id" class="form-control" required>
                            <option value="">Select Specialization</option>
                            <?php 
                            if ($disciplines):
                                $disciplines->data_seek(0); // Reset pointer
                                while ($row = $disciplines->fetch_assoc()): 
                                    $selected = ($row['discipline_id'] == $doctor_data['specialization_id']) ? 'selected' : '';
                            ?>
                                <option value="<?php echo e($row['discipline_id']); ?>" <?php echo $selected; ?>><?php echo e($row['discipline_name']); ?></option>
                            <?php 
                                endwhile;
                            endif;
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="designation_id">Designation</label>
                        <select id="designation_id" name="designation_id" class="form-control">
                            <option value="">Select Designation</option>
                            <?php 
                            if ($designations):
                                while ($row = $designations->fetch_assoc()): 
                                    $selected = ($row['designation_id'] == $doctor_data['designation_id']) ? 'selected' : '';
                            ?>
                                <option value="<?php echo e($row['designation_id']); ?>" <?php echo $selected; ?>><?php echo e($row['designation_name']); ?></option>
                            <?php 
                                endwhile;
                            endif;
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="institute_id">Institute/Organization <span class="required">*</span></label>
                        <select id="institute_id" name="institute_id" class="form-control" required>
                            <?php 
                            // Initial selected value for Select2
                            if (!empty($doctor_data['initial_institute_id'])):
                            ?>
                                <option value="<?php echo e($doctor_data['initial_institute_id']); ?>" selected><?php echo e($doctor_data['initial_institute_name']); ?></option>
                            <?php else: ?>
                                <option value="">Search/Select Institute</option>
                            <?php endif; ?>
                            <option value="other_add">➕ Add New Institute</option> 
                        </select>
                    </div>

                </div>
            </div>

            <div class="col-md-6">
                
                <div class="card">
                    <h3>Qualifications (Degrees)</h3>
                    <div id="additional_qualifications">
                        <?php
                        // 🔴 Existing Degrees Rendering
                        $q_count = 0;
                        if (!empty($doctor_degrees)):
                            foreach ($doctor_degrees as $degree):
                                $q_count++;
                                // Remove button for all rows except the first one
                                $is_removable = $q_count > 1 ? '<button type="button" class="remove-qual" data-index="'.$q_count.'">X</button>' : '';
                                
                                // Get selected values
                                $selected_degree_id = $degree['degree_id'] ?? null;
                                $selected_discipline_id = $degree['detail_id'] ?? null;
                                $selected_discipline_name = $degree['detail_value'] ?? '';
                        ?>
                                <div class="qualification-item qualification-row" id="qual_<?php echo $q_count; ?>">
                                    <?php echo $is_removable; ?>
                                    <div class="form-group">
                                        <label for="degree_id_<?php echo $q_count; ?>">Degree <?php echo ($q_count == 1 ? '<span class="required">*</span>' : ''); ?></label>
                                        <select id="degree_id_<?php echo $q_count; ?>" name="degree_ids[]" data-index="<?php echo $q_count; ?>" class="form-control degree-dropdown" <?php echo ($q_count == 1 ? 'required' : ''); ?>>
                                            <option value="">Select Degree</option>
                                            <?php 
                                            // সমস্ত ডিগ্রি অপশন লোড করা
                                            foreach ($all_degrees_data as $deg_option):
                                                $selected = ($deg_option['degree_id'] == $selected_degree_id) ? 'selected' : '';
                                                echo "<option value='{$deg_option['degree_id']}' {$selected}>".e($deg_option['degree_name'])."</option>";
                                            endforeach;
                                            ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="q_specialization_<?php echo $q_count; ?>">Specialization/Discipline</label>
                                        <select id="q_specialization_<?php echo $q_count; ?>" name="detail_ids[]" class="form-control specialization-dropdown">
                                            <?php 
                                            // সেভ করা মানটি লোড করা, বাকিগুলো JS লোড করবে।
                                            if (!empty($selected_discipline_id)):
                                                echo "<option value='{$selected_discipline_id}' selected>".e($selected_discipline_name)."</option>";
                                            endif;
                                            ?>
                                            <option value="">Select Specialization/Discipline</option> 
                                        </select>
                                    </div>
                                </div>
                        <?php
                            endforeach;
                        else:
                            // Default single row if no degrees found
                            $q_count = 1; // Default to 1 if empty
                        ?>
                            <div class="qualification-item qualification-row" id="qual_1">
                                <div class="form-group">
                                    <label for="degree_id_1">Degree <span class="required">*</span></label>
                                    <select id="degree_id_1" name="degree_ids[]" data-index="1" class="form-control degree-dropdown" required>
                                        <option value="">Select Main Degree</option>
                                        <?php 
                                        // সমস্ত ডিগ্রি অপশন লোড করা
                                        foreach ($all_degrees_data as $deg_option):
                                            echo "<option value='{$deg_option['degree_id']}'>".e($deg_option['degree_name'])."</option>";
                                        endforeach; 
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="q_specialization_1">Specialization/Discipline</label>
                                    <select id="q_specialization_1" name="detail_ids[]" class="form-control specialization-dropdown">
                                        <option value="">Select Degree First</option>
                                    </select>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="button" class="btn btn-secondary btn-small" id="add_qualification_btn">➕ Add More Degree</button>
                </div>
                
                <div class="card">
                    <h3>Assignment Details</h3>
                    <div class="form-group">
                        <label for="doctor_type">Doctor Type <span class="required">*</span></label>
                        <select id="doctor_type" name="doctor_type" class="form-control" required>
                            <option value="">Select Type</option>
                            <?php 
                            foreach (['G.P.', 'Specialist', 'Dental'] as $type): 
                                $selected = ($type == $assignment_data['doctor_type']) ? 'selected' : '';
                            ?>
                                <option value="<?php echo e($type); ?>" <?php echo $selected; ?>><?php echo e($type); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="unit_id">Unit</label>
                        <select id="unit_id" name="unit_id" class="form-control">
                            <option value="">Select Unit</option>
                            <?php 
                            if ($units):
                                while ($row = $units->fetch_assoc()): 
                                    $selected = ($row['unit_id'] == $assignment_data['unit_id']) ? 'selected' : '';
                            ?>
                                <option value="<?php echo e($row['unit_id']); ?>" <?php echo $selected; ?>><?php echo e($row['unit_name']); ?></option>
                            <?php 
                                endwhile;
                            endif;
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="chamber_id">Chamber/Clinic <span class="required">*</span></label>
                        <select id="chamber_id" name="chamber_id" class="form-control" required>
                            <?php 
                            // Initial selected value for Select2
                            if (!empty($assignment_data['chamber_id'])):
                            ?>
                                <option value="<?php echo e($assignment_data['chamber_id']); ?>" selected><?php echo e($assignment_data['initial_chamber_name']); ?></option>
                            <?php else: ?>
                                <option value="">Search/Select Chamber</option>
                            <?php endif; ?>
                            <option value="other_add">➕ Add New Chamber/Clinic</option> 
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="visit_type">Visit Type</label>
                        <select id="visit_type" name="visit_type" class="form-control">
                            <option value="">Select Visit Type</option>
                            <?php 
                            foreach ($visit_types as $type): 
                                $selected = ($type == $assignment_data['visit_type']) ? 'selected' : '';
                            ?>
                                <option value="<?php echo e($type); ?>" <?php echo $selected; ?>><?php echo e($type); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Assigned Territory</label>
                        <p class="form-control-static">
                            **<?php echo e($assignment_data['zone_name'] ?? 'N/A'); ?>** - 
                            **<?php echo e($assignment_data['territory_name'] ?? 'N/A'); ?>**
                        </p>
                        <input type="hidden" name="territory_id" value="<?php echo e($assignment_data['territory_id'] ?? ''); ?>">
                        <input type="hidden" name="zone_id" id="zone_id" value="<?php echo e($assignment_data['zone_id'] ?? ''); ?>" disabled>
                    </div>

                </div>
            </div>
            
            <div class="col-md-12">
                <div class="text-right">
                    <button type="submit" class="btn btn-primary btn-lg">Update Doctor</button>
                </div>
            </div>
            
        </div>
    </form>
    
    <?php else: ?>
        <p class="error-message">Invalid Doctor ID or Assignment ID provided for editing.</p>
    <?php endif; ?>
</div>

<div id="modal_institute_chamber" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3 id="modal_title">Add New Master Data</h3>
        <div id="modal_body">
            </div>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script src="../../assets/js/edit_doctor.js?v=<?php echo time(); ?>"></script>


<script>
$(document).ready(function() {
    // 🔴 Qualification Counter Initialization for edit_doctor.js
    // এটি edit_doctor.js এর qual_count ভেরিয়েবলটি ইনিশিয়ালাইজ করবে।
    window.qual_count_initial = <?php echo max($q_count, 1); ?>; // ✅ FIX: max($q_count, 1) ব্যবহার করা হয়েছে।
    
    // Zone ID disabled, so we manually set it in form submission to send with the request
    $('#doctor_entry_form').on('submit', function() {
        var zoneVal = $('#zone_id').val();
        if (zoneVal) {
            $(this).append('<input type="hidden" name="zone_id" value="' + zoneVal + '" />');
        }
    });

});
</script>


<?php 
// ==========================================================
// ✅ FIX 3: Safe Resource Freeing (Resolves Fatal Error at 539)
// ==========================================================
if (isset($titles) && ($titles instanceof mysqli_result)) $titles->free();
if (isset($disciplines) && ($disciplines instanceof mysqli_result)) $disciplines->free();
if (isset($degrees) && ($degrees instanceof mysqli_result)) $degrees->free(); // If the original $degrees was used elsewhere
if (isset($zones) && ($zones instanceof mysqli_result)) $zones->free();
if (isset($institute_types) && ($institute_types instanceof mysqli_result)) $institute_types->free();
if (isset($regions) && ($regions instanceof mysqli_result)) $regions->free();
if (isset($units) && ($units instanceof mysqli_result)) $units->free();
if (isset($designations) && ($designations instanceof mysqli_result)) $designations->free();

include '../../includes/footer.php'; 
?>
<?php
// ফাইল লোকেশন: /is_dms/modules/doctor/update_doctor.php
// ===============================
// Doctor Data UPDATE Script (NEW FILE for Edit functionality)
// ===============================

include '../../includes/header.php'; // $conn + session_start()
header('Content-Type: text/html'); // Set header back to HTML for SweetAlert

// ----------------------------------------------------
// 🔴 Audit Log Function (for history tracking)
// ----------------------------------------------------
function log_audit($conn, $doctor_id, $table_name, $record_id, $old_data, $new_data, $change_type) {
    if (!$doctor_id) return;

    // Changes only log
    $old_data_filtered = array_diff_assoc($old_data, $new_data);
    $new_data_filtered = array_diff_assoc($new_data, $old_data);

    // Skip if only timestamps changed or no change
    if (empty($old_data_filtered) && empty($new_data_filtered)) return;
    
    $changed_by = $_SESSION['user_id'];
    $stmt = $conn->prepare("INSERT INTO doctor_audit_log (doctor_id, table_name, record_id, old_data, new_data, changed_by, change_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $old_json = json_encode($old_data_filtered);
    $new_json = json_encode($new_data_filtered);

    $stmt->bind_param("isissis", $doctor_id, $table_name, $record_id, $old_json, $new_json, $changed_by, $change_type);
    $stmt->execute();
    $stmt->close();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 🔴 IDs and User Info (REQUIRED for UPDATE)
    $doctor_id      = !empty($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : null;
    $assignment_id  = !empty($_POST['assignment_id']) ? (int)$_POST['assignment_id'] : null;
    $updated_by     = $_SESSION['user_id'];

    if (!$doctor_id || !$assignment_id) {
        // Essential IDs missing for UPDATE
        $msg = "Error: Missing Doctor ID or Assignment ID for update operation.";
        $icon = 'error';
        goto final_alert;
    }


    // Doctor fields
    $name           = sanitize_input($conn, $_POST['name']);
    $title_id       = !empty($_POST['title_id']) ? (int)$_POST['title_id'] : NULL;
    $bmdc_number    = sanitize_input($conn, $_POST['bmdc_number']);
    $email          = sanitize_input($conn, $_POST['email']);
    $date_of_birth  = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : NULL;
    $designation_id = !empty($_POST['designation_id']) ? (int)$_POST['designation_id'] : NULL;
    $designation_status = $_POST['designation_status'] ?? 'Current';
    $institute_id   = !empty($_POST['institute_id']) ? (int)$_POST['institute_id'] : NULL;
    $is_ex_institute= (int)$_POST['is_ex_institute'];
    $specialization_id= !empty($_POST['specialization_id']) ? (int)$_POST['specialization_id'] : NULL;
    
    // Assignment fields
    $doctor_type    = $_POST['doctor_type'];
    $unit_id        = !empty($_POST['unit_id']) ? (int)$_POST['unit_id'] : NULL;
    $visit_type     = isset($_POST['visit_type']) && $_POST['visit_type'] !== '' ? $_POST['visit_type'] : NULL;
    $chamber_id     = !empty($_POST['chamber_id']) ? (int)$_POST['chamber_id'] : NULL;
    $grade_id       = !empty($_POST['doctor_grade']) ? (int)$_POST['doctor_grade'] : NULL;
    $territory_id   = !empty($_POST['territory_id']) ? (int)$_POST['territory_id'] : NULL;
    $is_primary     = (int)$_POST['is_primary'];
    $room_number    = !empty($_POST['room_number']) ? (int)$_POST['room_number'] : NULL;

    $conn->begin_transaction();
    $success = false;

    try {
        // ==========================================================
        // STEP 1: Update doctors table
        // ==========================================================
        $old_doctor_data = $conn->query("SELECT * FROM doctors WHERE doctor_id = $doctor_id")->fetch_assoc();

        $sql_doctor = "UPDATE doctors SET 
            title_id=?, name=?, bmdc_number=?, email=?, date_of_birth=?, designation_id=?, designation_status=?, 
            institute_id=?, is_ex_institute=?, specialization_id=?, updated_at=NOW() 
            WHERE doctor_id = ?";
        
        $stmt_doctor = $conn->prepare($sql_doctor);
        $stmt_doctor->bind_param("isssssisiii", 
            $title_id, $name, $bmdc_number, $email, $date_of_birth, $designation_id, $designation_status, 
            $institute_id, $is_ex_institute, $specialization_id, $doctor_id);

        if (!$stmt_doctor->execute()) {
            throw new Exception("Doctor Update Error: " . $stmt_doctor->error);
        }
        $stmt_doctor->close();

        // Audit Log for doctors table
        $new_doctor_data = $conn->query("SELECT * FROM doctors WHERE doctor_id = $doctor_id")->fetch_assoc();
        log_audit($conn, $doctor_id, 'doctors', $doctor_id, $old_doctor_data, $new_doctor_data, 'UPDATE');
        
        // ==========================================================
        // STEP 2: Handle Doctor Degrees (DELETE then INSERT for update)
        // ==========================================================
        $conn->query("DELETE FROM doctors_degrees WHERE doctor_id = $doctor_id");

        $degree_ids = $_POST['degree_id'] ?? [];
        $detail_ids = $_POST['detail_id'] ?? [];

        if (!empty($degree_ids)) {
            $sql_degree_insert = "INSERT INTO doctors_degrees (doctor_id, degree_id, detail_id) VALUES (?, ?, ?)";
            $stmt_degree = $conn->prepare($sql_degree_insert);
            
            foreach ($degree_ids as $index => $d_id) {
                $deg_id = (int)$d_id;
                $det_id = !empty($detail_ids[$index]) ? (int)$detail_ids[$index] : NULL;
                
                if ($deg_id > 0) { // Only insert if a valid degree is selected
                    $stmt_degree->bind_param("iii", $doctor_id, $deg_id, $det_id);
                    if (!$stmt_degree->execute()) {
                        throw new Exception("Degree Insert Error: " . $stmt_degree->error);
                    }
                }
            }
            $stmt_degree->close();
        }

        // ==========================================================
        // STEP 3: Update Doctor Assignment
        // ==========================================================
        $old_assignment_data = $conn->query("SELECT * FROM doctor_assignments WHERE assignment_id = $assignment_id")->fetch_assoc();

        $sql_assignment = "UPDATE doctor_assignments SET
            chamber_id=?, grade_id=?, doctor_type=?, unit_id=?, room_number=?, 
            territory_id=?, visit_type=?, is_primary=?, assigned_by=?
            WHERE assignment_id = ?";
            
        $stmt_assignment = $conn->prepare($sql_assignment);
        $stmt_assignment->bind_param("isisisiiii",
            $chamber_id, $grade_id, $doctor_type, $unit_id, $room_number,
            $territory_id, $visit_type, $is_primary, $updated_by, $assignment_id);

        if (!$stmt_assignment->execute()) {
            throw new Exception("Assignment Update Error: " . $stmt_assignment->error);
        }
        $stmt_assignment->close();

        // Audit Log for doctor_assignments table
        $new_assignment_data = $conn->query("SELECT * FROM doctor_assignments WHERE assignment_id = $assignment_id")->fetch_assoc();
        log_audit($conn, $doctor_id, 'doctor_assignments', $assignment_id, $old_assignment_data, $new_assignment_data, 'UPDATE');
        
        // Final Success
        $conn->commit();
        $success = true;
        $msg = 'Doctor information & assignment updated successfully!';

    } catch (Exception $e) {
        $conn->rollback();
        $msg = "Transaction failed: " . $e->getMessage();
    }


    // ----------------------------------------------------
    // FINAL ALERT AND REDIRECT
    // ----------------------------------------------------
    final_alert:
    $icon = $success ? 'success' : 'error';
    $title = $success ? '✅ Success!' : '❌ Error!';
    // Successful update redirects back to the edit page
    $redirect_page = $success ? "doctor_edit.php?doctor_id={$doctor_id}&assignment_id={$assignment_id}" : '../my_doctors.php';
    
    // If it failed due to missing IDs, redirect to my_doctors.php
    if (!$doctor_id || !$assignment_id) {
        $redirect_page = '../my_doctors.php';
    }


    echo "
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
    Swal.fire({
        title: '{$title}',
        text: '{$msg}',
        icon: '{$icon}',
        confirmButtonText: 'OK'
    }).then(() => {
        window.location.href = '{$redirect_page}';
    });
    </script>";
}
// --------------------------------------------------------------------
function sanitize_input($conn, $data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}
?>
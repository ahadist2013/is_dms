<?php
// ===============================
// Doctor Data Save Script (Final + SweetAlert + BMDC/Email/DOB)
// ===============================

include '../../includes/header.php'; // $conn + session_start()

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Common (both new + existing)
    $created_by   = $_SESSION['user_id'];
    $doctor_id    = !empty($_POST['existing_doctor_id']) ? (int)$_POST['existing_doctor_id'] : null;
    $mobile_number= $_POST['mobile_number'];

    // Assignment fields (always)
    $doctor_type  = $_POST['doctor_type'];
    $unit_id      = !empty($_POST['unit_id']) ? (int)$_POST['unit_id'] : NULL;
    $visit_type   = isset($_POST['visit_type']) && $_POST['visit_type'] !== '' ? $_POST['visit_type'] : NULL;
    $chamber_id   = !empty($_POST['chamber_id']) ? (int)$_POST['chamber_id'] : NULL;
    $grade_id     = !empty($_POST['doctor_grade']) ? (int)$_POST['doctor_grade'] : NULL;
    $territory_id = !empty($_POST['territory_id']) ? (int)$_POST['territory_id'] : NULL;
    $is_primary   = 1;

    // ==========================================================
    // STEP 1: Insert into doctors ONLY when doctor is NEW
    // ==========================================================
    if (!$doctor_id) {

        // New-doctor-only fields
        $title_id           = (int)$_POST['title_id'];
        $name               = strtoupper(trim($_POST['name']));
        $designation_id     = (int)$_POST['designation'];
        $designation_status = $_POST['designation_status'];
        $institute_id       = (int)$_POST['institute_id'];
        $is_ex_institute    = isset($_POST['is_ex_institute']) ? 1 : 0;
        $specialization_id  = !empty($_POST['specialization_id']) ? (int)$_POST['specialization_id'] : NULL;

        // Optional new fields (BMDC/Email/DOB)
        // Frontend-এ তুমি শুধু 6-digit পাঠাচ্ছো (A- prefix UI-তে দেখাচ্ছো) — DB-তে 'A-' যোগ করে রাখি।
        $bmdc_digits = isset($_POST['bmdc_number']) ? trim($_POST['bmdc_number']) : '';
        $bmdc_number = ($bmdc_digits !== '') ? ('A-' . $bmdc_digits) : NULL;

        $email = isset($_POST['email']) && $_POST['email'] !== '' ? $_POST['email'] : NULL;
        $dob   = isset($_POST['dob'])   && $_POST['dob']   !== '' ? $_POST['dob']   : NULL;

        // Safety: duplicate mobile check (যদি ইউজার কৌতুকে নতুন হিসাবে সাবমিট করে ফেলে)
        $check = $conn->prepare("SELECT doctor_id FROM doctors WHERE mobile_number = ?");
        $check->bind_param("s", $mobile_number);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            // Already exists -> fetch doctor_id and treat as existing
            $check->bind_result($doctor_id);
            $check->fetch();
            $check->close();
        } else {
            $check->close();

            // INSERT new doctor (BMDC/Email/DOB সহ)
            $sql_doctor = "INSERT INTO doctors
                (mobile_number, bmdc_number, title_id, name, email, date_of_birth,
                 designation_id, designation_status, institute_id, is_ex_institute,
                 specialization_id, created_by, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

            $stmt_doctor = $conn->prepare($sql_doctor);
            if (!$stmt_doctor) {
                die("❌ MySQL prepare error (doctors): " . $conn->error);
            }

            // Types: s s i s s s i s i i i i  => "ssisssisiiii"
            $stmt_doctor->bind_param(
                "ssisssisiiii",
                $mobile_number,
                $bmdc_number,
                $title_id,
                $name,
                $email,
                $dob,
                $designation_id,
                $designation_status,
                $institute_id,
                $is_ex_institute,
                $specialization_id,
                $created_by
            );

            if ($stmt_doctor->execute()) {
                $doctor_id = $stmt_doctor->insert_id;

                // ========== STEP 1.1: Doctors Degrees (only for new) ==========
                if (!empty($_POST['degree_ids'])) {
                    $degree_ids = $_POST['degree_ids'];
                    $details    = isset($_POST['q_specializations']) ? $_POST['q_specializations'] : [];

                    $stmt_degree = $conn->prepare(
                        "INSERT INTO doctors_degrees (doctor_id, degree_id, detail_id) VALUES (?, ?, ?)"
                    );
                    $stmt_degree->bind_param("iii", $doctor_id, $degree_id, $detail_id);

                    foreach ($degree_ids as $idx => $degree_id) {
                        $degree_id  = (int)$degree_id;
                        $detail_id  = !empty($details[$idx]) ? (int)$details[$idx] : NULL;
                        $stmt_degree->execute();
                    }
                    $stmt_degree->close();
                }

                $stmt_doctor->close();

            } else {
                // INSERT failed
                echo "
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                Swal.fire({ title:'❌ Error', text: ".json_encode($stmt_doctor->error).", icon:'error' })
                      .then(() => { window.history.back(); });
                </script>";
                exit;
            }
        }
    }

      // ===== Correct assignment insert (with Room Number) =====
        if ($doctor_id) {
            // ✅ Room Number সহ insert query
            $stmt_assignment = $conn->prepare("INSERT INTO doctor_assignments
                (doctor_id, chamber_id, grade_id, doctor_type, unit_id, room_number, territory_id, visit_type,
                is_primary, assigned_by, assigned_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            
            if (!$stmt_assignment) {
                die("❌ MySQL prepare error (doctor_assignments): " . $conn->error);
            }

            // ফর্ম থেকে room_number নেওয়া (null হলে null রাখবে)
            $room_number = isset($_POST['room_number']) ? trim($_POST['room_number']) : null;

            // Bind types ব্যাখ্যা:
            // i = int, s = string
            // doctor_id (i)
            // chamber_id (i)
            // grade_id (i)
            // doctor_type (s)
            // unit_id (i)
            // room_number (s)
            // territory_id (i)
            // visit_type (s)
            // is_primary (i)
            // assigned_by (i)
            // তাই bind_param string হবে: "iiisi sisii" (no space) → "iiisi sisii" → "iiisisisii"

            $stmt_assignment->bind_param(
                "iiisisisii",
                $doctor_id,
                $chamber_id,
                $grade_id,
                $doctor_type,
                $unit_id,
                $room_number,   // ✅ নতুন ফিল্ড
                $territory_id,
                $visit_type,
                $is_primary,
                $created_by
            );
        if ($stmt_assignment->execute()) {
            echo "
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            <script>
            Swal.fire({
                title: '✅ Success!',
                text: 'Doctor information & assignment saved successfully!',
                icon: 'success',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = 'create.php';
            });
            </script>";
        } else {
            echo "
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            <script>
            Swal.fire({
                title: '❌ Error!',
                text: ".json_encode($stmt_assignment->error).",
                icon: 'error',
                confirmButtonText: 'Close'
            }).then(() => { window.history.back(); });
            </script>";
        }

        $stmt_assignment->close();
    } else {
        echo "
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
        Swal.fire({
            title:'⚠️ Error',
            text:'No valid doctor ID found. Cannot assign.',
            icon:'warning'
        }).then(() => { window.history.back(); });
        </script>";
    }
}
?>

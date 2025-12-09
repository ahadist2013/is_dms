<?php
if (session_status()===PHP_SESSION_NONE) session_start();
require_once __DIR__.'/../config/db_connection.php';
header('Content-Type: application/json');

$doctor_id = (int)($_POST['doctor_id'] ?? 0);
if ($doctor_id<=0) { echo json_encode(['success'=>false,'msg'=>'Invalid doctor ID']); exit; }

$role = $_SESSION['role'] ?? 'User';
$nowUser = (int)($_SESSION['user_id'] ?? 0);

// ===== AUDIT: take BEFORE snapshot =====
$beforeDoctor  = null;
$beforeAssign  = null;
$beforeDegrees = [];

// 1) current doctor row
$qd = $conn->prepare("SELECT * FROM doctors WHERE doctor_id=?");
$qd->bind_param("i", $doctor_id);
$qd->execute();
$beforeDoctor = $qd->get_result()->fetch_assoc();
$qd->close();

// 2) latest assignment row for this doctor (same as your update logic)
$qa = $conn->prepare("SELECT * FROM doctor_assignments WHERE doctor_id=? ORDER BY assigned_at DESC LIMIT 1");
$qa->bind_param("i", $doctor_id);
$qa->execute();
$beforeAssign = $qa->get_result()->fetch_assoc();
$qa->close();

// 3) all degrees in order
$qdeg = $conn->prepare("SELECT * FROM doctors_degrees WHERE doctor_id=? ORDER BY doctor_degree_id ASC");
$qdeg->bind_param("i", $doctor_id);
$qdeg->execute();
$rdeg = $qdeg->get_result();
while($row = $rdeg->fetch_assoc()){
    $beforeDegrees[] = $row;
}
$qdeg->close();

$beforePayload = json_encode([
    'doctor'     => $beforeDoctor,
    'assignment' => $beforeAssign,
    'degrees'    => $beforeDegrees,
]);

// collect inputs
$title_id         = (int)($_POST['title_id'] ?? 0);
$name             = strtoupper(trim($_POST['name'] ?? ''));
$email            = trim($_POST['email'] ?? '');
$date_of_birth    = ($_POST['date_of_birth'] ?? null);
$designation_id   = (int)($_POST['designation'] ?? 0);
$designation_stat = ($_POST['designation_status'] ?? 'Current');
$specialization_id= (int)($_POST['specialization_id'] ?? 0);
$is_ex_institute  = (int)($_POST['is_ex_institute'] ?? 0);
$institute_id     = (int)($_POST['institute_id'] ?? 0);
$bmdc_number_core = trim($_POST['bmdc_number'] ?? '');
$bmdc_number      = $bmdc_number_core ? 'A-'.$bmdc_number_core : null;

// assignment
$chamber_id  = (int)($_POST['chamber_id'] ?? 0);
$grade_id    = (int)($_POST['doctor_grade'] ?? 0);
$doctor_type = ($_POST['doctor_type'] ?? '');
$unit_id     = (int)($_POST['unit_id'] ?? 0);
$room_number = trim($_POST['room_number'] ?? '');
$territory_id= (int)($_POST['territory_id'] ?? 0);
$visit_type  = trim($_POST['visit_type'] ?? '');

// mobile—only admin can change
$mobile_number = null;
if ($role==='Admin') {
  $mobile_number = trim($_POST['mobile_number'] ?? '');
  if (!preg_match('/^01\d{9}$/', $mobile_number)) {
    echo json_encode(['success'=>false,'msg'=>'Invalid mobile number']); exit;
  }
}

// validations
if ($title_id<=0 || $name==='' || $designation_id<=0 || $specialization_id<=0 || $chamber_id<=0 || $grade_id<=0 || $territory_id<=0) {
  echo json_encode(['success'=>false,'msg'=>'Required fields missing']); exit;
}
if ($doctor_type==='in_house'){
  if ($unit_id<=0 || $room_number===''){ echo json_encode(['success'=>false,'msg'=>'Unit & Room required for In-House']); exit; }
} elseif ($doctor_type==='outside'){
  if ($visit_type===''){ echo json_encode(['success'=>false,'msg'=>'Visit Type required for Outside']); exit; }
} else {
  echo json_encode(['success'=>false,'msg'=>'Doctor Type required']); exit;
}

$conn->begin_transaction();
try {
    // update doctors
      $sql = "UPDATE doctors 
            SET title_id=?, name=?, email=?, date_of_birth=?, designation_id=?, 
                designation_status=?, specialization_id=?, bmdc_number=?, 
                is_ex_institute=?, institute_id=?, updated_at=NOW()"
           . ($mobile_number!==null ? ", mobile_number=? " : " ")
           . "WHERE doctor_id=?";

    
      if ($mobile_number!==null){
        // Admin mobile পরিবর্তন করলে → 11টা parameter
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
          "isssisisissi",
          $title_id,          // i
          $name,              // s
          $email,             // s
          $date_of_birth,     // s
          $designation_id,    // i
          $designation_stat,  // s
          $specialization_id, // i
          $bmdc_number,       // s
          $is_ex_institute,   // i
          $institute_id,
          $mobile_number,     // s
          $doctor_id          // i
        );
      } else {
        // Admin না হলে / mobile না বদলালে → mobile_number untouched
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
          "isssisisiii",
          $title_id,          // i
          $name,              // s
          $email,             // s
          $date_of_birth,     // s
          $designation_id,    // i
          $designation_stat,  // s
          $specialization_id, // i
          $bmdc_number,       // s
          $is_ex_institute,   // i
          $institute_id,
          $doctor_id          // i
        );
      }
      $stmt->execute();
      $stmt->close();


  // latest assignment row → update
    $get = $conn->prepare("SELECT assignment_id FROM doctor_assignments WHERE doctor_id=? ORDER BY assigned_at DESC LIMIT 1");
      $get->bind_param("i",$doctor_id); 
      $get->execute();
      $rs  = $get->get_result(); 
      $row = $rs->fetch_assoc(); 
      $get->close();
    
      if ($row) {
            $aid = (int)$row['assignment_id'];
        
            // 1️⃣ পুরনো assigned_by এবং assigned_at নাও (unchanged রাখতে)
            $getOld = $conn->prepare("SELECT assigned_by, assigned_at FROM doctor_assignments WHERE assignment_id=?");
            $getOld->bind_param("i", $aid);
            $getOld->execute();
            $old = $getOld->get_result()->fetch_assoc();
            $getOld->close();
        
            $old_assigned_by = $old['assigned_by'];
            $old_assigned_at = $old['assigned_at'];
        
            // 2️⃣ এখন UPDATE করো শুধুমাত্র editable fields + updated_by + updated_at
            $stmt2 = $conn->prepare("
                UPDATE doctor_assignments
                SET chamber_id=?, grade_id=?, doctor_type=?, unit_id=?, room_number=?, 
                    territory_id=?, visit_type=?, 
                    assigned_by=?, assigned_at=?, 
                    updated_by=?, updated_at=NOW()
                WHERE assignment_id=?
            ");
        
            $stmt2->bind_param(
                "iisiissssii",
                $chamber_id,
                $grade_id,
                $doctor_type,
                $unit_id,
                $room_number,
                $territory_id,
                $visit_type,
                $old_assigned_by,   // assigned_by unchanged
                $old_assigned_at,   // assigned_at unchanged
                $nowUser,           // updated_by
                $aid                // assignment_id
            );
        
            $stmt2->execute();
            $stmt2->close();
        }

        else {
        // যদি কোনো assignment না থাকে—নতুন ইনসার্ট
    
        if ($doctor_type === 'in_house') {
            $visit_type = '';
        } elseif ($doctor_type === 'outside') {
            $unit_id     = 0;
            $room_number = '';
        }
    
        $stmt2=$conn->prepare("INSERT INTO doctor_assignments
          (doctor_id,chamber_id,grade_id,doctor_type,unit_id,room_number,territory_id,visit_type,is_primary,assigned_by) 
          VALUES(?,?,?,?,?,?,?,?,1,?)");
    
        // doctor_id(i), chamber_id(i), grade_id(i), doctor_type(s),
        // unit_id(i), room_number(s), territory_id(i), visit_type(s), assigned_by(i)
        $stmt2->bind_param(
          "iiisisisi",
          $doctor_id,
          $chamber_id,
          $grade_id,
          $doctor_type,
          $unit_id,
          $room_number,
          $territory_id,
          $visit_type,
          $nowUser
        );
    
        $stmt2->execute(); 
        $stmt2->close();
      }

  // degrees—refresh
  $conn->query("DELETE FROM doctors_degrees WHERE doctor_id=".$doctor_id);
  if (!empty($_POST['degree_ids'])) {
    $ins=$conn->prepare("INSERT INTO doctors_degrees(doctor_id,degree_id,detail_id) VALUES(?,?,?)");
    foreach($_POST['degree_ids'] as $k=>$deg_id){
      $deg_id = (int)$deg_id; if($deg_id<=0) continue;
      $detail_id = isset($_POST['q_specializations'][$k]) && $_POST['q_specializations'][$k]!=='' ? (int)$_POST['q_specializations'][$k] : null;
      // null হলে set null
      if ($detail_id) { $ins->bind_param("iii", $doctor_id,$deg_id,$detail_id); }
      else { $zero = null; $ins->bind_param("iii",$doctor_id,$deg_id,$zero); }
      $ins->execute();
    }
    $ins->close();
  }
  // ===== AUDIT: take AFTER snapshot & INSERT LOG =====
  $afterDoctor  = null;
  $afterAssign  = null;
  $afterDegrees = [];

  // 1) doctor after update
  $qd2 = $conn->prepare("SELECT * FROM doctors WHERE doctor_id=?");
  $qd2->bind_param("i", $doctor_id);
  $qd2->execute();
  $afterDoctor = $qd2->get_result()->fetch_assoc();
  $qd2->close();

  // 2) latest assignment after update
  $qa2 = $conn->prepare("SELECT * FROM doctor_assignments WHERE doctor_id=? ORDER BY assigned_at DESC LIMIT 1");
  $qa2->bind_param("i", $doctor_id);
  $qa2->execute();
  $afterAssign = $qa2->get_result()->fetch_assoc();
  $qa2->close();

  // 3) degrees after update
  $qdeg2 = $conn->prepare("SELECT * FROM doctors_degrees WHERE doctor_id=? ORDER BY doctor_degree_id ASC");
  $qdeg2->bind_param("i", $doctor_id);
  $qdeg2->execute();
  $rdeg2 = $qdeg2->get_result();
  while($row = $rdeg2->fetch_assoc()){
      $afterDegrees[] = $row;
  }
  $qdeg2->close();

  $afterPayload = json_encode([
      'doctor'     => $afterDoctor,
      'assignment' => $afterAssign,
      'degrees'    => $afterDegrees,
  ]);

  // 4) finally insert into audit table
  $logStmt = $conn->prepare("
      INSERT INTO doctor_edit_logs
          (doctor_id, changed_by, changed_at, before_data, after_data)
      VALUES
          (?, ?, NOW(), ?, ?)
  ");
  $logStmt->bind_param("iiss", $doctor_id, $nowUser, $beforePayload, $afterPayload);
  $logStmt->execute();
  $logStmt->close();

  // ===== END AUDIT =====
  
  $conn->commit();
  echo json_encode(['success'=>true,'msg'=>'Doctor information updated.']);

} catch(Exception $e){
  $conn->rollback();
  echo json_encode(['success'=>false,'msg'=>'Update failed: '.$e->getMessage()]);
}

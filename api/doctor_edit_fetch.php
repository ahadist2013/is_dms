<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status()===PHP_SESSION_NONE) session_start();
require_once __DIR__.'/../config/db_connection.php';
header('Content-Type: application/json');

$doctor_id = (int)($_GET['doctor_id'] ?? 0);
if ($doctor_id<=0){ echo json_encode(['success'=>false,'msg'=>'Invalid doctor ID']); exit; }

// basic + latest assignment (assigned_at DESC)
$sql = "
SELECT 
  d.doctor_id, d.mobile_number, d.bmdc_number, d.title_id, d.name, d.email,
  d.date_of_birth, d.designation_id, d.designation_status, d.institute_id,
  d.is_ex_institute, d.specialization_id, d.created_by,
  mt.title_name, mi.institute_name,
  da.assignment_id, da.chamber_id, mc.chamber_name,
  da.grade_id, gd.grade_code AS doctor_grade,
  da.unit_id, da.room_number, da.territory_id, t.territory_name,
  z.zone_id, z.zone_name, da.visit_type, da.doctor_type
FROM doctors d
LEFT JOIN master_titles mt ON d.title_id=mt.title_id
LEFT JOIN master_institutes mi ON d.institute_id=mi.institute_id
LEFT JOIN doctor_assignments da 
  ON da.doctor_id=d.doctor_id
  AND da.assigned_at = (
      SELECT MAX(da2.assigned_at) FROM doctor_assignments da2 WHERE da2.doctor_id=d.doctor_id
  )
LEFT JOIN master_chambers mc ON da.chamber_id=mc.chamber_id
LEFT JOIN master_doctor_grades gd ON da.grade_id=gd.grade_id
LEFT JOIN territories t ON da.territory_id=t.territory_id
LEFT JOIN zones z ON t.zone_id=z.zone_id
WHERE d.doctor_id=?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$doctor_id);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$doctor){ echo json_encode(['success'=>false,'msg'=>'Doctor not found']); exit; }

// degrees (detail_id লাগবে)
$degrees = [];
$q = "SELECT dd.degree_id, mdd.detail_id, mdd.detail_value 
      FROM doctors_degrees dd
      LEFT JOIN master_degree_details mdd ON dd.detail_id=mdd.detail_id
      WHERE dd.doctor_id=?
      ORDER BY dd.doctor_degree_id ASC";
$stmt2=$conn->prepare($q);
$stmt2->bind_param("i",$doctor_id);
$stmt2->execute();
$r=$stmt2->get_result();
while($row=$r->fetch_assoc()){ $degrees[]=$row; }
$stmt2->close();

// latest assignment (optional—already এসেছে, তবে আলাদা অবজেক্টও পাঠাই)
$assignment=null;
$q2="SELECT da.*, t.territory_name, z.zone_name 
     FROM doctor_assignments da 
     LEFT JOIN territories t ON da.territory_id=t.territory_id
     LEFT JOIN zones z ON t.zone_id=z.zone_id
     WHERE da.doctor_id=? ORDER BY da.assigned_at DESC LIMIT 1";
$stmt3=$conn->prepare($q2);
$stmt3->bind_param("i",$doctor_id);
$stmt3->execute();
$assignment=$stmt3->get_result()->fetch_assoc();
$stmt3->close();

// degree master (dropdown)
$d_master=[];
$res=$conn->query("SELECT degree_id AS id, degree_name AS name FROM master_degrees ORDER BY degree_name ASC");
while($row=$res->fetch_assoc()){ $d_master[]=$row; }

$assignment['zone_id'] = $doctor['zone_id'];
$assignment['territory_id'] = $doctor['territory_id'];
echo json_encode([
  'success'=>true,
  'doctor'=>$doctor,
  'degrees'=>$degrees,
  'assignment'=>$assignment,
  'degree_master'=>$d_master
]);

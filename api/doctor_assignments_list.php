<?php
// api/doctor_assignments_list.php
if (session_status()===PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db_connection.php';
header('Content-Type: application/json');

// Territories by Zone (for edit form dependent dropdown)
if (isset($_GET['load']) && $_GET['load']==='territories') {
  $zone_id = intval($_GET['zone_id'] ?? 0);
  $out = [];
  if ($zone_id>0){
    $q = $conn->prepare("SELECT territory_id, territory_name FROM territories WHERE zone_id=? ORDER BY territory_name");
    $q->bind_param("i",$zone_id);
    $q->execute();
    $r=$q->get_result();
    while($row=$r->fetch_assoc()){
      $out[] = ['territory_id'=>$row['territory_id'], 'territory_name'=>$row['territory_name']];
    }
  }
  echo json_encode(['success'=>true,'data'=>$out]); exit;
}

$doctor_id = intval($_GET['doctor_id'] ?? 0);
if ($doctor_id<=0) { echo json_encode(['success'=>false,'data'=>[]]); exit; }

$sql = "
  SELECT 
    da.assignment_id,
    da.doctor_type,
    da.assigned_at,
    u.name AS assigned_by_name,
    t.territory_name,
    z.zone_name,
    mc.chamber_name
  FROM doctor_assignments da
  LEFT JOIN users u ON da.assigned_by = u.user_id
  LEFT JOIN territories t ON da.territory_id = t.territory_id
  LEFT JOIN zones z ON t.zone_id = z.zone_id
  LEFT JOIN master_chambers mc ON da.chamber_id = mc.chamber_id
  WHERE da.doctor_id = ?
  ORDER BY da.assigned_at DESC, da.assignment_id DESC
";
$st = $conn->prepare($sql);
$st->bind_param("i",$doctor_id);
$st->execute();
$r = $st->get_result();

$data=[];
while($row=$r->fetch_assoc()){
  $data[] = [
    'assignment_id'   => $row['assignment_id'],
    'doctor_type'     => $row['doctor_type'],
    'zone_name'       => $row['zone_name'] ?? '',
    'territory_name'  => $row['territory_name'] ?? '',
    'chamber_name'    => $row['chamber_name'] ?? '',
    'assigned_by_name'=> $row['assigned_by_name'] ?? '',
    'assigned_at'     => $row['assigned_at'] ?? '',
  ];
}
echo json_encode(['success'=>true,'data'=>$data]);

<?php
// api/geo_options.php
if (session_status()===PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db_connection.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'divisions') {
  // table name: divisions (change if yours differs)
  $sql = "SELECT division_id AS id, division_name AS name FROM divisions ORDER BY division_name ASC";
  $res = $conn->query($sql);
  $data = [];
  while($row = $res->fetch_assoc()){
    $data[] = $row;
  }
  echo json_encode(['success'=>true, 'data'=>$data]); exit;
}

if ($action === 'districts') {
  $division_id = intval($_GET['division_id'] ?? 0);
  if ($division_id<=0) { echo json_encode(['success'=>true, 'data'=>[]]); exit; }
  // districts must have division_id column
  $sql = "SELECT district_id AS id, district_name AS name FROM districts WHERE division_id = $division_id ORDER BY district_name ASC";
  $res = $conn->query($sql);
  $data = [];
  while($row = $res->fetch_assoc()){
    $data[] = $row;
  }
  echo json_encode(['success'=>true, 'data'=>$data]); exit;
}

if ($action === 'upazilas') {
  $district_id = intval($_GET['district_id'] ?? 0);
  if ($district_id<=0) { echo json_encode(['success'=>true, 'data'=>[]]); exit; }
  $sql = "SELECT upazila_id AS id, upazila_name AS name FROM upazilas WHERE district_id = $district_id ORDER BY upazila_name ASC";
  $res = $conn->query($sql);
  $data = [];
  while($row = $res->fetch_assoc()){
    $data[] = $row;
  }
  echo json_encode(['success'=>true, 'data'=>$data]); exit;
}

echo json_encode(['success'=>false, 'data'=>[], 'msg'=>'Unknown action']);

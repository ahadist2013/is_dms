<?php
if (session_status()===PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db_connection.php';
header('Content-Type: application/json');

$sql = "SELECT type_id AS id, type_name AS name FROM master_institute_types ORDER BY type_name ASC";
$res = $conn->query($sql);

$data = [];
if ($res) {
  while($row = $res->fetch_assoc()) {
    $data[] = $row;
  }
}

echo json_encode(['success'=>true,'data'=>$data]);

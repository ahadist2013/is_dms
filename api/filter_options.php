<?php
if (session_status()===PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db_connection.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? '';

try {
  if ($action === 'bdd_zones') {
    $sql = "SELECT bdd_zone_id, bdd_zone_name FROM master_bdd_zones ORDER BY bdd_zone_name ASC";
    $r = $conn->query($sql);
    $out = [];
    while($row = $r->fetch_assoc()){
      $out[] = [
        'bdd_zone_id' => (int)$row['bdd_zone_id'],
        'bdd_zone_name' => $row['bdd_zone_name']
      ];
    }
    echo json_encode(['success'=>true,'data'=>$out], JSON_UNESCAPED_UNICODE); exit;
  }

  if ($action === 'regions') {
    $bdd_zone_id = intval($_POST['bdd_zone_id'] ?? 0);
    $out = [];
    if ($bdd_zone_id>0){
      $stmt = $conn->prepare("SELECT region_id, region_name FROM regions WHERE bdd_zone_id=? ORDER BY region_name ASC");
      $stmt->bind_param("i", $bdd_zone_id);
      $stmt->execute();
      $res = $stmt->get_result();
      while($row = $res->fetch_assoc()){
        $out[]=['region_id'=>(int)$row['region_id'],'region_name'=>$row['region_name']];
      }
      $stmt->close();
    }
    echo json_encode(['success'=>true,'data'=>$out], JSON_UNESCAPED_UNICODE); exit;
  }

  if ($action === 'units') {
    $region_id = intval($_POST['region_id'] ?? 0);
    $out = [];
    if ($region_id>0){
      $stmt = $conn->prepare("SELECT unit_id, unit_name FROM master_units WHERE region_id=? ORDER BY unit_name ASC");
      $stmt->bind_param("i", $region_id);
      $stmt->execute();
      $res = $stmt->get_result();
      while($row = $res->fetch_assoc()){
        $out[]=['unit_id'=>(int)$row['unit_id'],'unit_name'=>$row['unit_name']];
      }
      $stmt->close();
    }
    echo json_encode(['success'=>true,'data'=>$out], JSON_UNESCAPED_UNICODE); exit;
  }

  if ($action === 'zones') {
    // ধরে নিচ্ছি zones.region_id আছে
    $region_id = intval($_POST['region_id'] ?? 0);
    $out = [];
    if ($region_id>0){
      $stmt = $conn->prepare("SELECT zone_id, zone_name FROM zones WHERE region_id=? ORDER BY zone_name ASC");
      $stmt->bind_param("i", $region_id);
      $stmt->execute();
      $res = $stmt->get_result();
      while($row = $res->fetch_assoc()){
        $out[]=['zone_id'=>(int)$row['zone_id'],'zone_name'=>$row['zone_name']];
      }
      $stmt->close();
    }
    echo json_encode(['success'=>true,'data'=>$out], JSON_UNESCAPED_UNICODE); exit;
  }

  if ($action === 'territories') {
    $zone_id = intval($_POST['zone_id'] ?? 0);
    $out = [];
    if ($zone_id>0){
      $stmt = $conn->prepare("SELECT territory_id, territory_name FROM territories WHERE zone_id=? ORDER BY territory_name ASC");
      $stmt->bind_param("i", $zone_id);
      $stmt->execute();
      $res = $stmt->get_result();
      while($row = $res->fetch_assoc()){
        $out[]=['territory_id'=>(int)$row['territory_id'],'territory_name'=>$row['territory_name']];
      }
      $stmt->close();
    }
    echo json_encode(['success'=>true,'data'=>$out], JSON_UNESCAPED_UNICODE); exit;
  }

  echo json_encode(['success'=>false,'msg'=>'Unknown action']);
} catch(Exception $e){
  echo json_encode(['success'=>false,'msg'=>$e->getMessage()]);
}

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (session_status()===PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db_connection.php';
header('Content-Type: application/json; charset=utf-8');

$page     = max(1, intval($_GET['page'] ?? 1));
$per_page = max(1, min(100, intval($_GET['per_page'] ?? 100)));

$bdd_zone_id  = intval($_GET['bdd_zone_id'] ?? 0);
$region_id    = intval($_GET['region_id'] ?? 0);
$unit_id      = intval($_GET['unit_id'] ?? 0);
$zone_id      = intval($_GET['zone_id'] ?? 0);
$territory_id = intval($_GET['territory_id'] ?? 0);
$doctor_type  = trim($_GET['doctor_type'] ?? '');
$q            = trim($_GET['q'] ?? '');

$offset = ($page - 1) * $per_page;

// ===== Base FROM + JOIN =====
// ধরা হলো: regions.bdd_zone_id, master_units.region_id, zones.region_id, territories.zone_id
$params = [];
$types = "";

$base_sql = "
  FROM doctor_assignments da
  JOIN doctors d ON da.doctor_id = d.doctor_id
  LEFT JOIN master_titles mt ON d.title_id = mt.title_id
  LEFT JOIN master_chambers mc ON da.chamber_id = mc.chamber_id
  LEFT JOIN territories tr ON da.territory_id = tr.territory_id
  LEFT JOIN zones z ON tr.zone_id = z.zone_id
  LEFT JOIN regions r ON z.region_id = r.region_id
  LEFT JOIN master_bdd_zones bz ON r.bdd_zone_id = bz.bdd_zone_id
  LEFT JOIN master_units mu ON da.unit_id = mu.unit_id
  WHERE 1=1
";
// ================== APPLY FILTERS ==================

// 🔹 Master BDD Zone filter
if (!empty($_GET['bdd_zone_id']) && intval($_GET['bdd_zone_id']) > 0) {
    $base_sql .= " AND r.bdd_zone_id = ? ";
    $params[] = intval($_GET['bdd_zone_id']);
    $types .= "i";
}

// 🔹 Region filter
if (!empty($_GET['region_id']) && intval($_GET['region_id']) > 0) {
    $base_sql .= " AND z.region_id = ? ";
    $params[] = intval($_GET['region_id']);
    $types .= "i";
}

// 🔹 Unit filter
if (!empty($_GET['unit_id']) && intval($_GET['unit_id']) > 0) {
    $base_sql .= " AND da.unit_id = ? ";
    $params[] = intval($_GET['unit_id']);
    $types .= "i";
}

// 🔹 Zone filter
if (!empty($_GET['zone_id']) && intval($_GET['zone_id']) > 0) {
    $base_sql .= " AND tr.zone_id = ? ";
    $params[] = intval($_GET['zone_id']);
    $types .= "i";
}

// 🔹 Territory filter
if (!empty($_GET['territory_id']) && intval($_GET['territory_id']) > 0) {
    $base_sql .= " AND da.territory_id = ? ";
    $params[] = intval($_GET['territory_id']);
    $types .= "i";
}

// 🔹 Doctor type filter
if (!empty($_GET['doctor_type']) && $_GET['doctor_type'] != 'all') {
    $base_sql .= " AND da.doctor_type = ? ";
    $params[] = $_GET['doctor_type'];
    $types .= "s";
}

// 🔹 Search filter (Name/Mobile/Chamber)
if (!empty($_GET['q'])) {
    $q = "%" . trim($_GET['q']) . "%";
    $base_sql .= " AND (
        d.name LIKE ? OR
        d.mobile_number LIKE ? OR
        mc.chamber_name LIKE ?
    )";
    $params[] = $q;
    $params[] = $q;
    $params[] = $q;
    $types .= "sss";
}

$params = [];
$types  = "";

// Progressive filters
if ($bdd_zone_id > 0) { $base_sql .= " AND r.bdd_zone_id = ? "; $params[] = $bdd_zone_id; $types .= "i"; }
if ($region_id > 0)   { $base_sql .= " AND r.region_id = ? ";    $params[] = $region_id;   $types .= "i"; }
if ($unit_id > 0)     { $base_sql .= " AND da.unit_id = ? ";     $params[] = $unit_id;     $types .= "i"; }
if ($zone_id > 0)     { $base_sql .= " AND z.zone_id = ? ";      $params[] = $zone_id;     $types .= "i"; }
if ($territory_id> 0) { $base_sql .= " AND tr.territory_id = ? ";$params[] = $territory_id; $types .= "i"; }
if ($doctor_type !== '') { $base_sql .= " AND da.doctor_type = ? "; $params[] = $doctor_type; $types .= "s"; }

if ($q !== '') {
  $base_sql .= " AND ( d.name LIKE ? OR d.mobile_number LIKE ? OR mc.chamber_name LIKE ? ) ";
  $w = "%{$q}%"; $params[]=$w; $params[]=$w; $params[]=$w; $types.="sss";
}

// ===== Count =====
$sql_count = "SELECT COUNT(*) AS cnt " . $base_sql;
$stmt = $conn->prepare($sql_count);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

if (!$stmt) {
    error_log("❌ SQL Prepare failed for COUNT: " . $conn->error);
}

$stmt->execute();
$res = $stmt->get_result();
$total = ($res && $res->num_rows) ? intval($res->fetch_assoc()['cnt']) : 0;
$stmt->close();


// ===== Data =====
// Degrees: doctor-wise ordered list
$degree_expr = "
  ( SELECT GROUP_CONCAT(
        CONCAT(md.degree_name, IF(mdd.detail_value IS NOT NULL AND mdd.detail_value<>'', CONCAT(' (', mdd.detail_value, ')'), ''))
        ORDER BY dd.doctor_degree_id ASC
        SEPARATOR ', '
    )
    FROM doctors_degrees dd
    JOIN master_degrees md ON dd.degree_id = md.degree_id
    LEFT JOIN master_degree_details mdd ON dd.detail_id = mdd.detail_id
    WHERE dd.doctor_id = d.doctor_id
  ) AS degrees
";

// ===== Data =====

// ===== Data Fetch =====
$sql_data = "
  SELECT 
    d.doctor_id,
    CONCAT(mt.title_name, ' ', d.name) AS full_name,
    d.mobile_number,
    mc.chamber_name,
    da.assignment_id
  " . $base_sql . "
  ORDER BY d.name ASC
  LIMIT ?, ?
";

$params2 = $params;
$types2 = $types . "ii";
$params2[] = $offset;
$params2[] = $per_page;

$stmt = $conn->prepare($sql_data);

if (!empty($params2)) {
    $stmt->bind_param($types2, ...$params2);
}

if (!$stmt) {
    error_log("❌ SQL Prepare failed for DATA: " . $conn->error);
}

$stmt->execute();



$rr = $stmt->get_result();

$data = [];
while($row = $rr->fetch_assoc()){
  $data[] = [
    'doctor_id'     => (int)$row['doctor_id'],
    'full_name'     => $row['full_name'] ?? '',
    'mobile_number' => $row['mobile_number'] ?? '',
    'chamber_name'  => $row['chamber_name'] ?? '',
  ];
}
$stmt->close();

echo json_encode([
  'success'=>true,
  'data'=>$data,
  'page'=>$page,
  'per_page'=>$per_page,
  'total'=>$total
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

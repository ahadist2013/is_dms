<?php
// api/chambers_list.php
if (session_status()===PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db_connection.php';
header('Content-Type: application/json');

// Inputs
$page       = max(1, intval($_GET['page'] ?? 1));
$per_page   = max(1, min(100, intval($_GET['per_page'] ?? 100)));
$q          = trim($_GET['q'] ?? '');
$division_id= intval($_GET['division_id'] ?? 0);
$district_id= intval($_GET['district_id'] ?? 0);
$upazila_id = intval($_GET['upazila_id'] ?? 0);

// Base SQL
$base_sql = "
  FROM master_chambers c
  LEFT JOIN divisions dv ON c.division_id = dv.division_id
  LEFT JOIN districts d  ON c.district_id = d.district_id
  LEFT JOIN upazilas u   ON c.upazila_id = u.upazila_id
  WHERE 1=1
";

$params = [];
$types  = "";

// Search filter
if ($q !== '') {
  $base_sql .= " AND c.chamber_name LIKE ? ";
  $params[] = "%{$q}%";
  $types   .= "s";
}

// Division filter
if ($division_id > 0) {
  $base_sql .= " AND c.division_id = ? ";
  $params[] = $division_id;
  $types   .= "i";
}

// District filter
if ($district_id > 0) {
  $base_sql .= " AND c.district_id = ? ";
  $params[] = $district_id;
  $types   .= "i";
}

// Upazila filter
if ($upazila_id > 0) {
  $base_sql .= " AND c.upazila_id = ? ";
  $params[] = $upazila_id;
  $types   .= "i";
}

// Count total
$sql_count = "SELECT COUNT(*) AS cnt " . $base_sql;
$stmt = $conn->prepare($sql_count);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$total = ($res && $res->num_rows) ? intval($res->fetch_assoc()['cnt']) : 0;
$stmt->close();

// Data query
$offset = ($page - 1) * $per_page;
$sql_data = "
  SELECT
    c.chamber_id,
    c.chamber_name,
    c.division_id,
    c.district_id,
    c.upazila_id,
    dv.division_name,
    d.district_name,
    u.upazila_name
  " . $base_sql . "
  ORDER BY c.chamber_name ASC
  LIMIT ?, ?
";
$params2 = $params;
$types2  = $types . "ii";
$params2[] = $offset;
$params2[] = $per_page;

$stmt = $conn->prepare($sql_data);
$stmt->bind_param($types2, ...$params2);
$stmt->execute();
$r = $stmt->get_result();

$data = [];
while ($row = $r->fetch_assoc()) {
  $data[] = [
    'chamber_id'     => $row['chamber_id'],
    'chamber_name'   => $row['chamber_name'],
    'division_id'    => $row['division_id'],
    'district_id'    => $row['district_id'],
    'upazila_id'     => $row['upazila_id'],
    'division_name'  => $row['division_name'] ?? '',
    'district_name'  => $row['district_name'] ?? '',
    'upazila_name'   => $row['upazila_name'] ?? ''
  ];
}
$stmt->close();

echo json_encode([
  'success'  => true,
  'data'     => $data,
  'page'     => $page,
  'per_page' => $per_page,
  'total'    => $total
]);

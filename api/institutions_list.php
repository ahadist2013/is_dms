<?php
if (session_status()===PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db_connection.php';
header('Content-Type: application/json');

// 🧩 Pagination & Filters
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = max(1, min(100, intval($_GET['per_page'] ?? 100)));
$q = trim($_GET['q'] ?? '');
$division_id = intval($_GET['division_id'] ?? 0);
$district_id = intval($_GET['district_id'] ?? 0);
$type_id = intval($_GET['type'] ?? 0); // renamed for type_id filter

// 🧩 Base SQL with correct joins
$base_sql = "
  FROM master_institutes i
  LEFT JOIN divisions dv ON i.division_id = dv.division_id
  LEFT JOIN districts d ON i.district_id = d.district_id
  LEFT JOIN master_institute_types t ON i.type_id = t.type_id
  WHERE 1=1
";

$params = [];
$types  = "";

// 🔍 Search by institute name
if ($q !== '') {
  $base_sql .= " AND i.institute_name LIKE ? ";
  $params[] = "%{$q}%";
  $types   .= "s";
}

// 🔹 Division filter
if ($division_id > 0) {
  $base_sql .= " AND i.division_id = ? ";
  $params[] = $division_id;
  $types   .= "i";
}

// 🔹 District filter
if ($district_id > 0) {
  $base_sql .= " AND i.district_id = ? ";
  $params[] = $district_id;
  $types   .= "i";
}

// 🔹 Type filter
if ($type_id > 0) {
  $base_sql .= " AND i.type_id = ? ";
  $params[] = $type_id;
  $types   .= "i";
}

// 🔢 Count total rows
$sql_count = "SELECT COUNT(*) AS cnt " . $base_sql;
$stmt = $conn->prepare($sql_count);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$total = ($res && $res->num_rows) ? intval($res->fetch_assoc()['cnt']) : 0;
$stmt->close();

// 🧾 Fetch data
$offset = ($page - 1) * $per_page;
$sql_data = "
  SELECT 
    i.institute_id,
    i.institute_name,
    t.type_name AS institute_type,
    dv.division_name,
    d.district_name
  " . $base_sql . "
  ORDER BY i.institute_name ASC
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
    'institute_id'   => $row['institute_id'],
    'institute_name' => $row['institute_name'],
    'institute_type' => $row['institute_type'] ?? '',
    'division_name'  => $row['division_name'] ?? '',
    'district_name'  => $row['district_name'] ?? ''
  ];
}
$stmt->close();

// ✅ Return JSON response
echo json_encode([
  'success'  => true,
  'data'     => $data,
  'page'     => $page,
  'per_page' => $per_page,
  'total'    => $total
]);

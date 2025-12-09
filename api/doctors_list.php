<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db_connection.php';
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['role'] ?? 'User';
$scope = $_GET['scope'] ?? 'my';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = max(1, min(300, intval($_GET['per_page'] ?? 300)));
$q = trim($_GET['q'] ?? '');
$offset = ($page - 1) * $per_page;

// Base filter
$where = "WHERE 1=1";
if ($scope === 'my' && $role !== 'Admin') {
    $where .= " AND da.territory_id IN (
        SELECT territory_id FROM users WHERE user_id = $user_id
    )";
}
if ($q !== '') {
    $safe = "%".$conn->real_escape_string($q)."%";
    $where .= " AND (d.name LIKE '$safe' OR d.mobile_number LIKE '$safe')";
}

// ** ৩. মোট ডেটা গণনার জন্য কোয়েরি (Fixing Pagination Total)**
$count_sql = "
    SELECT COUNT(da.assignment_id) as total_rows
    FROM doctor_assignments da
    JOIN doctors d ON da.doctor_id = d.doctor_id
    {$where}
";
$total_result = $conn->query($count_sql);
$total_rows = $total_result->fetch_assoc()['total_rows'] ?? 0;
$total_result->free();

//main data fetch query
$sql = "
  SELECT 
    d.doctor_id,
    d.name,
    d.mobile_number,
    mt.title_name,
    mdg.designation_name,
    mi.institute_name,
    mc.chamber_name,
    da.assignment_id
  FROM doctor_assignments da
  JOIN doctors d ON da.doctor_id = d.doctor_id
  LEFT JOIN master_titles mt ON d.title_id = mt.title_id
  LEFT JOIN master_designations mdg ON d.designation_id = mdg.designation_id
  LEFT JOIN master_institutes mi ON d.institute_id = mi.institute_id
  LEFT JOIN master_chambers mc ON da.chamber_id = mc.chamber_id
  $where
  ORDER BY d.name ASC
  LIMIT $offset, $per_page
";

$result = $conn->query($sql);
$data = [];

while ($row = $result->fetch_assoc()) {
    // Fetch degrees for each doctor
    $deg_sql = "
      SELECT md.degree_name, mdd.detail_value
      FROM doctors_degrees dd
      JOIN master_degrees md ON dd.degree_id = md.degree_id
      LEFT JOIN master_degree_details mdd ON dd.detail_id = mdd.detail_id
      WHERE dd.doctor_id = {$row['doctor_id']}
      ORDER BY dd.doctor_degree_id ASC
    ";
    $deg_res = $conn->query($deg_sql);
    $degree_list = [];
    while ($deg = $deg_res->fetch_assoc()) {
        $degree_text = $deg['degree_name'];
        if (!empty($deg['detail_value'])) {
            $degree_text .= " ({$deg['detail_value']})";
        }
        $degree_list[] = $degree_text;
    }
    $deg_res->free();

    $formatted_name = trim(($row['title_name'] ?? '') . ' ' . $row['name']);
    if (!empty($degree_list)) {
        $formatted_name .= ', ' . implode(', ', $degree_list);
    }

    $data[] = [
        'doctor_id' => $row['doctor_id'],
        'full_name' => $formatted_name,
        'mobile_number' => $row['mobile_number'],
        'designation_name' => $row['designation_name'],
        'chamber_name' => $row['chamber_name'],
        'assignment_id' => $row['assignment_id']
    ];
}

echo json_encode([
  'success' => true,
  'data' => $data,
  'page' => $page,
  'per_page' => $per_page,
  'total' => $total_rows
]);

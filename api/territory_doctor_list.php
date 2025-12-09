<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db_connection.php';
header('Content-Type: application/json');

// --- 1. Security Check and Input ---
$role = $_SESSION['role'] ?? 'User';
if ($role !== 'Admin') {
    echo json_encode(['success' => false, 'msg' => 'Access Denied. Admin only.']);
    exit();
}

// Get the specific territory ID from the request
$territory_id = intval($_GET['territory_id'] ?? 0);
if ($territory_id === 0) {
    echo json_encode(['success' => false, 'msg' => 'Territory ID is required.']);
    exit();
}

// --- 2. Pagination, Sorting, and Search ---
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = max(1, min(100, intval($_GET['per_page'] ?? 50))); 
$q = trim($_GET['q'] ?? '');
$sort_by = $_GET['sort_by'] ?? 'd.name';
$sort_order = $_GET['sort_order'] ?? 'ASC';

// Sanitize inputs
$allowed_sorts = ['d.name', 'd.mobile_number', 'mdg.designation_name', 'mi.institute_name'];
$sort_by = in_array($sort_by, $allowed_sorts) ? $sort_by : 'd.name';
$sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';
$offset = ($page - 1) * $per_page;

// --- 3. Filtering ---
$where_conditions = ["da.territory_id = $territory_id"];

if (!empty($q)) {
    $safe_q = "%" . $conn->real_escape_string($q) . "%";
    $where_conditions[] = "(d.name LIKE '$safe_q' OR d.mobile_number LIKE '$safe_q')";
}
$where_clause = "WHERE " . implode(" AND ", $where_conditions);


// --- 4. Total Count Query ---
$count_sql = "
  SELECT COUNT(DISTINCT d.doctor_id) AS total_rows 
  FROM doctors d
  JOIN doctor_assignments da ON d.doctor_id = da.doctor_id
  {$where_clause}
";
$count_result = $conn->query($count_sql);
$total = $count_result ? ($count_result->fetch_assoc()['total_rows'] ?? 0) : 0;


// --- 5. Main Data Query ---
$sql = "
  SELECT 
    d.doctor_id,
    d.name,
    d.mobile_number,
    mt.title_name,
    mdg.designation_name,
    mi.institute_name,
    da.assignment_id,
    da.doctor_type
  FROM doctors d
  JOIN doctor_assignments da ON d.doctor_id = da.doctor_id
  LEFT JOIN master_titles mt ON d.title_id = mt.title_id
  LEFT JOIN master_designations mdg ON d.designation_id = mdg.designation_id
  LEFT JOIN master_institutes mi ON d.institute_id = mi.institute_id
  {$where_clause}
  GROUP BY d.doctor_id, d.name, da.assignment_id, da.doctor_type
  ORDER BY {$sort_by} {$sort_order}
  LIMIT {$offset}, {$per_page}
";

$result = $conn->query($sql);
$data = [];

if ($result === FALSE) {
    error_log("SQL Error in territory_doctor_list.php: " . $conn->error);
} else {
    while ($row = $result->fetch_assoc()) {
        // Fetch degrees for each doctor (as done in doctors_list.php)
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
            'assignment_id' => $row['assignment_id'],
            'name' => $formatted_name,
            'mobile_number' => $row['mobile_number'],
            'designation_name' => $row['designation_name'],
            'institute_name' => $row['institute_name'],
            'doctor_type' => $row['doctor_type']
        ];
    }
}


// --- 6. JSON Output ---
echo json_encode([
    'success' => true,
    'data' => $data,
    'page' => $page,
    'per_page' => $per_page,
    'total' => $total,
    'sort_by' => $sort_by,
    'sort_order' => $sort_order,
    'territory_id' => $territory_id
]);

$conn->close();
?>
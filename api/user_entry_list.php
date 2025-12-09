<?php
// Security: Only allow access if the session is started (handled by db_connection)
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db_connection.php';
header('Content-Type: application/json');

// --- 1. Security Check ---
$role = $_SESSION['role'] ?? 'User';
if ($role !== 'Admin') {
    echo json_encode(['success' => false, 'msg' => 'Access Denied. Admin only.']);
    exit();
}

// --- 2. Pagination and Sorting ---
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = max(1, min(50, intval($_GET['per_page'] ?? 20))); // Default to 20 per page
$sort_by = $_GET['sort_by'] ?? 'total_entry';
$sort_order = $_GET['sort_order'] ?? 'DESC';
$q = trim($_GET['q'] ?? '');

// Ensure safe sorting columns
$allowed_sorts = ['name', 'login_id', 'zone_name', 'territory_name', 'total_entry'];
$sort_by = in_array($sort_by, $allowed_sorts) ? $sort_by : 'total_entry';
$sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';

$offset = ($page - 1) * $per_page;

// ✅ ফিক্স ২: ডাইনামিক WHERE ক্লজ তৈরি করা
$where_clause = "WHERE u.role != 'Admin'";

if (!empty($q)) {
    // সার্চ ইনপুটকে SQL Injection থেকে সুরক্ষিত করা
    $safe_q = "%" . $conn->real_escape_string($q) . "%";
    // u.name এবং u.login_id (ID No) এর উপর সার্চ যোগ করা
    $where_clause .= " AND (u.name LIKE '$safe_q' OR u.login_id LIKE '$safe_q')";
}

// --- 3. Total Count Query (for Pagination) ---
// Note: Only count non-Admin users for this list
$count_sql = "SELECT COUNT(u.user_id) AS total FROM users u {$where_clause}"; 
$count_result = $conn->query($count_sql);
$total = $count_result ? ($count_result->fetch_assoc()['total'] ?? 0) : 0;

// --- 4. Main Data Query ---
$sql = "
  SELECT
    u.user_id,
    u.name,
    u.login_id,
    u.territory_id, -- territory_id added for the View button in the front-end
    t.territory_name,
    z.zone_name,
    COUNT(da.territory_id) AS total_entry
  FROM users u
  LEFT JOIN territories t ON u.territory_id = t.territory_id
  LEFT JOIN zones z ON t.zone_id = z.zone_id
  LEFT JOIN doctor_assignments da ON u.territory_id = da.territory_id
  
  {$where_clause} 
  
  GROUP BY u.user_id, u.name, u.login_id, u.territory_id, t.territory_name, z.zone_name
  ORDER BY {$sort_by} {$sort_order}
  LIMIT {$offset}, {$per_page}
";

$result = $conn->query($sql);
$data = [];

if ($result === FALSE) {
    // Graceful error handling in case of SQL failure
    error_log("SQL Error in user_entry_list.php: " . $conn->error);
    echo json_encode(['success' => false, 'msg' => 'Database Query Failed.']);
    $conn->close();
    exit();
}

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

// --- 5. JSON Output ---
echo json_encode([
    'success' => true,
    'data' => $data,
    'page' => $page,
    'per_page' => $per_page,
    'total' => $total,
    'sort_by' => $sort_by,
    'sort_order' => $sort_order,
    'msg' => 'User entry data fetched successfully'
]);

$conn->close();
?>
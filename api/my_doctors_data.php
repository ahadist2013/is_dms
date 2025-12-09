<?php
/**
 * File: is_dms/api/my_doctors_data.php
 * Purpose: Server-side processing for My Doctors List DataTables.
 * FINAL CLEAN VERSION (Data preparation for JS rendering).
 */

// Debugging: Show errors 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../includes/header.php'; // Includes session_start, db_connection, BASE_PATH

// Ensure this script is only accessed via POST request from DataTables
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['error' => 'Direct access forbidden']);
    exit();
}

$user_role = $_SESSION['role'] ?? 'Guest';
$user_territory_id = $_SESSION['territory_id'] ?? 0; 

// ===============================================================
// 1. DataTables POST Parameters
// ===============================================================
$draw = $_POST['draw'] ?? 1;
$start = $_POST['start'] ?? 0;
$length = $_POST['length'] ?? 300; 
$searchValue = $_POST['search']['value'] ?? '';

// Columns for sorting and searching
$columns = [
    'SL', 
    'd.doctor_id', 
    'formatted_doctor_name', 
    'd.mobile_number', 
    'mi.institute_name', 
    'mc.chamber_name', 
    'md.discipline_name', 
    'actions'
];

// Sorting Logic
$order = '';
if (isset($_POST['order'])) {
    $columnIndex = $_POST['order'][0]['column'];
    $columnName = $columns[$columnIndex];
    $columnSortOrder = $_POST['order'][0]['dir'];
    
    if ($columnName !== 'SL' && $columnName !== 'actions' && $columnName !== 'formatted_doctor_name') {
        $order = " ORDER BY {$columnName} {$columnSortOrder} ";
    }
    if ($columnName === 'formatted_doctor_name') {
        $order = " ORDER BY d.name {$columnSortOrder} "; 
    }
}


// ===============================================================
// 2. Base Query and Role-Based Filtering
// ===============================================================

// Base SELECT clause
$selectQuery = "
    SELECT 
        da.assignment_id,
        d.doctor_id,
        d.name AS doctor_name,
        d.mobile_number,
        mt.title_name,
        mi.institute_name,
        mc.chamber_name,
        md.discipline_name AS specialization_name, 
        -- Subquery to aggregate degrees for the formatted name
        (
            SELECT GROUP_CONCAT(CONCAT(mdg.degree_name, ' (', mdd.detail_value, ')') SEPARATOR ', ')
            FROM doctors_degrees dd
            INNER JOIN master_degrees mdg ON dd.degree_id = mdg.degree_id
            LEFT JOIN master_degree_details mdd ON dd.detail_id = mdd.detail_id
            WHERE dd.doctor_id = d.doctor_id
        ) AS degrees_details_list
";

// Base FROM clause
$fromQuery = "
    FROM doctor_assignments da
    JOIN doctors d ON da.doctor_id = d.doctor_id
    LEFT JOIN master_titles mt ON d.title_id = mt.title_id 
    LEFT JOIN master_institutes mi ON d.institute_id = mi.institute_id  
    LEFT JOIN master_chambers mc ON da.chamber_id = mc.chamber_id
    LEFT JOIN master_disciplines md ON d.specialization_id = md.discipline_id 
";

// Role-based WHERE clause
$whereClause = " WHERE 1=1 ";
$bindParams = '';
$bindValues = [];

// Officer Filter
if ($user_role !== 'Admin' && $user_territory_id > 0) {
    $whereClause .= " AND da.territory_id = ? ";
    $bindParams .= 'i';
    $bindValues[] = $user_territory_id;
}


// ===============================================================
// 3. Search (Global Filter) Logic
// ===============================================================

$globalSearchClause = "";
if (!empty($searchValue)) {
    $globalSearchClause = " AND (
        d.doctor_id LIKE ? OR
        d.name LIKE ? OR
        d.mobile_number LIKE ? OR
        mt.title_name LIKE ? OR
        mi.institute_name LIKE ? OR
        mc.chamber_name LIKE ? OR
        md.discipline_name LIKE ? 
    )";
    $searchWildcard = '%' . $searchValue . '%';
    $bindParams .= 'sssssss'; 
    $bindValues = array_merge($bindValues, array_fill(0, 7, $searchWildcard));
}


// ===============================================================
// 4. Get Total & Filtered Count
// ===============================================================

// Total records before filtering
$countSql = "SELECT COUNT(da.assignment_id) AS total_count " . $fromQuery . " WHERE 1=1 " . ($user_role !== 'Admin' && $user_territory_id > 0 ? " AND da.territory_id = ? " : "");
$stmtCount = $conn->prepare($countSql);

// Bind only for the role filter
if ($user_role !== 'Admin' && $user_territory_id > 0) {
    $stmtCount->bind_param('i', $user_territory_id);
}
$stmtCount->execute();
$totalRecords = $stmtCount->get_result()->fetch_assoc()['total_count'] ?? 0;
$stmtCount->close();


// Filtered records after search
$filteredCountSql = "SELECT COUNT(da.assignment_id) AS filtered_count " . $fromQuery . $whereClause . $globalSearchClause;
$stmtFilteredCount = $conn->prepare($filteredCountSql);

// Bind values for the filtered count query
if (!empty($bindParams)) {
    $temp_bind_values = [];
    if ($user_role !== 'Admin' && $user_territory_id > 0) {
        $temp_bind_values[] = $user_territory_id; // Role filter value
    }
    $search_values = array_fill(0, 7, $searchWildcard); 
    $temp_bind_values = array_merge($temp_bind_values, $search_values);
    
    $stmtFilteredCount->bind_param($bindParams, ...$temp_bind_values);
}

$stmtFilteredCount->execute();
$totalFiltered = $stmtFilteredCount->get_result()->fetch_assoc()['filtered_count'] ?? 0;
$stmtFilteredCount->close();


// ===============================================================
// 5. Final Data Query
// ===============================================================

$dataSql = $selectQuery . $fromQuery . $whereClause . $globalSearchClause . $order . " LIMIT ? OFFSET ?";
$stmtData = $conn->prepare($dataSql);

// Final binding setup
$final_bind_params = '';
$final_bind_values = [];

// 1. Role Filter
if ($user_role !== 'Admin' && $user_territory_id > 0) {
    $final_bind_params .= 'i';
    $final_bind_values[] = $user_territory_id;
}

// 2. Global Search
if (!empty($searchValue)) {
    $final_bind_params .= 'sssssss';
    $final_bind_values = array_merge($final_bind_values, array_fill(0, 7, $searchWildcard));
}

// 3. Pagination (LIMIT/OFFSET)
$final_bind_params .= 'ii';
$final_bind_values[] = (int)$length;
$final_bind_values[] = (int)$start;


if (!empty($final_bind_params)) {
    $stmtData->bind_param($final_bind_params, ...$final_bind_values);
}

$stmtData->execute();
$result = $stmtData->get_result();

$data = [];
$sl = (int)$start + 1;

while ($row = $result->fetch_assoc()) {
    // Doctor Name Formatting
    $title_name = $row['title_name'] ?? ''; 
    $doctorName = ($title_name ? $title_name . ' ' : '') . $row['doctor_name']; 
    
    if (!empty($row['degrees_details_list'])) {
        $doctorName .= ', ' . $row['degrees_details_list'];
    }

    $data[] = [
        'SL'                        => $sl++,
        'doctor_id'                 => $row['doctor_id'],
        'assignment_id'             => $row['assignment_id'],         // ✅ JS-এর জন্য raw ID পাঠানো হলো
        'doctor_name_raw'           => htmlspecialchars($row['doctor_name']), // ✅ JS-এর জন্য HTML-সুরক্ষিত নাম পাঠানো হলো
        'formatted_doctor_name'     => $doctorName,
        'mobile_number'             => $row['mobile_number'],
        'institute_name'            => $row['institute_name'],
        'chamber_name'              => $row['chamber_name'],
        'specialization_name'       => $row['specialization_name'], 
        'actions'                   => ''                             // ✅ HTML corruption এড়াতে খালি পাঠানো হলো
    ];
}

$stmtData->close();
$conn->close();

// ===============================================================
// 6. Final JSON Output
// ===============================================================

echo json_encode([
    'draw'            => (int)$draw,
    'recordsTotal'    => (int)$totalRecords,
    'recordsFiltered' => (int)$totalFiltered,
    'data'            => $data
]);

exit();
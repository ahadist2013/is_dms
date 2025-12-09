<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db_connection.php';

$user_id = $_SESSION['user_id'] ?? 0;
$role    = $_SESSION['role'] ?? '';
if (!$user_id) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$export   = $_GET['export'] ?? '';  // '', 'excel', 'pdf'
$page     = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 350;
if ($per_page <= 0) $per_page = 350;
$offset   = ($page - 1) * $per_page;

$q        = trim($_GET['q'] ?? '');
$sort_by  = $_GET['sort_by'] ?? 'name';
$sort_dir = ($_GET['sort_dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

// sort_by map
$allowed_sort = [
    'doctor_id'   => 'd.doctor_id',
    'name'        => 'd.name',
    'mobile'      => 'd.mobile_number',
    'chamber'     => 'mc.chamber_name',
    'institution' => 'mi.institute_name',
    'grade'       => 'mg.grade_code',
    'sl'          => 'd.doctor_id'
];
$order_col = $allowed_sort[$sort_by] ?? 'd.name';

// Officer হলে তার territory লাগবে
$current_territory_id = null;
if ($role !== 'Admin') {
    $stmt = $conn->prepare("SELECT territory_id FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $current_territory_id = $res['territory_id'] ?? null;
    $stmt->close();
}

// Common FROM + JOIN + WHERE
$base_sql = "
    FROM doctor_assignments da
    JOIN doctors d ON da.doctor_id = d.doctor_id
    LEFT JOIN master_titles mt ON mt.title_id = d.title_id
    LEFT JOIN doctors_degrees dd ON dd.doctor_id = d.doctor_id
    LEFT JOIN master_degrees md ON md.degree_id = dd.degree_id
    LEFT JOIN master_degree_details mdd ON mdd.detail_id = dd.detail_id
    LEFT JOIN master_chambers mc ON mc.chamber_id = da.chamber_id
    LEFT JOIN master_doctor_grades mg ON mg.grade_id = da.grade_id
    LEFT JOIN master_institutes mi ON mi.institute_id = d.institute_id
    WHERE 1 = 1
";

$params = [];
$types  = '';

if ($role !== 'Admin' && $current_territory_id) {
    $base_sql .= " AND da.territory_id = ? ";
    $params[] = $current_territory_id;
    $types   .= 'i';
}

if ($q !== '') {
    $like = '%' . $q . '%';
    $base_sql .= " AND (
        d.name LIKE ?
        OR d.mobile_number LIKE ?
        OR mc.chamber_name LIKE ?
        OR mi.institute_name LIKE ?
    )";
    $params = array_merge($params, [$like, $like, $like, $like]);
    $types .= 'ssss';
}

// ====== HELPER: SELECT part (for both JSON & export) ======
$select_sql = "
   SELECT 
    da.assignment_id,
    d.doctor_id,
    d.mobile_number,
    d.name,
    mt.title_name,
    mc.chamber_name,
    mi.institute_name,
    mg.grade_code,

   GROUP_CONCAT(
        CONCAT(
            md.degree_name,
            CASE 
                WHEN mdd.detail_value IS NOT NULL AND mdd.detail_value <> ''
                THEN CONCAT(' (', mdd.detail_value, ')')
                ELSE ''
            END
        )
        ORDER BY dd.doctor_degree_id ASC
        SEPARATOR ', '
    ) AS degrees
";

// ====== EXPORT BRANCH (EXCEL / PDF) ======
if ($export === 'excel' || $export === 'pdf') {
    // No pagination for export
    $export_sql = $select_sql . $base_sql . "
        GROUP BY da.assignment_id
        ORDER BY $order_col $sort_dir
    ";

    $stmt = $conn->prepare($export_sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    // Convert rows
    $rows = [];
    $sl   = 1;
    while ($row = $result->fetch_assoc()) {
        $name_full = trim(($row['title_name'] ? $row['title_name'] . ' ' : '') . $row['name']);
        if ($row['degrees']) {
            $name_full .= ' ' . $row['degrees'];
        }

        $rows[] = [
            'sl'            => $sl++,
            'doctor_id'     => $row['doctor_id'],
            'name_full'     => $name_full,
            'mobile_number' => $row['mobile_number'],
            'chamber'       => $row['chamber_name'],
            'institution'   => $row['institute_name'],
            'grade'         => $row['grade_code'],
        ];
    }
    $stmt->close();

    if ($export === 'excel') {
        // ==== EXCEL EXPORT (HTML table, Excel-friendly) ====
        $filename = "my_doctors_" . date('Ymd_His') . ".xls";
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Pragma: no-cache");
        header("Expires: 0");

        echo "<table border='1'>";
        echo "<tr>
                <th>SL</th>
                <th>Doctor ID</th>
                <th>Name</th>
                <th>Mobile</th>
                <th>Chamber</th>
                <th>Institution</th>
                <th>Grade</th>
              </tr>";
        foreach ($rows as $r) {
            echo "<tr>";
            echo "<td>{$r['sl']}</td>";
            echo "<td>{$r['doctor_id']}</td>";
            echo "<td>" . htmlspecialchars($r['name_full']) . "</td>";
            echo "<td>{$r['mobile_number']}</td>";
            echo "<td>" . htmlspecialchars($r['chamber']) . "</td>";
            echo "<td>" . htmlspecialchars($r['institution']) . "</td>";
            echo "<td>{$r['grade']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        exit;
    }

    // ==== PDF EXPORT PLACEHOLDER ====
    // Real PDF এর জন্য dompdf/fpdf এর মত লাইব্রেরি লাগবে।
    // আপাতত চাইলে এখানে simple HTML view করে, browser-এর "Print to PDF" ব্যবহার করতে পারো।
    header("Content-Type: text/html; charset=utf-8");
    echo "<h3>My Doctor List (PDF Export Placeholder)</h3>";
    echo "<table border='1' cellspacing='0' cellpadding='4'>";
    echo "<tr>
            <th>SL</th>
            <th>Doctor ID</th>
            <th>Name</th>
            <th>Mobile</th>
            <th>Chamber</th>
            <th>Institution</th>
            <th>Grade</th>
          </tr>";
    foreach ($rows as $r) {
        echo "<tr>";
        echo "<td>{$r['sl']}</td>";
        echo "<td>{$r['doctor_id']}</td>";
        echo "<td>" . htmlspecialchars($r['name_full']) . "</td>";
        echo "<td>{$r['mobile_number']}</td>";
        echo "<td>" . htmlspecialchars($r['chamber']) . "</td>";
        echo "<td>" . htmlspecialchars($r['institution']) . "</td>";
        echo "<td>{$r['grade']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    exit;
}

// ====== NORMAL JSON RESPONSE (table view er jonno) ======
header('Content-Type: application/json; charset=utf-8');

// count total
$count_sql = "SELECT COUNT(DISTINCT da.assignment_id) AS total " . $base_sql;
$stmt = $conn->prepare($count_sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$count_res  = $stmt->get_result()->fetch_assoc();
$total_rows = (int)$count_res['total'];
$stmt->close();

// data with pagination
$data_sql = $select_sql . $base_sql . "
    GROUP BY da.assignment_id
    ORDER BY $order_col $sort_dir
    LIMIT ? OFFSET ?
";

$final_types = $types . 'ii';
$params_with_limit = $params;
$params_with_limit[] = $per_page;
$params_with_limit[] = $offset;

$stmt = $conn->prepare($data_sql);
$stmt->bind_param($final_types, ...$params_with_limit);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
$sl_start = $offset + 1;
while ($row = $result->fetch_assoc()) {
    $name_full = trim(($row['title_name'] ? $row['title_name'] . ' ' : '') . $row['name']);
    if ($row['degrees']) {
        $name_full .= ' ' . $row['degrees'];
    }

    $data[] = [
        'sl'             => $sl_start++,
        'assignment_id'  => (int)$row['assignment_id'],
        'doctor_id'      => (int)$row['doctor_id'],
        'name_full'      => $name_full,
        'mobile_number'  => $row['mobile_number'],
        'chamber'        => $row['chamber_name'],
        'institution'    => $row['institute_name'],
        'grade'          => $row['grade_code']
    ];
}
$stmt->close();

echo json_encode([
    'success'    => true,
    'data'       => $data,
    'total_rows' => $total_rows,
    'page'       => $page,
    'per_page'   => $per_page
]);

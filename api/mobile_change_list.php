<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db_connection.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Helper: get degrees string for a doctor
function getDoctorDegrees(mysqli $conn, int $doctor_id): string {
    $sql = "
        SELECT 
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
        FROM doctors_degrees dd
        JOIN master_degrees md ON md.degree_id = dd.degree_id
        LEFT JOIN master_degree_details mdd ON mdd.detail_id = dd.detail_id
        WHERE dd.doctor_id = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res['degrees'] ?? '';
}

// Step 1: get all pending requests with OLD doctor & submitted_by info
$sql = "
    SELECT 
        mcr.id,
        mcr.doctor_id AS old_doctor_id,
        mcr.old_mobile,
        mcr.new_mobile,
        mcr.change_status,
        mcr.submitted_at,
        
        d.doctor_id,
        d.name AS doctor_name,
        d.mobile_number AS doctor_mobile,
        mt.title_name,
        mi.institute_name,
        mdsg.designation_name,
        
        u.user_id AS submitted_by_id,
        u.name AS submitted_by_name,
        u.login_id,
        t.territory_name,
        z.zone_name
    FROM mobile_change_requests mcr
    JOIN doctors d ON d.doctor_id = mcr.doctor_id
    LEFT JOIN master_titles mt ON mt.title_id = d.title_id
    LEFT JOIN master_institutes mi ON mi.institute_id = d.institute_id
    LEFT JOIN master_designations mdsg ON mdsg.designation_id = d.designation_id
    LEFT JOIN users u ON u.user_id = mcr.submitted_by
    LEFT JOIN territories t ON t.territory_id = u.territory_id
    LEFT JOIN zones z ON z.zone_id = t.zone_id
    WHERE mcr.change_status = 'Pending'
    ORDER BY mcr.submitted_at ASC
";

$result = $conn->query($sql);
$data = [];
$sl = 1;

while ($row = $result->fetch_assoc()) {

    $old_degrees = getDoctorDegrees($conn, (int)$row['old_doctor_id']);

    $old_name_full = trim(
        ($row['title_name'] ? $row['title_name'] . ' ' : '') .
        $row['doctor_name']
    );
    if ($old_degrees) {
        $old_name_full .= ' ' . $old_degrees;
    }

    // Step 2: New doctor info by new_mobile
    $new_doctor = null;
    $assignments = [];

    if (!empty($row['new_mobile'])) {
        $sqlNew = "
            SELECT 
                d2.doctor_id,
                d2.name,
                mt2.title_name
            FROM doctors d2
            LEFT JOIN master_titles mt2 ON mt2.title_id = d2.title_id
            WHERE d2.mobile_number = ?
            LIMIT 1
        ";
        $stmtNew = $conn->prepare($sqlNew);
        $stmtNew->bind_param("s", $row['new_mobile']);
        $stmtNew->execute();
        $newRes = $stmtNew->get_result();
        if ($newRow = $newRes->fetch_assoc()) {
            $new_degrees = getDoctorDegrees($conn, (int)$newRow['doctor_id']);
            $new_name_full = trim(
                ($newRow['title_name'] ? $newRow['title_name'] . ' ' : '') .
                $newRow['name']
            );
            if ($new_degrees) {
                $new_name_full .= ' ' . $new_degrees;
            }

            $new_doctor = [
                'doctor_id' => (int)$newRow['doctor_id'],
                'name_full' => $new_name_full,
            ];

            // Step 3: assignments for this new doctor
            $sqlAssign = "
                SELECT 
                    da.assignment_id,
                    u2.name AS officer_name,
                    u2.login_id,
                    t2.territory_name,
                    z2.zone_name
                FROM doctor_assignments da
                LEFT JOIN territories t2 ON t2.territory_id = da.territory_id
                LEFT JOIN zones z2 ON z2.zone_id = t2.zone_id
                LEFT JOIN users u2 ON u2.user_id = da.assigned_by
                WHERE da.doctor_id = ?
                ORDER BY z2.zone_name, t2.territory_name
            ";
            $stmtA = $conn->prepare($sqlAssign);
            $stmtA->bind_param("i", $new_doctor['doctor_id']);
            $stmtA->execute();
            $resA = $stmtA->get_result();
            while ($a = $resA->fetch_assoc()) {
                $assignments[] = [
                    'officer_name'   => $a['officer_name'],
                    'login_id'       => $a['login_id'],
                    'territory_name' => $a['territory_name'],
                    'zone_name'      => $a['zone_name'],
                ];
            }
            $stmtA->close();
        }
        $stmtNew->close();
    }

    $data[] = [
        'sl' => $sl++,
        'request_id'   => (int)$row['id'],
        'old_mobile'   => $row['old_mobile'],
        'new_mobile'   => $row['new_mobile'],
        'old_doctor_id'=> (int)$row['old_doctor_id'],
        'old_name_full'=> $old_name_full,
        'old_institute'=> $row['institute_name'],
        'old_designation' => $row['designation_name'],
        'submitted_by' => [
            'user_id'   => (int)$row['submitted_by_id'],
            'name'      => $row['submitted_by_name'],
            'login_id'  => $row['login_id'],
            'territory' => $row['territory_name'],
            'zone'      => $row['zone_name'],
        ],
        'new_doctor'   => $new_doctor,   // null or {doctor_id, name_full}
        'assignments'  => $assignments,  // array (possibly empty)
    ];
}

echo json_encode(['success' => true, 'data' => $data]);

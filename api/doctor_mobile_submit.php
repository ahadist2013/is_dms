<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db_connection.php';

header('Content-Type: application/json; charset=utf-8');

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$doctor_id  = isset($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : 0;
$old_mobile = trim($_POST['old_mobile'] ?? '');
$new_mobile = trim($_POST['new_mobile'] ?? '');

// DUPLICATE CHECK: same doctor, same new_mobile already submitted
$stmtDup = $conn->prepare("
    SELECT id 
    FROM mobile_change_requests 
    WHERE doctor_id = ? 
      AND new_mobile = ?
    LIMIT 1
");
$stmtDup->bind_param("is", $doctor_id, $new_mobile);
$stmtDup->execute();
$resDup = $stmtDup->get_result();
$stmtDup->close();

if ($resDup->num_rows > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'This mobile number has already been submitted for this doctor.'
    ]);
    exit;
}

if ($doctor_id <= 0 || $new_mobile === '') {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// Server-side validation
if (!preg_match('/^\d{11}$/', $new_mobile)) {
    echo json_encode(['success' => false, 'message' => 'Mobile must be 11 digits']);
    exit;
}
$prefix = substr($new_mobile, 0, 3);
$allowed = ['013','014','015','016','017','018','019'];
if (!in_array($prefix, $allowed, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid mobile prefix']);
    exit;
}

// optional: verify doctor exists & old mobile correct from doctors table
$stmtCheck = $conn->prepare("SELECT mobile_number FROM doctors WHERE doctor_id = ?");
$stmtCheck->bind_param("i", $doctor_id);
$stmtCheck->execute();
$row = $stmtCheck->get_result()->fetch_assoc();
$stmtCheck->close();

$db_old_mobile = $row['mobile_number'] ?? '';
if ($db_old_mobile && $old_mobile && $db_old_mobile !== $old_mobile) {
    // চাইলে warning দিতে পারো, আপাতত ignore করছি
}

$stmt = $conn->prepare("
    INSERT INTO mobile_change_requests 
    (doctor_id, old_mobile, new_mobile, change_status, submitted_by) 
    VALUES (?, ?, ?, 'Pending', ?)
");
$stmt->bind_param("issi", $doctor_id, $old_mobile, $new_mobile, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Mobile change request submitted.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit request.']);
}
$stmt->close();

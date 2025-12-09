<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db_connection.php';

header('Content-Type: application/json; charset=utf-8');

$user_id = $_SESSION['user_id'] ?? 0;
$role    = $_SESSION['role'] ?? '';
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$assignment_id = isset($_POST['assignment_id']) ? (int)$_POST['assignment_id'] : 0;
if ($assignment_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid assignment id']);
    exit;
}

if ($role === 'Admin') {
    $stmt = $conn->prepare("DELETE FROM doctor_assignments WHERE assignment_id = ?");
    $stmt->bind_param("i", $assignment_id);
} else {
    // Officer: তার নিজের territory এর assignmentই remove করতে পারবে
    $stmtTerr = $conn->prepare("SELECT territory_id FROM users WHERE user_id = ?");
    $stmtTerr->bind_param("i", $user_id);
    $stmtTerr->execute();
    $resTerr = $stmtTerr->get_result()->fetch_assoc();
    $stmtTerr->close();

    $territory_id = $resTerr['territory_id'] ?? 0;
    if (!$territory_id) {
        echo json_encode(['success' => false, 'message' => 'No territory assigned']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM doctor_assignments WHERE assignment_id = ? AND territory_id = ?");
    $stmt->bind_param("ii", $assignment_id, $territory_id);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Doctor assignment removed successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to remove assignment.']);
}
$stmt->close();

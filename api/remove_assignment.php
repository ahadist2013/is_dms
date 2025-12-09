<?php
/**
 * File: is_dms/api/remove_assignment.php
 * Purpose: Handles removing a doctor assignment from a territory.
 */

include '../includes/header.php'; // Includes session_start, db_connection

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$assignment_id = $_POST['assignment_id'] ?? null;

if (empty($assignment_id) || !is_numeric($assignment_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Assignment ID.']);
    exit;
}

// Security Check: Optionally, you can add an extra check here to ensure 
// the user is an Admin OR the assignment belongs to the user's territory.
// For simplicity, we assume logged-in users with access to the page can trigger this.

$sql = "DELETE FROM doctor_assignments WHERE assignment_id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode(['status' => 'error', 'message' => 'Database prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param('i', $assignment_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Assignment removed successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No assignment found with this ID or already removed.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Execution failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
exit;
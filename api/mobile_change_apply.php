<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db_connection.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? 0;
$action  = $_POST['action'] ?? '';
$request_id    = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
$old_doctor_id = isset($_POST['old_doctor_id']) ? (int)$_POST['old_doctor_id'] : 0;
$new_doctor_id = isset($_POST['new_doctor_id']) ? (int)$_POST['new_doctor_id'] : 0;
$new_mobile    = trim($_POST['new_mobile'] ?? '');

if (!$request_id || !$old_doctor_id || !$action) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

$conn->begin_transaction();

try {
    // Load request row to validate
    $stmt = $conn->prepare("SELECT * FROM mobile_change_requests WHERE id = ? FOR UPDATE");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$req) {
        throw new Exception('Request not found.');
    }
    if ($req['change_status'] !== 'Pending') {
        throw new Exception('This request is already processed.');
    }

    if ($action === 'update_number') {

        if ($new_mobile === '') {
            throw new Exception('New mobile is required.');
        }

        // ensure no other doctor already uses this new_mobile
        $stmt = $conn->prepare("SELECT doctor_id FROM doctors WHERE mobile_number = ? AND doctor_id <> ?");
        $stmt->bind_param("si", $new_mobile, $old_doctor_id);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if ($exists) {
            throw new Exception('This mobile number is already used by another doctor.');
        }

        // 1) update doctor mobile
        $stmt = $conn->prepare("UPDATE doctors SET mobile_number = ? WHERE doctor_id = ?");
        $stmt->bind_param("si", $new_mobile, $old_doctor_id);
        if (!$stmt->execute()) {
            throw new Exception('Failed to update doctor mobile number.');
        }
        $stmt->close();

        // 2) mark request Approved
        $stmt = $conn->prepare("
            UPDATE mobile_change_requests
            SET change_status = 'Approved',
                updated_by = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("ii", $user_id, $request_id);
        if (!$stmt->execute()) {
            throw new Exception('Failed to update request status.');
        }
        $stmt->close();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Doctor mobile number updated successfully.']);
        exit;

    } elseif ($action === 'add_user') {

        if (!$new_doctor_id) {
            throw new Exception('Target doctor id missing.');
        }

        // transfer doctor_assignments from old doctor to new doctor
        // (WARNING: if unique constraint conflict, you may need extra handling)
        $stmt = $conn->prepare("
            UPDATE doctor_assignments
            SET doctor_id = ?
            WHERE doctor_id = ?
        ");
        $stmt->bind_param("ii", $new_doctor_id, $old_doctor_id);
        if (!$stmt->execute()) {
            throw new Exception('Failed to transfer doctor assignments.');
        }
        $stmt->close();

        // mark request Approved
        $stmt = $conn->prepare("
            UPDATE mobile_change_requests
            SET change_status = 'Approved',
                updated_by = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("ii", $user_id, $request_id);
        if (!$stmt->execute()) {
            throw new Exception('Failed to update request status.');
        }
        $stmt->close();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Assignments transferred to the new doctor.']);
        exit;

    } else {
        throw new Exception('Unknown action.');
    }

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

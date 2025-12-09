<?php
// doctor_actions.php (Place in /api/ folder)
if (session_status() === PHP_SESSION_NONE) session_start();
// Path adjusted for '/is_dms/api/' to reach '/is_dms/config/'
require_once __DIR__ . '/../config/db_connection.php'; 
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['role'] ?? 'User';
$action = $_POST['action'] ?? '';
$assignment_id = intval($_POST['assignment_id'] ?? 0);

if ($user_id === 0) {
    echo json_encode(['success' => false, 'msg' => 'Access Denied. User not logged in.']);
    exit;
}

switch ($action) {
    case 'remove_assignment':
        if ($assignment_id === 0) {
            echo json_encode(['success' => false, 'msg' => 'Invalid assignment ID.']);
            exit;
        }

        // 1. Check permission: Only Admin or the assigned officer can remove
        $can_remove = ($role === 'Admin');
        
        $sql_check = "SELECT da.territory_id FROM doctor_assignments da WHERE da.assignment_id = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("i", $assignment_id);
        $stmt_check->execute();
        $assignment_territory_id = $stmt_check->get_result()->fetch_assoc()['territory_id'] ?? 0;
        $stmt_check->close();

        // Get user's territory_id for officer check
        $sql_user_terr = "SELECT territory_id FROM users WHERE user_id = ?";
        $stmt_user_terr = $conn->prepare($sql_user_terr);
        $stmt_user_terr->bind_param("i", $user_id);
        $stmt_user_terr->execute();
        $user_territory_id = $stmt_user_terr->get_result()->fetch_assoc()['territory_id'] ?? 0;
        $stmt_user_terr->close();

        // Officer check: Does the assignment territory match the user's territory?
        if (!$can_remove && ($role !== 'Admin') && ($assignment_territory_id === $user_territory_id)) {
            $can_remove = true;
        }

        if (!$can_remove) {
            echo json_encode(['success' => false, 'msg' => 'Permission Denied. You can only remove assignments within your territory.']);
            exit;
        }

        // 2. Perform Removal
        $sql_delete = "DELETE FROM doctor_assignments WHERE assignment_id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $assignment_id);

        if ($stmt_delete->execute()) {
            echo json_encode(['success' => true, 'msg' => 'Doctor assignment removed successfully.']);
        } else {
            echo json_encode(['success' => false, 'msg' => 'Database error during removal: ' . $conn->error]);
        }
        $stmt_delete->close();
        break;

    default:
        echo json_encode(['success' => false, 'msg' => 'Invalid action specified.']);
        break;
}
?>
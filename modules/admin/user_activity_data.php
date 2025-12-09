<?php
session_start();
require_once __DIR__ . '/../../config/db_connection.php';
header('Content-Type: application/json');

$role = $_SESSION['role'] ?? 'User';
if ($role !== 'Admin') {
    echo json_encode(['success' => false, 'msg' => 'Access Denied. Admin only.']);
    exit();
}

// ----------------------------------------------------
// Force Logout Action (POST Request)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'force_logout') {
    $user_id_to_logout = intval($_POST['user_id'] ?? 0);
    
    if ($user_id_to_logout > 0) {
        // Update the user's current active session to inactive
        $sql_update = "UPDATE user_sessions SET logout_time = NOW(), is_active = FALSE WHERE user_id = ? AND is_active = TRUE";
        $stmt_update = $conn->prepare($sql_update);
        
        if ($stmt_update) {
            $stmt_update->bind_param("i", $user_id_to_logout);
            if ($stmt_update->execute()) {
                // The update is successful. The user's session is invalidated in the database.
                echo json_encode(['success' => true, 'msg' => 'User successfully logged out.']);
            } else {
                echo json_encode(['success' => false, 'msg' => 'Database error during logout.']);
            }
            $stmt_update->close();
        } else {
             echo json_encode(['success' => false, 'msg' => 'Database preparation error.']);
        }
    } else {
        echo json_encode(['success' => false, 'msg' => 'Invalid user ID.']);
    }
    exit();
}


// ----------------------------------------------------
// Fetch User Activity Data (GET Request)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $status_filter = $_GET['status'] ?? 'all';
    
    // Subquery to get the LATEST session info (active or logged out) for each user
    // We only track field-level users (not admins)
    $sql_data = "
        SELECT 
            u.user_id,
            u.login_id,
            u.name,
            z.zone_name,
            t.territory_name,
            us.is_active,
            us.login_time,
            us.last_activity AS last_activity_time,
            us.logout_time
        FROM users u
        LEFT JOIN territories t ON u.territory_id = t.territory_id
        LEFT JOIN zones z ON t.zone_id = z.zone_id
        LEFT JOIN user_sessions us ON u.user_id = us.user_id
        WHERE u.role != 'Admin' AND u.user_id = (
            SELECT user_id FROM user_sessions 
            WHERE user_id = u.user_id 
            ORDER BY session_id DESC LIMIT 1
        )
    ";

    // Filtering logic
    $where = "WHERE u.role != 'Admin'";
    if ($status_filter === 'logged_in') {
        $where .= " AND us.is_active = TRUE";
    } elseif ($status_filter === 'logged_out') {
        $where .= " AND us.is_active = FALSE";
    }

    // Main Query
    $sql_final = "
        SELECT 
            u.user_id,
            u.login_id,
            u.name,
            z.zone_name,
            t.territory_name,
            COALESCE(us.is_active, FALSE) AS is_active,
            us.login_time,
            us.last_activity AS last_activity_time,
            us.logout_time
        FROM users u
        LEFT JOIN territories t ON u.territory_id = t.territory_id
        LEFT JOIN zones z ON t.zone_id = z.zone_id
        LEFT JOIN user_sessions us ON u.user_id = us.user_id AND us.session_id = (
            SELECT session_id FROM user_sessions 
            WHERE user_id = u.user_id 
            ORDER BY session_id DESC LIMIT 1
        )
        $where
        ORDER BY us.is_active DESC, us.last_activity DESC
    ";

    $result = $conn->query($sql_final);
    $data = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Failed to retrieve data.']);
    }
}

// $conn->close(); // Assuming you close connection in db_connection or globally
?>
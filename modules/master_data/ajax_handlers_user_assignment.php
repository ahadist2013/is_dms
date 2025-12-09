<?php
session_start();
require_once '../../config/db_connection.php';

// Ensure the request has an action parameter
if (isset($_POST['action']) || isset($_GET['action'])) {
    
    $action = $_REQUEST['action'];
    
    header('Content-Type: application/json'); // Set header for JSON response

    switch ($action) {
        
        // ✅ Action: Get Territories by Zone (for User Assignment dropdown)
        case 'get_territories_by_zone':
            $zone_id = intval($_POST['zone_id'] ?? 0);
            $territories = [];

            if ($zone_id > 0) {
                $sql = "SELECT territory_id AS id, territory_name AS name 
                        FROM territories 
                        WHERE zone_id = $zone_id 
                        ORDER BY territory_name ASC";
                $res = $conn->query($sql);

                if ($res && $res->num_rows > 0) {
                    while ($row = $res->fetch_assoc()) {
                        $territories[] = $row;
                    }
                    echo json_encode(['success' => true, 'data' => $territories]);
                } else {
                    echo json_encode(['success' => true, 'data' => []]);
                }
            } else {
                echo json_encode(['success' => false, 'data' => [], 'message' => 'Invalid Zone ID']);
            }
            exit;

    }
    
    $conn->close();
    exit();
}
// Helper function (assuming it's in db_connection.php and loaded via require_once)
// function sanitize_input($conn, $data) { ... }
?>
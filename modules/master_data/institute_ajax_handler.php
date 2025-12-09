<?php
session_start();
// Assuming db_connection.php contains sanitize_input() and the $conn object setup
require_once '../../config/db_connection.php'; 

// Ensure the request has an action parameter
if (isset($_POST['action']) || isset($_GET['action'])) {
    
    $action = $_REQUEST['action'];
    
    header('Content-Type: application/json'); // Set header for JSON response

    switch ($action) {
        
        // =================================================================
        // ✅ Institute/Location Handlers: Division -> District -> Upazila
        // =================================================================
        
        // Action 1: Load Districts based on Division ID
        case 'load_districts':
            if (isset($_POST['division_id'])) {
                // Ensure the input is an integer for security and correct query execution
                $division_id = (int)sanitize_input($conn, $_POST['division_id']); 
                $districts = [];

                // Assuming 'districts' table structure: district_id, division_id, district_name
                $sql = "SELECT district_id AS value, district_name AS text 
                        FROM districts
                        WHERE division_id = $division_id
                        ORDER BY district_name ASC";
                
                $result = $conn->query($sql);
                
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        // Using 'value' and 'text' keys for consistency in JS
                        $districts[] = $row; 
                    }
                    echo json_encode(['success' => true, 'data' => $districts]);
                    $result->free();
                } else {
                    echo json_encode([
                        'success' => false, 
                        'message' => 'SQL Error: Could not fetch districts. ' . $conn->error,
                        'query' => $sql
                    ]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Division ID is missing.']);
            }
            break;

        // Action 2: Load Upazilas based on District ID
        case 'load_upazilas':
            if (isset($_POST['district_id'])) {
                $district_id = (int)sanitize_input($conn, $_POST['district_id']);
                $upazilas = [];
                
                // Assuming 'upazilas' table structure: upazila_id, district_id, upazila_name
                $sql = "SELECT upazila_id AS value, upazila_name AS text 
                        FROM upazilas 
                        WHERE district_id = $district_id 
                        ORDER BY upazila_name ASC";
                
                $result = $conn->query($sql);
                
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        // Using 'value' and 'text' keys for consistency in JS
                        $upazilas[] = $row; 
                    }
                    echo json_encode(['success' => true, 'data' => $upazilas]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'SQL Error: ' . $conn->error]);
                }
                
                if ($result) $result->free();
            } else {
                 echo json_encode(['success' => false, 'message' => 'District ID not provided.']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
            break;
    }
    
    $conn->close();
    exit();
}
?>
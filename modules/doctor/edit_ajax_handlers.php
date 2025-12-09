<?php
// ফাইল লোকেশন: /is_dms/modules/doctor/edit_ajax_handlers.php
// ===============================
// AJAX Handlers for Doctor EDIT/UPDATE form (NEW FILE)
// ===============================

ini_set('display_errors', 1); // 💡 FIX 2.1: PHP ত্রুটি দেখানোর জন্য
error_reporting(E_ALL);     // 💡 FIX 2.1: PHP ত্রুটি দেখানোর জন্য

session_start();
require_once '../../config/db_connection.php';

// Helper function
function sanitize_input($conn, $data) {
    return $conn->real_escape_string(trim($data));
}

// Security: Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit();
}

// Ensure the request has an action parameter
if (isset($_POST['action']) || isset($_GET['action'])) {
    
    $action = $_REQUEST['action'];
    
    // Default header for JSON response
    header('Content-Type: application/json'); 

    switch ($action) {
        
        // ============================================================
        // Master Data Search (for Select2)
        // ============================================================
        case 'search_institutes':
            $query = $conn->real_escape_string($_GET['query'] ?? '');
            $sql = "SELECT institute_id AS id, institute_name AS name FROM master_institutes WHERE institute_name LIKE '%$query%' ORDER BY institute_name ASC LIMIT 50";
            $result = $conn->query($sql);
            $data = [];
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                $result->free();
            }
            
            // Add 'Add New' option
            $data[] = ['id' => 'other_add', 'name' => '➕ Add New Institute'];

            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'search_chambers':
            $query = $conn->real_escape_string($_GET['query'] ?? '');
        
            $sql = "
                SELECT 
                    c.chamber_id AS id, 
                    CONCAT(c.chamber_name,
                        IF(d.district_name IS NOT NULL, CONCAT(' — ', d.district_name), ''),
                        IF(u.upazila_name IS NOT NULL, CONCAT(', ', u.upazila_name), '')
                    ) AS name
                FROM master_chambers c
                LEFT JOIN districts d ON c.district_id = d.district_id
                LEFT JOIN upazilas u ON c.upazila_id = u.upazila_id
                WHERE c.chamber_name LIKE '%$query%'
                ORDER BY c.chamber_name ASC
                LIMIT 50
            ";
        
            $result = $conn->query($sql);
            $data = [];
        
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                $result->free();
            }
            
            // Add 'Add New' option
            $data[] = ['id' => 'other_add', 'name' => '➕ Add New Chamber/Clinic'];

            echo json_encode(['success' => true, 'data' => $data]);
            break;

        // ============================================================
        // Master Data Modals (Forms and Save)
        // ============================================================

        case 'get_master_data_form':
            $type = $_GET['type'] ?? '';
            $html = '';

            // Fetch Districts for both
            $districts = $conn->query("SELECT district_id, district_name FROM districts ORDER BY district_name ASC");
            $district_options = '';
            if ($districts) {
                while ($row = $districts->fetch_assoc()) {
                    $district_options .= "<option value='{$row['district_id']}'>".e($row['district_name'])."</option>";
                }
                $districts->free();
            }

            if ($type == 'institute') {
                // Fetch Institute Types
                $types_result = $conn->query("SELECT type_id, type_name FROM master_institute_types ORDER BY type_name ASC");
                $type_options = '';
                if ($types_result) {
                    while ($row = $types_result->fetch_assoc()) {
                        $type_options .= "<option value='{$row['type_id']}'>".e($row['type_name'])."</option>";
                    }
                    $types_result->free();
                }

                $html = '
                    <form id="add_institute_form">
                        <input type="hidden" name="action" value="add_institute">
                        <div class="form-group">
                            <label for="new_institute_name">Institute Name <span class="required">*</span></label>
                            <input type="text" id="new_institute_name" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="new_institute_type">Institute Type</label>
                            <select id="new_institute_type" name="type_id" class="form-control">
                                <option value="">Select Type</option>' . $type_options . '
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="new_institute_district">District</label>
                            <select id="new_institute_district" name="district_id" class="form-control">
                                <option value="">Select District</option>' . $district_options . '
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Institute</button>
                        <p id="institute_message" class="mt-2"></p>
                    </form>
                    <script>
                    $("#add_institute_form").on("submit", function(e) {
                        e.preventDefault();
                        var formData = $(this).serialize();
                        $.post("../doctor/edit_ajax_handlers.php", formData, function(response) {
                            $("#institute_message").text(response.message).css("color", response.success ? "green" : "red");
                            if (response.success) {
                                // Add to select2 and select it
                                var newOption = new Option(response.data.name, response.data.id, true, true);
                                $("#institute_id").append(newOption).trigger("change");
                                $(".close-modal").click();
                            }
                        }, "json");
                    });
                    </script>
                ';
            } elseif ($type == 'chamber') {
                $html = '
                    <form id="add_chamber_form">
                        <input type="hidden" name="action" value="add_chamber">
                        <div class="form-group">
                            <label for="new_chamber_name">Chamber/Clinic Name <span class="required">*</span></label>
                            <input type="text" id="new_chamber_name" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="new_chamber_district">District</label>
                            <select id="new_chamber_district" name="district_id" class="form-control">
                                <option value="">Select District</option>' . $district_options . '
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Chamber</button>
                        <p id="chamber_message" class="mt-2"></p>
                    </form>
                    <script>
                    $("#add_chamber_form").on("submit", function(e) {
                        e.preventDefault();
                        var formData = $(this).serialize();
                        $.post("../doctor/edit_ajax_handlers.php", formData, function(response) {
                            $("#chamber_message").text(response.message).css("color", response.success ? "green" : "red");
                            if (response.success) {
                                // Add to select2 and select it
                                var newOption = new Option(response.data.name, response.data.id, true, true);
                                $("#chamber_id").append(newOption).trigger("change");
                                $(".close-modal").click();
                            }
                        }, "json");
                    });
                    </script>
                ';
            } else {
                $html = '<p class="text-danger">Invalid request type.</p>';
            }
            
            echo $html;
            break;

        case 'add_institute':
            $name = sanitize_input($conn, $_POST['name'] ?? '');
            $type_id = (int)($_POST['type_id'] ?? 0);
            $district_id = (int)($_POST['district_id'] ?? 0);
            
            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Institute Name is required.']);
                break;
            }
            
            $stmt = $conn->prepare("INSERT INTO master_institutes (institute_name, type_id, district_id, created_by) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("siii", $name, $type_id, $district_id, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $new_id = $conn->insert_id;
                echo json_encode(['success' => true, 'message' => 'Institute added successfully!', 'data' => ['id' => $new_id, 'name' => $name]]);
            } else {
                echo json_encode(['success' => false, 'message' => 'DB Error: ' . $stmt->error]);
            }
            $stmt->close();
            break;

        case 'add_chamber':
            $name = sanitize_input($conn, $_POST['name'] ?? '');
            $district_id = (int)($_POST['district_id'] ?? 0);
            
            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Chamber Name is required.']);
                break;
            }
            
            $stmt = $conn->prepare("INSERT INTO master_chambers (chamber_name, district_id, created_by) VALUES (?, ?, ?)");
            $stmt->bind_param("sii", $name, $district_id, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $new_id = $conn->insert_id;
                echo json_encode(['success' => true, 'message' => 'Chamber added successfully!', 'data' => ['id' => $new_id, 'name' => $name]]);
            } else {
                echo json_encode(['success' => false, 'message' => 'DB Error: ' . $stmt->error]);
            }
            $stmt->close();
            break;

        // ============================================================
        // ✅ FIX 2.2: Qualifications AJAX (NEW)
        // ============================================================
        
        // একটি Degree ID-র বিপরীতে সমস্ত Specialization/Discipline লোড করা
        case 'get_specializations_by_degree':
            $degree_id = (int)($_GET['degree_id'] ?? 0);
            $data = [];

            if ($degree_id > 0) {
                // Fetch discipline details linked to the selected degree
                $sql = "SELECT detail_id, detail_value FROM master_degree_details WHERE degree_id = ? ORDER BY detail_value ASC";
                $stmt = $conn->prepare($sql);
                
                if ($stmt === false) {
                    // Return empty array on prepare error (no specializations)
                    echo json_encode([]); 
                    break;
                }
                
                $stmt->bind_param("i", $degree_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $data[] = $row;
                    }
                    $result->free();
                }
                $stmt->close();
            }
            // শুধু ডেটা array রিটার্ন করা হয় (কারণ JS ফাংশনটি এটাই আশা করে)
            echo json_encode($data); 
            break;

        // নতুন Row যোগ করার জন্য সব ডিগ্রি লোড করা (আপনার সমস্যার মূল সমাধান)
        case 'get_all_degrees':
            $sql = "SELECT degree_id, degree_name FROM master_degrees ORDER BY degree_name ASC";
            $result = $conn->query($sql);
            $data = [];

            if ($result === false) { 
                 echo json_encode(['success' => false, 'message' => 'Query failed: ' . $conn->error]);
                 break;
            }
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                $result->free();
            }
            // JS-এ success: true চেক করা হয়, তাই এটি নিশ্চিত করুন।
            echo json_encode(['success' => true, 'data' => $data]);
            break;


        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
            break;
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Action not specified.']);
}

?>
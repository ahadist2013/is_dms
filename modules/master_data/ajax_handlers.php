<?php
session_start();
require_once '../../config/db_connection.php';

// Ensure the request has an action parameter
if (isset($_POST['action']) || isset($_GET['action'])) {
    
    $action = $_REQUEST['action'];
    
    header('Content-Type: application/json'); // Set header for JSON response

    switch ($action) {
        
        // Action 1: Check Doctor by Mobile Number (Existing logic)
        case 'check_doctor_by_mobile':
			if (isset($_POST['mobile_number'])) {
				$mobile_number = sanitize_input($conn, $_POST['mobile_number']);

				// 1️⃣  Basic doctor info
                    $sql_doctor = "
                        SELECT 
                            d.doctor_id, d.name, 
                            mt.title_name,
                            mdg.designation_name,
                            ms.discipline_name AS specialization_name,
                            mi.institute_name,
                            u.name AS entry_name,
                            z.zone_name, 
                            t.territory_name
                        FROM doctors d
                        JOIN users u ON d.created_by = u.user_id
                        LEFT JOIN territories t ON u.territory_id = t.territory_id
                        LEFT JOIN zones z ON t.zone_id = z.zone_id
                        LEFT JOIN master_titles mt ON d.title_id = mt.title_id
                        LEFT JOIN master_designations mdg ON d.designation_id = mdg.designation_id
                        LEFT JOIN master_disciplines ms ON d.specialization_id = ms.discipline_id
                        LEFT JOIN master_institutes mi ON d.institute_id = mi.institute_id
                        WHERE d.mobile_number = '$mobile_number'
                        LIMIT 1
                    ";


				$result = $conn->query($sql_doctor);

				if ($result && $result->num_rows > 0) {
					$doctor = $result->fetch_assoc();

					// 2️⃣  Collect all degrees
					$degrees_sql = "
                        SELECT md.degree_name, mdd.detail_value
                        FROM doctors_degrees dd
                        JOIN master_degrees md ON dd.degree_id = md.degree_id
                        LEFT JOIN master_degree_details mdd ON dd.detail_id = mdd.detail_id
                        WHERE dd.doctor_id = {$doctor['doctor_id']}
                        ORDER BY dd.doctor_degree_id ASC
                    ";

					$degrees_result = $conn->query($degrees_sql);

					$degree_list = [];
					if ($degrees_result && $degrees_result->num_rows > 0) {
						while ($deg = $degrees_result->fetch_assoc()) {
							$degree_text = $deg['degree_name'];
							if (!empty($deg['detail_value'])) {
								$degree_text .= " (" . $deg['detail_value'] . ")";
							}
							$degree_list[] = $degree_text;
						}
					}

                    // 3️⃣ Format full doctor summary text (aligned layout)
                    $formatted_name = trim("{$doctor['title_name']} {$doctor['name']}");
                    $formatted_degrees = !empty($degree_list) ? implode(", ", $degree_list) : "";

                    // Make degrees appear with name in same row (optional)
                    if ($formatted_degrees !== "") {
                        $formatted_name .= ", {$formatted_degrees}";
                    }

                    // Use a clean HTML table for alignment
                    $formatted_summary = '
                        <div class="doctor-summary" style="font-size:14px; line-height:1.5; color:#222; background: #f9fafb; padding: 10px 15px; border-radius: 6px; border: 1px solid #e2e2e2;">
                            <table style="width:100%; border-collapse:collapse;">
                                <tr>
                                    <td style="width:180px; font-weight:bold; vertical-align:top;">Name:</td>
                                    <td>' . htmlspecialchars($formatted_name) . '</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:bold; vertical-align:top;">Designation:</td>
                                    <td>' . htmlspecialchars($doctor['designation_name']) . '</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:bold; vertical-align:top;">Specialization:</td>
                                    <td>' . htmlspecialchars($doctor['specialization_name']) . '</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:bold; vertical-align:top;">Working Institute:</td>
                                    <td>' . htmlspecialchars($doctor['institute_name']) . '</td>
                                </tr>
                            </table>

                            <div style="margin-top:8px; font-size:13px; color:#333;">
                                <b>First Entry By:</b> ' . htmlspecialchars($doctor['entry_name']) . 
                                ' (' . htmlspecialchars($doctor['zone_name']) . ' - ' . htmlspecialchars($doctor['territory_name']) . ')<br>
                                <span style="color:#007bff; font-style:italic;">Select your Zone & Territory and Submit to Assign.</span>
                            </div>
                        </div>
                    ';


					echo json_encode([
						'exists' => true,
						'doctor' => [
							'doctor_id' => $doctor['doctor_id'],
							'name' => $formatted_summary
						],
						'entry_info' => [
							'name' => $doctor['entry_name'],
							'zone' => $doctor['zone_name'],
							'territory' => $doctor['territory_name']
						]
					]);
				} else {
					echo json_encode(['exists' => false]);
				}
			}
			break;


        // Action 2: Load Territories and Officer Info by Zone ID (Existing logic)
        case 'load_territories_by_zone':
        // map older create.php calls to new action
        $_GET['action'] = 'get_territories_by_zone';
        // (Don't break; we want to fall through)
        
        case 'get_territories_by_zone':

                $zone_id = sanitize_input($conn, $_GET['zone_id']);
                $territories = [];
            
                $sql = "SELECT t.territory_id, t.territory_name, u.name AS user_name, u.login_id
                        FROM territories t
                        LEFT JOIN users u ON t.territory_id=u.territory_id
                        WHERE t.zone_id = '$zone_id'
                        ORDER BY t.territory_name ASC";
            
                $res = $conn->query($sql);
                while($row = $res->fetch_assoc()){
                    $territories[] = $row;
                }
            
                // ❗ DETECT WHICH PAGE CALLED: create.php or edit.php
                $isEditPage = isset($_GET['edit']) || isset($_POST['edit']);
            
                if ($isEditPage) {
                    // edit.php expects: { success:true, data:[...] }
                    echo json_encode([
                        "success" => true,
                        "data"    => $territories
                    ]);
                } else {
                    // create.php expects simple array: [ {...}, {...} ]
                    echo json_encode($territories);
                }
                
                break;

        // =========================================================================
        // NEW ACTIONS FOR MASTER DATA ADDITION VIA MODAL
        // =========================================================================

        // Action 3: Load Modal Form HTML (Institute or Chamber)
        case 'load_modal_form':
            $type = sanitize_input($conn, $_POST['type']);
            
            if ($type == 'institute') {
                // Fetch Division and Institute Types
                $divisions = $conn->query("SELECT division_id, division_name FROM divisions ORDER BY division_name ASC");
                $institute_types = $conn->query("SELECT type_id, type_name FROM master_institute_types ORDER BY type_name ASC");
                
                // Return the Institute Form HTML
                ob_start(); // Start output buffering
                ?>
                <form id="institute_add_form">
                    <div class="form-group">
                        <label for="division_id_m">Division <span class="required">*</span></label>
                        <select id="division_id_m" name="division_id" required>
                            <option value="">Select Division</option>
                            <?php while($row = $divisions->fetch_assoc()): ?>
                                <option value="<?php echo $row['division_id']; ?>"><?php echo $row['division_name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="district_id_m">District <span class="required">*</span></label>
                        <select id="district_id_m" name="district_id" required>
                            <option value="">Select Division First</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="institute_name_m">Institute Name <span class="required">*</span></label>
                        <input type="text" id="institute_name_m" name="institute_name" required>
                    </div>
                    <div class="form-group">
                        <label for="institute_type_id_m">Institute Type <span class="required">*</span></label>
                        <select id="institute_type_id_m" name="type_id" required>
                            <option value="">Select Type</option>
                            <?php while($row = $institute_types->fetch_assoc()): ?>
                                <option value="<?php echo $row['type_id']; ?>"><?php echo $row['type_name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-submit" style="width: auto;">Save New Institute</button>
                    <div id="modal_message_m" style="margin-top: 10px;"></div>
                </form>
                <?php
                $html = ob_get_clean();
                echo json_encode(['success' => true, 'html' => $html]);
                
            } else if ($type == 'chamber') {
                // Fetch Division
                $divisions = $conn->query("SELECT division_id, division_name FROM divisions ORDER BY division_name ASC");
                
                // Return the Chamber Form HTML
                ob_start();
                ?>
                <form id="chamber_add_form">
                    <div class="form-group">
                        <label for="division_id_c">Division <span class="required">*</span></label>
                        <select id="division_id_c" name="division_id" required>
                            <option value="">Select Division</option>
                            <?php while($row = $divisions->fetch_assoc()): ?>
                                <option value="<?php echo $row['division_id']; ?>"><?php echo $row['division_name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="district_id_c">District <span class="required">*</span></label>
                        <select id="district_id_c" name="district_id" required>
                            <option value="">Select Division First</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="upazila_id_c">Upazila/Thana <span class="required">*</span></label>
                        <select id="upazila_id_c" name="upazila_id">
                            <option value="">Select District First</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="chamber_name_c">Chamber Name <span class="required">*</span></label>
                        <input type="text" id="chamber_name_c" name="chamber_name" required>
                    </div>
                    <div class="form-group">
                        <label for="address_c">Full Address</label>
                        <input type="text" id="address_c" name="address">
                    </div>
                    <button type="submit" class="btn-submit" style="width: auto;">Save New Chamber</button>
                    <div id="modal_message_c" style="margin-top: 10px;"></div>
                </form>
                <?php
                $html = ob_get_clean();
                echo json_encode(['success' => true, 'html' => $html]);
                
            } else if ($type == 'districts') {
                // Sub-Action: Get Districts by Division (Used by both forms)
                $division_id = sanitize_input($conn, $_POST['division_id']);
                $districts = [];
                if ($division_id) {
                    $sql = "SELECT district_id, district_name FROM districts WHERE division_id = '$division_id' ORDER BY district_name ASC";
                    $result = $conn->query($sql);
                    while ($row = $result->fetch_assoc()) {
                        $districts[] = $row;
                    }
                }
                echo json_encode(['success' => true, 'data' => $districts]);
            } else if ($type == 'upazilas') {
                // Sub-Action: Get Upazilas by District (Used by Chamber form)
                $district_id = sanitize_input($conn, $_POST['district_id']);
                $upazilas = [];
                if ($district_id) {
                    $sql = "SELECT upazila_id, upazila_name FROM upazilas WHERE district_id = '$district_id' ORDER BY upazila_name ASC";
                    $result = $conn->query($sql);
                    while ($row = $result->fetch_assoc()) {
                        $upazilas[] = $row;
                    }
                }
                echo json_encode(['success' => true, 'data' => $upazilas]);
            }
            break;

        // Action 4: Save New Institute
        case 'add_institute':
            $institute_name = sanitize_input($conn, $_POST['institute_name']);
            $division_id = sanitize_input($conn, $_POST['division_id']);
            $district_id = sanitize_input($conn, $_POST['district_id']);
            $type_id = sanitize_input($conn, $_POST['type_id']);
            
            $sql = "INSERT INTO master_institutes (institute_name, division_id, district_id, type_id, created_by) 
                    VALUES ('$institute_name', '$division_id', '$district_id', '$type_id', '{$_SESSION['user_id']}')";

            if ($conn->query($sql) === TRUE) {
                $new_id = $conn->insert_id;
                echo json_encode([
                    'success' => true, 
                    'message' => 'Institute Added Successfully!',
                    'institute_id' => $new_id,
                    'institute_name' => $institute_name
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database Error: ' . $conn->error]);
            }
            break;
            
        // Action 5: Save New Chamber
        case 'add_chamber':
            $chamber_name = sanitize_input($conn, $_POST['chamber_name']);
            $division_id = sanitize_input($conn, $_POST['division_id']);
            $district_id = sanitize_input($conn, $_POST['district_id']);
            $upazila_id = isset($_POST['upazila_id']) ? sanitize_input($conn, $_POST['upazila_id']) : NULL;
            $address = isset($_POST['address']) ? sanitize_input($conn, $_POST['address']) : '';

            $sql = "INSERT INTO master_chambers (chamber_name, division_id, district_id, upazila_id, full_address, created_by) 
                    VALUES ('$chamber_name', '$division_id', '$district_id', " . ($upazila_id ? "'$upazila_id'" : "NULL") . ", '$address', '{$_SESSION['user_id']}')";

            if ($conn->query($sql) === TRUE) {
                $new_id = $conn->insert_id;
                echo json_encode([
                    'success' => true, 
                    'message' => 'Chamber Added Successfully!',
                    'chamber_id' => $new_id,
                    'chamber_name' => $chamber_name
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database Error: ' . $conn->error]);
            }
            break;
            
        // Action 6: Search Master Data (Specialization, Institute, Chamber)
        case 'search_master_data':
            $type = sanitize_input($conn, $_POST['type']);
            $query = sanitize_input($conn, $_POST['query']);
            
            $table = '';
            $column_id = '';
            $column_name = '';
            
            // Determine table and columns based on type
            if ($type == 'discipline') {
                $table = 'master_disciplines';
                $column_id = 'discipline_id';
                $column_name = 'discipline_name';
            } else if ($type == 'institute') {
                $table = 'master_institutes';
                $column_id = 'institute_id';
                $column_name = 'institute_name';
            } else if ($type == 'chamber') {
                $table = 'master_chambers';
                $column_id = 'chamber_id';
                $column_name = 'chamber_name';
            }

            if ($table) {
                // Use LIKE for search functionality
                $sql = "SELECT {$column_id}, {$column_name} FROM {$table} 
                        WHERE {$column_name} LIKE '%{$query}%' 
                        ORDER BY {$column_name} ASC 
                        LIMIT 10"; 
                        
                $result = $conn->query($sql);
                $results = [];
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $results[] = [
                            'id' => $row[$column_id],
                            'name' => $row[$column_name]
                        ];
                    }
                }
                echo json_encode(['success' => true, 'data' => $results]);
            }
            break;


        // Action 7: Get Specializations/Disciplines by Main Degree ID
        case 'get_specializations_by_degree':
            if (isset($_GET['degree_id'])) {
                $degree_id = sanitize_input($conn, $_GET['degree_id']);
                $specializations = [];
                
                // FIX: Selecting detail_id as 'value' (for <option value="...") and detail_value as 'text' (for display)
                // কারণ আপনার master_degree_details টেবিলে সরাসরি স্পেশালাইজেশনের ডেটা (detail_value) আছে।
                $sql = "SELECT mdd.detail_id AS value, mdd.detail_value AS text 
                        FROM master_degree_details mdd
                        WHERE mdd.degree_id = $degree_id 
                        ORDER BY mdd.detail_value ASC";
                
                $result = $conn->query($sql);
                
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $specializations[] = $row;
                    }
                    echo json_encode(['success' => true, 'data' => $specializations]);
                } else {
                    // কোয়েরি ফেইল করলে ত্রুটি দেখাবে
                    echo json_encode([
                        'success' => false, 
                        'message' => 'SQL Error: ' . $conn->error,
                        'query' => $sql
                    ]);
                }
            }
            break;

            
        
        // ফাইল: modules/master_data/ajax_handlers.php
// (আপনার existing switch ($action) ব্লকের মধ্যে যোগ করুন)

// ... existing cases ...

        // =================================================================
        // ✅ NEW: LOCATION HANDLERS FOR CHAMBER DEPENDENT DROPDOWNS
        // =================================================================
        
        // Action: Load Districts based on Division ID
        case 'load_districts':
            if (isset($_POST['division_id'])) {
                $division_id = (int)sanitize_input($conn, $_POST['division_id']);
                $districts = [];
                
                // Assuming 'districts' table structure: district_id, division_id, district_name
                $sql = "SELECT district_id, district_name FROM districts WHERE division_id = $division_id ORDER BY district_name ASC";
                $result = $conn->query($sql);
                
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $districts[] = $row;
                    }
                    echo json_encode(['success' => true, 'data' => $districts]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'SQL Error: ' . $conn->error]);
                }
                
                if ($result) $result->free();
            } else {
                 echo json_encode(['success' => false, 'message' => 'Division ID not provided.']);
            }
            break;

        // Action: Load Upazilas based on District ID
        case 'load_upazilas':
            if (isset($_POST['district_id'])) {
                $district_id = (int)sanitize_input($conn, $_POST['district_id']);
                $upazilas = [];
                
                // Assuming 'upazilas' table structure: upazila_id, district_id, upazila_name
                $sql = "SELECT upazila_id, upazila_name FROM upazilas WHERE district_id = $district_id ORDER BY upazila_name ASC";
                $result = $conn->query($sql);
                
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
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
            
    // ============================================================
// ✅ Action: Search Institutes (Only Name)
// ============================================================
case 'search_institutes':
    $query = $conn->real_escape_string($_GET['query'] ?? '');

    $sql = "
        SELECT 
            institute_id AS id,
            institute_name AS name
        FROM master_institutes
        WHERE institute_name LIKE '%$query%'
        ORDER BY institute_name ASC
        LIMIT 50
    ";

    $result = $conn->query($sql);
    $data = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }

    echo json_encode(['success' => true, 'data' => $data]);
    break;


    
    
    // ============================================================
    // ✅ Action: Search Chambers (for Select2)
    // ============================================================
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
        }
    
        echo json_encode(['success' => true, 'data' => $data]);
        break;

        

        // ... default case



// ... (শেষের কোড)
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
            break;
    }
    
    $conn->close();
    exit();
}
// Helper function (assuming it's in db_connection.php and loaded via require_once)
// function sanitize_input($conn, $data) { ... }
?>
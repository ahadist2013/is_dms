<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ FIX: Define BASE_PATH globally (project root URL)
// আপনার দেওয়া URL অনুযায়ী সংশোধন করা হলো
define('BASE_PATH', 'https://bdd.ibnsinatrust.com/is_dms/'); 

// Database connection file inclusion (BASE_PATH is loaded here)
require_once __DIR__ . '/../config/db_connection.php';

// Security Check: Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_PATH . "login.php");
    exit();
}

// --------------------------------------------------------
// ✅ C. NEW: Update Last Activity Time (এইখানে কোডটি যোগ করুন)
// --------------------------------------------------------
if (isset($_SESSION['user_id']) && isset($conn)) {
    $user_id = $_SESSION['user_id'];
    $current_time = date('Y-m-d H:i:s');
    
    // প্রতি ২ মিনিটের বেশি নিষ্ক্রিয় থাকলে আপডেট করুন (অপটিমাইজেশনের জন্য)
    if (!isset($_SESSION['last_activity_update']) || (time() - $_SESSION['last_activity_update']) > 120) {
        // মনে রাখবেন, এই কোডটি অবশ্যই $conn অবজেক্ট ব্যবহার করে।
        $sql_activity = "UPDATE user_sessions SET last_activity = ? WHERE user_id = ? AND is_active = TRUE ORDER BY session_id DESC LIMIT 1";
        $stmt_activity = $conn->prepare($sql_activity);
        $stmt_activity->bind_param("si", $current_time, $user_id);
        $stmt_activity->execute();
        $stmt_activity->close();
        
        $_SESSION['last_activity_update'] = time();
    }
}
// --------------------------------------------------------
// ... [session_start(), BASE_PATH definition, db_connection.php inclusion-এর কোড] ...
require_once __DIR__ . '/../config/db_connection.php';

// Security Check: Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_PATH . "login.php");
    exit();
}

// --------------------------------------------------------
// C. Last Activity Update (আপনার আগের যুক্ত করা কোড)
// --------------------------------------------------------
if (isset($_SESSION['user_id']) && isset($conn)) {
    $user_id = $_SESSION['user_id'];
    $current_time = date('Y-m-d H:i:s');
    
    // প্রতি ২ মিনিটের বেশি নিষ্ক্রিয় থাকলে আপডেট করুন (অপটিমাইজেশনের জন্য)
    if (!isset($_SESSION['last_activity_update']) || (time() - $_SESSION['last_activity_update']) > 120) {
        $sql_activity = "UPDATE user_sessions SET last_activity = ? WHERE user_id = ? AND is_active = TRUE ORDER BY session_id DESC LIMIT 1";
        $stmt_activity = $conn->prepare($sql_activity);
        $stmt_activity->bind_param("si", $current_time, $user_id);
        $stmt_activity->execute();
        $stmt_activity->close();
        
        $_SESSION['last_activity_update'] = time();
    }
}

// =========================================================
// ✅ NEW FIX: Database Session Status Check (ফোর্স লগআউটের জন্য)
// =========================================================
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // ডাটাবেসে active session আছে কিনা তা চেক করা
    $sql_check = "SELECT session_id FROM user_sessions WHERE user_id = ? AND is_active = TRUE LIMIT 1";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $user_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    // যদি কোনো Active Session না পাওয়া যায় (অর্থাৎ অ্যাডমিন ফোর্স লগআউট করেছে)
    if ($result_check->num_rows === 0) {
        // ১. PHP সেশন ধ্বংস করা
        session_unset();
        session_destroy();
        
        // ২. লগইন পেজে রিডাইরেক্ট করা
        header("Location: " . BASE_PATH . "login.php");
        exit();
    }
    
    $stmt_check->close();
}
// =========================================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - DMS</title>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
       $(".submenu-toggle").on("click", function(e) {
            e.preventDefault();  // ✅ anchor click behavior বন্ধ
            e.stopPropagation(); // ✅ event bubbling বন্ধ

            var parentLi = $(this).parent(".has-submenu");
            parentLi.toggleClass("open");
            parentLi.find(".submenu").stop(true, true).slideToggle(200); // ✅ animation reset
        });

    });
    </script>
        <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    
    <style>
        /* General Styles */
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
        .dashboard-container { display: flex; min-height: 100vh; }
        
        /* Sidebar (Absolute positioning for footer) */
        .sidebar { 
            width: 250px; 
            background-color: rgba(5, 58, 114, 1); 
            color: white; 
            padding-top: 20px; 
            box-shadow: 2px 0 5px rgba(0,0,0,0.1); 
            position: fixed; 
            height: 100%; 
            padding-bottom: 70px; /* Space reserved for the footer */
            box-sizing: border-box; 
            z-index: 10;
        }
        .sidebar-header { text-align: center; padding: 10px 0; margin-bottom: 20px; font-size: 1.2em; border-bottom: 1px solid #34495e; }
       /* ==== Modern Sidebar Menu Style ==== */
        .menu-list {
        list-style: none;
        padding: 0;
        margin: 20px 0;
        }

        .menu-item {
        margin: 6px 10px;
        }

        .menu-item a {
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        color: #dce6f5;
        font-weight: 500;
        font-size: 15px;
        padding: 12px 16px;
        border-radius: 8px;
        transition: all 0.3s ease;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(4px);
        }

        .menu-item a i {
        font-size: 18px;
        width: 22px;
        text-align: center;
        color: #9ac7ff;
        transition: all 0.3s ease;
        }

        /* Hover & Active effects */
        .menu-item a:hover,
        .menu-item.active a {
        background: linear-gradient(90deg, #0057c1, #007bff);
        color: #ffffff;
        transform: translateX(4px);
        box-shadow: 0 3px 8px rgba(0, 91, 187, 0.25);
        }

        .menu-item a:hover i,
        .menu-item.active a i {
        color: #ffffff;
        transform: scale(1.1);
        }
/* ===== Submenu Styling (Updated Spacing & Visual Clarity) ===== */
        .has-submenu {
            margin-bottom: 8px; /* প্রতিটি dropdown মেনুর মাঝে ফাঁকা জায়গা */
        }

        .has-submenu > a {
            border-radius: 6px;
        }

        /* Submenu container */
        .has-submenu .submenu {
            display: none;
            list-style: none;
            padding-left: 25px;
            margin: 8px 0 10px 0; /* ওপরে-নিচে কিছু ফাঁকা জায়গা */
        }

        /* Submenu open state */

        /* Submenu items */
        .submenu li {
            margin-bottom: 6px; /* প্রতিটি সাবমেনুর আইটেমের মাঝে spacing */
        }

        .submenu a {
            font-size: 14px;
            color: #dce6f5;
            padding: 8px 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .submenu a:hover {
            background: linear-gradient(90deg, #0057c1, #007bff);
            transform: translateX(3px);
        }

        /* Animation for smooth open */
        @keyframes slideDown {
        from { opacity: 0; transform: translateY(-5px); }
        to { opacity: 1; transform: translateY(0); }
        }

        /* Chevron rotation */
        .has-submenu.open .submenu-icon {
            transform: rotate(180deg);
            transition: transform 0.3s ease;
        }


        /* Sidebar Footer (Positioned Absolutely) */
        .sidebar-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            padding: 15px 0;
            text-align: center;
            border-top: 1px solid #34495e;
            width: 100%; 
            background-color: #053a72ff;
        }
        .sidebar-footer .logout-btn {
            display: block;
            margin: 0 20px;
            padding: 10px;
            text-decoration: none;
            background-color: #e74c3c; 
            color: white;
            border-radius: 4px;
            transition: background 0.3s;
        }
        .sidebar-footer .logout-btn:hover {
            background-color: #c0392b;
        }

        /* Main Content & Header */
        .main-content { margin-left: 250px; flex-grow: 1; }
        .header { background-color: #ffffff; color: #333; padding: 15px 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        
        /* User Info (Header) */
        .user-info { 
            font-size: 0.9em; 
            text-align: right; 
            line-height: 1.4;
        }
        .user-info span { font-weight: bold; color: #007bff; }
        
        .content-area { padding: 30px; }
        
        /* Form & Card Styles (Crucial for create.php layout) */
        .doctor-entry-container { max-width: 1200px; margin: 0 auto; }
        .form-layout { display: flex; gap: 20px; margin-top: 20px; }
        .form-column { flex: 1; }
        .card { background: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card h3 { color: #0252a7ff; margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .required { color: #dc3545; }
        .hint { font-size: 0.8em; color: #6c757d; display: block; margin-top: 4px; }
        /* (Remaining styles for form, modal, etc., are omitted here for brevity) */
        .error-message { color: #dc3545; font-weight: bold; }
        .success-message { color: #28a745; font-weight: bold; }
        .warning-message { color: #ffc107; font-weight: bold; }
        .btn-submit { width: 100%; padding: 15px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1.1em; margin-top: 20px; }
		/* /* Sticky Preview Card FIX */
		#preview_card_sticky {
			position: sticky;
			top: 20px; /* Adjust as needed */
			z-index: 5;
		} */
        /* Radio Button & Label Alignment Fix */

        /* radio-group-কে পাশাপাশি রাখার জন্য */
        .radio-group {
            display: flex;
            gap: 20px; /* অপশনগুলোর মধ্যে কিছুটা ফাঁকা জায়গা দেবে */
            margin-top: 5px;
        }

        /* প্রতিটি রেডিও বাটন ও লেবেলের কন্টেইনারকে একটি লাইনে সারিবদ্ধ করবে */
        .radio-option {
            display: inline-flex; /* ইনপুট এবং লেবেলকে একসাথে রাখবে */
            align-items: center; /* উল্লম্বভাবে মাঝখানে সারিবদ্ধ করবে */
        }

        /* শুধুমাত্র রেডিও বাটনটির জন্য স্টাইল */
        .radio-option input[type="radio"] {
            /* এটি নিশ্চিত করবে যে বাটনটি টেক্সটের সাথে অ্যালাইন হয়েছে */
            margin-right: 5px; /* বাটনের পরে টেক্সট থেকে একটি সামান্য ফাঁকা স্থান তৈরি করে */
            /* যদি আপনার ব্রাউজারে ডিফল্ট স্টাইল না থাকে তবে এটি উচ্চতা স্থির করবে */
            /* width: 16px;
            height: 16px; */
        }

        /* --- Status Message Box Style (For Institute Added Success) --- */
        .status-message {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 1.1em;
            border: 1px solid transparent;
            transition: all 0.3s ease;
        }

        .status-message.success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .status-message.error {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        /* --- Button Styling (For Form Actions) --- */
        .btn {
            padding: 10px 20px;
            font-size: 1em;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
            transition: background-color 0.3s ease, transform 0.1s;
        }

        .btn:active {
            transform: translateY(1px); /* Add a slight press effect */
        }

        .btn-primary {
            background-color: rgba(2, 66, 134, 1); /* Blue for Add */
            color: white;
            box-shadow: 0 4px #0056b3; /* Darker blue shadow */
        }

        .btn-primary:hover {
            background-color: #0069d9;
        }

        .btn-secondary {
            background-color: #6c757d; /* Gray for Clear */
            color: white;
            box-shadow: 0 4px #5a6268; /* Darker gray shadow */
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        
        <div class="sidebar">
            <div class="sidebar-header"><b>THE IBN SINA TRUST</b></div>
            
                <ul class="menu-list">

                    <!-- Dashboard -->
                    <li class="menu-item">
                        <a href="<?php echo BASE_PATH; ?>index.php">
                            <i class="fa-solid fa-gauge"></i> Dashboard
                        </a>
                    </li>

                    <!-- Doctor Management Dropdown -->
                    <li class="menu-item has-submenu">
                        <a href="javascript:void(0)" class="submenu-toggle">
                            <i class="fa-solid fa-stethoscope"></i> Doctor Management
                            <i class="fa fa-chevron-down submenu-icon" style="margin-left:auto; font-size:12px;"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="<?php echo BASE_PATH; ?>modules/doctor/create.php"><i class="fa-solid fa-user-plus"></i> Add New Doctor</a></li>
                            <?php if ($_SESSION['role'] === 'Admin'): ?>
                            <li><a href="<?php echo BASE_PATH; ?>pages/user_entries.php"><i class="fa-solid fa-users"></i>Territory-wise Doctor</a></li>
                                <?php if ($_SESSION['role'] === 'Admin'): ?>
                                <li><a href="<?php echo BASE_PATH; ?>pages/mobile_number_update.php">
                                    <i class="fa-solid fa-phone"></i> Mobile Change Approval
                                </a></li>
                                <?php endif; ?>
                            <?php endif; ?>
                            <li><a href="<?php echo BASE_PATH; ?>pages/my_doctors_2.php"><i class="fa-solid fa-user-doctor"></i> My Doctor List</a></li>

                        </ul>
                    </li>

                    <!-- Master Data Dropdown -->
                    <li class="menu-item has-submenu">
                        <a href="javascript:void(0)" class="submenu-toggle">
                            <i class="fa-solid fa-database"></i> Master Data
                            <i class="fa fa-chevron-down submenu-icon" style="margin-left:auto; font-size:12px;"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="<?php echo BASE_PATH; ?>modules/master_data/add_chamber.php"><i class="fa-solid fa-hospital-user"></i> Add Chamber</a></li>
                            <li><a href="<?php echo BASE_PATH; ?>modules/master_data/add_institute.php"><i class="fa-solid fa-building-columns"></i> Add Institute</a></li>
                            <li><a href="<?php echo BASE_PATH; ?>pages/institutions.php"><i class="fa fa-university"></i> Institution List</a></li>
                            <li><a href="<?php echo BASE_PATH; ?>pages/chambers.php"><i class="fa fa-hospital"></i> Chamber List</a></li>
                            <li><a href="<?php echo BASE_PATH; ?>modules/master_data/add_territory.php">
                                <i class="fa-solid fa-map-location-dot"></i> Add Territory
                            </a></li>

                        </ul>
                    </li>

                    <!-- Admin Section -->
                    <?php if ($_SESSION['role'] === 'Admin'): ?>
                    <li class="menu-item has-submenu">
                        <a href="javascript:void(0)" class="submenu-toggle">
                            <i class="fa-solid fa-user-shield"></i> Administration
                            <i class="fa fa-chevron-down submenu-icon" style="margin-left:auto; font-size:12px;"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="<?php echo BASE_PATH; ?>modules/master_data/user_assignment.php"><i class="fa-solid fa-user-gear"></i> User Assignment</a></li>
                            <li><a href="<?php echo BASE_PATH; ?>modules/master_data/add_degree_specialization.php"><i class="fa-solid fa-user-gear"></i> Add Degree Specializaion</a></li>
                            <li><a href="<?php echo BASE_PATH; ?>modules/admin/user_activity.php"><i class="fa-solid fa-signal"></i> Live User Activity</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>

                </ul>



            
            <div class="sidebar-footer">
                <a href="<?php echo BASE_PATH; ?>logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <div class="main-content">
            <div class="header">
                <h2 <span style="color: #0252a7ff;"></span>IBN SINA DOCTOR MANAGEMENT SYSTEM</h2>
                
                <div class="user-info">
                    <div style="font-size: 1.1em; font-weight: bold; margin-bottom: 2px;">
                        Welcome, <span style="color: #007bff;"><?php echo $_SESSION['name']; ?></span>
                    </div>
                    <div style="margin-bottom: 2px;">
                        ID: **<?php echo $_SESSION['login_id']; ?>**
                    </div>
                    <div style="font-size: 0.9em; color: #5a6268; font-weight: bold;">
                        Zone: <?php echo $_SESSION['zone_name']; ?> | Territory: <?php echo $_SESSION['territory_name']; ?>
                    </div>
                </div>
            </div>

            <div class="content-area">
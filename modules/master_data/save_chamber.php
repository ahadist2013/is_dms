<?php
session_start();
require_once '../../config/db_connection.php';
if (!defined('BASE_PATH')) define('BASE_PATH', '/is_dms/');

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $_SESSION['status_message'] = "Invalid request method.";
    $_SESSION['status_type'] = "error";
    header("Location: " . BASE_PATH . "modules/master_data/add_chamber.php");
    exit();
}

$chamber_name = trim($conn->real_escape_string($_POST['chamber_name'] ?? ''));
$division_id  = (int)sanitize_input($conn, $_POST['division_id'] ?? 0);
$district_id  = (int)sanitize_input($conn, $_POST['district_id'] ?? 0);
$upazila_id   = (int)sanitize_input($conn, $_POST['upazila_id'] ?? 0);
$full_address = sanitize_input($conn, $_POST['full_address'] ?? '');
$created_by   = $_SESSION['user_id'] ?? 0;

if (empty($chamber_name) || $division_id == 0 || $district_id == 0 || $upazila_id == 0 || empty($full_address)) {
    $_SESSION['status_message'] = "All required fields must be filled.";
    $_SESSION['status_type'] = "error";
    header("Location: " . BASE_PATH . "modules/master_data/add_chamber.php");
    exit();
}

$conn->begin_transaction();
try {
    $sql = "INSERT INTO master_chambers 
            (chamber_name, division_id, district_id, upazila_id, full_address, created_by)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siiisi", $chamber_name, $division_id, $district_id, $upazila_id, $full_address, $created_by);
    $stmt->execute();

    $conn->commit();
    $_SESSION['status_message'] = "Chamber '{$chamber_name}' saved successfully!";
    $_SESSION['status_type'] = "success";
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['status_message'] = "Failed to save chamber. Error: " . $e->getMessage();
    $_SESSION['status_type'] = "error";
} finally {
    if (isset($stmt)) $stmt->close();
    $conn->close();
}

header("Location: " . BASE_PATH . "modules/master_data/add_chamber.php");
exit();
?>

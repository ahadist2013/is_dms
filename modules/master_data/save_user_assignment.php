<?php
session_start();
require_once __DIR__ . '/../../config/db_connection.php';
header('Content-Type: application/json');

// ✅ Only Admin can add user
if ($_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Access Denied.']);
    exit;
}

// ✅ Collect Form Data
$login_id = trim($_POST['login_id'] ?? '');
$name = trim($_POST['name'] ?? '');
$password = trim($_POST['password'] ?? '');
$role = trim($_POST['role'] ?? '');
$zone_id = intval($_POST['zone_id'] ?? 0);
$territory_id = intval($_POST['territory_id'] ?? 0);

// ✅ Basic Validation
if (!$login_id || !$name || !$password || !$role || !$territory_id) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

// ✅ Check duplicate login ID
$check = $conn->prepare("SELECT user_id FROM users WHERE login_id = ?");
$check->bind_param("s", $login_id);
$check->execute();
$check_result = $check->get_result();
if ($check_result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'This Login ID already exists.']);
    exit;
}

// ✅ Insert user
$sql = "INSERT INTO users (login_id, name, password_hash, role, territory_id, is_active) 
        VALUES (?, ?, ?, ?, ?, 1)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssi", $login_id, $name, $password, $role, $territory_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'New user successfully added!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error while adding user.']);
}

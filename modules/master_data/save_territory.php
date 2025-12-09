<?php
session_start();
require_once __DIR__ . '/../../config/db_connection.php';

header('Content-Type: application/json');

// ✅ শুধুমাত্র Admin Access
if ($_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Access Denied.']);
    exit;
}

$zone_id = intval($_POST['zone_id'] ?? 0);
$territory_name = trim($_POST['territory_name'] ?? '');

if (empty($zone_id) || empty($territory_name)) {
    echo json_encode(['success' => false, 'message' => 'Zone and Territory name are required.']);
    exit;
}

// Check if same territory already exists in this zone
$check_sql = "SELECT territory_id FROM territories WHERE zone_id = ? AND territory_name = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("is", $zone_id, $territory_name);
$stmt->execute();
$check_result = $stmt->get_result();

if ($check_result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'This territory already exists in the selected zone.']);
    exit;
}

// ✅ Insert new territory
$sql = "INSERT INTO territories (territory_name, zone_id) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $territory_name, $zone_id);
$success = $stmt->execute();

if ($success) {
    echo json_encode(['success' => true, 'message' => 'New territory added successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add territory.']);
}

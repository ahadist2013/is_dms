<?php
session_start(); // সেশন শুরু করুন
// ... আপনার db_connection.php ফাইল অন্তর্ভুক্ত করুন
require_once __DIR__ . '/config/db_connection.php'; // assuming db_connection.php is in the root directory

// --------------------------------------------------------
// ✅ NEW: Log the session end in user_sessions table
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql_update = "UPDATE user_sessions SET logout_time = NOW(), is_active = FALSE WHERE user_id = ? AND is_active = TRUE";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("i", $user_id);
    $stmt_update->execute();
    $stmt_update->close();
}
// $conn->close(); // যদি db_connection.php-এ $conn বন্ধ করা না হয়ে থাকে
// --------------------------------------------------------
// ... বাকি logout.php কোড (session_destroy() সহ)
// সেশনের সকল ভেরিয়েবল মুছে দিন
$_SESSION = array();

// যদি সেশন কুকি ব্যবহার করা হয়, তবে কুকিটিও মুছে দিন
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// সেশনটি ধ্বংস করুন
session_destroy();

// ব্যবহারকারীকে লগইন পেজে রিডাইরেক্ট করুন
header("Location: login.php");
exit;
?>
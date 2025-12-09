<?php
// Global Database Connection Configuration
$host = 'localhost';
$username = 'ibnsina_dbahadbddshibsina';
$password = ''; // Replace with your database password
$database = 'ibnsina_ahadbddshibsina'; // Replace with your database name
// ===============================================
// New: Define Project Base Path for Universal Links
// FIX for Not Found Error in Navigation
// ===============================================

// আপনার প্রজেক্ট ফোল্ডারের নাম দিন (উদাহরণস্বরূপ 'dms_project_name/' বা শুধু '/')
//define('BASE_PATH', '/is_dms/'); 


// Establish connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    // ⚠️ Production-এ নিরাপত্তার জন্য error_log ব্যবহার করুন
    die("Connection Failed: " . $conn->connect_error);
}

/**
 * Basic sanitization function
 * @param mysqli $conn The database connection object
 * @param mixed $data The data to sanitize
 * @return mixed The sanitized data (string or array)
 */
function sanitize_input($conn, $data) {
    if (is_array($data)) {
        // অ্যারে ইনপুট রিকিউরসিভলি স্যানিটাইজ করা
        return array_map(function($item) use ($conn) {
            if (is_string($item)) { // শুধুমাত্র স্ট্রিং স্যানিটাইজ করা
                $item = trim($item);
                $item = htmlspecialchars($item, ENT_QUOTES, 'UTF-8');
                return $conn->real_escape_string($item);
            }
            return $item; // নন-স্ট্রিং আইটেম অপরিবর্তিত রাখা
        }, $data);
    }
    
    // নন-অ্যারে ইনপুট হ্যান্ডলিং
    $data = trim($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8'); // XSS প্রতিরোধ
    return $conn->real_escape_string($data); // SQL Injection প্রতিরোধ
}
?>

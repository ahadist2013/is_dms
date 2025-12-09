<?php

// ✅ সংশোধন ১: যদি আপনার ini_set() কমান্ড থাকে, তবে সেগুলোকে
//    অবশ্যই session_start() এর আগে এখানে রাখতে হবে।
// উদাহরণ:
// ini_set('session.gc_maxlifetime', 86400);

session_start();

$login_error = '';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Database connection details (আপনার দেওয়া তথ্য অনুযায়ী)
   $host = 'localhost';
    $username = 'ibnsina_dbahadbddshibsina';
    $password = '-5]KJ4XqHx?Scg#c';
    $database = 'ibnsina_ahadbddshibsina';

    $conn = new mysqli($host, $username, $password, $database);

    if ($conn->connect_error) {
        $login_error = "Database connection failed: " . $conn->connect_error; // ডিবাগিং এর জন্য error যোগ করা হলো
    } else {
        $login_id = $conn->real_escape_string($_POST['login_id']); 
        $input_password = $_POST['password'];

        // Query uses updated column names: login_id, password_hash, role
        $sql = "SELECT u.user_id, u.login_id, u.name, u.password_hash, u.role, t.territory_name, z.zone_name 
                FROM users u
                LEFT JOIN territories t ON u.territory_id = t.territory_id
                LEFT JOIN zones z ON t.zone_id = z.zone_id
                WHERE u.login_id = ? AND u.is_active = 1";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $login_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            
            // ✅ সংশোধন: একটি মাত্র fetch_assoc() কল করা হলো
            $user = $result->fetch_assoc();
            
            // ✅ পাসওয়ার্ড যাচাই করা হলো (আপনার বর্তমান পদ্ধতি অনুযায়ী)
            if ($input_password === $user['password_hash']) { 
                // Successful Login
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['login_id'] = $user['login_id']; 
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role']; 
                $_SESSION['territory_name'] = $user['territory_name'] ?? 'N/A';
                $_SESSION['zone_name'] = $user['zone_name'] ?? 'N/A';
                
                // =========================================================
                // ✅ SESSİON LOGGING CODE - (সঠিক স্থানে বসানো হলো)
                // =========================================================
                $user_id = $user['user_id'];
                $login_time = date('Y-m-d H:i:s');
                
                // ১. যদি একই ইউজারের পুরোনো কোনো Active সেশন থাকে, তবে সেগুলোকে Inactive করে দেওয়া হবে
                // Note: user_id একটি integer হওয়ায় সরাসরি query তে ব্যবহার করা যেতে পারে (যদিও prepared statement ভালো)
                $conn->query("UPDATE user_sessions SET is_active = FALSE, logout_time = NOW() WHERE user_id = {$user_id} AND is_active = TRUE");
                
                // ২. নতুন Active সেশনটি ডাটাবেসে ইনসার্ট করা হবে
                $sql_session = "INSERT INTO user_sessions (user_id, login_time, last_activity, is_active) VALUES (?, ?, ?, TRUE)";
                $stmt_session = $conn->prepare($sql_session);
                
                if ($stmt_session) {
                    $stmt_session->bind_param("iss", $user_id, $login_time, $login_time);
                    $stmt_session->execute();
                    $stmt_session->close();
                } else {
                    error_log("Session logging failed in login.php: " . $conn->error);
                }
                // =========================================================

                // ✅ সফল লগইনের পর রিডাইরেক্ট এবং exit
                header("Location: index.php"); 
                exit();
            } else {
                $login_error = "Invalid Login ID or Password.";
            }
        } else {
            $login_error = "Invalid Login ID or Password.";
        }
        
        // ✅ স্টেটমেন্ট এবং কানেকশন বন্ধ করা
        $stmt->close();
        $conn->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Doctor Management System</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background-color: #f4f7f6; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0;
            position: relative; 
            background-image: none; 
            overflow-x: hidden; 
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('assets/images/login_bg.jpg');
            background-size: cover; 
            background-position: center center; 
            background-attachment: fixed; 
            filter: blur(3px); 
            z-index: -1; 
        }

        .login-container { 
            background: #ffffff; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); 
            width: 100%; 
            max-width: 400px; 
        }
        .login-container h2 { 
            text-align: center; 
            color: #004c9cff; 
            margin-bottom: 20px; 
        }
        .form-group { 
            margin-bottom: 15px; 
        }
        .form-group label { 
            display: block; 
            margin-bottom: 5px; 
            font-weight: bold; 
        }
        .form-group input[type="text"], 
        .form-group input[type="password"] { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ced4da; 
            border-radius: 4px; 
            box-sizing: border-box; 
        }
        .btn-login { 
            width: 100%; 
            padding: 10px; 
            background-color: #004c9cff; 
            color: white; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            font-size: 16px; 
        }
        .btn-login:hover { 
            background-color: #1278e6ff; 
        }
        .error-message { 
            color: #dc3545; 
            text-align: center; 
            margin-bottom: 15px; 
        }

        /* ✅ Bottom fixed line */
        .bottom-line {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 10px;
            background: linear-gradient(to right, #004c9cff 70%, #d30000ff 30%);
            z-index: 9999; 
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Doctor Management System</h2>
        
        <?php if ($login_error): ?>
            <p class="error-message"><?php echo $login_error; ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="login_id">Login ID</label>
                <input type="text" id="login_id" name="login_id" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn-login">Login</button>
        </form>
    </div>

    <div class="bottom-line"></div>

</body>
</html>
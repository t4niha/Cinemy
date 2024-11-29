<?php
session_start();

// DB connection
require '_DB_connection.php';

// PHPMailer
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Error essage initialization
$error_message = "";

// Check if sign up form is submitted
if (isset($_POST['signup'])) {
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $admin_key = $_POST['admin_key'];
    $user_type = $_POST['user_type'];

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = " Invalid email format ";
    } 
    elseif (strpos($email, ' ') !== false) {
        $error_message = " Email cannot contain spaces ";
    } 
    elseif (!preg_match('/^[a-zA-Z0-9@.]*$/', $email)) {
        $error_message = " Email can only contain letters and numbers ";
    }
    // Validate password
    elseif (strlen($password) < 6) {
        $error_message = " Password must be at least 6 characters long ";
    } 
    elseif (strpos($password, ' ') !== false) {
        $error_message = " Password cannot contain spaces ";
    } 
    elseif (!preg_match('/^[a-zA-Z0-9]*$/', $password)) {
        $error_message = " Password can only contain letters and numbers ";
    }
    // Validate username
    elseif (strlen($username) > 10) {
        $error_message = " Username cannot be more than 10 characters ";
    } 
    elseif (strpos($username, ' ') !== false) {
        $error_message = " Username cannot contain spaces ";
    } 
    elseif (!preg_match('/^[a-zA-Z0-9]*$/', $username)) {
        $error_message = " Username can only contain letters and numbers ";
    }
    // If all correct creaate new admin/user account
    else {
        if ($user_type == 'admin') {
            // DB query: Check if admin username/email already exist
            $query = "SELECT * FROM admin_accounts WHERE admin_account_email = ? OR username = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ss', $email, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $existingAdmin = $result->fetch_assoc();
            if ($existingAdmin) {
                $error_message = ($existingAdmin['admin_account_email'] === $email) 
                    ? " Email already exists for selected account type " 
                    : " Username already exists for selected account type ";
            } 
            else {
                // Validate admin key
                if (empty($admin_key) || strtolower($admin_key) !== "cse311") {
                    $error_message = " Incorrect or missing admin key ";
                }
                else {
                    // Generate email verification code
                    $verification_code = rand(100000, 999999);

                    // DB query: add admin and verification details to the database
                    $query = "INSERT INTO admin_accounts (admin_account_email, username, password, verification_code) 
                    VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param('ssss', $email, $username, $password, $verification_code);
                    $stmt->execute();

                    // Save session and redirect to email verification page
                    $_SESSION['email'] = $email;
                    $_SESSION['verification_code'] = $verification_code;
                    $_SESSION['user_account_id'] = $conn->insert_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['user_type'] = 'admin';
                    header('Location: verify_signup.php');
                    exit();
                }
            }
        } 
        else {
            // DB query: Check if user username/email already exist
            $query = "SELECT * FROM user_accounts WHERE user_account_email = ? OR username = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ss', $email, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $existingUser = $result->fetch_assoc();
            
            if ($existingUser) {
                $error_message = ($existingUser['user_account_email'] === $email) 
                    ? " Email already exists for selected account type " 
                    : " Username already exists for selected account type ";
            } 
            else {
                // Generate email verification code
                $verification_code = rand(100000, 999999);

                // DB query: add user and verification details to the database
                $query = "INSERT INTO user_accounts (user_account_email, username, password, verification_code, subscription_status) 
                VALUES (?, ?, ?, ?, 'inactive')";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('ssss', $email, $username, $password, $verification_code);
                $stmt->execute();

                // Save session and redirect to email verification page
                $_SESSION['email'] = $email;
                $_SESSION['verification_code'] = $verification_code;
                $_SESSION['user_account_id'] = $conn->insert_id;
                $_SESSION['username'] = $username;
                $_SESSION['user_type'] = 'user';
                header('Location: verify_signup.php');
                exit();
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - CineMy</title>
    <style>
        body {
            font-family: Helvetica, sans-serif;
            background-color: #c4c4c4;
            margin: 0;
            padding: 0;
            background-image: url('https://wallpaperbat.com/img/8612835-minimalist-fog-5120-x-2880.jpg');
            background-position: center;
            background-attachment: fixed;
            background-size: cover;
            height: 100vh;
        }
        header {
            background-color: #333;
            color: #fff;
            padding: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            box-sizing: border-box;
            height: 80px;
        }
        .profile-icon {
            align-items: left;
            flex-direction: column;
            text-align: left;
        }
        .profile-box {
            display: flex;
            align-items: left;
            flex-direction: column;
            text-align: left;
            width: 500px;
        }
        .profile-icon img {
            width: 40px;
            height: 40px;
            margin-left: 10px;
            border-radius: 10px;
            margin-bottom: 5px;
        }
        .profile-info {
            font-size: 14px;
            color: #fff;
            margin-left: 10px;
        }
        header h1 {
            margin: 0;
            flex-grow: 1;
            text-align: left;
        }
        .sorry-message {
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            text-align: center;
            margin: 0;
        }
        .logout-btn {
            background-color: #333;
            color: #333;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            transition: background-color 0.3s ease;
            border: none;
        }
        .logout-btn:hover {
            background-color: #333;
        }
        .menu-bar {
            background-color: #444;
            display: flex;
            height: 38.5px;
            justify-content: center;
            padding: 10px;
            align-items: center;
        }
        .menu-bar a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            margin: 0 10px;
            border-radius: 5px;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        .menu-bar a:hover {
            background-color: #666;
        }
        .menu-bar .search-bar {
            display: flex;
            align-items: center;
            margin-left: 20px;
        }
        .menu-bar .search-bar input {
            padding: 8px;
            font-size: 16px;
            border: 2px solid #ccc;
            border-radius: 5px;
            width: 250px;
        }
        .menu-bar .search-bar button {
            padding: 10px 20px;
            background-color: #555;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            transition: background-color 0.3s ease;
            margin-left: 10px;
        }
        .menu-bar .search-bar button:hover {
            background-color: #777;
        }
        main {
            padding: 5px 100px;
            text-align: center;
        }
        .profile-button img {
            width: 35px;
            height: 35px;
            border-radius: 10px;
            transition: transform 0.3s ease;
            padding: 0px 0px;
        }
        .form-container { 
            max-width: 450px; 
            width: 80%; 
            margin: 0 auto; 
            background-color: white; 
            margin-top: 20px;
            margin-bottom: 20px;
            padding: 6px 20px 30px 20px; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); 
        }
        .input-bar {
            width: 100%;
            max-width: 400px;
            padding: 10px;
            margin: 0px 0px 0px 0px;
            border-radius: 5px;
            border: 1px solid #ccc;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        .btn { 
            background-color: #555; 
            color: white; 
            padding: 10px 20px; 
            text-decoration: none; 
            border-radius: 5px; 
            transition: background-color 0.3s ease; 
            display: inline-block; 
            cursor: pointer;
            border: none;
        }
        .btn:hover { 
            background-color: #777; 
        }
        .radio-btns {
            display: block;
            align-items: left;
            margin: 15px 20px 15px 180px;
        }
        .radio-option {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
            width: 13px;
        }
        .radio-option input[type="radio"] {
            margin-right: 13px;
        }
        .radio-btns label {
            margin-left: auto;
        }
        .signup-as { 
            background-color: #333; 
            color: white; 
            padding: 1px; 
            text-align: center; 
            border-radius: 5px; 
            margin: 0px auto 0px auto; 
            width: 100%; 
            max-width: 420px; 
        }
        .error_message { 
            color: black; 
            margin: 10px auto; 
            font-size: 14px; 
            max-width: 450px; 
        }
    </style>
</head>
<body>
    
<header>
    <div class = "profile-box">
        <div class="profile-icon">
        </div>
    </div>
    <h1>▶ CINEMY</h1>
</header>

<div class="menu-bar">
</div>

<main>
    <?php if (!empty($error_message)): ?>
        <div class="error_message"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="form-container">
        <h2>Create Your Account</h2>
        <form action="signup.php" method="POST">
            <input class="input-bar" type="email" name="email" placeholder="Enter your email" required><br>
            <input class="input-bar" type="text" name="username" placeholder="Enter your username" required><br>
            <input class="input-bar" type="password" name="password" placeholder="Enter your password" required><br>
            <input class="input-bar" type="password" name="admin_key" placeholder="Enter admin key (restricted access)"><br>

            <div class="signup-as">
                <p>Account Type:</p>
            </div>

            <div class="radio-btns">
                <div class="radio-option">
                    <input type="radio" id="user" name="user_type" value="user" checked>
                    <label for="user">User</label>
                </div>
                <div class="radio-option">
                    <input type="radio" id="admin" name="user_type" value="admin">
                    <label for="admin">Admin</label>
                </div>
            </div>
            
            <input type="submit" name='signup' value="Sign Up" class="btn">
        </form>
    </div>
</main>
</body>
</html>
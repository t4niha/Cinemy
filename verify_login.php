<?php
session_start();

// DB connection
require '_DB_connection.php';

// PHPMailer
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if verification form is submitted
if (isset($_POST['verify_code'])) {
    $entered_code = $_POST['verification_code'];   

    if ($entered_code == $_SESSION['verification_code']) {
        // Clear the verification code and log in the user
        unset($_SESSION['verification_code']);

        // Redirect user/admin to their homepage
        if ($_SESSION['user_type'] === 'user') {
            header('Location: user_profiles.php');
            exit();
        } elseif ($_SESSION['user_type'] === 'admin') {
            header('Location: admin_account.php');
            exit();
        }
    } else {
        $error_message = "Invalid verification code. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email - CineMy</title>
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
        header h1 {
            margin: 0;
            flex-grow: 1;
            text-align: left;
        }
        main {
            padding: 5px 100px;
            text-align: center;
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
            margin: 10px auto;
            border-radius: 5px;
            border: 1px solid #ccc;
            display: block;
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
            margin: 10px 5px;
        }
        .btn:hover { 
            background-color: #777; 
        }
        .error_message { 
            color: black; 
            margin: 10px auto; 
            font-size: 14px; 
            max-width: 450px; 
        }.menu-bar {
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
        <h2>Enter verification code</h2>
        An email has been sent to <?php echo $_SESSION['email']; ?>
            <form action="verify_login.php" method="POST">
                <input class="input-bar" type="text" name="verification_code" placeholder="Enter verification code" required>
                <button class="btn" type="submit" name="verify_code">Verify</button>
            </form>
    </div>
</main>
</body>
</html>
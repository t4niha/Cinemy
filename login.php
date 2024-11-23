<?php
session_start();

// DB connection
require '_DB_connection.php';

// Error essage initialization
$error_message = "";

// Check if login form is submitted
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $user_type = $_POST['user_type'];

    // Check user type
    if ($user_type === 'admin') {
        $table = 'admin_accounts';
        $account_id = 'admin_account_id';
    } else {
        $table = 'user_accounts';
        $account_id = 'user_account_id';
    }

    // DB query: check if username exists
    $query = "SELECT * FROM $table WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    if (!$user) {
        $error_message = " Username does not exist for selected account type ";
    } else {
        if ($password === $user['password']) {
            // Set session variables to login details
            $_SESSION['user_account_id'] = $user[$account_id];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user_type;

            // Redirect user/admin to respective homepage
            if ($user_type === 'user') {
                header('Location: user_profiles.php');
                exit();
            } elseif ($user_type === 'admin') {
                header('Location: admin_account.php');
                exit();
            }
        } else {
            $error_message = " Incorrect password ";
        }
    }
}

// DB query: Retrieve user account and profile information
$query = 
    "SELECT ua.subscription_status, ua.payment_info, ua.user_account_email
    FROM user_profiles up
    JOIN user_accounts ua ON up.user_account_id = ua.user_account_id
    WHERE ua.user_account_id = ? LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_account_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc(); 
    
    $subscription_status = $user['subscription_status'];
    $payment_info = $user['payment_info'] ? $user['payment_info'] : 'None';
    $user_account_email = $user['user_account_email'];
}
else { 
    $username = 'Unknown User';
    $subscription_status = 'Inactive';
    $payment_info = 'No payment info available';
    $user_account_email = 'No email available';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CineMy</title>
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
            position: relative;
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
        .login-as-box {
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
            margin-top: 10px;
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
        <p class="error_message"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>
    <div class="form-container">
        <h2>Login to Your Account</h2>
        <form action="login.php" method="POST">

            <input class="input-bar" type="text" name="username" placeholder="Enter your username" required><br>
            <input class="input-bar" type="password" name="password" placeholder="Enter your password" required><br>

            <div class="login-as-box">
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
            
            <input type="submit" name='login' value="Login" class="btn">
        </form>
    </div>
</main>

</body>
</html>

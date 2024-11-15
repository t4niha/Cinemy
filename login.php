<?php
session_start();

// DB connection
require '_DB_connection.php';

// Error essage initialization
$error_message = "";

// Check if login form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
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
        $error_message = "*** Username does not exist for selected account type ***";
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
                header('Location: admin_homepage.php');
                exit();
            }
        } else {
            $error_message = "*** Incorrect password ***";
        }
    }
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
        }
        header {
            background-color: #333;
            color: #fff;
            text-align: center;
            padding: 1px;
        }
        main {
            padding: 20px;
            text-align: center;
        }
        .form-container {
            max-width: 450px;
            width: 80%;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        input {
            width: 100%;
            max-width: 400px;
            padding: 10px;
            margin: 5px 0;
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
        }
        .btn:hover {
            background-color: #777;
        }
        .radio-btns {
            display: block;
            align-items: left;
            margin: 20px 20px 20px 180px;
        }
        .radio-option {
            display: flex;
            align-items: center;
            margin-bottom: 5px; /* Space between each radio button row */
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
            margin: 10px auto;
            width: 100%;
            max-width: 420px;
        }
        .error_message {
            color: black;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<header>
    <h1>▶ CINEMY</h1>
</header>
<main>
    <?php if (!empty($error_message)): ?>
        <p class="error_message"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>
    <div class="form-container">
        <h2>Login to Your Account</h2>
        <form action="login.php" method="POST">

            <input type="text" name="username" placeholder="Enter your username" required><br>
            <input type="password" name="password" placeholder="Enter your password" required><br>

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
            
            <input type="submit" value="Login" class="btn">
        </form>
    </div>
</main>
</body>
</html>
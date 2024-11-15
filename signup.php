<?php
session_start();

// DB connection
require '_DB_connection.php';

// Error essage initialization
$error_message = "";

// Check if sign up form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $admin_key = $_POST['admin_key'];
    $user_type = $_POST['user_type'];

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "*** Invalid email format ***";
    } 
    // Validate password format
    elseif (strlen($password) < 6) {
        $error_message = "*** Password must be at least 6 characters long ***";
    } 
    else { // Create new admin account
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
                    ? "*** Email already exists for selected account type ***" 
                    : "*** Username already exists for selected account type ***";
            } else {
                // Validate admin key
                if (empty($admin_key) || $admin_key !== "CSE311L") {
                    $error_message = "*** Incorrect or missing admin key ***";
                } else {
                    // DB query: add new admin account
                    $query = "INSERT INTO admin_accounts (admin_account_email, username, password) VALUES (?, ?, ?)";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param('sss', $email, $username, $password);
                    $stmt->execute();
                    // Set session variables to new admin account details
                    $_SESSION['user_account_id'] = $conn->insert_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['user_type'] = 'admin';
                    header('Location: admin_homepage.php');
                    exit();
                }
            }
        } 
        else { // Create new user account
            // DB query: Check if user username/email already exist
            $query = "SELECT * FROM user_accounts WHERE user_account_email = ? OR username = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ss', $email, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $existingUser = $result->fetch_assoc();
            
            if ($existingUser) {
                $error_message = ($existingUser['user_account_email'] === $email) 
                    ? "*** Email already exists for selected account type ***" 
                    : "*** Username already exists for selected account type ***";
            } else {
                // DB query: add new user account
                $query = "INSERT INTO user_accounts (user_account_email, username, password, subscription_status) VALUES (?, ?, ?, 'inactive')";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('sss', $email, $username, $password);
                $stmt->execute();
                
                // Set session variables to new user account details
                $_SESSION['user_account_id'] = $conn->insert_id;
                $_SESSION['username'] = $username;
                $_SESSION['user_type'] = 'user';
                header('Location: user_profiles.php');
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
            margin: 0; padding: 0; 
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
        footer { 
            background-color: #333; 
            color: #fff; 
            text-align: center; 
            padding: 10px; 
            position: fixed; 
            width: 100%; 
            bottom: 0; 
        }
        .form-container { 
            max-width: 450px; 
            width: 80%; 
            margin: 0 auto; 
            background-color: white; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); 
        }
        input { width: 100%; 
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
        .signup-as { 
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
            margin: 10px auto; 
            font-size: 14px; 
            max-width: 450px; 
        }
    </style>
</head>
<body>
<header>
    <h1>▶ CINEMY</h1>
</header>
<main>
    <?php if (!empty($error_message)): ?>
        <div class="error_message"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="form-container">
        <h2>Create Your Account</h2>
        <form action="signup.php" method="POST">
            <input type="email" name="email" placeholder="Enter your email" required><br>
            <input type="text" name="username" placeholder="Enter your username" required><br>
            <input type="password" name="password" placeholder="Enter your password" required><br>
            <input type="text" name="admin_key" placeholder="Enter admin key (optional)"><br>

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
            
            <input type="submit" value="Sign Up" class="btn">
        </form>
    </div>
</main>
</body>
</html>
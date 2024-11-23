<?php
session_start();

// DB connection
require '_DB_connection.php';

// Check if the user is logged in
if (!isset($_SESSION['user_account_id'])) {
    header("Location: login.php");
    exit();
}

// Retrieve session data
$user_account_id = $_SESSION['user_account_id'];
$username = $_SESSION['username'];
$user_profile_name = $_SESSION['user_profile_name'];

// Error essage initialization
$error_message = "";

// Sign out
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// DB query: retrieve user profile id
$query = "SELECT user_profile_id FROM user_profiles WHERE user_profile_name = ? AND user_account_id = ? LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $user_profile_name, $user_account_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$user_profile_id = $user['user_profile_id'];

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

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Retrieve form data
    $current_password = $_POST['current_password'] ? $_POST['current_password'] : '';
    $new_username = $_POST['new_username'] ? $_POST['new_username'] : '';
    $new_user_profile_name = $_POST['new_user_profile_name'] ? $_POST['new_user_profile_name'] : '';
    $new_email = $_POST['new_email'] ? $_POST['new_email'] : '';
    $new_password = $_POST['new_password'] ? $_POST['new_password'] : '';
    $new_payment_info = $_POST['new_payment_info'] ? $_POST['new_payment_info'] : '';
    $new_subscription_status = $_POST['new_subscription_status'] ? $_POST['new_subscription_status'] : '';

    // Check if current password is entered
    if (empty($current_password)) {
        $error_message = " Current password required to confirm ";
    } 
    else {
        // DB query: Retrieve stored password for logged-in user
        $query = "SELECT password FROM user_accounts WHERE username = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && $current_password == $user['password']) {

            if (isset($_POST['change'])) {
                if (!empty($new_username)) {
                    if (strlen($new_username) > 10) {
                        $error_message = " Username cannot be more than 10 characters ";
                    } 
                    elseif (strpos($new_username, ' ') !== false) {
                        $error_message = " Username cannot contain spaces ";
                    }
                    elseif (!preg_match('/^[a-zA-Z0-9]*$/', $new_username)) {
                        $error_message = " Username can only contain letters and numbers ";
                    }
                    else {
                        // DB query: Check if new username already exists
                        $query = "SELECT COUNT(*) FROM user_accounts WHERE username = ? AND user_account_id != ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("si", $new_username, $user_account_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $existingUsernameCount = $result->fetch_row()[0];

                        if ($existingUsernameCount > 0) {
                            $error_message = " Username already exists ";
                        } 
                        else {
                            // DB query: Update username
                            $query = "UPDATE user_accounts SET username = ? WHERE user_account_id = ?";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("si", $new_username, $user_account_id);
                            if (!$stmt->execute()) {
                                $error_message = " Something went wrong while updating username ";
                            }
                            // Set session variable to new username
                            $_SESSION['username'] = $new_username;
                        }
                    }
                }

                if (!empty($new_user_profile_name)){
                    // Check if profile name is unique within the user's account
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM user_profiles WHERE user_account_id = ? AND user_profile_name = ?");
                    $stmt->bind_param("is", $user_account_id, $new_user_profile_name);
                    $stmt->execute();
                    $stmt->bind_result($existing_profile_count);
                    $stmt->fetch();
                    $stmt->close();
                    if ($existing_profile_count == 0){
                        $error_message = " You already have a profile with this name";
                    }
                    else {
                        if (strlen($new_user_profile_name) > 10) {
                            $error_message = " Profile name cannot be more than 10 characters ";
                        } 
                        elseif (strpos($new_user_profile_name, ' ') !== false) {
                            $error_message = " Profile name cannot contain spaces ";
                        } 
                        elseif (!preg_match('/^[a-zA-Z0-9]*$/', $new_user_profile_name)) {
                            $error_message = " Profile name can only contain letters and numbers ";
                        }
                        else {
                            // DB query: Update user profile name
                            $query = "UPDATE user_profiles SET user_profile_name =? WHERE user_profile_id =?";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("si", $new_user_profile_name, $user_profile_id);
                            if (!$stmt->execute()) {
                                $error_message = " Something went wrong while updating user profile name ";
                            }
                            // Set session variable to new profile name
                            $_SESSION['user_profile_name'] = $new_user_profile_name;
                        }
                    }
                }

                if (!empty($new_email)) {
                    // DB query: Check if new email already exists
                    $query = "SELECT COUNT(*) FROM user_accounts WHERE user_account_email = ? AND user_account_id != ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("si", $new_email, $user_account_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $existingEmailCount = $result->fetch_row()[0];

                    if ($existingEmailCount > 0) {
                        $error_message = " Email already exists ";
                    } 
                    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $error_message = " Invalid email format ";
                    } 
                    elseif (strpos($email, ' ') !== false) {
                        $error_message = " Email cannot contain spaces ";
                    } 
                    elseif (!preg_match('/^[a-zA-Z0-9@.]*$/', $email)) {
                        $error_message = " Email can only contain letters and numbers ";
                    }
                    else {
                        // DB query: Update email
                        $query = "UPDATE user_accounts SET user_account_email = ? WHERE user_account_id = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("si", $new_email, $user_account_id);
                        if (!$stmt->execute()) {
                            $error_message = " Something went wrong while updating email ";
                        }
                        // Set session variable to new email
                        $_SESSION['user_account_email'] = $new_email;
                    }
                }
                if (!empty($new_password)) {
                    // Validate password length
                    if (strlen($new_password) < 6) {
                        $error_message = " Password must be at least 6 characters long ";
                    } 
                    elseif (strpos($new_password, ' ') !== false) {
                        $error_message = " Password cannot contain spaces ";
                    } 
                    elseif (!preg_match('/^[a-zA-Z0-9]*$/', $new_password)) {
                        $error_message = " Password can only contain letters and numbers ";
                    }
                    else{
                        // DB query: Update password
                        $query = "UPDATE user_accounts SET password = ? WHERE user_account_id = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("si", $new_password, $user_account_id);
                        if (!$stmt->execute()) {
                            $error_message = " Something went wrong while updating password ";
                        }
                    }
                }
                if (!empty($new_payment_info)) {
                    $new_payment_info = str_replace(['"', "'"], '', $new_payment_info);

                    // DB query: Update payment info
                    $query = "UPDATE user_accounts SET payment_info = ? WHERE user_account_id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("si", $new_payment_info, $user_account_id);
                    if (!$stmt->execute()) {
                        $error_message = " Something went wrong while updating payment information ";
                    }
                    // Set session variable to new payment info
                    $_SESSION['payment_info'] = $new_payment_info;
                }
                if (!empty($new_subscription_status)) {
                    $valid_statuses = ['Active', 'Inactive'];
                    if (in_array($new_subscription_status, $valid_statuses)) {
                        // DB query: Update subscription status
                        $query = "UPDATE user_accounts SET subscription_status = ? WHERE user_account_id = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("si", $new_subscription_status, $user_account_id);
                        if (!$stmt->execute()) {
                            $error_message = " Something went wrong while updating subscription status ";
                        }
                        // Set session variable to new subscription status
                        $_SESSION['subscription_status'] = $new_subscription_status;
                    }
                }

                if (empty($error_message)) {
                    // Redirect back to account
                    header("Location: /cinemy/account.php?success=1");
                    exit();
                }
            }

            if (isset($_POST['delete'])) {
                // DB query: Delete user profiles
                $query = "DELETE FROM user_profiles WHERE user_account_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $user_account_id);
                $stmt->execute();

                // DB query: Delete user account
                $query = "DELETE FROM user_accounts WHERE user_account_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $user_account_id);
                $stmt->execute();

                // End session and redirect to index
                session_destroy();
                header("Location: index.php");
                exit();
            }
        } 
        else {
            // Incorrect password
            $error_message = " Incorrect password ";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account - CineMy</title>
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
        .logout-btn {
            background-color: #555;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            transition: background-color 0.3s ease;
            border: none;
            cursor: pointer;
        }
        .logout-btn:hover {
            background-color: #777;
        }
        .update-btn { 
            background-color: #555;
            width: 140px;
            color: white;
            padding: 10px 10px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            transition: background-color 0.3s ease;
            border: none;
            color: white; 
            border: none; 
            border-radius: 4px;
            cursor: pointer;
        }
        .delete-btn { 
            background-color: #872d2d;
            width: 140px;
            padding: 10px 10px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            transition: background-color 0.3s ease;
            border: none;
            color: white; 
            border: none;
            cursor: pointer;
        }
        .update-btn:hover { 
            background-color: #777;
        }
        .delete-btn:hover { 
            background-color: #b34646;
        }
        .menu-bar {
            background-color: #444;
            display: flex;
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
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
            margin-left: 10px;
        }
        .menu-bar .search-bar button:hover {
            background-color: #777;
        }
        main {
            padding: 15px 100px;
            text-align: center;
        }
        .account-info {
            background-color: #fff;
            padding: 5px 20px 20px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: relative;
            border: 1px solid #ddd;
            margin: 10px 0;
            text-align: left;
        }
        .update-account-info {
            background-color: #fff;
            padding: 5px 20px 20px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: relative;
            border: 1px solid #ddd;
            margin: 10px 0;
            text-align: left;
        }
        .error_message {
            color: black;
            margin-top: 10px;
        }
        .title {
            font-size: 17px;
            margin: 10px;
            background: none;
            padding: 0;
            border: none;
            max-width: 300px;
            display: block;
            margin-left: auto;
            margin-right: auto;
            text-align: center;
            color: #000;
            font-weight: bold;
            margin-bottom: 0px;
        }
        .profile-button img {
            width: 35px;
            height: 35px;
            border-radius: 10px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            padding: 0px 0px;
            cursor: pointer;
        }
        .profile-button:hover img {
            transform: scale(1.1);
            box-shadow: 4px 4px 5px rgba(0, 0, 0, 0.5);
        }
        .button-container {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        }
    </style>
</head>
<body>
<header>
    <div class = "profile-box">
        <div class="profile-icon">
            <a href="user_profiles.php" class="profile-button">
                <img src="https://img.freepik.com/premium-vector/black-silhouette-default-profile-avatar_664995-354.jpg?semt=ais_hybrid" alt="Profile Icon">
            </a>
            <div class="profile-info">
                <?php echo ucwords(strtolower(htmlspecialchars($username))) . " | " . ucwords(strtolower(htmlspecialchars($user_profile_name))); ?>
            </div>
        </div>
    </div>
    <h1>▶ CINEMY</h1>
    <form action="account.php" method="POST" style="display: inline;">
        <button type="submit" name="logout" class="logout-btn">Sign Out</button>
    </form>
</header>

<div class="menu-bar">
    <a href="user_homepage.php">Home</a>
    <a href="account.php" style="background-color: #666;">Account</a>
    <a href="reviews.php">Reviews</a>
    <a href="watchlists.php">Watchlist</a>
    <a href="browse.php">Browse</a>
    <div class="search-bar">
        <form action="search.php" method="GET">
            <input type="text" name="search_query" placeholder="Search by title, director, or actor" required>
            <button type="submit">Search</button>
        </form>
    </div>
</div>

<main>
    <?php if (!empty($error_message)): ?>
        <p class="error_message"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>
    <section class="account-info">
        <h2><span style="font-size: 22px; font-weight: bold;">Your Account Info:</span></h2>
        <p><strong>Profile:</strong> <?php echo ucwords(strtolower(htmlspecialchars($user_profile_name))); ?></p>
        <p><strong>Username:</strong> <?php echo ucwords(strtolower(htmlspecialchars($username))); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($user_account_email); ?></p>
        <p><strong>Payment Information:</strong> <?php echo ucwords(strtolower(htmlspecialchars($payment_info))); ?></p>
        <p><strong>Subscription Status:</strong> <?php echo ucwords(strtolower($subscription_status)); ?></p>
    </section>

    <section class="update-account-info">
        <h2><span style="font-size: 20px; font-weight: bold;">Update Account Details:</span></h2>

        <form action="account.php" method="POST" style="display: flex; flex-direction: column; align-items: flex-start; margin-bottom: 10px; max-width: 400px;">
            
            <label for="new_user_profile_name" style="width: 100%; font-weight: bold; margin-bottom: 8px;">Profile:</label>
            <input type="text" name="new_user_profile_name" id="new_user_profile_name" placeholder="Enter new profile name" style="width: 100%; padding: 8px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px;">
             
            <label for="new_username" style="width: 100%; font-weight: bold; margin-bottom: 8px;">Username:</label>
            <input type="text" name="new_username" id="new_username" placeholder="Enter new username" style="width: 100%; padding: 8px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px;">
            
            <label for="new_password" style="width: 100%; font-weight: bold; margin-bottom: 8px;">Password:</label>
            <input type="password" name="new_password" id="new_password" placeholder="Enter new password" style="width: 100%; padding: 8px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px;">
            
            <label for="new_email" style="width: 100%; font-weight: bold; margin-bottom: 8px;">Email:</label>
            <input type="email" name="new_email" id="new_email" placeholder="Enter new email" style="width: 100%; padding: 8px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px;">

            <label for="new_payment_info" style="width: 100%; font-weight: bold; margin-bottom: 8px;">Payment:</label>
            <input type="text" name="new_payment_info" id="new_payment_info" placeholder="Enter new payment info" style="width: 100%; padding: 8px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px;">
            
            <label for="new_subscription_status" style="width: 100%; font-weight: bold; margin-bottom: 8px;">Subscription:</label>
            <select name="new_subscription_status" id="new_subscription_status" style="width: 100%; width: 120px; padding: 8px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px;">
                <option value="No Change">No Change</option>
                <option value="Active"> Activate</option>
                <option value="Inactive">Deactivate</option>
            </select>

            <p style="margin-bottom: 8px;">Enter current password to confirm *:</p>
            <input type="password" name="current_password" id="current_password" placeholder="Enter current password" style="width: 100%; padding: 8px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px;">
            
            <div class="button-container">
                <button type="submit" class="update-btn" name="change">Confirm Changes</button>
                <button type="submit" class="delete-btn" name="delete">Delete Account</button>
            </div>
        </form>
</section>



</main>
</body>
</html>
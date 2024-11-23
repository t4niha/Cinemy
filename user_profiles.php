<?php
session_start();

// DB connection
require '_DB_connection.php';

// Check if the user is logged in, if not redirect to login page
if (!isset($_SESSION['user_account_id'])) {
    header("Location: login.php");
    exit();
}

// Retrieve session data
$username = $_SESSION['username'];  // The username stored during login
$user_account_id = $_SESSION['user_account_id'];    // The user ID stored during login

// Error essage initialization
$error_message = "";

// Sign out
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Retrieve profiles for the logged-in user
$stmt = $conn->prepare("SELECT * FROM user_profiles WHERE user_account_id = ?");
$stmt->bind_param("i", $user_account_id);
$stmt->execute();
$result = $stmt->get_result();
$profiles = [];
while ($row = $result->fetch_assoc()) {
    $profiles[] = $row;
}
$stmt->close();

// Select profile
if (isset($_POST['select_profile'])) {
    $_SESSION['user_profile_name'] = $_POST['selected_profile_name'];
    header("Location: user_homepage.php");
    exit();
}

// Delete profile
if (isset($_POST['delete_profile_id'])) {
    $profile_id = $_POST['delete_profile_id'];
    $stmt = $conn->prepare("DELETE FROM user_profiles WHERE user_profile_id = ? AND user_account_id = ?");
    $stmt->bind_param("ii", $profile_id, $user_account_id);
    $stmt->execute();
    $stmt->close();

    header("Location: user_profiles.php");
    exit();
}

// Create new profile
if (isset($_POST['new_profile_name'])) {
    $new_profile_name = $_POST['new_profile_name'];

    // Check if there are less than 3 profiles
    $stmt = $conn->prepare("SELECT COUNT(*) FROM user_profiles WHERE user_account_id = ?");
    $stmt->bind_param("i", $user_account_id);
    $stmt->execute();
    $stmt->bind_result($profile_count);
    $stmt->fetch();
    $stmt->close();

    if ($profile_count < 3) {
        if (strlen($new_profile_name) > 10) {
            $error_message = " Profile name cannot be more than 10 characters ";
        } 
        elseif (strpos($new_profile_name, ' ') !== false) {
            $error_message = " Profile name cannot contain spaces ";
        } 
        elseif (!preg_match('/^[a-zA-Z0-9]*$/', $new_profile_name)) {
            $error_message = " Profile name can only contain letters and numbers ";
        }
        else {
            // Check if profile name is unique within the user's account
            $stmt = $conn->prepare("SELECT COUNT(*) FROM user_profiles WHERE user_account_id = ? AND user_profile_name = ?");
            $stmt->bind_param("is", $user_account_id, $new_profile_name);
            $stmt->execute();
            $stmt->bind_result($existing_profile_count);
            $stmt->fetch();
            $stmt->close();
            if ($existing_profile_count == 0) {
                // Insert new profile
                $stmt = $conn->prepare("INSERT INTO user_profiles (user_profile_name, user_account_id) VALUES (?, ?)");
                $stmt->bind_param("si", $new_profile_name, $user_account_id);
                $stmt->execute();
                $stmt->close();

                header("Location: user_profiles.php");
                exit();
            } else {
                $error_message = " You already have a profile with this name";
            }
        }
    }
    else {
        $error_message = " You have reached the profile limit";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews - CineMy</title>
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
        .profile-img-box {
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
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); 
        }
        .form-inline {
            align-items: center;
            gap: 10px;
            margin-top: 15px;
        }
        .input-bar {
            width: 263px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 16px;
        }
        .profile-container {
            max-width: 450px;
            width: 80%;
            margin: 0 auto;
            margin-top: 20px;
            background-color: white;
            padding: 20px 20px 30px 20px; 
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .profile-box {
            background-color: #e0e0e0;
            padding: 10px;
            margin: 10px auto;
            border: 1px solid #ddd;
            border-radius: 5px;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .profile-name {
            flex-grow: 1;
            text-align: left;
            padding-left: 10px;
        }
        .profile-buttons {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .delete-btn {
            background-color: #872d2d;
            padding: 10px 20px;
            font-size: 14px;
            margin-top: 5px;
            text-decoration: none;
            border-radius: 5px;
            color: white;
            transition: background-color 0.3s ease;
            display: block;
            width: 80px;
            text-align: center;
            cursor: pointer;
            border: none;
        }
        .delete-btn:hover {
            background-color: #b34646;
        }
        .enter-btn {
            background-color: #555;
            padding: 10px 20px;
            font-size: 14px;
            margin-top: 5px;
            text-decoration: none;
            border-radius: 5px;
            color: white;
            transition: background-color 0.3s ease;
            display: block;
            width: 80px;
            text-align: center;
            cursor: pointer;
            border: none;
        }
        .enter-btn:hover {
            background-color: #777;
        }
        .new-profile-input {
            margin-top: 2px;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
            box-sizing: border-box;
            height: 36px;
            width: 100%;
            max-width: 260px;
        }
        .btn {
            background-color: #555;
            color: white;
            height: 38px;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            border: none;
            outline: none;
            box-shadow: none;
            border: 1px solid #ddd;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #777;
        }
        .choose-profile {
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
        .profile-pic {
            width: 50px; 
            height: 50px; 
            border-radius: 10px; 
            margin-right: 10px;
            border: 1.5px solid #777;
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
    </style>
</head>
<body>
<header>
    <div class = "profile-img-box">
        <div class="profile-icon">
            <a href="user_profiles.php" class="profile-button">
                <img src="https://img.freepik.com/premium-vector/black-silhouette-default-profile-avatar_664995-354.jpg?semt=ais_hybrid" alt="Profile Icon">
            </a>
            <div class="profile-info">
                <?php echo ucwords(strtolower(htmlspecialchars($username))); ?>
            </div>
        </div>
    </div>
    <h1>▶ CINEMY</h1>
    <form action="user_homepage.php" method="POST" style="display: inline;">
        <button type="submit" name="logout" class="logout-btn">Sign Out</button>
    </form>
</header>

<div class="menu-bar">
</div>

<main>
    <!-- Display error message at the top of the main content area -->
    <?php if (isset($error_message)): ?>
        <p class="error_message"><?php echo htmlspecialchars($error_message); ?></p>
    <?php elseif (count($profiles) >= 3): ?>
        <p class="error_message"> Profile limit reached </p>
    <?php endif; ?>

    <div class="profile-container">
        <div class="choose-profile">
            <p>Choose Profile:</p>
        </div>

        <?php if (count($profiles) > 0): ?>
            <?php foreach ($profiles as $profile): ?>
                <div class="profile-box">
                    <div>
                        <img src="https://img.freepik.com/premium-vector/black-silhouette-default-profile-avatar_664995-354.jpg?semt=ais_hybrid" alt="Profile Image" class="profile-pic">
                    </div>
                    <div class="profile-name">
                        <p><?php echo htmlspecialchars(ucwords(strtolower($profile['user_profile_name']))); ?></p>
                    </div>
                    <div class="profile-buttons">
                    <form action="user_profiles.php" method="POST" style="display:inline;">
                        <input type="hidden" name="username" value="<?php echo $username; ?>">
                        <input type="hidden" name="selected_profile_name" value="<?php echo $profile['user_profile_name']; ?>">
                        <input type="hidden" name="user_account_id" value="<?php echo $user_account_id; ?>">
                        <button type="submit" name="select_profile" class="enter-btn">Select</button>
                    </form>
                        <form action="user_profiles.php" method="POST" style="display:inline;">
                            <input type="hidden" name="delete_profile_id" value="<?php echo $profile['user_profile_id']; ?>">
                            <button type="submit" class="delete-btn">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Wrap the "No profiles found" message inside a profile-box with centered text -->
            <div class="profile-box">
                <p>No profiles found. Create a new one below.</p>
            </div>
        <?php endif; ?>
        <div>
            <form class="form-inline" action="user_profiles.php" method="POST">
                <input class="input-bar" type="text" name="new_profile_name" placeholder="Enter profile name" required class="new-profile-input">
                <button type="submit" class="btn">Create New Profile</button>
            </form>
        </div>
    </div>
</main>

</body>
</html>

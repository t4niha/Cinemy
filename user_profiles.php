<?php
session_start();  // Start the session

// Check if the user is logged in, if not redirect to login page
if (!isset($_SESSION['user_account_id'])) {
    header("Location: login.php");
    exit();
}

// Retrieve session data
$username = $_SESSION['username'];  // The username stored during login
$user_account_id = $_SESSION['user_account_id'];    // The user ID stored during login

// Database connection
$host = "localhost";
$dbname = "ott";
$dbusername = "root";
$dbpassword = "";

// Create MySQLi connection
$mysqli = new mysqli($host, $dbusername, $dbpassword, $dbname);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

if (isset($_POST['select_profile'])) {
    $_SESSION['user_profile_name'] = $_POST['selected_profile_name'];
    header("Location: user_homepage.php");
    exit();
}

// Handle delete profile action
if (isset($_POST['delete_profile_id'])) {
    $profile_id = $_POST['delete_profile_id'];
    $stmt = $mysqli->prepare("DELETE FROM user_profiles WHERE user_profile_id = ? AND user_account_id = ?");
    $stmt->bind_param("ii", $profile_id, $user_account_id);
    $stmt->execute();
    $stmt->close();
}

// Handle create new profile action
if (isset($_POST['new_profile_name'])) {
    $new_profile_name = $_POST['new_profile_name'];

    // Check if there are fewer than 3 profiles
    $stmt = $mysqli->prepare("SELECT COUNT(*) FROM user_profiles WHERE user_account_id = ?");
    $stmt->bind_param("i", $user_account_id);
    $stmt->execute();
    $stmt->bind_result($profile_count);
    $stmt->fetch();
    $stmt->close();

    if ($profile_count < 3) {
        // Check if profile name is unique within the user's account
        $stmt = $mysqli->prepare("SELECT COUNT(*) FROM user_profiles WHERE user_account_id = ? AND user_profile_name = ?");
        $stmt->bind_param("is", $user_account_id, $new_profile_name);
        $stmt->execute();
        $stmt->bind_result($existing_profile_count);
        $stmt->fetch();
        $stmt->close();

        if ($existing_profile_count == 0) {
            // Insert new profile if name is unique
            $stmt = $mysqli->prepare("INSERT INTO user_profiles (user_profile_name, user_account_id) VALUES (?, ?)");
            $stmt->bind_param("si", $new_profile_name, $user_account_id);
            $stmt->execute();
            $stmt->close();
        } else {
            $error_message = "*** Profile name already exists ***";
        }
    }
}

// Retrieve profiles for the logged-in user
$stmt = $mysqli->prepare("SELECT * FROM user_profiles WHERE user_account_id = ?");
$stmt->bind_param("i", $user_account_id);
$stmt->execute();
$result = $stmt->get_result();
$profiles = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profiles - CineMy</title>
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
        .profile-container {
            max-width: 450px;
            width: 80%;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
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
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            border: none;
            outline: none;
            box-shadow: none;
            border: 1px solid #ddd;
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
    </style>
</head>
<body>
<header>
    <h1>▶ CINEMY</h1>
</header>
<main>
    <!-- Display error message at the top of the main content area -->
    <?php if (isset($error_message)): ?>
        <p class="error_message"><?php echo htmlspecialchars($error_message); ?></p>
    <?php elseif (count($profiles) >= 3): ?>
        <p class="error_message">*** Profile limit reached ***</p>
    <?php endif; ?>

    <div class="profile-container">
        <div class="choose-profile">
            <p>Choose Profile:</p>
        </div>

        <?php if (count($profiles) > 0): ?>
            <?php foreach ($profiles as $profile): ?>
                <div class="profile-box">
                    <div class="profile-image">
                        <!-- Default profile image in a rounded square box -->
                        <img src="https://img.freepik.com/premium-vector/black-silhouette-default-profile-avatar_664995-354.jpg?semt=ais_hybrid" alt="Profile Image" class="profile-img" style="width: 50px; height: 50px; border-radius: 10px; margin-right: 10px;">
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

        <div class="new-profile-container">
            <form action="user_profiles.php" method="POST">
                <input type="text" name="new_profile_name" placeholder="Enter profile name" required class="new-profile-input">
                <button type="submit" class="btn" <?php echo count($profiles) >= 3 ? 'disabled' : ''; ?>>Create New Profile</button>
            </form>
        </div>
    </div>
</main>
</body>
</html>
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
    <title>Reviews - CineMy</title>
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
            padding: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            box-sizing: border-box;
        }
        .profile-icon {
            display: flex;
            align-items: center;
            flex-direction: column;
            text-align: center;
        }
        .profile-icon img {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            margin-bottom: 5px;
        }
        .profile-info {
            font-size: 14px;
            color: #fff;
        }
        header h1 {
            margin: 0;
            flex-grow: 1;
            text-align: center;
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
        }
        .logout-btn:hover {
            background-color: #777;
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
            padding: 5px 100px;
            text-align: center;
        }
        .reviews {
            background-color: #fff;
            padding: 20px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: relative;
            border: 1px solid #ddd;
            margin: 10px 0;
            text-align: left;
        }
        .profile-button img {
        width: 35px;
        height: 35px;
        border-radius: 10px;
        transition: transform 0.3s ease;

        padding: 0px 0px;
        }
        .profile-button:hover img {
            transform: scale(1.1);
        }
    </style>
</head>
<body>
<header>
    <div class="profile-icon">
        <a href="user_profiles.php" class="profile-button">
            <img src="https://img.freepik.com/premium-vector/black-silhouette-default-profile-avatar_664995-354.jpg?semt=ais_hybrid" alt="Profile Icon">
        </a>
        <div class="profile-info">
            <?php echo ucwords(strtolower(htmlspecialchars($username))) . " | " . ucwords(strtolower(htmlspecialchars($user_profile_name))); ?>
        </div>
    </div>
    <h1>▶ CINEMY</h1>
    <form action="account.php" method="POST" style="display: inline;">
        <button type="submit" name="logout" class="logout-btn">Sign Out</button>
    </form>
</header>

<div class="menu-bar">
    <a href="user_homepage.php">Home</a>
    <a href="account.php">Account</a>
    <a href="reviews.php" style="background-color: #666;">Reviews</a>
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
    <section class="reviews">
        <?php if ($subscription_status == 'active'): ?>
                <h2>Your Account Info:</h2>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
                <p><strong>Profile Name:</strong> <?php echo htmlspecialchars($user_profile_name); ?></p>
            <?php else: ?>
                <div class="sorry-message">
                    <h3>Sorry, you must have an active subscription to access reviews.</h3>
                </div>
        <?php endif; ?>
    </section>
</main>

</body>
</html>

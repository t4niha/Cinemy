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
$admin_account_id = $_SESSION['user_account_id'];
$username = $_SESSION['username'];

// Error essage initialization
$error_message = "";

// Sign out
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// DB query: count number of movies
$query = "SELECT COUNT(*) AS account_count FROM user_accounts";
$result = $conn->query($query);
$account_count = $result->fetch_assoc()['account_count'];

// DB query: count number of series
$query = "SELECT COUNT(*) AS profile_count FROM user_profiles";
$result = $conn->query($query);
$profile_count = $result->fetch_assoc()['profile_count'];

// DB query = create user view
$query = "DROP VIEW IF EXISTS user_details";
$conn->query($query);
$viewquery = 
    "CREATE VIEW IF NOT EXISTS user_details AS
    SELECT
        ua.user_account_id AS user_id,
        ua.username,
        COUNT(up.user_profile_name) AS number_of_profiles,
        ua.user_account_email AS email,
        ua.password,
        ua.subscription_status AS sub_status,
        ua.payment_info,
        -- Calculate the total number of reviews written (movies + series)
        (SELECT COUNT(*) 
         FROM movie_reviews mr 
         WHERE mr.user_profile_id IN (SELECT up.user_profile_id 
                                       FROM user_profiles up 
                                       WHERE up.user_account_id = ua.user_account_id)) +
        (SELECT COUNT(*) 
         FROM series_reviews sr 
         WHERE sr.user_profile_id IN (SELECT up.user_profile_id 
                                       FROM user_profiles up 
                                       WHERE up.user_account_id = ua.user_account_id)) 
         AS number_of_reviews,
        -- Calculate the total number of watchlists (movies + series)
        (SELECT COUNT(*) 
         FROM movie_watchlists mw 
         WHERE mw.movie_watchlist_id IN (SELECT w.list_id 
                                        FROM watchlists w 
                                        WHERE w.user_profile_id IN (SELECT up.user_profile_id 
                                                                    FROM user_profiles up 
                                                                    WHERE up.user_account_id = ua.user_account_id))) +
        (SELECT COUNT(*) 
         FROM series_watchlists sw 
         WHERE sw.series_watchlist_id IN (SELECT w.list_id 
                                        FROM watchlists w 
                                        WHERE w.user_profile_id IN (SELECT up.user_profile_id 
                                                                    FROM user_profiles up 
                                                                    WHERE up.user_account_id = ua.user_account_id))) 
         AS watchlist_size
    FROM user_accounts ua
    LEFT JOIN user_profiles up ON ua.user_account_id = up.user_account_id
    GROUP BY ua.user_account_id;";
$conn->query($viewquery);
$result = $conn->query("SELECT * FROM user_details");
$user_table = [];
while ($row = $result->fetch_assoc()) {
    $user_table[] = $row;
}

// Initialize default values
$filtered_list = $user_table;
$filter_type = $_POST['filter_type'] ?? 'all';
$filter_order_by = $_POST['filter_order_by'] ?? 'user_id';
$filter_order = $_POST['filter_order'] ?? 'ASC';

// Filter & sort inputs
if (isset($_POST['filter_list'])) {
    $filter_type = $_POST['filter_type'];
    $filter_order_by = $_POST['filter_order_by'];
    $filter_order = $_POST['filter_order'];
}

// Filter
$filtered_list = array_filter($user_table, function ($content) use ($filter_type) {
    if (strtolower($filter_type) !== 'all' && strtolower($content['sub_status']) !== strtolower($filter_type)) {
        return false; }
    return true;
});

// Sort
usort($filtered_list, function ($a, $b) use ($filter_order_by, $filter_order) {
    $value_a = $a[$filter_order_by];
    $value_b = $b[$filter_order_by];

    $comparison = 0;
    if (is_string($value_a) && is_string($value_b)) {
        $comparison = strcmp($value_a, $value_b);
    } else {
        $comparison = $value_a <=> $value_b;
    }
    return ($filter_order === 'ASC') ? $comparison : -$comparison;
});

if (isset($_POST['delete_acc'])) {
    // DB query: Delete user profiles
    $delete_account_id = $_POST['delete_acc'];
    $query = "DELETE FROM user_profiles WHERE user_account_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $delete_account_id);
    $stmt->execute();

    // DB query: Delete user account
    $query = "DELETE FROM user_accounts WHERE user_account_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $delete_account_id);
    $stmt->execute();

    header("Location: admin_table_users.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - CineMy</title>
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
        main {
            padding: 5px 100px;
            text-align: center;
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
        .profile-button img {
            width: 35px;
            height: 35px;
            border-radius: 10px;
            transition: transform 0.3s ease;
            padding: 0px 0px;
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
        .content-box {
            background-color: #ddd;
            padding: 0.25px;
            text-align: left;
            align-items: left;
            margin-bottom: 10px;
        }
        .box {
            padding: 2px;
            border: none;
            font-size: 14px;
            text-align: center;
            background-color: #f9f9f9;
        }
        .delete-btn { 
            background-color: #872d2d;
            text-decoration: none;
            transition: background-color 0.3s ease;
            border: none;
            font-size: 12px;
            padding: 3px 10px;
            color: white; 
            border: none;
            cursor: pointer;
        }
        .delete-btn:hover { 
            background-color: #b34646;
        }
        .filter {
            background-color: #555;
            color: white;
            padding: 5px;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            display: block;
            width: 60px;
            text-align: center;
            border: none;
            cursor: pointer;
        }
        .filter:hover {
            background-color: #777;
        }
        .filter-box {
            background-color: none;
            padding: 5px 20px 20px 20px;
            max-height: 5px;
            position: relative;
            border: none;
            margin: 0px;
            text-align: left;
        }
        .filter-form {
            display: inline-flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            width: 100%;
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
    <form action="user_homepage.php" method="POST" style="display: inline;">
        <button type="submit" name="logout" class="logout-btn">Sign Out</button>
    </form>
</header>

<div class="menu-bar">
    <a href="admin_account.php">Account</a>
    <a href="admin_picks.php">Picks</a>
    <a href="admin_upload.php">Upload</a>
    <a href="admin_table_content.php">Contents</a>
    <a href="admin_table_users.php" style="background-color: #666;">Users</a>
    <div class="search-bar">
        <form action="admin_search.php" method="GET">
            <input type="text" name="search_query" placeholder="Search by title, director, or actor" required>
            <button type="submit">Search</button>
        </form>
    </div>
</div>

<main>
    <h3 style="color: #333">Users in Database:</h3>
    <p style="color: #333">(<strong>Accounts: </strong><?php echo htmlspecialchars($account_count);?> | <strong>Profiles: </strong><?php echo htmlspecialchars($profile_count);?>)</p>
    <div class="filter-box">
        <form action="admin_table_users.php" method="POST" class="filter-form">
            <!-- Subscription filter -->
            <label for="filter_type">Subscription: </label>
            <select name="filter_type" id="filter_type" style="width: 100%; width: 220px; border: 1px solid #ccc; border-radius: 4px;">
                <option value="all" <?php echo ($filter_type == 'all') ? 'selected' : ''; ?>>All</option>
                <option value="active" <?php echo ($filter_type == 'active') ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo ($filter_type == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
            </select>

            <!-- Order by -->
            <label for="filter_order_by">Sort by: </label>
            <select name="filter_order_by" id="filter_order_by" style="width: 100%; width: 220px; border: 1px solid #ccc; border-radius: 4px;">
                <option value="user_id" <?php echo ($filter_order_by == 'user_id') ? 'selected' : ''; ?>>Date Created</option>
                <option value="username" <?php echo ($filter_order_by == 'username') ? 'selected' : ''; ?>>Username</option>
                <option value="number_of_profiles" <?php echo ($filter_order_by == 'number_of_profiles') ? 'selected' : ''; ?>>Profiles</option>
                <option value="email" <?php echo ($filter_order_by == 'email') ? 'selected' : ''; ?>>Email</option>
            </select>

            <!-- Order -->
            <label for="filter_order">Order: </label>
            <select name="filter_order" id="filter_order" style="width: 100%; width: 220px; border: 1px solid #ccc; border-radius: 4px;">
                <option value="ASC" <?php echo ($filter_order == 'ASC') ? 'selected' : ''; ?>>Ascending</option>
                <option value="DESC" <?php echo ($filter_order == 'DESC') ? 'selected' : ''; ?>>Descending</option>
            </select>

            <!-- Submit Button -->
                <input type="hidden" name="filter_list">
                <button type="submit" class="filter">Filter</button>
        </form>
    </div><br>
    <div class="content-box">
        <table style="width: 100%">
            <thead>
                <tr>
                    <th class="box">Username</th>
                    <th class="box">Profiles</th>
                    <th class="box">Email</th>
                    <th class="box">Password</th>
                    <th class="box">Subscription</th>
                    <th class="box">Payment</th>
                    <th class="box">Reviews</th>
                    <th class="box">Watchlist</th>
                    <th class="box"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filtered_list as $user): ?>
                    <tr>
                        <td class="box"> <?php echo htmlspecialchars($user['username']); ?> </td>
                        <td class="box"> <?php echo htmlspecialchars($user['number_of_profiles']); ?> </td>
                        <td class="box"> <?php echo htmlspecialchars($user['email']); ?> </td>
                        <td class="box"> <?php echo htmlspecialchars($user['password']); ?> </td>
                        <td class="box"> <?php echo htmlspecialchars($user['sub_status']); ?> </td>
                        <td class="box"> <?php echo htmlspecialchars($user['payment_info']); ?> </td>
                        <td class="box"> <?php echo htmlspecialchars($user['number_of_reviews']); ?> </td>
                        <td class="box"> <?php echo htmlspecialchars($user['watchlist_size']); ?> </td>
                        <td class="box">
                            <form action="admin_table_users.php" method="POST">
                                <input type="hidden" name="delete_acc" value="<?php echo htmlspecialchars($user['user_id']);?>">
                                <button type="submit" class="delete-btn" name="delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>
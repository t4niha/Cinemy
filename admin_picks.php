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

// Error essage initialization
$error_message = "";

// Sign out
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// DB query: retrieve list of genres
$query = "SELECT genre FROM genres";
$result = $conn->query($query);
$genre_list = [];
while ($row = $result->fetch_assoc()) {
    $genre_list[] = $row['genre'];
}

// DB query: delete movie pick
if (isset($_POST['delete_movie_pick'])) {
    $delete_movie_id = $_POST['delete_movie_pick'];
    $query = 
        "DELETE FROM admin_picks
        WHERE movie_id = ? AND admin_account_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $delete_movie_id, $user_account_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_picks.php");
    exit();
}

// DB query: delete series pick
if (isset($_POST['delete_series_pick'])) {
    $delete_series_id = $_POST['delete_series_pick'];
    $query = 
        "DELETE FROM admin_picks
        WHERE series_id = ? AND user_account_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $delete_series_id, $user_account_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_picks.php");
    exit();
}

// DB query: retrieve picked movies and series
$query = 
        "SELECT ap.admin_pick_id AS admin_pick_id, ap.date_added AS date_added,
        m.movie_id AS content_id, 'movie' AS content_type, m.movie_title AS title, m.release_date AS release_date, ap.admin_pick_review AS review_text
        FROM admin_picks ap
        JOIN movies m ON m.movie_id = ap.movie_id
        WHERE ap.admin_account_id = ?
        UNION ALL 
        SELECT ap.admin_pick_id AS admin_pick_id, ap.date_added AS date_added,
        s.series_id AS content_id, 'series' AS content_type, s.series_title AS title, s.release_date AS release_date, ap.admin_pick_review AS review_text
        FROM admin_picks ap
        JOIN series s ON s.series_id = ap.series_id
        WHERE ap.admin_account_id = ?
        ORDER BY admin_pick_id DESC;";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $user_account_id, $user_account_id);
$stmt->execute();
$result = $stmt->get_result();
$pick_list = [];
while ($rows = $result->fetch_assoc()) {
    $pick_list[] = $rows;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Picks - CineMy</title>
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
            background-color: #fff;
            padding: 20px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border: 1px solid #ddd;
            margin: 10px 0;
            text-align: left;
            align-items: left;
        }
        .list-item-box {
            background-color: #fff;
            padding: 20px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border: 1px solid #ddd;
            margin: 10px 0;
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
        .go-to-link {
            background-color: #555;
            color: white;
            width: 110px;
            padding: 10px 10px;
            text-align: center;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            cursor: pointer;
            border: none;
        }

        .go-to-link:hover {
            background-color: #777;
        }
        .delete {
            background-color: #872d2d;
            color: white;
            width: 110px;
            padding: 10px 10px;
            text-align: center;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            cursor: pointer;
            border: none;
        }
        .delete:hover {
            background-color: #b34646;
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
        </div>
    </div>
    <h1>▶ CINEMY</h1>
    <form action="user_homepage.php" method="POST" style="display: inline;">
        <button type="submit" name="logout" class="logout-btn">Sign Out</button>
    </form>
</header>

<div class="menu-bar">
    <a href="admin_account.php">Account</a>
    <a href="admin_picks.php" style="background-color: #666;">Picks</a>
    <a href="admin_upload.php">Upload</a>
    <a href="admin_table_content.php">Contents</a>
    <a href="admin_table_users.php">Users</a>
    <div class="search-bar">
        <form action="admin_search.php" method="GET">
            <input type="text" name="search_query" placeholder="Search by title, director, or actor" required>
            <button type="submit">Search</button>
        </form>
    </div>
</div>

<main>
<h3 style="color: #333">Your Recent Picks:</h3>
    <?php if ($pick_list): ?>
        <?php foreach ($pick_list as $pick): ?>
            <div class="list-item-box">
                <!-- Display details -->
                <p><span style="font-size: 20px; font-weight: bold;"><?php echo htmlspecialchars($pick['title']); ?></span>
                <span style="font-size: 20px;">(<?php $release_date = new DateTime($pick['release_date']); echo $release_date->format('Y');?>)</span><br>
                <span style="font-size: 15px;"><em>"<?php echo htmlspecialchars($pick['review_text']); ?>"</em></span><br>
                <span style="font-size: 14px;">~ <?php echo htmlspecialchars($pick['date_added']) . '</span>'; ?>

                <!-- Go to Page & delete buttons -->
                <?php if ($pick['content_type'] == 'movie'): ?>
                    <div class="button-container">
                        <!-- Movies -->
                        <form action="admin_picks.php" method="POST">
                            <input type="hidden" name="delete_movie_pick" value="<?php echo $pick['content_id']; ?>">
                            <button type="submit" class="delete">Delete</button>
                        </form>
                        <form action="admin_movie.php" method="POST">
                            <input type="hidden" name="content_id" value="<?php echo $pick['content_id']; ?>">
                            <button type="submit" class="go-to-link">Go to Page</button>
                        </form>
                    </div>
                <?php elseif ($pick['content_type'] == 'series'): ?>
                    <div class="button-container">
                        <!-- Series -->
                        <form action="admin_picks.php" method="POST">
                            <input type="hidden" name="delete_series_pick" value="<?php echo $pick['content_id']; ?>">
                            <button type="submit" class="delete">Delete</button>
                        </form>
                        <form action="admin_series.php" method="POST">
                            <input type="hidden" name="content_id" value="<?php echo $pick['content_id']; ?>">
                            <button type="submit" class="go-to-link">Go to Page</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="list-item-box">
            <div class="sorry-message">
                <h3>Nothing here!</h3>
            </div>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
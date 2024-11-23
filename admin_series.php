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

if (isset($_GET['series_id'])) {
    $series_id = $_GET['series_id'];
} else {
    $series_id = $_POST['content_id'];
}

// Sign out
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// DB query: calculate average user rating
$query = "SELECT AVG(rating) FROM series_reviews WHERE series_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $series_id);
$stmt->execute();
$stmt->bind_result($avg_rating);
$stmt->fetch();
$stmt->close();

// Find number of reviews written
$query = "SELECT COUNT(rating) FROM series_reviews WHERE series_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $series_id);
$stmt->execute();
$stmt->bind_result($num_reviews);
$stmt->fetch();
$stmt->close();

// Find number of watchlists added to 
$query = "SELECT COUNT(series_watchlist_id) FROM series_watchlists WHERE series_id =?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $series_id);
$stmt->execute();
$stmt->bind_result($num_watchlists);
$stmt->fetch();
$stmt->close();

// DB query: retrieve all series info
$query =
    "SELECT 
        m.series_id AS id, m.series_title AS title, m.series_description AS description, m.imdb_rating, m.rotten_tomatoes_rating, m.release_date, m.series_link AS link,
        GROUP_CONCAT(DISTINCT g.genre ORDER BY g.genre ASC SEPARATOR ' | ') AS genres,
        '' AS director,
        GROUP_CONCAT(DISTINCT CONCAT(a.actor_name, ' as ', aim.role) ORDER BY a.actor_name ASC SEPARATOR ' | ') AS actors,
        GROUP_CONCAT(DISTINCT CONCAT(ma.award_org, ' for ', ma.award_title, ' (', ma.award_status, ')') ORDER BY ma.award_org ASC SEPARATOR ' | ') AS awards
    FROM series m
    LEFT JOIN series_genres mg ON m.series_id = mg.series_id
    LEFT JOIN genres g ON mg.genre_id = g.genre_id
    LEFT JOIN acted_in_series aim ON m.series_id = aim.series_id
    LEFT JOIN actors a ON aim.series_actor_id = a.actor_id
    LEFT JOIN series_awards ma ON m.series_id = ma.series_id
    WHERE m.series_id = ?;";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $series_id);
$stmt->execute();
$result = $stmt->get_result();
$content_info = $result->fetch_assoc();
$stmt->close();

// DB query: get this admin's review for this series
$admin_pick_review = '';
$pick_type = 'series';
$query = "SELECT admin_pick_review, date_added FROM admin_picks WHERE admin_account_id = ? AND admin_pick_type = ? AND series_ID = ?;";
$stmt = $conn->prepare($query);
$stmt->bind_param("isi", $user_account_id, $pick_type, $series_id);
$stmt->execute();
$result = $stmt->get_result();
$pick_details = [];
if ($row = $result->fetch_assoc()) {
    $pick_details['admin_pick_review'] = $row['admin_pick_review'];
    $pick_details['date_added'] = $row['date_added'];
}

if (isset($_POST['delete_content'])) {
    $delete_id = $_POST['delete_content'];
    //DB query: delete from tables
    $queries = [
        "DELETE FROM series_genres WHERE series_id = ?",
        "DELETE FROM acted_in_series WHERE series_id = ?",
        "DELETE FROM series_reviews WHERE series_id = ?",
        "DELETE FROM series_watchlists WHERE series_id = ?",
        "DELETE FROM series_awards WHERE series_id = ?",
        "DELETE FROM admin_picks WHERE series_id = ?",
        "DELETE FROM series WHERE series_id = ?"
    ];
    foreach ($queries as $query) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: admin_series.php?series_id=$delete_id");
    exit();
}

// Add pick review
if (isset($_POST['post_pick'])) {
    $add_series_id = $_POST['post_pick'];
    $admin_pick_review = $_POST['pick_review'];
    $admin_pick_review = str_replace(['"', "'"], '', $admin_pick_review);
    $date_added = date('Y-m-d');
    $admin_pick_type = 'series';
    $admin_account_id = $user_account_id;
    // DB query: add series pick
    $query = 
        "INSERT INTO admin_picks (admin_pick_type, series_id, admin_account_id, admin_pick_review, date_added)
        VALUES (?,?,?,?,?);";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('siiss', $admin_pick_type, $add_series_id, $admin_account_id, $admin_pick_review, $date_added);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_series.php?series_id=$add_series_id");
    exit();
}

// Delete pick review
if (isset($_POST['delete_pick'])) {
    $delete_series_id = $_POST['delete_pick'];
    // DB query: delete series pick
    $query = 
        "DELETE FROM admin_picks
        WHERE series_id = ? AND admin_account_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $delete_series_id, $user_account_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_series.php?series_id=$delete_series_id");
    exit();
}

$genres = [];
$actors = [];
$awards = [];

if(isset($_POST['update_content'])) {
    $series_id = $_POST['update_content'];

    // Delete from tables
    $queries = [
        "DELETE FROM series_genres WHERE series_id = ?",
        "DELETE FROM acted_in_series WHERE series_id = ?",
        "DELETE FROM series_awards WHERE series_id = ?"
    ];
    foreach ($queries as $query) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $series_id);
        $stmt->execute();
        $stmt->close();
    }

    // Content details
    $title = ucwords(strtolower(str_replace(['"', "'"], '', trim($_POST['title']))));
    $link = trim($_POST['link']);
    $description = str_replace(['"', "'"], '', trim($_POST['description']));
    $release_date = $_POST['release_date'];
    $director = ucwords(strtolower(str_replace(['"', "'"], '', trim($_POST['director']))));
    $imdb_rating = (float)$_POST['imdb'] ?? null;
    $rotten_rating = (int)$_POST['rotten'] ?? null;

    // Genres
    $genres = [ucwords(strtolower(str_replace(['"', "'"], '', trim($_POST['genre1'])))), 
                ucwords(strtolower(str_replace(['"', "'"], '', trim($_POST['genre2'])))), 
                ucwords(strtolower(str_replace(['"', "'"], '', trim($_POST['genre3']))))];

    // Actors and roles
    $actors = [
        ['name' => ucwords(strtolower(str_replace(['"', "'"], '', trim($_POST['actor1'])))), 
        'role' => ucwords(strtolower(str_replace(['"', "'"], '', trim($_POST['role1']))))],
        ['name' => ucwords(strtolower(str_replace(['"', "'"], '', trim($_POST['actor2'])))), 
        'role' => ucwords(strtolower(str_replace(['"', "'"], '', trim($_POST['role2']))))],
        ['name' => ucwords(strtolower(str_replace(['"', "'"], '', trim($_POST['actor3'])))), 
        'role' => ucwords(strtolower(str_replace(['"', "'"], '', trim($_POST['role3']))))]
    ];

    // Awards
    $awards = [
        ['title' => ucwords(strtolower(str_replace(['"', "'"], '', trim($_POST['award1'])))), 
        'org' => ucwords(str_replace(['"', "'"], '', trim($_POST['org1']))), 
        'status' => $_POST['status1']],
        ['title' => ucwords(strtolower(str_replace(['"', "'"], '', trim($_POST['award2'])))), 
        'org' => ucwords(str_replace(['"', "'"], '', trim($_POST['org2']))), 
        'status' => $_POST['status2']],
        ['title' => ucwords(strtolower(str_replace(['"', "'"], '', trim($_POST['award3'])))), 
        'org' => ucwords(str_replace(['"', "'"], '', trim($_POST['org3']))), 
        'status' => $_POST['status3']]
    ];

    // DB query: update series
    $stmt = $conn->prepare("UPDATE series 
                            SET series_title = ?, series_link = ?, series_description = ?, imdb_rating = ?, rotten_tomatoes_rating = ?, release_date = ? 
                            WHERE series_id = ?");
    $stmt->bind_param("sssdisi", $title, $link, $description, $imdb_rating, $rotten_rating, $release_date, $series_id);
    $stmt->execute();
    $stmt->close();

    // DB query: add genres
    foreach ($genres as $genre) {
        if (!empty($genre)) {
            $stmt = $conn->prepare("SELECT genre_id FROM genres WHERE genre = ?");
            $stmt->bind_param("s", $genre);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $genre_id = $result->fetch_assoc()['genre_id'];
            } else {
                $stmt = $conn->prepare("INSERT INTO genres (genre) VALUES (?)");
                $stmt->bind_param("s", $genre);
                $stmt->execute();
                $genre_id = $conn->insert_id;
            }
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO series_genres (series_id, genre_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $series_id, $genre_id);
            $stmt->execute();
            $stmt->close();
        }
    }
    // DB query: add actors
    foreach ($actors as $actor) {
        if (!empty($actor['name'])) {
            $stmt = $conn->prepare("SELECT actor_id FROM actors WHERE actor_name = ?");
            $stmt->bind_param("s", $actor['name']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $actor_id = $result->fetch_assoc()['actor_id'];
            } else {
                $stmt = $conn->prepare("INSERT INTO actors (actor_name) VALUES (?)");
                $stmt->bind_param("s", $actor['name']);
                $stmt->execute();
                $actor_id = $conn->insert_id;
            }
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO acted_in_series (series_actor_id, series_id, role) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $actor_id, $series_id, $actor['role']);
            $stmt->execute();
            $stmt->close();
        }
    }
    // DB query: add awards
    foreach ($awards as $award) {
        if (!empty($award['title']) && !empty($award['org'])) {
            $stmt = $conn->prepare("INSERT INTO series_awards (series_id, award_org, award_title, award_status) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $series_id, $award['org'], $award['title'], $award['status']);
            $stmt->execute();
            $stmt->close();
        }
    }
    header("Location: admin_series.php?series_id=$series_id");
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Series - CineMy</title>
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
            gap: 10px;
            flex-direction: row;
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
            padding: 0px 0px;
        }
        .content-box {
            background-color: #fff;
            padding: 20px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: relative;
            border: 1px solid #ddd;
            margin: 10px 0;
            text-align: left;
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
        .submit {
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
        .submit:hover {
            background-color: #777;
        }
        .enter-review textarea {
            padding: 10px;
            font-size: 16px;
            border: 1.5px solid #ccc;
            border-radius: 5px;
            width: 946px;
            height: 100px;
            resize: none;
            overflow: auto;
        }
        .enter-rating input {
            border: 1.5px solid #ccc;
            margin-left: 5px;
            border-radius: 5px;
            width: 65px;
        }
        .none-box {
            background-color: #fff;
            padding: 5px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: relative;
            margin: 10px 0;
            text-align: center;
        }
        .addremove_button {
            background-color: #555;
            color: white;
            padding: 10px 10px;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            display: block;
            width: 170px;
            margin-top: 10px;
            margin-left: auto;
            text-align: center;
            cursor: pointer;
            border: none;
        }
        .addremove_button:hover {
            background-color: #777;
        }
        .review-profile-pic {
            width: 50px; 
            height: 50px; 
            border-radius: 10px; 
            margin-right: 10px;
            border: 1.5px solid #777;
        }
        .review-profile-info{
            text-align: left;
        }
        .review-profile-box {
            width: 100%;
            align-items: top left;
            display: flex;
            gap: 5px;
            flex-direction: row;
        }
        .delete-btn { 
            background-color: #872d2d;
            color: white;
            padding: 10px 10px;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            display: block;
            width: 110px;
            margin-top: 10px;
            margin-left: auto;
            text-align: center;
            cursor: pointer;
            border: none;
        }
        .delete-btn:hover { 
            background-color: #b34646;
        }
        .description textarea {
            padding: 10px;
            font-size: 14.5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 929px;
            margin-top: 5px;
            margin-left: 5px;
            margin-bottom: 2px;
            height: 100px;
            resize: none;
            overflow: auto;
        }
        .title-input-bar { 
            width: 100%; 
            max-width: 800px; 
            padding: 10px; 
            margin: 5px 0; 
            border-radius: 5px; 
            border: 1px solid #ccc;
            margin-left: 5px;
        }
        .input-bar { 
            width: 100%; 
            max-width: 448px; 
            padding: 10px; 
            margin: 5px;
            margin-right: 2px;
            border-radius: 5px; 
            border: 1px solid #ccc;
        }
        .award-input-bar { 
            width: 100%; 
            max-width: 380px;
            margin-right: 5px;
            padding: 10px; 
            margin: 5px; 
            border-radius: 5px; 
            border: 1px solid #ccc;
        }
        .add-new {
            background-color: #555;
            color: white;
            padding: 10px 10px;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            display: block;
            width: 110px;
            margin-top: 15px;
            margin-left: auto;
            text-align: center;
            cursor: pointer;
            border: none;
        }
        .add-new:hover {
            background-color: #777;
        }
        .type {
            margin-left: 5px; 
            height: 36px;
            width: 120px; 
            padding: 8px; 
            margin-bottom: 4px; 
            border: 1px solid #ccc; 
            border-radius: 4px;
        }
        .date {
            margin-left: 5px; 
            height: 20px;
            width: 103px; 
            padding: 8px; 
            margin-bottom: 4px; 
            border: 1px solid #ccc; 
            border-radius: 4px;
        }
        .nothing-box {
            background-color: #fff;
            padding: 20px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border: 1px solid #ddd;
            margin: 10px 0;
            text-align: center;
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
    <a href="admin_table_users.php">Users</a>
    <div class="search-bar">
        <form action="admin_search.php" method="GET">
            <input type="text" name="search_query" placeholder="Search by title, director, or actor" required>
            <button type="submit">Search</button>
        </form>
    </div>
</div>

<main>
    <?php if (!$content_info['title']): ?>
        <div class="nothing-box">
            <div class="sorry-message">
                <h3>Nothing here!</h3>
            </div>
        </div>
    <?php else: ?>
        <div class="content-box">
            <!-- Display content details -->
            <p><span style="font-size: 20px; font-weight: bold;"><?php echo htmlspecialchars($content_info['title']); ?></span>
            <span style="font-size: 20px;">(<?php $release_date = new DateTime($content_info['release_date']); echo $release_date->format('Y-m-d');?>)</span></p>
            <p><span style="font-size: 15px;"><strong>Link: </strong> <a href="<?php echo htmlspecialchars($content_info['link']); ?>" target="_blank"><?php echo htmlspecialchars($content_info['link']); ?></a></span></p>
            <span style="font-size: 15px;">Series: <?php echo strtoupper(htmlspecialchars($content_info['genres'])); ?></span><br>
            <p><em><span style="font-size: 16px;"><?php echo htmlspecialchars($content_info['description']); ?></span></em></p>
            <?php if ($content_info['actors']): ?>
                <span style="font-size: 15px;"><strong>Starring </strong> <?php echo htmlspecialchars($content_info['actors']); ?></span>
            <?php endif; ?>
            </p>
            <?php if ($content_info['awards']): ?>
                <span style="font-size: 15px;"><strong>Awards: </strong> <?php echo htmlspecialchars($content_info['awards']); ?></span><br>
            <?php endif; ?>
            <?php if ($content_info['imdb_rating']): ?>
                <span style="font-size: 15px;"><strong>IMDb: </strong><?php echo htmlspecialchars($content_info['imdb_rating']); ?></span><br>
            <?php endif; ?>
            <?php if ($content_info['rotten_tomatoes_rating']): ?>
                <span style="font-size: 15px;"><strong>Rotten Tomatoes: </strong> <?php echo htmlspecialchars($content_info['rotten_tomatoes_rating']); ?>%</span>
            <?php endif; ?>
            <p> User Statistics: <br>
            <span style="font-size: 15px;"><strong>Audience Rating: </strong> <?php echo htmlspecialchars(number_format($avg_rating, 2)); ?></span><br>
            <span style="font-size: 15px;"><strong>Reviews Submitted: </strong> <?php echo htmlspecialchars($num_reviews); ?></span><br>
            <span style="font-size: 15px;"><strong>Watchlists Added: </strong> <?php echo htmlspecialchars($num_watchlists); ?></span>

            <!-- Delete content button -->
            <form action="admin_series.php" method="POST">
                <input type="hidden" name="delete_content" value="<?php echo htmlspecialchars($content_info['id']); ?>">
                <button type="submit" class="delete-btn">Delete</button>
            </form>
        </div>

        <?php if ($pick_details): ?>
            <!-- Show admin pick -->
            <div class="content-box">
                <h3>Your Pick Review:</h3>
                <p><span style="font-style: italic; font-size: 15px;">"<?php echo htmlspecialchars($pick_details['admin_pick_review']); ?>"</span>
                - <span style="font-weight: bold; font-size: 15px;"><?php echo ucwords(strtolower(htmlspecialchars($username))); ?></span><br>
                <span style="font-size: 14px;">~ <?php echo htmlspecialchars($pick_details['date_added']) . '</span>'; ?>
                <!-- Delete pick button -->
                <form action="admin_series.php" method="POST">
                    <input type="hidden" name="delete_pick" value="<?php echo $content_info['id']; ?>">
                    <button type="submit" class="delete-btn">Delete</button>
                </form>
        </div>
        <?php else: ?>
            <!-- Write pick review -->
            <div class="content-box">
                <h3>Post to Admin Picks:</h3>
                <form action="admin_series.php" method="POST">
                    <div class="description">
                        <textarea name="pick_review" rows="5" placeholder="Enter review" required></textarea>
                    </div>
                    <!-- Post button -->

                    <input type="hidden" name="post_pick" value="<?php echo $content_info['id']; ?>">
                    <button type="submit" class="add-new">Post</button>
                </form>
            </div>
        <?php endif;?>

        <!-- Update info form -->
        <div class="content-box">
            <h3 style="color: #333">Update Info:</h3>
            <form action="admin_series.php" method="POST">
                <select name="content_type" class="type">
                    <option value="series">Series</option>
                </select>
                <input class="title-input-bar" type="text" name="title" placeholder="Enter title*" required><br>
                <input class="date" type="date" name="release_date" required>
                <input class="title-input-bar" type="text" name="link" placeholder="Enter link*" required><br>
                <div class="description">
                    <textarea name="description" rows="5" placeholder="Enter description*" required></textarea>
                </div>
                <input class="input-bar" type="text" name="genre1" placeholder="Enter genre 1*" required>
                <input class="input-bar" type="text" name="director" placeholder="Enter director"><br>
                <input class="input-bar" type="text" name="genre2" placeholder="Enter genre 2">
                <input class="input-bar" type="number" min="0" max="10" step="0.1" name="imdb" placeholder="Enter IMDb rating"><br>
                <input class="input-bar" type="text" name="genre3" placeholder="Enter genre 3">
                <input class="input-bar" type="number" min="0" max="100" step="1" name="rotten" placeholder="Enter Rotten Tomatoes rating"><br>       
                <input class="input-bar" type="text" name="actor1" placeholder="Enter actor 1*" required>
                <input class="input-bar" type="text" name="role1" placeholder="Actor 1 role*" required><br>
                <input class="input-bar" type="text" name="actor2" placeholder="Enter actor 2">
                <input class="input-bar" type="text" name="role2" placeholder="Actor 2 role"><br>
                <input class="input-bar" type="text" name="actor3" placeholder="Enter actor 3">
                <input class="input-bar" type="text" name="role3" placeholder="Actor 3 role"><br>
                <input class="award-input-bar" type="text" name="award1" placeholder="Enter award 1">
                <input class="award-input-bar" type="text" name="org1" placeholder="Award 1 organization">
                <select name="status1" style="width: 100%; height: 36px; width: 123px; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="none">N/A</option>
                    <option value="won">Won</option>
                    <option value="nominated">Nominated</option>
                </select><br>
                <input class="award-input-bar" type="text" name="award2" placeholder="Enter award 2">
                <input class="award-input-bar" type="text" name="org2" placeholder="Award 2 organization">
                <select name="status2" style="width: 100%; height: 36px; width: 123px; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="none">N/A</option>
                    <option value="won">Won</option>
                    <option value="nominated">Nominated</option>
                </select><br>
                <input class="award-input-bar" type="text" name="award3" placeholder="Enter award 3">
                <input class="award-input-bar" type="text" name="org3" placeholder="Award 3 organization">
                <select name="status3" style="width: 100%; height: 36px; width: 123px; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="none">N/A</option>
                    <option value="won">Won</option>
                    <option value="nominated">Nominated</option>
                </select><br>

                <!-- Update button -->
                <input type="hidden" name="update_content" value="<?= htmlspecialchars($series_id) ?>">
                <button type="submit" class="add-new">Update</button>
            </form>
        </div>
    <?php endif ?>
</main>
</body>
</html>
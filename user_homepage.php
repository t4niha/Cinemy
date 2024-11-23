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

// DB query: retrieve the 5 latest admin picks
$query = 
    "SELECT 
        ap.movie_id AS content_id, ap.admin_pick_type, ap.date_added AS date_added,
        ap.admin_pick_id, ap.admin_account_id, ap.admin_pick_review,
        m.movie_title AS title, m.movie_description AS description, m.rotten_tomatoes_rating, m.imdb_rating, m.release_date AS release_date,
        aa.username AS admin_username,
        GROUP_CONCAT(DISTINCT mg_genre.genre SEPARATOR ' | ') AS genres,
        d.director_name AS director,
        GROUP_CONCAT(DISTINCT a.actor_name ORDER BY a.actor_name ASC SEPARATOR ' | ') AS actors,
        GROUP_CONCAT(DISTINCT ma.award_org SEPARATOR ' | ') AS awards
    FROM admin_picks ap
    LEFT JOIN movies m ON ap.movie_id = m.movie_id
    LEFT JOIN admin_accounts aa ON ap.admin_account_id = aa.admin_account_id
    LEFT JOIN movie_genres mg ON m.movie_id = mg.movie_id
    LEFT JOIN genres mg_genre ON mg.genre_id = mg_genre.genre_id
    LEFT JOIN directors d ON m.movie_director_id = d.director_id
    LEFT JOIN acted_in_movies aim ON m.movie_id = aim.movie_id
    LEFT JOIN actors a ON aim.movie_actor_id = a.actor_id
    LEFT JOIN movie_awards ma ON m.movie_id = ma.movie_id
    WHERE ap.movie_id IS NOT NULL
    GROUP BY ap.admin_pick_id

    UNION ALL
    
    SELECT 
        ap.series_id AS content_id, ap.admin_pick_type, ap.date_added AS date_added,
        ap.admin_pick_id, ap.admin_account_id, ap.admin_pick_review,
        s.series_title AS title, s.series_description AS description, s.rotten_tomatoes_rating, s.imdb_rating, s.release_date AS release_date,
        aa.username AS admin_username,
        GROUP_CONCAT(DISTINCT sg_genre.genre SEPARATOR ' | ') AS genres,
        '' AS director,
        GROUP_CONCAT(DISTINCT a.actor_name ORDER BY a.actor_name ASC SEPARATOR ' | ') AS actors,
        GROUP_CONCAT(DISTINCT sa.award_org SEPARATOR ' | ') AS awards
    FROM admin_picks ap
    LEFT JOIN series s ON ap.series_id = s.series_id
    LEFT JOIN admin_accounts aa ON ap.admin_account_id = aa.admin_account_id
    LEFT JOIN series_genres sg ON s.series_id = sg.series_id
    LEFT JOIN genres sg_genre ON sg.genre_id = sg_genre.genre_id
    LEFT JOIN acted_in_series ais ON s.series_id = ais.series_id
    LEFT JOIN actors a ON ais.series_actor_id = a.actor_id
    LEFT JOIN series_awards sa ON s.series_id = sa.series_id
    WHERE ap.series_id IS NOT NULL
    GROUP BY ap.admin_pick_id
    ORDER BY admin_pick_id DESC LIMIT 5";
$result = $conn->query($query);
$admin_picks = [];
while ($rows = $result->fetch_assoc()) {
    $admin_picks[] = $rows;
}

// Go to content page
if (isset($_POST['content_id'])) {
    $content_id = $_POST['content_id'];
} else

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - CineMy</title>
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
            padding: 0px 100px 10px 100px;
            text-align: center;
        }
        .admin-pick-box {
            background-color: #fff;
            padding: 20px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: relative;
            border: 1px solid #ddd;
            margin: 10px 0;
            text-align: left;
        }
        .go-to-link {
            background-color: #555;
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
        .go-to-link:hover {
            background-color: #777;
        }
        .title {
            font-size: 17px;
            margin: 10px;
            background: none;
            padding: 0px 0px 0px 0px;
            border: none;
            max-width: 300px;
            max-height: 29.5px;
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
    <form action="user_homepage.php" method="POST" style="display: inline;">
        <button type="submit" name="logout" class="logout-btn">Sign Out</button>
    </form>
</header>

<div class="menu-bar">
    <a href="user_homepage.php" style="background-color: #666;">Home</a>
    <a href="account.php">Account</a>
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
    
    <?php if (empty($admin_picks)): ?>
        <div class="title">
            <h2 style="color: #333;" >Featured Highlights</h2>
        </div>
        <p>None at the moment, feel free to browse!</p>
    <?php else: ?>
        <div class="title">
            <h2 style="color: #333;" >Featured Highlights</h2>
        </div>
        <p>Handpicked recommendations, updated regularly!</p>
        <?php foreach ($admin_picks as $admin_pick): ?>
            <div class="admin-pick-box">
                <!-- Display content details -->
                <p><span style="font-size: 20px; font-weight: bold;"><?php echo htmlspecialchars($admin_pick['title']); ?></span>
                <span style="font-size: 20px;">(<?php $release_date = new DateTime($admin_pick['release_date']); echo $release_date->format('Y');?>)</span></p>
                <span style="font-size: 14px;"><?php if ($admin_pick['admin_pick_type'] == 'movie'): ?> Movie: <?php echo strtoupper(htmlspecialchars($admin_pick['genres'])); ?></span><br>
                <span style="font-size: 14px;"><?php elseif ($admin_pick['admin_pick_type'] == 'series'): ?> Series: <?php echo strtoupper(htmlspecialchars($admin_pick['genres'])); ?></span><br>
                <?php endif; ?>
                <p><em><span style="font-size: 15px;"><?php echo htmlspecialchars($admin_pick['description']); ?></span></em></p>

                <!-- Display admin review -->
                <p><span style="font-style: italic; font-size: 15px;">"<?php echo htmlspecialchars($admin_pick['admin_pick_review']); ?>"</span>
                    - <span style="font-weight: bold; font-size: 15px;"><?php echo ucwords(strtolower(htmlspecialchars($admin_pick['admin_username']))); ?></span><br>
                <span style="font-size: 14px;">~ <?php echo htmlspecialchars($admin_pick['date_added']) . '</span>'; ?>
                
                <!-- Go to Page buttons -->
                <?php if ($admin_pick['admin_pick_type'] == 'movie'): ?>
                    <form action="movie_info.php" method="POST">
                        <input type="hidden" name="content_id" value="<?php echo $admin_pick['content_id']; ?>">
                        <button type="submit" class="go-to-link">Go to Page</button>
                    </form>
                    <?php elseif ($admin_pick['admin_pick_type'] == 'series'): ?>
                    <form action="series_info.php" method="POST">
                        <input type="hidden" name="content_id" value="<?php echo $admin_pick['content_id']; ?>">
                        <button type="submit" class="go-to-link">Go to Page</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>
</body>
</html>
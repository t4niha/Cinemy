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

// DB query: retrieve user profile id
$query = "SELECT user_profile_id FROM user_profiles WHERE user_profile_name = ? AND user_account_id = ? LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $user_profile_name, $user_account_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$user_profile_id = $user['user_profile_id'];

// DB query: retrieve list of genres
$query = "SELECT genre FROM genres";
$result = $conn->query($query);
$genre_list = [];
while ($row = $result->fetch_assoc()) {
    $genre_list[] = $row['genre'];
}

// DB query: delete movie from watchlist
if (isset($_POST['delete_movie_id'])) {
    $delete_movie_id = $_POST['delete_movie_id'];
    $query = 
        "DELETE mw FROM movie_watchlists mw
        JOIN watchlists w ON w.list_id = mw.movie_watchlist_id
        WHERE mw.movie_id = ? AND w.user_profile_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $delete_movie_id, $user_profile_id);
    $stmt->execute();
    $stmt->close();
    header("Location: watchlists.php");
    exit();
}
// DB query: delete series from watchlist
if (isset($_POST['delete_series_id'])) {
    $delete_series_id = $_POST['delete_series_id'];
    $query = 
        "DELETE sw FROM series_watchlists sw
        JOIN watchlists w ON w.list_id = sw.series_watchlist_id 
        WHERE sw.series_id = ? AND w.user_profile_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $delete_series_id, $user_profile_id);
    $stmt->execute();
    $stmt->close();
    header("Location: watchlists.php");
    exit();
}

// DB query: retrieve combined movie and series watchlists with details
$query =
    "SELECT 
        mw.movie_id as content_id, 'movie' AS content_type,
        m.movie_title AS title, m.movie_description AS description, m.imdb_rating, m.rotten_tomatoes_rating, m.release_date,
        GROUP_CONCAT(DISTINCT g.genre ORDER BY g.genre ASC SEPARATOR ' | ') AS genres,
        d.director_name AS director,
        GROUP_CONCAT(DISTINCT a.actor_name ORDER BY a.actor_name ASC SEPARATOR ' | ') AS actors,
        GROUP_CONCAT(DISTINCT ma.award_org SEPARATOR ' | ') AS awards,
        mw.date_added, CONCAT(mw.date_added, ' ', mw.time_added) AS datetime_added
    FROM movie_watchlists mw
    JOIN watchlists w ON mw.movie_watchlist_id = w.list_id
    JOIN movies m ON mw.movie_id = m.movie_id
    LEFT JOIN movie_genres mg ON m.movie_id = mg.movie_id
    LEFT JOIN genres g ON mg.genre_id = g.genre_id
    LEFT JOIN directors d ON m.movie_director_id = d.director_id
    LEFT JOIN acted_in_movies aim ON m.movie_id = aim.movie_id
    LEFT JOIN actors a ON aim.movie_actor_id = a.actor_id
    LEFT JOIN movie_awards ma ON m.movie_id = ma.movie_id
    WHERE w.user_profile_id = ?
    GROUP BY mw.movie_id

    UNION ALL

    SELECT 
        sw.series_id as content_id, 'series' AS content_type,
        s.series_title AS title, s.series_description AS description, s.imdb_rating, s.rotten_tomatoes_rating, s.release_date,
        GROUP_CONCAT(DISTINCT g.genre ORDER BY g.genre ASC SEPARATOR ' | ') AS genres,
        '' AS director,
        GROUP_CONCAT(DISTINCT a.actor_name ORDER BY a.actor_name ASC SEPARATOR ' | ') AS actors,
        GROUP_CONCAT(DISTINCT sa.award_org SEPARATOR ' | ') AS awards,
        sw.date_added, CONCAT(sw.date_added, ' ', sw.time_added) AS datetime_added
    FROM series_watchlists sw
    JOIN watchlists w ON sw.series_watchlist_id = w.list_id
    JOIN series s ON sw.series_id = s.series_id
    LEFT JOIN series_genres sg ON s.series_id = sg.series_id
    LEFT JOIN genres g ON sg.genre_id = g.genre_id
    LEFT JOIN acted_in_series ais ON s.series_id = ais.series_id
    LEFT JOIN actors a ON ais.series_actor_id = a.actor_id
    LEFT JOIN series_awards sa ON s.series_id = sa.series_id
    WHERE w.user_profile_id = ?
    GROUP BY sw.series_id";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $user_profile_id, $user_profile_id);
$stmt->execute();
$result = $stmt->get_result();
$combined_list = [];
while ($rows = $result->fetch_assoc()) {
    $combined_list[] = $rows;
}

// Initialize default values
$filtered_list = $combined_list;
$filter_content_type = $_POST['filter_content_type'] ?? 'all';
$filter_genre = $_POST['filter_genre'] ?? 'all';
$filter_order_by = $_POST['filter_order_by'] ?? 'datetime_added';
$filter_order = $_POST['filter_order'] ?? 'DESC';

// Filter & sort inputs
if (isset($_POST['filter_list'])) {
    $filter_content_type = $_POST['filter_content_type'];
    $filter_genre = $_POST['filter_genre'];
    $filter_order_by = $_POST['filter_order_by'];
    $filter_order = $_POST['filter_order'];
}

// Filter
$filtered_list = array_filter($combined_list, function ($content) use ($filter_content_type, $filter_genre) {
    if (strtolower($filter_content_type) !== 'all' && strtolower($content['content_type']) !== strtolower($filter_content_type)) {
        return false; }
    if (strtolower($filter_genre) !== 'all' && stripos(strtolower($content['genres']), strtolower($filter_genre)) === false) {
        return false; }
    return true;
});

// Sort
usort($filtered_list, function ($a, $b) use ($filter_order_by, $filter_order) {
    $value_a = $a[$filter_order_by];
    $value_b = $b[$filter_order_by];
    $comparison = 0;
    if ($filter_order_by == 'release_date' || $filter_order_by == 'datetime_added') {
        $value_a = strtotime($value_a);
        $value_b = strtotime($value_b);
        $comparison = $value_a <=> $value_b;
    }
    elseif (is_string($value_a) && is_string($value_b)) {
        $comparison = strcmp($value_a, $value_b);
    } else {
        $comparison = $value_a <=> $value_b;
    }
    return ($filter_order === 'ASC') ? $comparison : -$comparison;
});

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Watchlist - CineMy</title>
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
        .list-item-box {
            background-color: #fff;
            padding: 20px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border: 1px solid #ddd;
            margin: 10px 0;
            text-align: left;
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
        .button-container {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
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
        .filter-form {
            display: inline-flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            width: 100%;
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
        .sorry-box {
            padding: 10px 0px;
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
    <form action="watchlists.php" method="POST" style="display: inline;">
        <button type="submit" name="logout" class="logout-btn">Sign Out</button>
    </form>
</header>

<div class="menu-bar">
    <a href="user_homepage.php">Home</a>
    <a href="account.php">Account</a>
    <a href="reviews.php">Reviews</a>
    <a href="watchlists.php" style="background-color: #666;">Watchlist</a>
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
    <?php if ($subscription_status == 'active'): ?>
        <div class="filter-box">
            <form action="watchlists.php" method="POST" class="filter-form">
                <!-- Content type filter -->
                <label for="filter_content_type">Showing: </label>
                <select name="filter_content_type" id="filter_content_type" style="width: 100%; width: 100px; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="all" <?php echo ($filter_content_type == 'all') ? 'selected' : ''; ?>>All</option>
                    <option value="movie" <?php echo ($filter_content_type == 'movie') ? 'selected' : ''; ?>>Movies</option>
                    <option value="series" <?php echo ($filter_content_type == 'series') ? 'selected' : ''; ?>>Series</option>
                </select>

                <!-- Genre filter -->
                <label for="filter_genre">Genre: </label>
                <select name="filter_genre" id="filter_genre" style="width: 100%; width: 140px; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="all" <?php echo ($filter_genre == 'all') ? 'selected' : ''; ?>>All</option>
                    <?php foreach ($genre_list as $genre): ?>
                        <option value="<?php echo htmlspecialchars($genre); ?>" <?php echo ($filter_genre == $genre) ? 'selected' : ''; ?>>
                            <?php echo ucwords(strtolower(htmlspecialchars($genre))); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Order by -->
                <label for="filter_order_by">Sort by: </label>
                <select name="filter_order_by" id="filter_order_by" style="width: 100%; width: 220px; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="datetime_added" <?php echo ($filter_order_by == 'datetime_added') ? 'selected' : ''; ?>>Date Added</option>
                    <option value="release_date" <?php echo ($filter_order_by == 'release_date') ? 'selected' : ''; ?>>Release Date</option>
                    <option value="title" <?php echo ($filter_order_by == 'title') ? 'selected' : ''; ?>>Title</option>
                    <option value="imdb_rating" <?php echo ($filter_order_by == 'imdb_rating') ? 'selected' : ''; ?>>IMDb Rating</option>
                    <option value="rotten_tomatoes_rating" <?php echo ($filter_order_by == 'rotten_tomatoes_rating') ? 'selected' : ''; ?>>Rotten Tomatoes Rating</option>
                </select>

                <!-- Order -->
                <label for="filter_order">Order: </label>
                <select name="filter_order" id="filter_order" style="width: 100%; width: 140px; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="DESC" <?php echo ($filter_order == 'DESC') ? 'selected' : ''; ?>>Descending</option>
                    <option value="ASC" <?php echo ($filter_order == 'ASC') ? 'selected' : ''; ?>>Ascending</option>
                </select>

                <!-- Submit Button -->
                    <input type="hidden" name="filter_list">
                    <button type="submit" class="filter">Filter</button>
            </form>
        </div>
        <?php if ($filtered_list): ?>
            <?php foreach ($filtered_list as $content): ?>
                <div class="list-item-box">
                    <!-- Display content details -->
                    <p><span style="font-size: 20px; font-weight: bold;"><?php echo htmlspecialchars($content['title']); ?></span>
                    <span style="font-size: 20px;">(<?php $release_date = new DateTime($content['release_date']); echo $release_date->format('Y');?>)</span><br>
                    ~ <?php echo '<span style="font-size: 14px;">' . htmlspecialchars($content['date_added']) . '</span>'; ?>

                    <!-- Go to Page & delete buttons -->
                    <?php if ($content['content_type'] == 'movie'): ?>
                        <div class="button-container">
                            <!-- Movies -->
                            <form action="watchlists.php" method="POST">
                                <input type="hidden" name="delete_movie_id" value="<?php echo $content['content_id']; ?>">
                                <button type="submit" class="delete">Delete</button>
                            </form>
                            <form action="movie_info.php" method="POST">
                                <input type="hidden" name="content_id" value="<?php echo $content['content_id']; ?>">
                                <button type="submit" class="go-to-link">Go to Page</button>
                            </form>
                        </div>
                    <?php elseif ($content['content_type'] == 'series'): ?>
                        <div class="button-container">
                            <!-- Series -->
                            <form action="watchlists.php" method="POST">
                                <input type="hidden" name="delete_series_id" value="<?php echo $content['content_id']; ?>">
                                <button type="submit" class="delete">Delete</button>
                            </form>
                            <form action="series_info.php" method="POST">
                                <input type="hidden" name="content_id" value="<?php echo $content['content_id']; ?>">
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
    <?php else: ?>
        <div class="sorry-box">
            <div class="list-item-box">
                <div class="sorry-message">
                    <h3>This feature requires an active subscription</h3>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>

</body>
</html>
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
$search_query = $_GET['search_query'] ?? '';
$search_query = str_replace(['"', "'"], '', $search_query);

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

// DB query: retrieve all movies and series
$query =
    "SELECT 
    m.movie_id AS content_id, 'movie' AS content_type,
    m.movie_title AS title, m.movie_description AS description, m.imdb_rating, m.rotten_tomatoes_rating, m.release_date,
    GROUP_CONCAT(DISTINCT g.genre ORDER BY g.genre ASC SEPARATOR ' | ') AS genres,
    d.director_name AS director,
    GROUP_CONCAT(DISTINCT a.actor_name ORDER BY a.actor_name ASC SEPARATOR ' | ') AS actors,
    GROUP_CONCAT(DISTINCT ma.award_org SEPARATOR ' | ') AS awards
    FROM movies m
    LEFT JOIN movie_genres mg ON m.movie_id = mg.movie_id
    LEFT JOIN genres g ON mg.genre_id = g.genre_id
    LEFT JOIN directors d ON m.movie_director_id = d.director_id
    LEFT JOIN acted_in_movies aim ON m.movie_id = aim.movie_id
    LEFT JOIN actors a ON aim.movie_actor_id = a.actor_id
    LEFT JOIN movie_awards ma ON m.movie_id = ma.movie_id
    GROUP BY m.movie_id

    UNION ALL

    SELECT 
        s.series_id AS content_id, 'series' AS content_type,
        s.series_title AS title, s.series_description AS description, s.imdb_rating, s.rotten_tomatoes_rating, s.release_date,
        GROUP_CONCAT(DISTINCT g.genre ORDER BY g.genre ASC SEPARATOR ' | ') AS genres,
        '' AS director,
        GROUP_CONCAT(DISTINCT a.actor_name ORDER BY a.actor_name ASC SEPARATOR ' | ') AS actors,
        GROUP_CONCAT(DISTINCT sa.award_org SEPARATOR ' | ') AS awards
    FROM series s
    LEFT JOIN series_genres sg ON s.series_id = sg.series_id
    LEFT JOIN genres g ON sg.genre_id = g.genre_id
    LEFT JOIN acted_in_series ais ON s.series_id = ais.series_id
    LEFT JOIN actors a ON ais.series_actor_id = a.actor_id
    LEFT JOIN series_awards sa ON s.series_id = sa.series_id
    GROUP BY s.series_id;";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$combined_list = [];
while ($rows = $result->fetch_assoc()) {
    $combined_list[] = $rows;
}

// Search list from combined list
$search_list = [];
foreach ($combined_list as $row) {
    if (stripos($row['title'], $search_query) !== false || 
        stripos($row['director'], $search_query) !== false || 
        stripos($row['actors'], $search_query) !== false) {
        $search_list[] = $row;
    }
}

// Initialize default values
$filtered_list = $search_list;
$filter_content_type = $_POST['filter_content_type'] ?? 'all';
$filter_genre = $_POST['filter_genre'] ?? 'all';
$filter_order_by = $_POST['filter_order_by'] ?? 'release_date';
$filter_order = $_POST['filter_order'] ?? 'DESC';

// Filter & sort search list
if (isset($_POST['filter_list'])) {
    $filter_content_type = $_POST['filter_content_type'];
    $filter_genre = $_POST['filter_genre'];
    $filter_order_by = $_POST['filter_order_by'];
    $filter_order = $_POST['filter_order'];
    $search_query = $_POST['search_query'] ?? $search_query;
}

// Search list from combined list
$search_list = [];
foreach ($combined_list as $row) {
    if (stripos($row['title'], $search_query) !== false || 
        stripos($row['director'], $search_query) !== false || 
        stripos($row['actors'], $search_query) !== false) {
        $search_list[] = $row;
    }
}
$filtered_list = $search_list;

// Filter
$filtered_list = array_filter($search_list, function ($content) use ($filter_content_type, $filter_genre) {
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
    if ($filter_order_by == 'release_date') {
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
    <title>Search - CineMy</title>
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
        .list-item-box {
            background-color: #fff;
            padding: 20px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border: 1px solid #ddd;
            margin: 10px 0px;
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
        .sorry-message {
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            text-align: center;
            margin: 0;
        }
        .show-box {
            height: 20px;
            background-color: none;
            padding: 0px 20px;
            border-radius: 8px;
            margin: 0px;
            text-align: left;
        }
        .showing_box {
            color: #333;
            height: 100%;
            text-align: left;
            display: block;
            padding: 20px;
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
    <div class="filter-box">
        <form action="admin_search.php" method="POST" class="filter-form">
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

            <input type="hidden" name="search_query" value="<?php echo htmlspecialchars($search_query); ?>">

            <!-- Submit Button -->
                <input type="hidden" name="filter_list">
                <button type="submit" class="filter">Filter</button>
        </form>
    </div>
    <div class="show-box">
            <div class="showing-box">
                <h4>Showing results for: <span style="font-weight: normal;">"<?php echo htmlspecialchars($search_query); ?>"</span></h4>
            </div>
        </div>
    <?php if ($filtered_list): ?>
        <?php foreach ($filtered_list as $content): ?>
            <div class="list-item-box">
                <!-- Display content details -->
                <p><span style="font-size: 20px; font-weight: bold;"><?php echo htmlspecialchars($content['title']); ?></span>
                <span style="font-size: 20px;">(<?php $release_date = new DateTime($content['release_date']); echo $release_date->format('Y');?>)</span></p>
                <span style="font-size: 14px;"><?php if ($content['content_type'] == 'movie'): ?> Movie: <?php echo strtoupper(htmlspecialchars($content['genres'])); ?></span><br>
                <span style="font-size: 14px;"><?php elseif ($content['content_type'] == 'series'): ?> Series: <?php echo strtoupper(htmlspecialchars($content['genres'])); ?></span><br>
                <?php endif; ?>
                <p><em><span style="font-size: 15px;"><?php echo htmlspecialchars($content['description']); ?></span></em></p>
                <?php if ($content['content_type'] == 'movie'): ?>
                    <p><span style="font-size: 14px;"><strong>Directed by </strong> <?php echo htmlspecialchars($content['director']); ?></span><br>
                <?php endif; ?>
                <?php if ($content['actors']): ?>
                    <span style="font-size: 14px;"><strong>Starring </strong> <?php echo htmlspecialchars($content['actors']); ?></span></p>
                <?php endif; ?>

                <!-- Go to Page buttons -->
                <?php if ($content['content_type'] == 'movie'): ?>
                    <form action="admin_movie.php" method="POST">
                        <input type="hidden" name="content_id" value="<?php echo $content['content_id']; ?>">
                        <button type="submit" class="go-to-link">Go to Page</button>
                    </form>
                <?php elseif ($content['content_type'] == 'series'): ?>
                    <form action="admin_series.php" method="POST">
                        <input type="hidden" name="content_id" value="<?php echo $content['content_id']; ?>">
                        <button type="submit" class="go-to-link">Go to Page</button>
                    </form>
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
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

// DB query: retrieve list of genres
$query = "SELECT genre FROM genres";
$result = $conn->query($query);
$genre_list = [];
while ($row = $result->fetch_assoc()) {
    $genre_list[] = $row['genre'];
}

// DB query: count number of movies
$query = "SELECT COUNT(*) AS movie_count FROM movies";
$result = $conn->query($query);
$movie_count = $result->fetch_assoc()['movie_count'];

// DB query: count number of series
$query = "SELECT COUNT(*) AS series_count FROM series";
$result = $conn->query($query);
$series_count = $result->fetch_assoc()['series_count'];

// DB query = create contents view
$query = "DROP VIEW IF EXISTS content_details";
$conn->query($query);

$viewquery = 
    "CREATE VIEW IF NOT EXISTS content_details AS
        SELECT
            m.movie_id AS id,
            m.movie_title AS title,
            m.release_date,
            m.movie_link AS link,
            (SELECT COUNT(*) FROM movie_reviews mr WHERE mr.movie_id = m.movie_id) AS number_of_reviews_written,
            (SELECT COUNT(*) FROM movie_watchlists mw WHERE mw.movie_id = m.movie_id) AS number_of_watchlists,
            (SELECT AVG(mr.rating) FROM movie_reviews mr WHERE mr.movie_id = m.movie_id AND mr.rating IS NOT NULL) AS rating,
            GROUP_CONCAT(DISTINCT g.genre ORDER BY g.genre ASC SEPARATOR ' | ') AS genres,
            'movie' AS type
        FROM movies m
        LEFT JOIN movie_genres mg ON m.movie_id = mg.movie_id
        LEFT JOIN genres g ON mg.genre_id = g.genre_id
        GROUP BY m.movie_id

        UNION ALL

        SELECT
            s.series_id AS id,
            s.series_title AS title,
            s.release_date,
            s.series_link AS link,
            (SELECT COUNT(*) FROM series_reviews sr WHERE sr.series_id = s.series_id) AS number_of_reviews_written,
            (SELECT COUNT(*) FROM series_watchlists sw WHERE sw.series_id = s.series_id) AS number_of_watchlists,
            (SELECT AVG(sr.rating) FROM series_reviews sr WHERE sr.series_id = s.series_id AND sr.rating IS NOT NULL) AS rating,
            GROUP_CONCAT(DISTINCT g.genre ORDER BY g.genre ASC SEPARATOR ' | ') AS genres,
            'series' AS type
        FROM series s
        LEFT JOIN series_genres sg ON s.series_id = sg.series_id
        LEFT JOIN genres g ON sg.genre_id = g.genre_id
        GROUP BY s.series_id;";

$conn->query($viewquery);

$result = $conn->query("SELECT * FROM content_details");
$content_table = [];
while ($row = $result->fetch_assoc()) {
    $content_table[] = $row;
}

// Initialize default values
$filtered_list = $content_table;
$filter_type = $_POST['filter_type'] ?? 'all';
$filter_genre = $_POST['filter_genre'] ?? 'all';
$filter_order_by = $_POST['filter_order_by'] ?? 'title';
$filter_order = $_POST['filter_order'] ?? 'ASC';

// Filter & sort inputs
if (isset($_POST['filter_list'])) {
    $filter_type = $_POST['filter_type'];
    $filter_genre = $_POST['filter_genre'];
    $filter_order_by = $_POST['filter_order_by'];
    $filter_order = $_POST['filter_order'];
}

// Filter
$filtered_list = array_filter($content_table, function ($content) use ($filter_type, $filter_genre) {
    if (strtolower($filter_type) !== 'all' && strtolower($content['type']) !== strtolower($filter_type)) {
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
    if ($filter_order_by === 'title') {
        $comparison = strcmp($value_a, $value_b);
    } 
    elseif ($filter_order_by === 'rating') {
        $value_a = (float) $value_a;
        $value_b = (float) $value_b;
        $comparison = $value_a <=> $value_b;
    }
    else {
        $value_a = strtotime($value_a);
        $value_b = strtotime($value_b);
        $comparison = $value_a <=> $value_b;
    }
    return ($filter_order === 'ASC') ? $comparison : -$comparison;
});

if (isset($_POST['delete_series'])) {
    $delete_id = $_POST['delete_series'];
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
    header("Location: admin_table_content.php");
    exit();
}

if (isset($_POST['delete_movie'])) {
    $delete_id = $_POST['delete_movie'];
    //DB query: delete from tables
    $queries = [
        "DELETE FROM movie_genres WHERE movie_id = ?",
        "DELETE FROM acted_in_movies WHERE movie_id = ?",
        "DELETE FROM movie_reviews WHERE movie_id = ?",
        "DELETE FROM movie_watchlists WHERE movie_id = ?",
        "DELETE FROM movie_awards WHERE movie_id = ?",
        "DELETE FROM admin_picks WHERE movie_id = ?",
        "DELETE FROM movies WHERE movie_id = ?"
    ];
    foreach ($queries as $query) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: admin_table_content.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contents - CineMy</title>
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
        .page-btn { 
            background-color: #777;
            text-decoration: none;
            transition: background-color 0.3s ease;
            border: none;
            font-size: 12px;
            padding: 3px 10px;
            color: white; 
            border: none; 
            cursor: pointer;
        }
        .page-btn:hover { 
            background-color: #999;
        }
        .del-btn { 
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
        .del-btn:hover { 
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
    <a href="admin_table_content.php" style="background-color: #666;">Contents</a>
    <a href="admin_table_users.php">Users</a>
    <div class="search-bar">
        <form action="admin_search.php" method="GET">
            <input type="text" name="search_query" placeholder="Search by title, director, or actor" required>
            <button type="submit">Search</button>
        </form>
    </div>
</div>

<main>
    <h3 style="color: #333">Contents in Database:</h3>
    <p style="color: #333">(<strong>Movies: </strong><?php echo htmlspecialchars($movie_count);?> | <strong>Series: </strong><?php echo htmlspecialchars($series_count);?>)</p>
    <div class="filter-box">
        <form action="admin_table_content.php" method="POST" class="filter-form">
            <!-- Content type filter -->
            <label for="filter_type">Showing: </label>
            <select name="filter_type" id="filter_type" style="width: 100%; width: 100px; border: 1px solid #ccc; border-radius: 4px;">
                <option value="all" <?php echo ($filter_type == 'all') ? 'selected' : ''; ?>>All</option>
                <option value="movie" <?php echo ($filter_type == 'movie') ? 'selected' : ''; ?>>Movies</option>
                <option value="series" <?php echo ($filter_type == 'series') ? 'selected' : ''; ?>>Series</option>
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
                <option value="title" <?php echo ($filter_order_by == 'title') ? 'selected' : ''; ?>>Title</option>
                <option value="release_date" <?php echo ($filter_order_by == 'release_date') ? 'selected' : ''; ?>>Release Date</option>
                <option value="rating" <?php echo ($filter_order_by == 'rating') ? 'selected' : ''; ?>>Avg User Rating</option>
            </select>

            <!-- Order -->
            <label for="filter_order">Order: </label>
            <select name="filter_order" id="filter_order" style="width: 100%; width: 140px; border: 1px solid #ccc; border-radius: 4px;">
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
                <th class="box">Type</th>
                <th class="box">Title</th>
                <th class="box">Release</th>
                <th class="box">Genres</th>
                <th class="box">Link</th>
                <th class="box">Avg Rating</th>
                <th class="box">Reviews</th>
                <th class="box">Watchlists</th>
                <th class="box"></th>
                <th class="box"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($filtered_list as $content): ?>
                <tr>
                    <td class="box"> <?php echo htmlspecialchars($content['type']); ?> </td>
                    <td class="box"> <?php echo htmlspecialchars($content['title']); ?> </td>
                    <td class="box"> <?php echo htmlspecialchars(date("Y", strtotime($content['release_date']))); ?> </td>
                    <td class="box"> <?php echo htmlspecialchars($content['genres']); ?> </td>
                    <td class="box"><a href="<?php echo htmlspecialchars($content['link']); ?>" target="_blank">link</a></td>
                    <td class="box">
                    <?php if ($content['rating'] !== null) {
                        echo htmlspecialchars(number_format($content['rating'], 2));
                    } ?>
                    </td>
                    <td class="box"> <?php echo htmlspecialchars($content['number_of_reviews_written']); ?> </td>
                    <td class="box"> <?php echo htmlspecialchars($content['number_of_watchlists']); ?> </td>
                    <td class="box">
                        <!-- Go to Page buttons -->
                        <?php if ($content['type'] == 'movie'): ?>
                            <form action="admin_movie.php" method="POST">
                                <input type="hidden" name="content_id" value="<?php echo $content['id']; ?>">
                                <button type="submit" class="page-btn">Update</button>
                            </form>
                        <?php elseif ($content['type'] == 'series'): ?>
                            <form action="admin_series.php" method="POST">
                                <input type="hidden" name="content_id" value="<?php echo $content['id']; ?>">
                                <button type="submit" class="page-btn">Update</button>
                            </form>
                        <?php endif; ?>
                    </td>
                    <td class="box">
                        <!-- Delete buttons -->
                        <?php if ($content['type'] == 'movie'): ?>
                            <form action="admin_table_content.php" method="POST">
                                <input type="hidden" name="delete_movie" value="<?php echo htmlspecialchars($content['id']); ?>">
                                <button type="submit" class="del-btn">Delete</button>
                            </form>
                        <?php elseif ($content['type'] == 'series'): ?>
                            <form action="admin_table_content.php" method="POST">
                                <input type="hidden" name="delete_series" value="<?php echo htmlspecialchars($content['id']); ?>">
                                <button type="submit" class="del-btn">Delete</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</main>
</body>
</html>
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

$genres = [];
$actors = [];
$awards = [];

if(isset($_POST['upload'])) {
    // Content details
    $content_type = $_POST['content_type'] ?? '';
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

    if ($content_type == 'movie'){
        // DB query: add or get director id
        $director_id = null;
        if (!empty($director)) {
            $stmt = $conn->prepare("SELECT director_id FROM directors WHERE director_name = ?");
            $stmt->bind_param("s", $director);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $director_id = $result->fetch_assoc()['director_id'];
            } else {
                $stmt = $conn->prepare("INSERT INTO directors (director_name) VALUES (?)");
                $stmt->bind_param("s", $director);
                $stmt->execute();
                $director_id = $conn->insert_id;
            }
            $stmt->close();
        }
        // DB query: add movie
        $stmt = $conn->prepare("INSERT INTO movies (movie_title, movie_link, movie_description, imdb_rating, rotten_tomatoes_rating, release_date, movie_director_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdisi", $title, $link, $description, $imdb_rating, $rotten_rating, $release_date, $director_id);
        $stmt->execute();
        $movie_id = $conn->insert_id;
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

                $stmt = $conn->prepare("INSERT INTO movie_genres (movie_id, genre_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $movie_id, $genre_id);
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

                $stmt = $conn->prepare("INSERT INTO acted_in_movies (movie_actor_id, movie_id, role) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $actor_id, $movie_id, $actor['role']);
                $stmt->execute();
                $stmt->close();
            }
        }
        // DB query: add awards
        foreach ($awards as $award) {
            if (!empty($award['title']) && !empty($award['org'])) {
                $stmt = $conn->prepare("INSERT INTO movie_awards (movie_id, award_org, award_title, award_status) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $movie_id, $award['org'], $award['title'], $award['status']);
                $stmt->execute();
                $stmt->close();
            }
        }
        header("Location: admin_movie.php?movie_id=$movie_id");
    }

    else {
        // DB query: add series
        $stmt = $conn->prepare("INSERT INTO series (series_title, series_link, series_description, imdb_rating, rotten_tomatoes_rating, release_date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdis", $title, $link, $description, $imdb_rating, $rotten_rating, $release_date);
        $stmt->execute();
        $series_id = $conn->insert_id;
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
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload - CineMy</title>
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
    <a href="admin_upload.php" style="background-color: #666;">Upload</a>
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
    <h3 style="color: #333">Upload New Content:</h3>
    <div class="content-box">
        <form action="admin_upload.php" method="POST">
            <select name="content_type" class="type">
                <option value="movie">Movie</option>
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
                <option value="Won">Won</option>
                <option value="Nominated">Nominated</option>
            </select><br>
            <!-- Submit button -->
            <input type="hidden" name="upload">
            <button type="submit" class="add-new">Upload</button>
        </form>
    </div>
</main>
</body>
</html>
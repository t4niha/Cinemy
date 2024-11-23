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

if (isset($_GET['movie_id'])) {
    $movie_id = $_GET['movie_id'];
} else {
    $movie_id = $_POST['content_id'];
}

// Sign out
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// DB query: calculate average user rating
$query = "SELECT AVG(rating) FROM movie_reviews WHERE movie_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $movie_id);
$stmt->execute();
$stmt->bind_result($avg_rating);
$stmt->fetch();
$stmt->close();

// DB query: retrieve user profile id and subscription status
$query = 
    "SELECT up.user_profile_id, ua.subscription_status 
    FROM user_profiles up
    JOIN user_accounts ua ON up.user_account_id = ua.user_account_id
    WHERE up.user_profile_name = ? AND up.user_account_id = ? LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $user_profile_name, $user_account_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$user_profile_id = $user['user_profile_id'];
$subscription_status = $user['subscription_status'];

// DB query: this user's review
$query = 
    "SELECT mr.date_added, mr.rating, mr.review_text, ua.username
    FROM movie_reviews mr
    JOIN user_profiles up ON up.user_profile_id = mr.user_profile_id
    JOIN user_accounts ua ON ua.user_account_id = up.user_account_id
    WHERE mr.movie_id =? AND mr.user_profile_id =? LIMIT 1;";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $movie_id, $user_profile_id);
$stmt->execute();
$result=$stmt->get_result();
$this_review=$result->fetch_assoc();
$stmt->close();

// DB query: retrieve all other user reviews
$query = 
    "SELECT mr.date_added, mr.time_added, mr.rating, mr.review_text, ua.username, up.user_profile_name
    FROM movie_reviews mr
    JOIN user_profiles up ON up.user_profile_id = mr.user_profile_id
    JOIN user_accounts ua ON ua.user_account_id = up.user_account_id
    WHERE mr.movie_id =?
    ORDER BY CONCAT(mr.date_added, ' ', mr.time_added) DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $movie_id);
$stmt->execute();
$result=$stmt->get_result();
$other_reviews = [];
while ($row = $result->fetch_assoc()) {
    $other_reviews[] = [
        'rating' => $row['rating'],
        'user_profile_name' => $row['user_profile_name'],
        'review_text' => $row['review_text'],
        'date_added' => $row['date_added'],
        'username' => $row['username']
    ];
}
$stmt->close();

// DB query: retrieve all movie info
$query =
    "SELECT 
        m.movie_id AS id, m.movie_title AS title, m.movie_description AS description, m.imdb_rating, m.rotten_tomatoes_rating, m.release_date, m.movie_link AS link,
        GROUP_CONCAT(DISTINCT g.genre ORDER BY g.genre ASC SEPARATOR ' | ') AS genres,
        d.director_name AS director,
        GROUP_CONCAT(DISTINCT CONCAT(a.actor_name, ' as ', aim.role) ORDER BY a.actor_name ASC SEPARATOR ' | ') AS actors,
        GROUP_CONCAT(DISTINCT CONCAT(ma.award_org, ' for ', ma.award_title, ' (', ma.award_status, ')') ORDER BY ma.award_org ASC SEPARATOR ' | ') AS awards
    FROM movies m
    LEFT JOIN movie_genres mg ON m.movie_id = mg.movie_id
    LEFT JOIN genres g ON mg.genre_id = g.genre_id
    LEFT JOIN acted_in_movies aim ON m.movie_id = aim.movie_id
    LEFT JOIN directors d ON m.movie_director_id = d.director_id
    LEFT JOIN actors a ON aim.movie_actor_id = a.actor_id
    LEFT JOIN movie_awards ma ON m.movie_id = ma.movie_id
    WHERE m.movie_id = ?;";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $movie_id);
$stmt->execute();
$result = $stmt->get_result();
$content_info = $result->fetch_assoc();
$stmt->close();

// Delete movie review
if (isset($_POST['delete_review_movie_id'])) {
    $delete_review_movie_id = $_POST['delete_review_movie_id'];
    // DB query: delete movie review
    $query = 
        "DELETE FROM movie_reviews
        WHERE movie_id = ? AND user_profile_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $delete_review_movie_id, $user_profile_id);
    $stmt->execute();
    $stmt->close();
    header("Location: movie_info.php?movie_id=$delete_review_movie_id");
    exit();
}

// Add new movie review
if (isset($_POST['rating'])) {
    $rating = $_POST['rating'];
    $rating = $_POST['rating'];
    if ($_POST['review_text']){
    $review_text = isset($_POST['review_text']) ? $_POST['review_text'] : '';
        $review_text = $_POST['review_text'];
        $review_text = str_replace(['"', "'"], '', $review_text);
    }
    else { $review_text = '';
    }
    $new_movie_id = $_POST['new_movie_id'];
    $date_added = date('Y-m-d');
    $time_added = date('H:i:s');
    // DB query: Insert new movie review
    $query = 
        "INSERT INTO movie_reviews (review_text, rating, date_added, user_profile_id, movie_id, time_added) VALUES (?,?,?,?,?,?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sdsiis', $review_text, $rating, $date_added, $user_profile_id, $new_movie_id, $time_added);
    $stmt->execute();
    $stmt->close();
    header("Location: movie_info.php?movie_id=$new_movie_id");
    exit();
}

// DB query: if movie already in watchlist
$query = 
    "SELECT COUNT(*) 
    FROM movie_watchlists mw
    JOIN watchlists w ON mw.movie_watchlist_id = w.list_id
    WHERE movie_id =? AND user_profile_id =?";
$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $movie_id, $user_profile_id);
$stmt->execute();
$stmt->bind_result($is_added);
$stmt->fetch();
$stmt->close();

// Delete from watchlist
if (isset($_POST['delete_movie_id'])) {
    $movie_id = $_POST['delete_movie_id'];
    $query = 
        "DELETE mw
        FROM movie_watchlists mw
        JOIN watchlists w ON mw.movie_watchlist_id = w.list_id
        WHERE mw.movie_id = ? AND w.user_profile_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $movie_id, $user_profile_id);
    $stmt->execute();
    $stmt->close();
    header("Location: movie_info.php?movie_id=$movie_id");
    exit();
}

// Add to watchlist
if (isset($_POST['add_movie_id'])) {
    $movie_id = $_POST['add_movie_id'];

    // DB query: Check if user has a watchlist
    $query = "SELECT list_id FROM watchlists WHERE user_profile_id =? LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $user_profile_id);
    $stmt->execute();
    $stmt->bind_result($list_id);
    $stmt->fetch();
    $stmt->close();

    if (!$list_id) {
        // DB query: Create new watchlist if none found
        $list_type = "movies";
        $query = "INSERT INTO watchlists (user_profile_id, list_type) VALUES (?,?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $user_profile_id, $list_type);
        $stmt->execute();
        $list_id = $stmt->insert_id;
        $stmt->close();
    }
    
    // DB query: Insert movie into watchlist
    $date_adding = date('Y-m-d');
    $time_added = date('H:i:s');
    $query = 
        "INSERT INTO movie_watchlists (movie_watchlist_id, movie_id, date_added, time_added)
        VALUES (?,?,?,?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('iiss', $list_id, $movie_id, $date_adding, $time_added);
    $stmt->execute();
    $stmt->close();
    header("Location: movie_info.php?movie_id=$movie_id");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie - CineMy</title>
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
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            padding: 0px 0px;
            cursor: pointer;
        }
        .profile-button:hover img {
            transform: scale(1.1);
            box-shadow: 4px 4px 5px rgba(0, 0, 0, 0.5);
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
        .button-container {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
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
        .content_button {
            background-color: #541c8c;
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
        .content_button:hover {
            background-color: #7e43b5;
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
    <form action="movie_info.php" method="POST" style="display: inline;">
        <button type="submit" name="logout" class="logout-btn">Sign Out</button>
    </form>
</header>

<div class="menu-bar">
    <a href="user_homepage.php">Home</a>
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
    <div class="content-box">
        <!-- Display content details -->
        <p><span style="font-size: 20px; font-weight: bold;"><?php echo htmlspecialchars($content_info['title']); ?></span>
        <span style="font-size: 20px;">(<?php $release_date = new DateTime($content_info['release_date']); echo $release_date->format('Y');?>)</span></p>
        <span style="font-size: 15px;">Movie: <?php echo strtoupper(htmlspecialchars($content_info['genres'])); ?></span><br>
        <p><em><span style="font-size: 16px;"><?php echo htmlspecialchars($content_info['description']); ?></span></em></p>
        <?php if ($content_info['director']): ?>
            <span style="font-size: 15px;"><strong>Directed by </strong> <?php echo htmlspecialchars($content_info['director']); ?></span><br>
        <?php endif; ?>
        <?php if ($content_info['actors']): ?>
            <span style="font-size: 15px;"><strong>Starring </strong> <?php echo htmlspecialchars($content_info['actors']); ?></span>
        <?php endif; ?>
        </p>
        <?php if ($content_info['awards']): ?>
            <p><span style="font-size: 15px;"><strong>Awards: </strong> <?php echo htmlspecialchars($content_info['awards']); ?></span><br>
        <?php endif; ?>
        <?php if ($content_info['imdb_rating']): ?>
            <span style="font-size: 15px;"><strong>IMDb: </strong><?php echo htmlspecialchars($content_info['imdb_rating']); ?></span><br>
        <?php endif; ?>
        <?php if ($content_info['rotten_tomatoes_rating']): ?>
            <span style="font-size: 15px;"><strong>Rotten Tomatoes: </strong> <?php echo htmlspecialchars($content_info['rotten_tomatoes_rating']); ?>%</span><br>
        <?php endif; ?> 
        <?php if ($avg_rating): ?>
            <span style="font-size: 15px;"><strong>Audience Rating: </strong> <?php echo htmlspecialchars(number_format($avg_rating, 2)); ?></span>
        <?php endif; ?>

        <!-- Content buttons -->
        <div class="button-container">
            <!-- Add/Remove watchlist -->
            <?php if (strtolower($subscription_status)=='active'): ?>
                <?php if ($is_added>0): ?>
                    <form action="movie_info.php" method="POST">
                        <input type="hidden" name="delete_movie_id" value="<?php echo $content_info['id']; ?>">
                        <button type="submit" class="addremove_button">Remove From Watchlist</button>
                    </form>
                <?php else: ?>
                    <form action="movie_info.php" method="POST">
                        <input type="hidden" name="add_movie_id" value="<?php echo $content_info['id']; ?>">
                        <button type="submit" class="addremove_button">Add To Watchlist</button>
                    </form>
                <?php endif ?>
            <?php endif?>
            <!-- Go to link -->
            <a href="<?php echo htmlspecialchars($content_info['link']); ?>" target="_blank">
                <button type="button" class="content_button">Watch Now</button>
            </a>
        </div>
    </div>

    <!-- This user's review -->
    <?php if ($subscription_status == 'active'): ?>
        <?php if ($this_review): ?>
            <div class="content-box">
                <h3>Review:</h3>
                <!-- Show review -->
                <span style="font-size: 15px;"><?php echo ucwords(strtolower(htmlspecialchars($username))) . 
                " | " . ucwords(strtolower(htmlspecialchars($user_profile_name))); ?></span>: 
                <span style="font-size: 15px;"><strong><?php echo htmlspecialchars($this_review['rating']); ?></strong></span>
                <?php if ($this_review['review_text']): ?><br>
                    <span style="font-size: 15px;"><em>"<?php echo htmlspecialchars($this_review['review_text']); ?>"</em></span>
                <?php endif; ?>
                <br><span style="font-size: 14px;">~ <?php echo htmlspecialchars($this_review['date_added']) . '</span>'; ?>
                <div class="button-container">
                    <!-- Delete review -->
                    <form action="movie_info.php" method="POST">
                        <input type="hidden" name="delete_review_movie_id" value="<?php echo $content_info['id']; ?>">
                        <button type="submit" class="delete">Delete</button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="content-box">
                <!-- Review form -->
                <h3>Write a Review</h3>
                <form action="movie_info.php?movie_id=<?php echo $movie_id; ?>" method="POST">
                    <div class="enter-rating">
                        <label for="rating">Rating:</label>
                        <input type="number" name="rating" min="0" max="10" step="0.1" required>
                    </div>
                    <div class="enter-review">
                        <p><textarea name="review_text" rows="5" placeholder="Share your thoughts..."></textarea>
                    </div>
                    <input type="hidden" name="new_movie_id" value="<?php echo $content_info['id']; ?>">
                    <div class="button-container">
                        <button type="submit" class="submit">Submit</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    <!-- If no access -->
    <?php else: ?>
        <div class="none-box">
            <h3>You must have an active subscription to write a review or add to watchlist</h3>
        </div>
    <?php endif; ?>
    <!-- Other user reviews -->
    <?php if ($other_reviews): ?>
        <?php $count = count($other_reviews); $index=0;?>
        <div class="content-box">
            <?php foreach ($other_reviews as $review): $index++;?>
                <div class="review-profile-box">
                    <div>
                        <img src="https://img.freepik.com/premium-vector/black-silhouette-default-profile-avatar_664995-354.jpg?semt=ais_hybrid" alt="Profile Image" class="review-profile-pic">
                    </div>
                    <div class="review-profile-info">
                        <span style="font-size: 15px;"><?php echo ucwords(strtolower(htmlspecialchars($review['username']))) . 
                        " | " . ucwords(strtolower(htmlspecialchars($review['user_profile_name']))); ?></span>: 
                        <span style="font-size: 15px;"><strong><?php echo htmlspecialchars($review['rating']); ?></strong></span>
                        <?php if ($review['review_text']): ?><br>
                            <span style="font-size: 15px;"><em>"<?php echo htmlspecialchars($review['review_text']); ?>"</em></span>
                        <?php endif; ?>
                        <br><span style="font-size: 14px;">~ <?php echo htmlspecialchars($review['date_added']) . '</span>'; ?>
                    </div>
                </div>
                <?php if ($index!=$count): ?>
                    <hr style="border: none; border-top: 1px solid #ccc; margin: 10px 0;">
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php elseif (!$this_review): ?>
        <div class="none-box">
            <h3>No reviews</h3>
        </div>
    <?php endif; ?> 
</main>
</body>
</html>
<?php
require '_DB_connection.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineMy</title>
    <style>
        body {
            font-family: Helvetica, sans-serif;
            background-color: #c4c4c4;
            background-image: url("https://wallpapercave.com/wp/wp6970219.jpg");
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            margin: 0;
            padding: 0;
        }
        header {
            background-color: #333;
            color: #fff;
            text-align: center;
            padding: 1px;
        }
        main {
            margin-top: 220px;
            padding: 20px;
            text-align: center;
            color: #fff;
        }
        footer {
            background-color: #333;
            color: #fff;
            text-align: center;
            padding: 0px;
            position: fixed;
            width: 100%;
            bottom: 0;
        }
        .btn {
            background-color: #555;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            margin-top: 10px;
            display: inline-block;
        }
        .btn:hover {
            background-color: #777;
        }
    </style>
</head>
<body>
<header>
    <h1>▶ CINEMY</h1>
</header>
<main>
    <h2>Enjoy Unlimited Streaming</h2>
    <p>Browse our collection, watch your favorite content, and more!</p>
    <a href="login.php" class="btn">Login</a> | <a href="signup.php" class="btn">Sign Up</a>
</main>
<footer>
    <p>CSE311 Final Project by Taniha & Rakeen</p>
</footer>
</body>
</html>

<?php
// DB connection setup
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ott";

// Connect to DB
$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
  die("Connection failed: " . mysqli_connect_error());
}
?>
//Function



<?php
$servername = "localhost";
$username = "root";
$password = "CLEB2d@mysql";
$dbname = "skillshare";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
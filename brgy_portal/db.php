<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "login_system";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    error_log($conn->connect_error);
    exit("Database connection failed.");
}

$conn->set_charset("utf8mb4");

function e($text) {
    return htmlspecialchars($text, ENT_QUOTES, "UTF-8");
}
?>

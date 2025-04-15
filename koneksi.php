<?php
$host = "localhost";
$dbname = "absensi";
$username = "root";
$password = "";

// Menggunakan MySQLi Object-Oriented
$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

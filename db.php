<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'library_management_system';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

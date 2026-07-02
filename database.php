<?php
// Database connection for Chrisland University Course Registration System.
$host = 'localhost';
$database = 'course_reg_db';
$username = 'root';
$password = '';

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die('Database connection failed. Please contact the system administrator.');
}

mysqli_set_charset($conn, 'utf8mb4');
?>

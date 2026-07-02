<?php
// setup_database.php
// Usage:
// - Browser: http://localhost/course_registration/scripts/setup_database.php?confirm=1
// - CLI: php scripts/setup_database.php confirm

$confirmed = false;
if (PHP_SAPI === 'cli') {
    $confirmed = in_array('confirm', $argv, true);
} else {
    $confirmed = (isset($_GET['confirm']) && $_GET['confirm'] === '1');
}

if (! $confirmed) {
    echo "This script will create the database 'course_reg_db' and required tables.\n";
    echo "Run with `?confirm=1` in the browser or `php scripts/setup_database.php confirm` on CLI to execute.\n";
    exit;
}

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'course_reg_db';

$conn = mysqli_connect($host, $user, $pass);
if (! $conn) {
    echo "Could not connect to MySQL: " . mysqli_connect_error() . "\n";
    exit(1);
}

if (! mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci")) {
    echo "Failed to create database: " . mysqli_error($conn) . "\n";
    exit(1);
}

mysqli_select_db($conn, $db);

$queries = [];

$queries[] = "CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    matric_no VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) DEFAULT NULL,
    department VARCHAR(255) DEFAULT NULL,
    level VARCHAR(10) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$queries[] = "CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) DEFAULT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$queries[] = "CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(50) NOT NULL,
    course_title VARCHAR(255) NOT NULL,
    units INT NOT NULL DEFAULT 0,
    semester VARCHAR(50) DEFAULT NULL,
    session VARCHAR(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$queries[] = "CREATE TABLE IF NOT EXISTS registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Pending',
    date_registered DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$queries[] = "CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    session VARCHAR(20) DEFAULT NULL,
    amount DECIMAL(10,2) DEFAULT 0.00,
    status VARCHAR(20) DEFAULT 'Unpaid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

foreach ($queries as $q) {
    if (! mysqli_query($conn, $q)) {
        echo "Error creating table: " . mysqli_error($conn) . "\n";
        exit(1);
    }
}

// Insert a default admin if none exists.
$defaultAdminUser = 'admin';
$defaultAdminPass = 'admin123';
$stmt = mysqli_prepare($conn, 'SELECT id FROM admins WHERE username = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 's', $defaultAdminUser);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$exists = (bool) mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (! $exists) {
    $hash = password_hash($defaultAdminPass, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($conn, 'INSERT INTO admins (name, username, password) VALUES (?, ?, ?)');
    $name = 'Administrator';
    mysqli_stmt_bind_param($stmt, 'sss', $name, $defaultAdminUser, $hash);
    if (mysqli_stmt_execute($stmt)) {
        echo "Inserted default admin: username=admin password=admin123\n";
    } else {
        echo "Failed to insert default admin: " . mysqli_error($conn) . "\n";
    }
    mysqli_stmt_close($stmt);
} else {
    echo "Admin user already exists; skipped creating default admin.\n";
}

echo "Database and tables created (or already existed).\n";
echo "You can now open your app at: http://localhost/course_registration/\n";
mysqli_close($conn);

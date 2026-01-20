<?php
// config/database.php
function db() : mysqli {
    static $conn = null;
    if ($conn instanceof mysqli) return $conn;

    // Database credentials directly in this file
    $host = "localhost";     // usually localhost on XAMPP
    $user = "root";          // default XAMPP MySQL user
    $pass = "";              // default is empty
    $name = "banking_system"; //  database name

    $conn = @new mysqli($host, $user, $pass, $name);

    if ($conn->connect_error) {
        die('DB Connection failed: ' . $conn->connect_error);
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}




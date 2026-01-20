<?php
function sendSMS($to, $message) {
    $username = "OKMicro"; 
    $apiKey   = "atsk_7ad4e886cb089c52719c491a449ec9829472dc28a6c0ab1f3ab444511be3c489b6a0102c"; 

$url = "https://api.africastalking.com/version1/messaging";

    $postData = http_build_query([
        'username' => $username,
        'to'       => $to, // must match a Sandbox Test Phone
        'message'  => $message
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apiKey: $apiKey",
        "Content-Type: application/x-www-form-urlencoded",
        "Accept: application/json"
    ]);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        return "cURL Error: " . curl_error($ch);
    }
    curl_close($ch);

    return $response;
}



// config/database.php
function db() : mysqli {
    static $conn = null;
    if ($conn instanceof mysqli) return $conn;

    $configPath = 'C:/xampp/secure_config/db_config.ini'; // <-- your real path

    if (!file_exists($configPath)) {
        die("Database config file not found! Checked: $configPath");
    }

    $config = parse_ini_file($configPath, true);

    if (!$config || !isset($config['database'])) {
        die('DB config invalid!');
    }

    $db = $config['database'];
    $conn = @new mysqli($db['host'], $db['user'], $db['pass'], $db['name']);
    if ($conn->connect_error) {
        die('DB Connection failed: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

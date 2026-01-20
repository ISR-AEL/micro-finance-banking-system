<?php
require_once __DIR__ . '/../config/database.php';

function sendSMS($to, $message) {
    $conn = db();

    // Fetch SMS settings from DB
    $res = $conn->query("SELECT sms_username, sms_api_key FROM settings LIMIT 1");
    $config = $res ? $res->fetch_assoc() : null;

    $username = $config['sms_username'] ?? '';
    $apiKey   = $config['sms_api_key'] ?? '';

    if (empty($username) || empty($apiKey)) {
        error_log("SMS Error: Username or API key not set in settings.");
        return ['status' => 'error', 'message' => 'SMS settings missing'];
    }

    $url = "https://api.africastalking.com/version1/messaging";

    $postData = http_build_query([
        'username' => $username,
        'to'       => $to,
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

    if ($response === false) {
        $err = curl_error($ch);
        curl_close($ch);
        error_log("SMS cURL Error: $err");
        return ['status' => 'error', 'message' => $err];
    }

    curl_close($ch);

    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("SMS Response JSON Decode Error: " . json_last_error_msg());
        return ['status' => 'error', 'message' => 'Invalid JSON response', 'raw' => $response];
    }

    // Check API response status
    if (isset($decoded['SMSMessageData']['Recipients']) && count($decoded['SMSMessageData']['Recipients']) > 0) {
        return ['status' => 'success', 'message' => 'SMS sent', 'recipients' => $decoded['SMSMessageData']['Recipients']];
    }

    $errorMessage = $decoded['SMSMessageData']['Message'] ?? 'Unknown error';
    error_log("SMS API Error: $errorMessage");
    return ['status' => 'error', 'message' => $errorMessage];
}

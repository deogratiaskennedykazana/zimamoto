<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Polyfill for servers without mysqlnd (no get_result())
function stmt_fetch_assoc($stmt) {
    $stmt->store_result();
    $meta = $stmt->result_metadata();
    if (!$meta || $stmt->errno) return null;
    $fields = []; $refs = [];
    while ($field = $meta->fetch_field()) {
        $refs[] = &$fields[$field->name];
    }
    call_user_func_array([$stmt, 'bind_result'], $refs);
    if ($stmt->fetch()) {
        $row = [];
        foreach ($fields as $k => $v) $row[$k] = $v;
        return $row;
    }
    return null;
}
function stmt_fetch_all($stmt) {
    $stmt->store_result();
    $meta = $stmt->result_metadata();
    if (!$meta || $stmt->errno) return [];
    $fields = []; $refs = [];
    while ($field = $meta->fetch_field()) {
        $refs[] = &$fields[$field->name];
    }
    call_user_func_array([$stmt, 'bind_result'], $refs);
    $rows = [];
    while ($stmt->fetch()) {
        $row = [];
        foreach ($fields as $k => $v) $row[$k] = $v;
        $rows[] = $row;
    }
    return $rows;
}

          function openConn() : mysqli{

                    $dbuser = "sbisaccosco_zimamoto";

               $dbhost = "localhost";
               
               $dbname ="sbisaccosco_zimamoto"; 
               $dbpassword = "Zimamoto@Password.2025";
               $conn =  mysqli_connect($dbhost, $dbuser, $dbpassword, $dbname);
               return $conn;
          }
          function closeConn(mysqli $conn){
               $conn->close();  
          }

// ========== EMAIL / SMTP CONFIGURATION ==========
define('SMTP_HOST', 'smtp.gmail.com');        // Change to your SMTP server
define('SMTP_PORT', 587);                      // 587 for TLS, 465 for SSL
define('SMTP_USERNAME', 'your-email@gmail.com'); // Change to your email
define('SMTP_PASSWORD', 'your-app-password');   // Change to your app password
define('SMTP_FROM_EMAIL', 'noreply@saccos.co.tz');
define('SMTP_FROM_NAME', 'SACCOS System');

// ========== SMS CONFIGURATION (Example with Africa's Talking) ==========
define('SMS_PROVIDER', 'africastalking');      // or 'twilio', 'custom'
define('SMS_API_KEY', 'your-api-key');
define('SMS_USERNAME', 'sandbox');             // Africa's Talking username
define('SMS_FROM', 'SACCOSS');

// ========== APPLICATION URL ==========
define('APP_URL', 'https://zimamoto.tellicerpsys.co.tz');
define('APP_NAME', 'SACCOS System');
?>
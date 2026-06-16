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

function openConn(): mysqli {
    // ----------------------------------------------------------------
    // LOCAL (XAMPP)  vs  LIVE (cPanel) credentials
    // The live cPanel host prefixes DB names and usernames with the
    // cPanel account name.  Update the LIVE_ constants below once and
    // never touch this file again.
    // ----------------------------------------------------------------
    $isLive = (isset($_SERVER['SERVER_NAME']) &&
               str_contains($_SERVER['SERVER_NAME'], 'tellicerpsys'));

    if ($isLive) {
        // cPanel live server — username and dbname must match exactly
        // what is shown in cPanel → MySQL Databases
        $dbhost     = 'localhost';
        $dbuser     = 'tellicerpsysco_sbisaccosco_zimamoto'; // ← NO leading space
        $dbname     = 'tellicerpsysco_sbisaccosco_zimamoto'; // ← same prefix
        $dbpassword = 'Zimamoto@Password.2025';              // ← change if different
    } else {
        // Local XAMPP
        $dbhost     = 'localhost';
        $dbuser     = 'sbisaccosco_zimamoto';
        $dbname     = 'sbisaccosco_zimamoto';
        $dbpassword = 'Zimamoto@Password.2025';
    }

    $conn = mysqli_connect($dbhost, $dbuser, $dbpassword, $dbname);

    if (!$conn) {
        // Fail gracefully — show a friendly message instead of a fatal stack trace
        error_log('DB connection failed: ' . mysqli_connect_error());
        die('<div style="font-family:sans-serif;padding:40px;color:#721c24;background:#f8d7da;border:1px solid #f5c6cb;border-radius:6px;max-width:600px;margin:40px auto;">
                <h3>⚠️ Database connection error</h3>
                <p>The system could not connect to the database. Please contact the administrator.</p>
                <small>' . htmlspecialchars(mysqli_connect_error()) . '</small>
             </div>');
    }

    return $conn;
}

function closeConn(mysqli $conn): void {
    $conn->close();
}

// ========== EMAIL / SMTP CONFIGURATION ==========
define('SMTP_HOST',       'smtp.gmail.com');
define('SMTP_PORT',       587);                         // 587=TLS, 465=SSL
define('SMTP_USERNAME',   'deodemo802@gmail.com');      // ← your Gmail/SMTP address
define('SMTP_PASSWORD',   'ssbu npxx kusy xeqv');         // ← Gmail App Password
define('SMTP_FROM_EMAIL', 'noreply@zimamoto.tellicerpsys.co.tz');
define('SMTP_FROM_NAME',  'Zima-Moto SACCOS');

// ========== SMS — Africa's Talking ==========
define('SMS_PROVIDER', 'africastalking');
define('SMS_API_KEY',  'atsk_940c46208c74a0cbf959180e75417bb13006540f9a4e925bee37e4e32fb9d53d7ed914f0');                 // ← from AT dashboard
define('SMS_USERNAME', 'sandbox');                      // 'sandbox' for testing, real username for prod
define('SMS_FROM',     'ZIMAMOTO');

// ========== APPLICATION ==========
define('APP_URL',  'https://zimamoto.tellicerpsys.co.tz');
define('APP_NAME', 'Zima-Moto SACCOS');

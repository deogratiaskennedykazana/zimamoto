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
?>
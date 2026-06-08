<?php
include 'configs.php';

$conn = openConn();

// Read the SQL file
$sql = file_get_contents('zima_moto_new_features.sql');

// Split by semicolon and execute each query
$queries = explode(';', $sql);
foreach ($queries as $query) {
    $query = trim($query);
    if (!empty($query)) {
        if (!$conn->query($query)) {
            echo "Error executing query: " . $conn->error . "\n";
            echo "Query: " . $query . "\n";
        }
    }
}

closeConn($conn);
echo "Migration completed.\n";
?>
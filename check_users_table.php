<?php
include 'configs.php';
$conn = openConn();
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows > 0) {
    echo "Users table exists.\n";
    // Let's also check the structure
    $struct = $conn->query("DESCRIBE users");
    while ($row = $struct->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Users table does not exist.\n";
}
closeConn($conn);
?>
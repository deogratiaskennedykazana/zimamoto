<?php
include 'configs.php';
$conn = openConn();

$result = $conn->query("SHOW TABLES LIKE 'system_notifications'");
if ($result->num_rows > 0) {
    echo "Table system_notifications exists.\n";
} else {
    echo "Table system_notifications does not exist.\n";
}
closeConn($conn);
?>
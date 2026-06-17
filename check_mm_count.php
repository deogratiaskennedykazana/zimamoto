<?php
include 'configs.php';
$conn = openConn();
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$result = $conn->query('SELECT COUNT(*) FROM meeting_minutes');
$row = $result->fetch_row();
echo 'Meeting minutes count: ' . $row[0] . "\n";
closeConn($conn);
?>
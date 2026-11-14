<?php
// delete_parish.php
require 'auth.php'; // Ensure user is logged in

$host = 'localhost'; $user = 'root'; $pass = ''; $dbname = 'vital_records_db';
$conn = new mysqli($host, $user, $pass, $dbname);

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $stmt = $conn->prepare("DELETE FROM parishes WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Redirect back to index. The dashboard will automatically recalculate!
        header("Location: index.php?status=success&msg=Record deleted successfully");
    } else {
        header("Location: index.php?status=error&msg=Failed to delete record");
    }
    $stmt->close();
} else {
    header("Location: index.php");
}
$conn->close();
?>
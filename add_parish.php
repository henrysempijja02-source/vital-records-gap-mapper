<?php
// add_parish.php
require 'auth.php'; // ADD THIS LINE
// ... rest of your code ...
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'vital_records_db';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $parish_name = $_POST['parish_name'];
    $district = $_POST['district'];
    $total_population = intval($_POST['total_population']);
    $registered_count = intval($_POST['registered_count']);

    // Basic validation
    if ($registered_count > $total_population) {
        header("Location: index.php?status=error&msg=Registered count cannot exceed total population.");
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO parishes (parish_name, district, total_population, registered_count) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssii", $parish_name, $district, $total_population, $registered_count);

    if ($stmt->execute()) {
        header("Location: index.php?status=success");
    } else {
        header("Location: index.php?status=error&msg=" . urlencode($stmt->error));
    }

    $stmt->close();
    $conn->close();
}
?>
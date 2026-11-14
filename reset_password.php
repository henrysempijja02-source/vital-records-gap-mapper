<?php
$host = 'localhost'; 
$user = 'root'; 
$pass = ''; 
$dbname = 'vital_records_db';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 1. Generate a fresh, guaranteed hash for "admin123"
$new_password = 'admin123';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// 2. Update the database (or insert if it doesn't exist)
$sql = "INSERT INTO users (username, password) VALUES ('admin', '$hashed_password') 
        ON DUPLICATE KEY UPDATE password = '$hashed_password'";

if ($conn->query($sql) === TRUE) {
    echo "<h2 style='color:green;'>Success! Password reset complete.</h2>";
    echo "<p>Your new login details are:</p>";
    echo "<ul>";
    echo "<li><strong>Username:</strong> admin</li>";
    echo "<li><strong>Password:</strong> admin123</li>";
    echo "</ul>";
    echo "<p><a href='login.php'>Click here to go to the Login Page</a></p>";
    echo "<p style='color:red;'><strong>IMPORTANT: Delete this file (reset_password.php) immediately after logging in!</strong></p>";
} else {
    echo "Error updating record: " . $conn->error;
}

$conn->close();
?>
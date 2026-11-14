<?php
// register.php
session_start();
$host = 'localhost'; $user = 'root'; $pass = ''; $dbname = 'vital_records_db';
$conn = new mysqli($host, $user, $pass, $dbname);

$error = ''; $success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if username exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $error = "Username already taken.";
        } else {
            // Hash the password securely
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $insert = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $insert->bind_param("ss", $username, $hashed_password);
            
            if ($insert->execute()) {
                $success = "Registration successful! You can now <a href='login.php'>Login here</a>.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
            $insert->close();
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Vital Records System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <h2>Create Account</h2>
        <?php if($error): ?><div class="message error" style="text-align:center;"><?php echo $error; ?></div><?php endif; ?>
        <?php if($success): ?><div class="message success" style="text-align:center;"><?php echo $success; ?></div><?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required>
            </div>
            <button type="submit">Register</button>
        </form>
        <div class="auth-links">
            Already have an account? <a href="login.php">Login</a>
        </div>
    </div>
</div>
</body>
</html>
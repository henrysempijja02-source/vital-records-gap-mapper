<?php
// login.php
session_start();

// If already logged in, go to index
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("Location: index.php");
    exit;
}

$host = 'localhost'; $user = 'root'; $pass = ''; $dbname = 'vital_records_db';
$conn = new mysqli($host, $user, $pass, $dbname);

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $row['password'])) {
            // Login successful
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $row['username'];
            header("Location: index.php");
            exit;
        } else {
            $error = "Incorrect password. Please try again.";
        }
    } else {
        $error = "Username not found.";
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Vital Records System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .login-box { max-width: 400px; margin: 100px auto; padding: 30px; background: #fff; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .login-box h2 { text-align: center; margin-bottom: 20px; color: #2c3e50; }
    </style>
</head>
<body style="background-color: #f4f7f6;">
    <div class="login-box">
        <h2>System Login</h2>
        
        <?php if(!empty($error)): ?>
            <div class="message error" style="background:#f8d7da; color:#721c24; padding:10px; margin-bottom:15px; border-radius:4px; text-align:center;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
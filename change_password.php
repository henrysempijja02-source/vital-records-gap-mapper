<?php
// change_password.php
require 'auth.php'; 

$host = 'localhost'; $user = 'root'; $pass = ''; $dbname = 'vital_records_db';
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$error = ''; $success = '';
$current_username = $_SESSION['username'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = $_POST['current_password'];
    $new_username = trim($_POST['new_username']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Check if current password is correct
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $current_username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($current_password, $row['password'])) {
            $user_id = $row['id'];
            $update_needed = false;

            // 2. Check if they are trying to change their username
            if ($new_username !== $current_username) {
                // Check if the new username is already taken by someone else
                $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                $check_stmt->bind_param("si", $new_username, $user_id);
                $check_stmt->execute();
                if ($check_stmt->get_result()->num_rows > 0) {
                    $error = "That username is already taken. Please choose another.";
                } else {
                    $update_needed = true;
                }
                $check_stmt->close();
            }

            // 3. Process password change if provided
            $password_changed = false;
            $new_hash = "";
            if (!empty($new_password)) {
                if ($new_password !== $confirm_password) {
                    $error = "New passwords do not match.";
                } elseif (strlen($new_password) < 4) {
                    $error = "New password must be at least 4 characters.";
                } else {
                    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $password_changed = true;
                    $update_needed = true;
                }
            }

            // 4. Execute the Update if no errors
            if (empty($error) && $update_needed) {
                if ($password_changed && $new_username !== $current_username) {
                    // Change BOTH username and password
                    $update = $conn->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?");
                    $update->bind_param("ssi", $new_username, $new_hash, $user_id);
                } elseif ($password_changed) {
                    // Change ONLY password
                    $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $update->bind_param("si", $new_hash, $user_id);
                } else {
                    // Change ONLY username
                    $update = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
                    $update->bind_param("si", $new_username, $user_id);
                }

                if ($update->execute()) {
                    // Update the session so the new name shows on the dashboard immediately
                    $_SESSION['username'] = $new_username;
                    $current_username = $new_username;
                    $success = "Account settings updated successfully!";
                } else {
                    $error = "Database error: " . $conn->error;
                }
                $update->close();
            } elseif (empty($error) && !$update_needed) {
                $error = "No changes were made.";
            }

        } else {
            $error = "Your CURRENT password is incorrect.";
        }
    } else {
        $error = "User not found.";
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Settings</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <h2>Account Settings</h2>
        
        <?php if($error): ?>
            <div style="background:#f8d7da; color:#721c24; padding:15px; text-align:center; border-radius:4px; margin-bottom:15px; font-weight:bold;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div style="background:#d4edda; color:#155724; padding:15px; text-align:center; border-radius:4px; margin-bottom:15px; font-weight:bold;">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Current Password (Required to save changes)</label>
                <input type="password" name="current_password" required placeholder="Enter your current password">
            </div>
            
            <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">

            <div class="form-group">
                <label>Username</label>
                <input type="text" name="new_username" required value="<?php echo htmlspecialchars($current_username); ?>">
            </div>
            
            <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">

            <div class="form-group">
                <label>New Password (Leave blank to keep current)</label>
                <input type="password" name="new_password" placeholder="Enter new password">
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" placeholder="Confirm new password">
            </div>
            
            <button type="submit">Update Account</button>
        </form>
        <div class="auth-links">
            <a href="index.php">← Back to Dashboard</a>
        </div>
    </div>
</div>
</body>
</html>
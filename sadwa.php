<?php
include 'db.php';

// This file resets the admin password to: admin123
$password = 'admin123';
$hashed = password_hash($password, PASSWORD_BCRYPT);

// Check if admin exists
$check = $conn->query("SELECT * FROM users WHERE username='admin'");

if ($check->num_rows > 0) {
    // Update existing admin
    $stmt = $conn->prepare("UPDATE users SET password=?, full_name='System Administrator', role='admin' WHERE username='admin'");
    $stmt->bind_param("s", $hashed);
    $stmt->execute();
    echo "<h2 style='color:green;font-family:sans-serif;'>✅ Admin password reset to: <strong>admin123</strong></h2>";
} else {
    // Insert new admin
    $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, role) VALUES ('admin', ?, 'System Administrator', 'admin')");
    $stmt->bind_param("s", $hashed);
    $stmt->execute();
    echo "<h2 style='color:green;font-family:sans-serif;'>✅ Admin account created! Password: <strong>admin123</strong></h2>";
}

echo "<p style='font-family:sans-serif;'>Generated hash: <code>$hashed</code></p>";
echo "<p style='font-family:sans-serif;'><a href='login.php'>➡️ Go to Login</a></p>";
echo "<p style='color:red;font-family:sans-serif;'><strong>⚠️ Delete this file (reset_admin.php) after logging in!</strong></p>";
?>

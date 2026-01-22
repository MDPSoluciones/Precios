<?php
require 'config.php';
// Run this once to create admin user with password 'admin123', then delete this file.
$username = 'admin';
$default_pass = 'admin123';
$stmt = $mysqli->prepare('SELECT id_user FROM users WHERE username = ?');
$stmt->bind_param('s', $username);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo 'Admin already exists.';
    exit;
}
$hash = password_hash($default_pass, PASSWORD_DEFAULT);
$stmt2 = $mysqli->prepare('INSERT INTO users (username, password_hash) VALUES (?,?)');
$stmt2->bind_param('ss', $username, $hash);
if ($stmt2->execute()) echo 'Admin created. Please delete create_admin.php for security.';
else echo 'Error creating admin.';
?>
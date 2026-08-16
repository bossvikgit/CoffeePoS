<?php
include 'db.php';

// New user details
$username = "cashier";
$password = "cashier123"; // plain text password to be hashed
$role = "cashier";

// Hash the password securely
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert into users table
$stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $username, $hashedPassword, $role);

if ($stmt->execute()) {
    echo "New user 'cashier' created successfully.";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
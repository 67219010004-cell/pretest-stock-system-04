<?php
session_start();
require 'db.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'register') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($password)) {
        header("Location: register.php?error=Please fill in all fields");
        exit();
    }

    if ($password !== $confirm_password) {
        header("Location: register.php?error=Passwords do not match");
        exit();
    }

    // Check if user exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetchColumn() > 0) {
        header("Location: register.php?error=Username already taken");
        exit();
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'customer')");
        $stmt->execute([$username, $hashed_password]);
        header("Location: login.php?success=Registration successful! Please login.");
        exit();
    } catch (PDOException $e) {
        header("Location: register.php?error=Registration failed");
        exit();
    }
}

if ($action === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        header("Location: login.php?error=Please fill in all fields");
        exit();
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && $user['password'] === $password) { // Plaintext for demo
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name']; // Load full name
        $_SESSION['profile_image'] = $user['profile_image']; // Load profile image
        
        header("Location: index.php");
        exit();
    } else {
        header("Location: login.php?error=Invalid credentials");
        exit();
    }
}

if ($action === 'logout') {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>

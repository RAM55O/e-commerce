<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate passwords match
    if ($password !== $confirm_password) {
        header("Location: index.php?error=Passwords do not match");
        exit();
    }

    // Check if username already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    if ($stmt->fetch()) {
        header("Location: index.php?error=Username already exists");
        exit();
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    if ($stmt->fetch()) {
        header("Location: index.php?error=Email already exists");
        exit();
    }

    // Insert new user (password stored as plain text as requested)
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, user_type) VALUES (:username, :email, :password, 'user')");
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $password);

    if ($stmt->execute()) {
        // Auto login after signup
        $user_id = $conn->lastInsertId();
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['user_type'] = 'user';

        header("Location: home.php");
        exit();
    } else {
        header("Location: index.php?error=Registration failed. Please try again.");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>

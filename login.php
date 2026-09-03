<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_username = trim($_POST['email_username']);
    $password = $_POST['password'];

    // Check if user exists by email or username
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email_username OR username = :email_username");
    $stmt->bindParam(':email_username', $email_username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Check password (plain text comparison as requested)
        if ($password === $user['password']) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_type'] = $user['user_type'];

            // Redirect based on user type
            if ($user['user_type'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: home.php");
            }
            exit();
        } else {
            header("Location: index.php?error=Invalid password");
            exit();
        }
    } else {
        header("Location: index.php?error=User not found");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>

<?php
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: index.php?error=Access denied");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            $stmt = $conn->prepare("INSERT INTO categories (name, icon) VALUES (?, ?)");
            $stmt->execute([$_POST['name'], $_POST['icon']]);
            header("Location: admin_dashboard.php?success=Category added successfully");
            break;

        case 'update':
            $stmt = $conn->prepare("UPDATE categories SET name = ?, icon = ? WHERE id = ?");
            $stmt->execute([$_POST['name'], $_POST['icon'], $_POST['category_id']]);
            header("Location: admin_dashboard.php?success=Category updated successfully");
            break;

        case 'delete':
            $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$_POST['category_id']]);
            header("Location: admin_dashboard.php?success=Category deleted successfully");
            break;

        default:
            header("Location: admin_dashboard.php");
    }
    exit();
}

header("Location: admin_dashboard.php");
exit();
?>

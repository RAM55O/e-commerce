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
            $stmt = $conn->prepare("INSERT INTO products (name, description, price, discount, image, category_id, brand, stock, rating, specifications, featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['name'],
                $_POST['description'],
                $_POST['price'],
                $_POST['discount'] ?? 0,
                $_POST['image'],
                $_POST['category_id'],
                $_POST['brand'],
                $_POST['stock'],
                $_POST['rating'],
                $_POST['specifications'],
                isset($_POST['featured']) ? 1 : 0
            ]);
            header("Location: admin_dashboard.php?success=Product added successfully");
            break;

        case 'update':
            $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, discount = ?, image = ?, category_id = ?, brand = ?, stock = ?, rating = ?, specifications = ?, featured = ? WHERE id = ?");
            $stmt->execute([
                $_POST['name'],
                $_POST['description'],
                $_POST['price'],
                $_POST['discount'] ?? 0,
                $_POST['image'],
                $_POST['category_id'],
                $_POST['brand'],
                $_POST['stock'],
                $_POST['rating'],
                $_POST['specifications'],
                isset($_POST['featured']) ? 1 : 0,
                $_POST['product_id']
            ]);
            header("Location: admin_dashboard.php?success=Product updated successfully");
            break;

        case 'delete':
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$_POST['product_id']]);
            header("Location: admin_dashboard.php?success=Product deleted successfully");
            break;

        default:
            header("Location: admin_dashboard.php");
    }
    exit();
}

header("Location: admin_dashboard.php");
exit();
?>

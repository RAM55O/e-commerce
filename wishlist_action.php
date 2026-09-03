<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Please login first");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $product_id = (int)($_POST['product_id'] ?? 0);

    switch ($action) {
        case 'add':
            // Check if already in wishlist
            $stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            if (!$stmt->fetch()) {
                $stmt = $conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
                $stmt->execute([$user_id, $product_id]);
            }
            $redirect = $_POST['redirect'] ?? 'wishlist.php?success=Added to wishlist';
            if (strpos($redirect, 'http') !== 0) {
                $sep = (strpos($redirect, '?') === false) ? '?' : '&';
                header("Location: " . $redirect . $sep . 'success=' . urlencode('Added to wishlist'));
            } else {
                header("Location: wishlist.php?success=Added to wishlist");
            }
            break;

        case 'remove':
            $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            $redirect = $_POST['redirect'] ?? 'wishlist.php?success=Removed from wishlist';
            if (strpos($redirect, 'http') !== 0) {
                $sep = (strpos($redirect, '?') === false) ? '?' : '&';
                header("Location: " . $redirect . $sep . 'success=' . urlencode('Removed from wishlist'));
            } else {
                header("Location: wishlist.php?success=Removed from wishlist");
            }
            break;

        case 'move_to_cart':
            // Add to cart
            $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $stmt = $conn->prepare("UPDATE cart SET quantity = quantity + 1 WHERE id = ?");
                $stmt->execute([$existing['id']]);
            } else {
                $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
                $stmt->execute([$user_id, $product_id]);
            }

            // Remove from wishlist
            $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);

            header("Location: cart.php?success=Moved to cart");
            break;

        default:
            header("Location: wishlist.php");
    }
    exit();
}

header("Location: wishlist.php");
exit();
?>

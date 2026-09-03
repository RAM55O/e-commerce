<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $product_id = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);

    switch ($action) {
        case 'add':
            // Check if product already in cart
            $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Update quantity
                $new_qty = $existing['quantity'] + $quantity;
                $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                $stmt->execute([$new_qty, $existing['id']]);
            } else {
                // Add new item
                $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $product_id, $quantity]);
            }
            
            $redirect = $_POST['redirect'] ?? 'cart.php?success=Product added to cart';
            if (strpos($redirect, 'cart.php') === false && strpos($redirect, 'http') !== 0) {
                $sep = (strpos($redirect, '?') === false) ? '?' : '&';
                $redirect = $redirect . $sep . 'success=' . urlencode('Added to cart');
            }
            header("Location: " . $redirect);
            break;

        case 'update':
            if ($quantity > 0) {
                $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$quantity, $user_id, $product_id]);
            } else {
                $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$user_id, $product_id]);
            }
            header("Location: cart.php");
            break;

        case 'remove':
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            header("Location: cart.php?success=Item removed from cart");
            break;

        case 'clear':
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
            header("Location: cart.php?success=Cart cleared");
            break;

        case 'buy':
            // Add to cart and redirect to cart
            $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $new_qty = $existing['quantity'] + $quantity;
                $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                $stmt->execute([$new_qty, $existing['id']]);
            } else {
                $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $product_id, $quantity]);
            }
            
            header("Location: cart.php");
            break;

        default:
            header("Location: home.php");
    }
    exit();
}

header("Location: home.php");
exit();
?>

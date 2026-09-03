<?php
function sale_price(array $product): float {
    $price = (float)$product['price'];
    $discount = (int)($product['discount'] ?? 0);
    if ($discount > 0) {
        return round($price - ($price * $discount / 100), 2);
    }
    return round($price, 2);
}

function parse_specs(?string $spec_string): array {
    $out = [];
    if (!$spec_string) {
        return $out;
    }
    foreach (explode('|', $spec_string) as $part) {
        $part = trim($part);
        if ($part === '') {
            continue;
        }
        if (strpos($part, ':') !== false) {
            $bits = explode(':', $part, 2);
            $out[trim($bits[0])] = trim($bits[1]);
        } else {
            $out[$part] = $part;
        }
    }
    return $out;
}

function user_wishlist_ids(PDO $conn, int $user_id): array {
    $stmt = $conn->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function ensure_shop_extras(PDO $conn): void {
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $conn->exec("CREATE TABLE IF NOT EXISTS coupons (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) UNIQUE NOT NULL,
        discount_percent INT NOT NULL,
        min_order DECIMAL(10,2) DEFAULT 0,
        description VARCHAR(255) DEFAULT NULL,
        active TINYINT(1) DEFAULT 1
    )");

    $count = (int)$conn->query("SELECT COUNT(*) FROM coupons")->fetchColumn();
    if ($count === 0) {
        $conn->exec("INSERT INTO coupons (code, discount_percent, min_order, description, active) VALUES
            ('WELCOME10', 10, 0, '10% off any order', 1),
            ('SAVE20', 20, 500, '20% off orders of ₹500 or more', 1),
            ('MOBILE15', 15, 0, '15% extra discount on any order', 1)");
    }

    $cols = $conn->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('coupon_code', $cols, true)) {
        $conn->exec("ALTER TABLE orders ADD COLUMN coupon_code VARCHAR(50) DEFAULT NULL");
    }
    if (!in_array('coupon_discount', $cols, true)) {
        $conn->exec("ALTER TABLE orders ADD COLUMN coupon_discount DECIMAL(10,2) DEFAULT 0");
    }
}

function find_coupon(PDO $conn, string $code): ?array {
    $stmt = $conn->prepare("SELECT * FROM coupons WHERE UPPER(code) = UPPER(?) AND active = 1");
    $stmt->execute([trim($code)]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function apply_coupon(array $coupon, float $subtotal): array {
    $min = (float)$coupon['min_order'];
    if ($subtotal < $min) {
        return [
            'ok' => false,
            'message' => 'Minimum order of ₹' . number_format($min, 2) . ' required for this coupon.',
            'discount' => 0,
        ];
    }
    $percent = (int)$coupon['discount_percent'];
    $discount = round($subtotal * $percent / 100, 2);
    return [
        'ok' => true,
        'message' => $coupon['code'] . ' applied: ' . $percent . '% off',
        'discount' => $discount,
    ];
}

function get_mobiles_category_id(PDO $conn): int {
    $stmt = $conn->query("SELECT id FROM categories WHERE name LIKE '%Mobile%' ORDER BY id LIMIT 1");
    $id = $stmt->fetchColumn();
    return $id ? (int)$id : 0;
}

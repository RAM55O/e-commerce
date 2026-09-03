<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Please login first");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$mobiles_category_id = get_mobiles_category_id($conn);

if (!isset($_SESSION['compare_ids']) || !is_array($_SESSION['compare_ids'])) {
    $_SESSION['compare_ids'] = [];
}

if (isset($_GET['add'])) {
    $add_id = (int)$_GET['add'];
    if ($add_id > 0) {
        $check = $conn->prepare("SELECT id FROM products WHERE id = ? AND category_id = ?");
        $check->execute([$add_id, $mobiles_category_id]);
        if ($check->fetch() && !in_array($add_id, $_SESSION['compare_ids'], true)) {
            if (count($_SESSION['compare_ids']) >= 3) {
                header("Location: compare.php?error=" . urlencode("You can compare up to 3 mobiles."));
                exit();
            }
            $_SESSION['compare_ids'][] = $add_id;
        }
    }
    header("Location: compare.php");
    exit();
}

if (isset($_GET['remove'])) {
    $rid = (int)$_GET['remove'];
    $_SESSION['compare_ids'] = array_values(array_filter($_SESSION['compare_ids'], function ($id) use ($rid) {
        return (int)$id !== $rid;
    }));
    header("Location: compare.php");
    exit();
}

if (isset($_GET['clear'])) {
    $_SESSION['compare_ids'] = [];
    header("Location: compare.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['compare_select'])) {
    $ids = array_map('intval', $_POST['phones'] ?? []);
    $ids = array_values(array_unique(array_filter($ids)));
    if (count($ids) > 3) {
        $ids = array_slice($ids, 0, 3);
    }
    $_SESSION['compare_ids'] = $ids;
    header("Location: compare.php");
    exit();
}

$compare_ids = array_map('intval', $_SESSION['compare_ids']);
$compared = [];
$all_spec_keys = [];

if ($compare_ids) {
    $placeholders = implode(',', array_fill(0, count($compare_ids), '?'));
    $stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id IN ($placeholders)");
    $stmt->execute($compare_ids);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $by_id = [];
    foreach ($rows as $row) {
        $row['specs'] = parse_specs($row['specifications']);
        $row['sale'] = sale_price($row);
        $by_id[(int)$row['id']] = $row;
        $all_spec_keys = array_unique(array_merge($all_spec_keys, array_keys($row['specs'])));
    }
    foreach ($compare_ids as $cid) {
        if (isset($by_id[$cid])) {
            $compared[] = $by_id[$cid];
        }
    }
}

$phones_stmt = $conn->prepare("SELECT * FROM products WHERE category_id = ? ORDER BY name");
$phones_stmt->execute([$mobiles_category_id]);
$all_phones = $phones_stmt->fetchAll(PDO::FETCH_ASSOC);

$wishlist_ids = user_wishlist_ids($conn, $user_id);
$cart_stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
$cart_stmt->execute([$user_id]);
$cart_count = $cart_stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compare Mobiles - Moonchild</title>
    <link rel="stylesheet" href="shop.css?v=3.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="header-content">
            <a href="home.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
            <h1 class="logo">Compare</h1>
            <div class="header-icons">
                <a href="cart.php" class="icon-btn">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if ($cart_count > 0): ?><span class="badge"><?php echo (int)$cart_count; ?></span><?php endif; ?>
                </a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <section class="section">
            <h3 class="section-title"><i class="fas fa-mobile-alt"></i> Select mobiles (up to 3)</h3>
            <form method="POST" class="compare-picker">
                <input type="hidden" name="compare_select" value="1">
                <div class="compare-phone-grid">
                    <?php foreach ($all_phones as $phone): ?>
                    <label class="compare-pick-card <?php echo in_array((int)$phone['id'], $compare_ids, true) ? 'selected' : ''; ?>">
                        <input type="checkbox" name="phones[]" value="<?php echo (int)$phone['id']; ?>"
                            <?php echo in_array((int)$phone['id'], $compare_ids, true) ? 'checked' : ''; ?>
                            onchange="limitCompareChecks(this)">
                        <img src="<?php echo htmlspecialchars($phone['image']); ?>" alt="">
                        <span><?php echo htmlspecialchars($phone['name']); ?></span>
                        <small>₹<?php echo number_format(sale_price($phone), 2); ?></small>
                    </label>
                    <?php endforeach; ?>
                </div>
                <?php if (!$all_phones): ?>
                <p class="no-results">No mobiles found. Add products in the Mobiles category.</p>
                <?php endif; ?>
                <button type="submit" class="checkout-btn" style="margin-top: 16px;">Compare selected</button>
            </form>
        </section>

        <?php if (count($compared) >= 2): ?>
        <section class="section">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                <h3 class="section-title" style="margin:0;">Side-by-side comparison</h3>
                <a href="compare.php?clear=1" class="filter-tab">Clear</a>
            </div>
            <div class="compare-table-wrap">
                <table class="compare-table">
                    <thead>
                        <tr>
                            <th>Feature</th>
                            <?php foreach ($compared as $p): ?>
                            <th>
                                <a href="compare.php?remove=<?php echo (int)$p['id']; ?>" class="compare-remove" title="Remove"><i class="fas fa-times"></i></a>
                                <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="">
                                <div><?php echo htmlspecialchars($p['name']); ?></div>
                            </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Brand</td>
                            <?php foreach ($compared as $p): ?><td><?php echo htmlspecialchars($p['brand']); ?></td><?php endforeach; ?>
                        </tr>
                        <tr>
                            <td>Price</td>
                            <?php foreach ($compared as $p): ?>
                            <td>
                                <?php if ((int)$p['discount'] > 0): ?>
                                <span class="original-price">₹<?php echo number_format($p['price'], 2); ?></span><br>
                                <strong class="discounted-price">₹<?php echo number_format($p['sale'], 2); ?></strong>
                                <?php else: ?>
                                <strong>₹<?php echo number_format($p['price'], 2); ?></strong>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td>Discount</td>
                            <?php foreach ($compared as $p): ?><td><?php echo (int)$p['discount'] > 0 ? (int)$p['discount'] . '%' : '—'; ?></td><?php endforeach; ?>
                        </tr>
                        <tr>
                            <td>Rating</td>
                            <?php foreach ($compared as $p): ?><td><?php echo htmlspecialchars($p['rating']); ?> / 5</td><?php endforeach; ?>
                        </tr>
                        <tr>
                            <td>Stock</td>
                            <?php foreach ($compared as $p): ?><td><?php echo (int)$p['stock']; ?></td><?php endforeach; ?>
                        </tr>
                        <?php foreach ($all_spec_keys as $key): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($key); ?></td>
                            <?php foreach ($compared as $p): ?>
                            <td><?php echo htmlspecialchars($p['specs'][$key] ?? '—'); ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td></td>
                            <?php foreach ($compared as $p): ?>
                            <td>
                                <?php if ($user_type === 'user'): ?>
                                <form action="cart_action.php" method="POST">
                                    <input type="hidden" name="product_id" value="<?php echo (int)$p['id']; ?>">
                                    <input type="hidden" name="action" value="add">
                                    <button type="submit" class="btn-add-cart">Add to Cart</button>
                                </form>
                                <a href="product.php?id=<?php echo (int)$p['id']; ?>" class="btn-view-details" style="display:inline-block;margin-top:8px;">View</a>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
        <?php elseif (count($compared) === 1): ?>
        <p class="no-results">Select at least one more mobile to compare.</p>
        <?php endif; ?>
    </main>

    <?php $nav_active = 'home'; include 'includes/user_nav.php'; ?>
    <script>
        function limitCompareChecks(el) {
            const boxes = document.querySelectorAll('input[name="phones[]"]');
            const checked = [...boxes].filter(b => b.checked);
            if (checked.length > 3) {
                el.checked = false;
                alert('You can compare up to 3 mobiles.');
            }
        }
    </script>
    <script src="script.js?v=2.1"></script>
</body>
</html>

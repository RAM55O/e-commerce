<?php
$discounted_price = sale_price($product);
$in_wishlist = !empty($wishlist_ids) && in_array((int)$product['id'], $wishlist_ids, true);
$show_actions = ($user_type ?? '') === 'user';
$is_mobile = isset($mobiles_category_id) && (int)$product['category_id'] === (int)$mobiles_category_id;
?>
<div class="product-card">
    <?php if ((int)$product['discount'] > 0): ?>
    <span class="discount-badge">-<?php echo (int)$product['discount']; ?>%</span>
    <?php endif; ?>
    <?php if ($show_actions): ?>
    <form action="wishlist_action.php" method="POST" class="card-wish-form">
        <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
        <input type="hidden" name="action" value="<?php echo $in_wishlist ? 'remove' : 'add'; ?>">
        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'home.php'); ?>">
        <button type="submit" class="card-wish-btn <?php echo $in_wishlist ? 'active' : ''; ?>" title="<?php echo $in_wishlist ? 'Remove from wishlist' : 'Add to wishlist'; ?>">
            <i class="<?php echo $in_wishlist ? 'fas' : 'far'; ?> fa-heart"></i>
        </button>
    </form>
    <?php endif; ?>
    <a href="product.php?id=<?php echo (int)$product['id']; ?>">
        <div class="product-image">
            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
        </div>
        <div class="product-info">
            <h4><?php echo htmlspecialchars($product['name']); ?></h4>
            <?php if ((int)$product['discount'] > 0): ?>
            <p class="product-price">
                <span class="original-price">₹<?php echo number_format($product['price'], 2); ?></span>
                <span class="discounted-price">₹<?php echo number_format($discounted_price, 2); ?></span>
            </p>
            <?php else: ?>
            <p class="product-price">₹<?php echo number_format($product['price'], 2); ?></p>
            <?php endif; ?>
        </div>
    </a>
    <?php if ($show_actions): ?>
    <div class="product-actions dual">
        <form action="cart_action.php" method="POST">
            <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'home.php'); ?>">
            <button type="submit" class="btn-add-cart">
                <i class="fas fa-cart-plus"></i> Cart
            </button>
        </form>
        <?php if ($is_mobile): ?>
        <a href="compare.php?add=<?php echo (int)$product['id']; ?>" class="btn-compare-mini" title="Compare">
            <i class="fas fa-balance-scale"></i>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

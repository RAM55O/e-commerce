<?php
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: index.php?error=Access denied. Admin only.");
    exit();
}

// Get product if editing
$product = null;
if (isset($_GET['id'])) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get categories
$cat_stmt = $conn->query("SELECT * FROM categories");
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product ? 'Edit' : 'Add'; ?> Product - Moonchild</title>
    <link rel="stylesheet" href="shop.css?v=2.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <div class="page-header">
        <a href="admin_dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <h1 class="page-title"><?php echo $product ? 'Edit' : 'Add'; ?> Product</h1>
    </div>

    <main class="main-content">
        <div class="form-container">
            <form action="product_action.php" method="POST">
                <input type="hidden" name="action" value="<?php echo $product ? 'update' : 'add'; ?>">
                <?php if($product): ?>
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Product Name *</label>
                    <input type="text" name="name" value="<?php echo $product ? htmlspecialchars($product['name']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label>Category *</label>
                    <select name="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ($product && $product['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Price ($) *</label>
                    <input type="number" name="price" step="0.01" min="0" value="<?php echo $product ? $product['price'] : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label>Discount (%) <span style="color: #00d4aa; font-size: 0.8rem;">- Leave 0 for no discount</span></label>
                    <input type="number" name="discount" min="0" max="100" value="<?php echo $product ? ($product['discount'] ?? 0) : '0'; ?>">
                </div>

                <div class="form-group">
                    <label>Stock Quantity *</label>
                    <input type="number" name="stock" min="0" value="<?php echo $product ? $product['stock'] : '0'; ?>" required>
                </div>

                <div class="form-group">
                    <label>Brand</label>
                    <input type="text" name="brand" value="<?php echo $product ? htmlspecialchars($product['brand']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>Image URL *</label>
                    <input type="url" name="image" value="<?php echo $product ? htmlspecialchars($product['image']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description"><?php echo $product ? htmlspecialchars($product['description']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label>Specifications (separate with | )</label>
                    <textarea name="specifications" placeholder="Display: 6.7 inch|RAM: 8GB|Storage: 256GB"><?php echo $product ? htmlspecialchars($product['specifications']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label>Rating (0-5)</label>
                    <input type="number" name="rating" step="0.1" min="0" max="5" value="<?php echo $product ? $product['rating'] : '0'; ?>">
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" name="featured" value="1" <?php echo ($product && $product['featured']) ? 'checked' : ''; ?> style="width: auto;">
                        Featured Product
                    </label>
                </div>

                <div class="form-actions">
                    <a href="admin_dashboard.php" class="btn-cancel">Cancel</a>
                    <button type="submit" class="btn-submit">
                        <?php echo $product ? 'Update Product' : 'Add Product'; ?>
                    </button>
                </div>
            </form>
        </div>
    </main>

    <!-- Admin Bottom Navigation -->
    <nav class="bottom-nav admin-nav">
        <a href="admin_orders.php" class="nav-item">
            <i class="fas fa-shopping-bag"></i>
            <span>Orders</span>
        </a>
        <a href="product_form.php" class="nav-item active">
            <i class="fas fa-plus-circle"></i>
            <span>Add Product</span>
        </a>
        <a href="admin_dashboard.php" class="nav-item center-btn">
            <div class="nav-icon-wrapper">
                <i class="fas fa-th-large"></i>
            </div>
            <span>Dashboard</span>
        </a>
        <a href="admin_settings.php" class="nav-item">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
        <a href="profile.php" class="nav-item">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
    </nav>
    <script src="script.js?v=2.1"></script>
</body>
</html>

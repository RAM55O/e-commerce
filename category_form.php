<?php
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: index.php?error=Access denied. Admin only.");
    exit();
}

// Get category if editing
$category = null;
if (isset($_GET['id'])) {
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Font Awesome icons for categories
$icons = [
    'fas fa-robot' => 'Robot',
    'fas fa-laptop' => 'Laptop',
    'fas fa-mobile-alt' => 'Mobile',
    'fas fa-tshirt' => 'T-Shirt',
    'fas fa-headphones' => 'Headphones',
    'fas fa-camera' => 'Camera',
    'fas fa-gamepad' => 'Gamepad',
    'fas fa-tv' => 'TV',
    'fas fa-watch' => 'Watch',
    'fas fa-gem' => 'Gem',
    'fas fa-shoe-prints' => 'Shoes',
    'fas fa-home' => 'Home',
    'fas fa-car' => 'Car',
    'fas fa-book' => 'Book',
    'fas fa-gift' => 'Gift',
    'fas fa-box' => 'Box'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $category ? 'Edit' : 'Add'; ?> Category - Moonchild</title>
    <link rel="stylesheet" href="shop.css?v=2.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <div class="page-header">
        <a href="admin_dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <h1 class="page-title"><?php echo $category ? 'Edit' : 'Add'; ?> Category</h1>
    </div>

    <main class="main-content">
        <div class="form-container">
            <form action="category_action.php" method="POST">
                <input type="hidden" name="action" value="<?php echo $category ? 'update' : 'add'; ?>">
                <?php if($category): ?>
                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Category Name *</label>
                    <input type="text" name="name" value="<?php echo $category ? htmlspecialchars($category['name']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label>Icon *</label>
                    <select name="icon" required>
                        <?php foreach($icons as $icon_class => $icon_name): ?>
                        <option value="<?php echo $icon_class; ?>" <?php echo ($category && $category['icon'] == $icon_class) ? 'selected' : ''; ?>>
                            <?php echo $icon_name; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Icon Preview</label>
                    <div style="background: rgba(20, 25, 45, 0.8); padding: 20px; border-radius: 10px; text-align: center;">
                        <i id="iconPreview" class="<?php echo $category ? $category['icon'] : 'fas fa-robot'; ?>" style="font-size: 2rem; color: #7c9ad6;"></i>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="admin_dashboard.php" class="btn-cancel">Cancel</a>
                    <button type="submit" class="btn-submit">
                        <?php echo $category ? 'Update Category' : 'Add Category'; ?>
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
        <a href="product_form.php" class="nav-item">
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

    <script>
        document.querySelector('select[name="icon"]').addEventListener('change', function() {
            document.getElementById('iconPreview').className = this.value;
        });
    </script>
    <script src="script.js?v=2.1"></script>
</body>
</html>

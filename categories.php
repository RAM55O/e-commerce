<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Please login first");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Get categories
$cat_stmt = $conn->query("SELECT * FROM categories");
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get products by category if selected
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$selected_category = null;

if ($category_id) {
    $cat_check = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $cat_check->execute([$category_id]);
    $selected_category = $cat_check->fetch(PDO::FETCH_ASSOC);
    
    $prod_stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.category_id = ?");
    $prod_stmt->execute([$category_id]);
} else {
    $prod_stmt = $conn->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id");
}
$products = $prod_stmt->fetchAll(PDO::FETCH_ASSOC);
$wishlist_ids = user_wishlist_ids($conn, (int)$user_id);
$mobiles_category_id = get_mobiles_category_id($conn);

// Get cart count
$cart_stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
$cart_stmt->execute([$user_id]);
$cart_count = $cart_stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $selected_category ? htmlspecialchars($selected_category['name']) : 'Categories'; ?> - Moonchild</title>
    <link rel="stylesheet" href="shop.css?v=2.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <a href="home.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
            <h1 class="logo">Moonchild</h1>
            <div class="header-icons">
                <a href="cart.php" class="icon-btn">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if($cart_count > 0): ?>
                    <span class="badge"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <?php if(!$category_id): ?>
        <!-- All Categories -->
        <section class="section">
            <h3 class="section-title">Browse Categories</h3>
            <div class="categories-grid">
                <?php foreach($categories as $cat): ?>
                <a href="categories.php?id=<?php echo $cat['id']; ?>" class="category-card">
                    <div class="category-icon">
                        <i class="<?php echo $cat['icon']; ?>"></i>
                    </div>
                    <span><?php echo htmlspecialchars($cat['name']); ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- All Products -->
        <section class="section">
            <h3 class="section-title">All Products</h3>
        <?php else: ?>
        <!-- Category Products -->
        <section class="section">
            <h3 class="section-title"><?php echo htmlspecialchars($selected_category['name']); ?></h3>
        <?php endif; ?>

            <div class="products-grid">
                <?php if(count($products) > 0): ?>
                    <?php foreach($products as $product) { include 'includes/product_card.php'; } ?>
                <?php else: ?>
                    <p class="no-results">No products found in this category.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="home.php" class="nav-item">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="categories.php" class="nav-item active">
            <i class="fas fa-th-large"></i>
            <span>Categories</span>
        </a>
        <a href="cart.php" class="nav-item center-btn">
            <div class="nav-icon-wrapper">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <span>Cart</span>
        </a>
        <a href="wishlist.php" class="nav-item">
            <i class="fas fa-heart"></i>
            <span>Wishlist</span>
        </a>
        <a href="profile.php" class="nav-item">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
    </nav>
    <script src="script.js?v=2.1"></script>
</body>
</html>

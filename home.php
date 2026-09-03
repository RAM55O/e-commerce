<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Please login first");
    exit();
}

$user_type = $_SESSION['user_type'];
$username = $_SESSION['username'];
$user_id = (int)$_SESSION['user_id'];
$wishlist_ids = user_wishlist_ids($conn, $user_id);
$mobiles_category_id = get_mobiles_category_id($conn);

if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    header('Location: search.php?q=' . urlencode(trim($_GET['search'])));
    exit();
}
if (isset($_GET['category']) && (int)$_GET['category'] > 0) {
    header('Location: search.php?category=' . (int)$_GET['category']);
    exit();
}

// Get cart count
$cart_stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
$cart_stmt->execute([$user_id]);
$cart_count = $cart_stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

// Get wishlist count
$wish_stmt = $conn->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?");
$wish_stmt->execute([$user_id]);
$wishlist_count = $wish_stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

// Get categories with their products
$cat_stmt = $conn->query("SELECT * FROM categories");
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get featured products
$prod_stmt = $conn->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.featured = 1 LIMIT 6");
$featured_products = $prod_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get products with discount for offers banner
$offer_stmt = $conn->query("SELECT COUNT(*) as count, MAX(discount) as max_discount FROM products WHERE discount > 0");
$offer_info = $offer_stmt->fetch(PDO::FETCH_ASSOC);

function getProductsByCategory($conn, $category_id, $limit = 4) {
    $limit = (int)$limit;
    $stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.category_id = ? LIMIT $limit");
    $stmt->execute([$category_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moonchild - Home</title>
    <link rel="stylesheet" href="shop.css?v=3.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <h1 class="logo">Moonchild</h1>
            <form class="search-bar" action="search.php" method="GET">
                <i class="fas fa-search"></i>
                <input type="text" name="q" placeholder="Search & filter products...">
            </form>
            <div class="header-icons">
                <a href="compare.php" class="icon-btn" title="Compare mobiles">
                    <i class="fas fa-balance-scale"></i>
                </a>
                <a href="search.php" class="icon-btn" title="Filters">
                    <i class="fas fa-sliders-h"></i>
                </a>
                <a href="wishlist.php" class="icon-btn">
                    <i class="fas fa-heart"></i>
                    <?php if($wishlist_count > 0): ?>
                    <span class="badge"><?php echo $wishlist_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="cart.php" class="icon-btn">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if($cart_count > 0): ?>
                    <span class="badge"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Hero Banner -->
        <section class="hero-banner">
            <div class="hero-content">
                <h2>Experience Tomorrow,<br>Today.</h2>
                <p>Discover the latest innovations and exclusive arrivals.</p>
                <a href="offers.php" class="btn-primary">View Offers</a>
                <a href="compare.php" class="btn-primary" style="margin-left:8px;background:#1a1f3a;">Compare Mobiles</a>
            </div>
            <div class="hero-image">
                <img src="https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=400" alt="Hero">
            </div>
        </section>

        <?php if($offer_info['count'] > 0): ?>
        <!-- Offers Banner -->
        <a href="offers.php" class="offers-banner" style="display: block; background: linear-gradient(135deg, #ff6b6b 0%, #ffd700 100%); border-radius: 15px; padding: 15px 20px; margin-bottom: 25px; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <span style="font-size: 2rem;">🔥</span>
                <div>
                    <h4 style="color: #1a1f3a; margin: 0; font-size: 1rem;">Special Offers!</h4>
                    <p style="color: #1a1f3a; margin: 0; font-size: 0.85rem; opacity: 0.8;">Up to <?php echo $offer_info['max_discount']; ?>% off on <?php echo $offer_info['count']; ?> items</p>
                </div>
            </div>
            <i class="fas fa-arrow-right" style="color: #1a1f3a;"></i>
        </a>
        <?php endif; ?>

        <!-- Categories Quick Links -->
        <section class="section">
            <h3 class="section-title">Browse Categories</h3>
            <div class="categories-grid">
                <?php foreach($categories as $cat): ?>
                <a href="search.php?category=<?php echo $cat['id']; ?>" class="category-card">
                    <div class="category-icon">
                        <i class="<?php echo $cat['icon']; ?>"></i>
                    </div>
                    <span><?php echo htmlspecialchars($cat['name']); ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="section">
            <h3 class="section-title">⭐ Featured Products</h3>
            <div class="products-grid">
                <?php if(count($featured_products) > 0): ?>
                    <?php foreach($featured_products as $product) { include 'includes/product_card.php'; } ?>
                <?php else: ?>
                    <p class="no-results">No featured products.</p>
                <?php endif; ?>
            </div>
        </section>

        <?php foreach($categories as $cat): 
            $cat_products = getProductsByCategory($conn, $cat['id'], 4);
            if(count($cat_products) > 0):
        ?>
        <section class="section" id="category-<?php echo $cat['id']; ?>">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 class="section-title" style="margin-bottom: 0;">
                    <i class="<?php echo $cat['icon']; ?>" style="margin-right: 10px; color: #00d4aa;"></i>
                    <?php echo htmlspecialchars($cat['name']); ?>
                </h3>
                <a href="search.php?category=<?php echo $cat['id']; ?>" style="color: #00d4aa; font-size: 0.85rem; text-decoration: none;">
                    View All <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="products-grid">
                <?php foreach($cat_products as $product) { include 'includes/product_card.php'; } ?>
            </div>
        </section>
        <?php endif; endforeach; ?>
    </main>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="home.php" class="nav-item active">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="offers.php" class="nav-item">
            <i class="fas fa-percent"></i>
            <span>Offers</span>
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

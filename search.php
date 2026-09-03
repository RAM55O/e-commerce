<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Please login first");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$wishlist_ids = user_wishlist_ids($conn, $user_id);
$mobiles_category_id = get_mobiles_category_id($conn);

$q = trim($_GET['q'] ?? $_GET['search'] ?? '');
$category_id = (int)($_GET['category'] ?? 0);
$brand = trim($_GET['brand'] ?? '');
$min_price = (isset($_GET['min_price']) && $_GET['min_price'] !== '') ? (float)$_GET['min_price'] : null;
$max_price = (isset($_GET['max_price']) && $_GET['max_price'] !== '') ? (float)$_GET['max_price'] : null;
$min_rating = (isset($_GET['min_rating']) && $_GET['min_rating'] !== '') ? (float)$_GET['min_rating'] : null;
$on_sale = isset($_GET['on_sale']) && $_GET['on_sale'] === '1';
$sort = $_GET['sort'] ?? 'newest';

$sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE 1=1";
$params = [];

if ($q !== '') {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ? OR p.brand LIKE ?)";
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($category_id > 0) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category_id;
}
if ($brand !== '') {
    $sql .= " AND p.brand = ?";
    $params[] = $brand;
}
if ($min_price !== null) {
    $sql .= " AND p.price >= ?";
    $params[] = $min_price;
}
if ($max_price !== null) {
    $sql .= " AND p.price <= ?";
    $params[] = $max_price;
}
if ($min_rating !== null) {
    $sql .= " AND p.rating >= ?";
    $params[] = $min_rating;
}
if ($on_sale) {
    $sql .= " AND p.discount > 0";
}

switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY (p.price - (p.price * p.discount / 100)) ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY (p.price - (p.price * p.discount / 100)) DESC";
        break;
    case 'rating':
        $sql .= " ORDER BY p.rating DESC";
        break;
    case 'discount':
        $sql .= " ORDER BY p.discount DESC";
        break;
    default:
        $sql .= " ORDER BY p.created_at DESC";
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$brands = $conn->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand <> '' ORDER BY brand")->fetchAll(PDO::FETCH_COLUMN);

$cart_stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
$cart_stmt->execute([$user_id]);
$cart_count = $cart_stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search & Filter - Moonchild</title>
    <link rel="stylesheet" href="shop.css?v=3.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="header-content">
            <a href="home.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
            <form class="search-bar" action="search.php" method="GET">
                <i class="fas fa-search"></i>
                <input type="text" name="q" placeholder="Search products..." value="<?php echo htmlspecialchars($q); ?>">
            </form>
            <div class="header-icons">
                <a href="compare.php" class="icon-btn" title="Compare mobiles"><i class="fas fa-balance-scale"></i></a>
                <a href="cart.php" class="icon-btn">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if ($cart_count > 0): ?><span class="badge"><?php echo (int)$cart_count; ?></span><?php endif; ?>
                </a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <form class="filter-panel" method="GET" action="search.php">
            <input type="hidden" name="q" value="<?php echo htmlspecialchars($q); ?>">
            <div class="filter-grid">
                <div class="form-group">
                    <label>Category</label>
                    <select name="category">
                        <option value="0">All categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo (int)$cat['id']; ?>" <?php echo $category_id === (int)$cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Brand</label>
                    <select name="brand">
                        <option value="">All brands</option>
                        <?php foreach ($brands as $b): ?>
                        <option value="<?php echo htmlspecialchars($b); ?>" <?php echo $brand === $b ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($b); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Min price</label>
                    <input type="number" name="min_price" min="0" step="1" value="<?php echo $min_price !== null ? htmlspecialchars((string)$min_price) : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Max price</label>
                    <input type="number" name="max_price" min="0" step="1" value="<?php echo $max_price !== null ? htmlspecialchars((string)$max_price) : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Min rating</label>
                    <select name="min_rating">
                        <option value="">Any</option>
                        <?php foreach ([4, 4.5, 3] as $r): ?>
                        <option value="<?php echo $r; ?>" <?php echo $min_rating == $r ? 'selected' : ''; ?>><?php echo $r; ?>+</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Sort by</label>
                    <select name="sort">
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
                        <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>Top rated</option>
                        <option value="discount" <?php echo $sort === 'discount' ? 'selected' : ''; ?>>Biggest discount</option>
                    </select>
                </div>
            </div>
            <label class="filter-check">
                <input type="checkbox" name="on_sale" value="1" <?php echo $on_sale ? 'checked' : ''; ?>>
                On sale / discounted only
            </label>
            <div class="filter-actions">
                <button type="submit" class="btn-primary">Apply filters</button>
                <a href="search.php" class="filter-tab">Reset</a>
            </div>
        </form>

        <section class="section">
            <h3 class="section-title"><?php echo count($products); ?> product<?php echo count($products) === 1 ? '' : 's'; ?> found</h3>
            <div class="products-grid">
                <?php if ($products): ?>
                    <?php foreach ($products as $product) { include 'includes/product_card.php'; } ?>
                <?php else: ?>
                    <p class="no-results">No products match your search and filters.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php $nav_active = 'home'; include 'includes/user_nav.php'; ?>
    <script src="script.js?v=2.1"></script>
</body>
</html>

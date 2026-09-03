<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Please login first");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $zip_code = trim($_POST['zip_code'] ?? '');
    $country = trim($_POST['country'] ?? '');
    
    try {
        // Check if email is already taken by another user
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check_stmt->execute([$email, $user_id]);
        
        if ($check_stmt->rowCount() > 0) {
            $error_msg = "Email is already taken by another user.";
        } else {
            $update_stmt = $conn->prepare("
                UPDATE users SET 
                    first_name = ?, 
                    surname = ?, 
                    mobile = ?, 
                    email = ?,
                    address = ?,
                    city = ?,
                    zip_code = ?,
                    country = ?
                WHERE id = ?
            ");
            $update_stmt->execute([$first_name, $surname, $mobile, $email, $address, $city, $zip_code, $country, $user_id]);
            
            // Update session email if changed
            $_SESSION['email'] = $email;
                header("Location: profile.php?success=" . urlencode("Profile updated successfully!"));
                exit();
        }
    } catch (PDOException $e) {
            header("Location: profile.php?error=" . urlencode("Error updating profile. Please try again."));
            exit();
    }
}

if (isset($_GET['success'])) {
    $success_msg = $_GET['success'];
}

if (isset($_GET['error'])) {
    $error_msg = $_GET['error'];
}

// Fetch current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$username = $user['username'];
$email = $user['email'];
$user_type = $user['user_type'];
$first_name = $user['first_name'] ?? '';
$surname = $user['surname'] ?? '';
$mobile = $user['mobile'] ?? '';
$address = $user['address'] ?? '';
$city = $user['city'] ?? '';
$zip_code = $user['zip_code'] ?? '';
$country = $user['country'] ?? '';

// Get order count
$order_stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
$order_stmt->execute([$user_id]);
$order_count = $order_stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

// Get wishlist count
$wish_stmt = $conn->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?");
$wish_stmt->execute([$user_id]);
$wishlist_count = $wish_stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Moonchild</title>
    <link rel="stylesheet" href="shop.css?v=2.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-section {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .profile-section-title {
            font-size: 1rem;
            font-weight: 600;
            color: #00d4aa;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .profile-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-group label {
            font-size: 0.85rem;
            color: #a0a0a0;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 12px 15px;
            color: #fff;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #00d4aa;
            background: rgba(0, 212, 170, 0.1);
        }
        
        .form-group input:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .form-group textarea {
            resize: none;
            min-height: 80px;
        }
        
        .btn-save {
            background: linear-gradient(135deg, #00d4aa 0%, #00a887 100%);
            color: #fff;
            border: none;
            padding: 14px 30px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 212, 170, 0.4);
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: rgba(0, 212, 170, 0.2);
            color: #00d4aa;
            border: 1px solid rgba(0, 212, 170, 0.3);
        }
        
        .alert-error {
            background: rgba(255, 107, 107, 0.2);
            color: #ff6b6b;
            border: 1px solid rgba(255, 107, 107, 0.3);
        }
        
        .profile-header-card {
            background: linear-gradient(135deg, rgba(0, 212, 170, 0.2) 0%, rgba(102, 126, 234, 0.2) 100%);
            border-radius: 20px;
            padding: 30px 20px;
            text-align: center;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .profile-header-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
            animation: shimmer 3s infinite;
        }
        
        @keyframes shimmer {
            0%, 100% { transform: rotate(0deg); }
            50% { transform: rotate(180deg); }
        }
        
        .quick-links {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .quick-link {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 15px 10px;
            text-align: center;
            text-decoration: none;
            color: #fff;
            transition: all 0.3s ease;
        }
        
        .quick-link:hover {
            background: rgba(0, 212, 170, 0.2);
            transform: translateY(-2px);
        }
        
        .quick-link i {
            font-size: 1.5rem;
            margin-bottom: 8px;
            display: block;
            background: linear-gradient(135deg, #00d4aa, #667eea);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .quick-link span {
            font-size: 0.75rem;
            color: #a0a0a0;
        }
        
        .quick-link .count {
            display: block;
            font-size: 1.1rem;
            font-weight: 700;
            color: #fff;
            margin-top: 2px;
        }
        
        .logout-btn {
            background: rgba(255, 107, 107, 0.15);
            border: 1px solid rgba(255, 107, 107, 0.3);
            color: #ff6b6b;
            padding: 14px 30px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            text-decoration: none;
            margin-top: 10px;
        }
        
        .logout-btn:hover {
            background: rgba(255, 107, 107, 0.25);
            transform: translateY(-2px);
        }
        
        .edit-toggle {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            color: #fff;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 10;
        }
        
        .edit-toggle:hover {
            background: rgba(0, 212, 170, 0.3);
        }
        
        @media (max-width: 480px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="page-header">
        <a href="<?php echo ($user_type === 'admin') ? 'admin_dashboard.php' : 'home.php'; ?>" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <h1 class="page-title">My Profile</h1>
    </div>

    <div class="profile-container" style="padding-bottom: 100px;">
        
        <!-- Profile Header Card -->
        <div class="profile-header-card">
            <div class="profile-avatar">
                <i class="fas fa-user"></i>
            </div>
            <h2 class="profile-name">
                <?php 
                    $display_name = trim($first_name . ' ' . $surname);
                    echo htmlspecialchars($display_name ?: $username); 
                ?>
            </h2>
            <p class="profile-email"><?php echo htmlspecialchars($email); ?></p>
            
            <?php if($user_type === 'admin'): ?>
            <span style="display: inline-block; background: linear-gradient(90deg, #ffd700, #ffed4a); color: #1a1f3a; padding: 5px 15px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; margin-top: 10px;">ADMIN</span>
            <?php else: ?>
            <span style="display: inline-block; background: linear-gradient(90deg, #00d4aa, #00f5c4); color: #1a1f3a; padding: 5px 15px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; margin-top: 10px;">CUSTOMER</span>
            <?php endif; ?>
        </div>
        
        <!-- Quick Links -->
        <div class="quick-links">
            <a href="wishlist.php" class="quick-link">
                <i class="fas fa-heart"></i>
                <span>Wishlist</span>
                <span class="count"><?php echo $wishlist_count; ?></span>
            </a>
            <a href="orders.php" class="quick-link">
                <i class="fas fa-shopping-bag"></i>
                <span>Orders</span>
                <span class="count"><?php echo $order_count; ?></span>
            </a>
            <?php if($user_type === 'admin'): ?>
            <a href="admin_dashboard.php" class="quick-link">
                <i class="fas fa-cog"></i>
                <span>Admin</span>
                <span class="count"><i class="fas fa-arrow-right" style="font-size: 0.8rem;"></i></span>
            </a>
            <?php else: ?>
            <a href="cart.php" class="quick-link">
                <i class="fas fa-shopping-cart"></i>
                <span>Cart</span>
                <span class="count"><i class="fas fa-arrow-right" style="font-size: 0.8rem;"></i></span>
            </a>
            <a href="compare.php" class="quick-link">
                <i class="fas fa-balance-scale"></i>
                <span>Compare</span>
                <span class="count"><i class="fas fa-arrow-right" style="font-size: 0.8rem;"></i></span>
            </a>
            <a href="search.php" class="quick-link">
                <i class="fas fa-search"></i>
                <span>Search</span>
                <span class="count"><i class="fas fa-arrow-right" style="font-size: 0.8rem;"></i></span>
            </a>
            <a href="offers.php" class="quick-link">
                <i class="fas fa-percent"></i>
                <span>Offers</span>
                <span class="count"><i class="fas fa-arrow-right" style="font-size: 0.8rem;"></i></span>
            </a>
            <a href="user_dashboard.php" class="quick-link">
                <i class="fas fa-th-large"></i>
                <span>Panel</span>
                <span class="count"><i class="fas fa-arrow-right" style="font-size: 0.8rem;"></i></span>
            </a>
            <?php endif; ?>
        </div>

        <!-- Success/Error Messages -->
        <?php if($success_msg): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($success_msg); ?>
        </div>
        <?php endif; ?>
        
        <?php if($error_msg): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error_msg); ?>
        </div>
        <?php endif; ?>

        <!-- Profile Edit Form -->
        <form method="POST" class="profile-form">
            <input type="hidden" name="update_profile" value="1">
            
            <!-- Personal Information -->
            <div class="profile-section">
                <h3 class="profile-section-title">
                    <i class="fas fa-user-circle"></i>
                    Personal Information
                </h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" 
                               value="<?php echo htmlspecialchars($first_name); ?>" 
                               placeholder="Enter first name">
                    </div>
                    <div class="form-group">
                        <label for="surname">Surname</label>
                        <input type="text" id="surname" name="surname" 
                               value="<?php echo htmlspecialchars($surname); ?>" 
                               placeholder="Enter surname">
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 15px;">
                    <label for="username">Username</label>
                    <input type="text" id="username" value="<?php echo htmlspecialchars($username); ?>" disabled>
                </div>
            </div>
            
            <!-- Contact Information -->
            <div class="profile-section">
                <h3 class="profile-section-title">
                    <i class="fas fa-address-book"></i>
                    Contact Information
                </h3>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($email); ?>" 
                           placeholder="Enter email address" required>
                </div>
                
                <div class="form-group" style="margin-top: 15px;">
                    <label for="mobile">Mobile Number</label>
                    <input type="tel" id="mobile" name="mobile" 
                           value="<?php echo htmlspecialchars($mobile); ?>" 
                           placeholder="Enter mobile number">
                </div>
            </div>
            
            <!-- Address Information -->
            <div class="profile-section">
                <h3 class="profile-section-title">
                    <i class="fas fa-map-marker-alt"></i>
                    Home Address
                </h3>
                
                <div class="form-group">
                    <label for="address">Street Address</label>
                    <textarea id="address" name="address" 
                              placeholder="Enter your street address"><?php echo htmlspecialchars($address); ?></textarea>
                </div>
                
                <div class="form-row" style="margin-top: 15px;">
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" 
                               value="<?php echo htmlspecialchars($city); ?>" 
                               placeholder="Enter city">
                    </div>
                    <div class="form-group">
                        <label for="zip_code">ZIP Code</label>
                        <input type="text" id="zip_code" name="zip_code" 
                               value="<?php echo htmlspecialchars($zip_code); ?>" 
                               placeholder="Enter ZIP code">
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 15px;">
                    <label for="country">Country</label>
                    <input type="text" id="country" name="country" 
                           value="<?php echo htmlspecialchars($country); ?>" 
                           placeholder="Enter country">
                </div>
            </div>
            
            <!-- Save Button -->
            <button type="submit" class="btn-save">
                <i class="fas fa-save"></i>
                Save Changes
            </button>
            
            <!-- Logout Button -->
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </form>
    </div>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <?php if($user_type === 'admin'): ?>
        <a href="admin_dashboard.php" class="nav-item">
            <i class="fas fa-chart-line"></i>
            <span>Dashboard</span>
        </a>
        <a href="admin_orders.php" class="nav-item">
            <i class="fas fa-shopping-bag"></i>
            <span>Orders</span>
        </a>
        <a href="categories.php" class="nav-item center-btn">
            <div class="nav-icon-wrapper">
                <i class="fas fa-th-large"></i>
            </div>
            <span>Categories</span>
        </a>
        <a href="admin_settings.php" class="nav-item">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
        <a href="profile.php" class="nav-item active">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
        <?php else: ?>
        <a href="home.php" class="nav-item">
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
        <a href="profile.php" class="nav-item active">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
        <?php endif; ?>
    </nav>
    <script src="script.js?v=2.1"></script>
</body>
</html>

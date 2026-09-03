<?php
$nav_active = $nav_active ?? 'home';
?>
<nav class="bottom-nav">
    <a href="home.php" class="nav-item <?php echo $nav_active === 'home' ? 'active' : ''; ?>">
        <i class="fas fa-home"></i>
        <span>Home</span>
    </a>
    <a href="offers.php" class="nav-item <?php echo $nav_active === 'offers' ? 'active' : ''; ?>">
        <i class="fas fa-percent"></i>
        <span>Offers</span>
    </a>
    <a href="cart.php" class="nav-item center-btn <?php echo $nav_active === 'cart' ? 'active' : ''; ?>">
        <div class="nav-icon-wrapper">
            <i class="fas fa-shopping-cart"></i>
        </div>
        <span>Cart</span>
    </a>
    <a href="wishlist.php" class="nav-item <?php echo $nav_active === 'wishlist' ? 'active' : ''; ?>">
        <i class="fas fa-heart"></i>
        <span>Wishlist</span>
    </a>
    <a href="profile.php" class="nav-item <?php echo $nav_active === 'profile' ? 'active' : ''; ?>">
        <i class="fas fa-user"></i>
        <span>Profile</span>
    </a>
</nav>

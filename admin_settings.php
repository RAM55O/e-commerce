<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: index.php?error=Access denied");
    exit();
}

// Handle customer delete
if (isset($_GET['delete_customer'])) {
    $customer_id = (int)$_GET['delete_customer'];
    // Prevent deleting admin users or self
    $check_stmt = $conn->prepare("SELECT user_type FROM users WHERE id = ?");
    $check_stmt->execute([$customer_id]);
    $user_check = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_check && $user_check['user_type'] !== 'admin' && $customer_id !== $_SESSION['user_id']) {
        $del_stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND user_type = 'user'");
        $del_stmt->execute([$customer_id]);
        header("Location: admin_settings.php?success=Customer deleted successfully&tab=customers");
        exit();
    } else {
        header("Location: admin_settings.php?error=Cannot delete this user&tab=customers");
        exit();
    }
}

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $shipping_cost = $_POST['shipping_cost'];
    $free_threshold = $_POST['free_shipping_threshold'];
    
    // Update or insert settings
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('shipping_cost', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->execute([$shipping_cost, $shipping_cost]);
    
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('free_shipping_threshold', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->execute([$free_threshold, $free_threshold]);
    
    header("Location: admin_settings.php?success=Settings updated successfully");
    exit();
}

// Handle add customer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_customer'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $first_name = trim($_POST['first_name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    
    // Check if username or email exists
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $check_stmt->execute([$username, $email]);
    
    if ($check_stmt->rowCount() > 0) {
        header("Location: admin_settings.php?error=Username or email already exists&tab=customers");
        exit();
    }
    
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, first_name, surname, mobile, user_type) VALUES (?, ?, ?, ?, ?, ?, 'user')");
    $stmt->execute([$username, $email, $password, $first_name, $surname, $mobile]);
    
    header("Location: admin_settings.php?success=Customer added successfully&tab=customers");
    exit();
}

// Handle update customer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_customer'])) {
    $customer_id = (int)$_POST['customer_id'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $first_name = trim($_POST['first_name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $zip_code = trim($_POST['zip_code'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    
    // Check if username or email exists for other users
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
    $check_stmt->execute([$username, $email, $customer_id]);
    
    if ($check_stmt->rowCount() > 0) {
        header("Location: admin_settings.php?error=Username or email already exists&tab=customers");
        exit();
    }
    
    if (!empty($new_password)) {
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ?, first_name = ?, surname = ?, mobile = ?, address = ?, city = ?, zip_code = ?, country = ? WHERE id = ? AND user_type = 'user'");
        $stmt->execute([$username, $email, $new_password, $first_name, $surname, $mobile, $address, $city, $zip_code, $country, $customer_id]);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, first_name = ?, surname = ?, mobile = ?, address = ?, city = ?, zip_code = ?, country = ? WHERE id = ? AND user_type = 'user'");
        $stmt->execute([$username, $email, $first_name, $surname, $mobile, $address, $city, $zip_code, $country, $customer_id]);
    }
    
    header("Location: admin_settings.php?success=Customer updated successfully&tab=customers");
    exit();
}

// Get current settings
$settings = [];
$stmt = $conn->query("SELECT * FROM settings");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$shipping_cost = $settings['shipping_cost'] ?? '25.00';
$free_threshold = $settings['free_shipping_threshold'] ?? '100.00';

// Get analytics
$revenue_stmt = $conn->query("SELECT 
    SUM(total_amount) as total_revenue,
    SUM(shipping_cost) as total_shipping,
    SUM(subtotal) as product_revenue,
    COUNT(*) as total_orders
    FROM orders WHERE status != 'cancelled'");
$analytics = $revenue_stmt->fetch(PDO::FETCH_ASSOC);

// Monthly revenue
$monthly_stmt = $conn->query("SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    SUM(total_amount) as revenue,
    SUM(shipping_cost) as shipping,
    COUNT(*) as orders
    FROM orders 
    WHERE status != 'cancelled' 
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 6");
$monthly_data = $monthly_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all customers
$customers_stmt = $conn->query("SELECT u.*, 
    (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as order_count,
    (SELECT SUM(total_amount) FROM orders WHERE user_id = u.id AND status != 'cancelled') as total_spent
    FROM users u WHERE u.user_type = 'user' ORDER BY u.created_at DESC");
$customers = $customers_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get customer for editing
$edit_customer = null;
if (isset($_GET['edit_customer'])) {
    $edit_id = (int)$_GET['edit_customer'];
    $edit_stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND user_type = 'user'");
    $edit_stmt->execute([$edit_id]);
    $edit_customer = $edit_stmt->fetch(PDO::FETCH_ASSOC);
}

// Active tab
$active_tab = $_GET['tab'] ?? 'analytics';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Moonchild Admin</title>
    <link rel="stylesheet" href="shop.css?v=2.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .settings-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            overflow-x: auto;
            padding-bottom: 5px;
        }
        .settings-tab {
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: #a0a0a0;
            text-decoration: none;
            font-size: 0.9rem;
            white-space: nowrap;
            transition: all 0.3s ease;
        }
        .settings-tab:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }
        .settings-tab.active {
            background: linear-gradient(135deg, #00d4aa 0%, #00a887 100%);
            color: #fff;
            border-color: #00d4aa;
        }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .customer-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .customer-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
        }
        .customer-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #00d4aa, #00f5c4);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1a1f3a;
            font-weight: bold;
            font-size: 1.1rem;
        }
        .customer-info h4 {
            margin: 0;
            color: #fff;
            font-size: 1rem;
        }
        .customer-info p {
            margin: 2px 0 0;
            color: #a0a0a0;
            font-size: 0.85rem;
        }
        .customer-stats {
            display: flex;
            gap: 20px;
            margin: 10px 0;
            padding: 10px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        .customer-stat {
            text-align: center;
        }
        .customer-stat-value {
            font-weight: 600;
            color: #00d4aa;
        }
        .customer-stat-label {
            font-size: 0.75rem;
            color: #888;
        }
        .customer-actions {
            display: flex;
            gap: 10px;
        }
        .btn-edit, .btn-delete {
            flex: 1;
            padding: 8px 15px;
            border: none;
            border-radius: 8px;
            font-size: 0.85rem;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
        }
        .btn-edit {
            background: rgba(0, 212, 170, 0.2);
            color: #00d4aa;
        }
        .btn-edit:hover { background: rgba(0, 212, 170, 0.3); }
        .btn-delete {
            background: rgba(255, 107, 107, 0.2);
            color: #ff6b6b;
        }
        .btn-delete:hover { background: rgba(255, 107, 107, 0.3); }
        
        .add-customer-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 15px;
            background: linear-gradient(135deg, #00d4aa 0%, #00a887 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-bottom: 20px;
            width: 100%;
        }
        .add-customer-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 212, 170, 0.4);
        }
        
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-overlay.active { display: flex; }
        .modal-content {
            background: #1a1f3a;
            border-radius: 15px;
            padding: 25px;
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .modal-header h3 {
            margin: 0;
            color: #fff;
        }
        .modal-close {
            background: none;
            border: none;
            color: #888;
            font-size: 1.5rem;
            cursor: pointer;
        }
        .modal-close:hover { color: #fff; }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        @media (max-width: 480px) {
            .form-row { grid-template-columns: 1fr; }
        }
        
        .search-box {
            margin-bottom: 20px;
        }
        .search-box input {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: #fff;
            font-size: 0.95rem;
        }
        .search-box input:focus {
            outline: none;
            border-color: #00d4aa;
        }
        .customer-count {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="page-header">
        <a href="admin_dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <h1 class="page-title">Settings & Analytics</h1>
    </div>

    <main class="main-content">
        <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        
        <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <!-- Settings Tabs -->
        <div class="settings-tabs">
            <a href="?tab=analytics" class="settings-tab <?php echo $active_tab === 'analytics' ? 'active' : ''; ?>">
                <i class="fas fa-chart-pie"></i> Analytics
            </a>
            <a href="?tab=shipping" class="settings-tab <?php echo $active_tab === 'shipping' ? 'active' : ''; ?>">
                <i class="fas fa-truck"></i> Shipping
            </a>
            <a href="?tab=customers" class="settings-tab <?php echo $active_tab === 'customers' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Customers
            </a>
        </div>

        <!-- Analytics Tab -->
        <div class="tab-content <?php echo $active_tab === 'analytics' ? 'active' : ''; ?>" id="analytics-tab">
            <!-- Revenue Overview -->
            <div class="detail-card">
                <h3><i class="fas fa-chart-pie"></i> Revenue Overview</h3>
                <div class="revenue-stats">
                    <div class="revenue-stat">
                        <div class="revenue-icon" style="background: linear-gradient(135deg, #00d4aa, #00f5c4);">
                            <i class="fas fa-rupee-sign"></i>
                        </div>
                        <div class="revenue-info">
                            <span class="revenue-label">Total Revenue</span>
                            <span class="revenue-value">₹<?php echo number_format($analytics['total_revenue'] ?? 0, 2); ?></span>
                        </div>
                    </div>
                    <div class="revenue-stat">
                        <div class="revenue-icon" style="background: linear-gradient(135deg, #7c5ce0, #a78bfa);">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="revenue-info">
                            <span class="revenue-label">Product Sales</span>
                            <span class="revenue-value">₹<?php echo number_format($analytics['product_revenue'] ?? 0, 2); ?></span>
                        </div>
                    </div>
                    <div class="revenue-stat">
                        <div class="revenue-icon" style="background: linear-gradient(135deg, #00bcd4, #00e5ff);">
                            <i class="fas fa-truck"></i>
                        </div>
                        <div class="revenue-info">
                            <span class="revenue-label">Shipping Collected</span>
                            <span class="revenue-value">₹<?php echo number_format($analytics['total_shipping'] ?? 0, 2); ?></span>
                        </div>
                    </div>
                    <div class="revenue-stat">
                        <div class="revenue-icon" style="background: linear-gradient(135deg, #ffd700, #ffed4a);">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <div class="revenue-info">
                            <span class="revenue-label">Total Orders</span>
                            <span class="revenue-value"><?php echo $analytics['total_orders'] ?? 0; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Breakdown -->
        <div class="detail-card">
            <h3><i class="fas fa-calendar-alt"></i> Monthly Breakdown</h3>
            <?php if(count($monthly_data) > 0): ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Orders</th>
                        <th>Product Sales</th>
                        <th>Shipping</th>
                        <th>Total Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($monthly_data as $month): 
                        $product_sales = $month['revenue'] - $month['shipping'];
                    ?>
                    <tr>
                        <td><?php echo date('F Y', strtotime($month['month'] . '-01')); ?></td>
                        <td><?php echo $month['orders']; ?></td>
                        <td>₹<?php echo number_format($product_sales, 2); ?></td>
                        <td>₹<?php echo number_format($month['shipping'], 2); ?></td>
                        <td><strong>₹<?php echo number_format($month['revenue'], 2); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="color: #888; text-align: center; padding: 20px;">No sales data available yet.</p>
            <?php endif; ?>
        </div>

        <!-- Profit Calculator -->
        <div class="detail-card">
            <h3><i class="fas fa-calculator"></i> Profit Summary</h3>
            <div class="profit-summary">
                <div class="profit-row">
                    <span>Total Product Sales</span>
                    <span class="positive">₹<?php echo number_format($analytics['product_revenue'] ?? 0, 2); ?></span>
                </div>
                <div class="profit-row">
                    <span>Shipping Collected</span>
                    <span class="positive">+₹<?php echo number_format($analytics['total_shipping'] ?? 0, 2); ?></span>
                </div>
                <div class="profit-row total">
                    <span>Gross Revenue</span>
                    <span>₹<?php echo number_format($analytics['total_revenue'] ?? 0, 2); ?></span>
                </div>
            </div>
            <p style="color: #888; font-size: 0.85rem; margin-top: 15px; text-align: center;">
                <i class="fas fa-info-circle"></i> Shipping revenue: ₹<?php echo number_format($analytics['total_shipping'] ?? 0, 2); ?> from <?php echo $analytics['total_orders'] ?? 0; ?> orders
            </p>
        </div>
        </div>
        <!-- End Analytics Tab -->

        <!-- Shipping Tab -->
        <div class="tab-content <?php echo $active_tab === 'shipping' ? 'active' : ''; ?>" id="shipping-tab">
            <div class="detail-card">
                <h3><i class="fas fa-cog"></i> Shipping Settings</h3>
                <form action="admin_settings.php" method="POST">
                    <input type="hidden" name="update_settings" value="1">
                    <div class="form-group">
                        <label>Default Shipping Cost ($)</label>
                        <input type="number" name="shipping_cost" step="0.01" min="0" value="<?php echo $shipping_cost; ?>" required>
                        <small style="color: #888;">This will be charged for orders below the free shipping threshold</small>
                    </div>
                    <div class="form-group">
                        <label>Free Shipping Threshold ($)</label>
                        <input type="number" name="free_shipping_threshold" step="0.01" min="0" value="<?php echo $free_threshold; ?>" required>
                        <small style="color: #888;">Orders above this amount get free shipping</small>
                    </div>
                    <button type="submit" class="btn-submit">Save Settings</button>
                </form>
            </div>
        </div>
        <!-- End Shipping Tab -->

        <!-- Customers Tab -->
        <div class="tab-content <?php echo $active_tab === 'customers' ? 'active' : ''; ?>" id="customers-tab">
            
            <button class="add-customer-btn" onclick="openAddModal()">
                <i class="fas fa-user-plus"></i> Add New Customer
            </button>
            
            <div class="search-box">
                <input type="text" id="customerSearch" placeholder="Search customers..." onkeyup="filterCustomers()">
            </div>
            
            <p class="customer-count"><i class="fas fa-users"></i> <?php echo count($customers); ?> customer(s) registered</p>
            
            <div id="customerList">
            <?php if(count($customers) > 0): ?>
                <?php foreach($customers as $customer): ?>
                <div class="customer-card" data-name="<?php echo strtolower($customer['username'] . ' ' . $customer['email'] . ' ' . $customer['first_name'] . ' ' . $customer['surname']); ?>">
                    <div class="customer-header">
                        <div class="customer-avatar">
                            <?php echo strtoupper(substr($customer['username'], 0, 1)); ?>
                        </div>
                        <div class="customer-info">
                            <h4><?php echo htmlspecialchars($customer['first_name'] && $customer['surname'] ? $customer['first_name'] . ' ' . $customer['surname'] : $customer['username']); ?></h4>
                            <p><?php echo htmlspecialchars($customer['email']); ?></p>
                        </div>
                    </div>
                    <div class="customer-stats">
                        <div class="customer-stat">
                            <div class="customer-stat-value"><?php echo $customer['order_count']; ?></div>
                            <div class="customer-stat-label">Orders</div>
                        </div>
                        <div class="customer-stat">
                            <div class="customer-stat-value">₹<?php echo number_format($customer['total_spent'] ?? 0, 2); ?></div>
                            <div class="customer-stat-label">Total Spent</div>
                        </div>
                        <div class="customer-stat">
                            <div class="customer-stat-value"><?php echo date('M d, Y', strtotime($customer['created_at'])); ?></div>
                            <div class="customer-stat-label">Joined</div>
                        </div>
                    </div>
                    <div class="customer-actions">
                        <a href="?edit_customer=<?php echo $customer['id']; ?>&tab=customers" class="btn-edit">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="?delete_customer=<?php echo $customer['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this customer? This will also delete their orders, cart, and wishlist.');">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: #888; text-align: center; padding: 40px;">No customers registered yet.</p>
            <?php endif; ?>
            </div>
        </div>
        <!-- End Customers Tab -->
    </main>

    <!-- Add Customer Modal -->
    <div class="modal-overlay" id="addCustomerModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-plus"></i> Add New Customer</h3>
                <button class="modal-close" onclick="closeAddModal()">&times;</button>
            </div>
            <form action="admin_settings.php" method="POST">
                <input type="hidden" name="add_customer" value="1">
                <div class="form-row">
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="first_name">
                    </div>
                    <div class="form-group">
                        <label>Surname</label>
                        <input type="text" name="surname">
                    </div>
                </div>
                <div class="form-group">
                    <label>Mobile</label>
                    <input type="text" name="mobile">
                </div>
                <button type="submit" class="btn-submit" style="width: 100%; margin-top: 15px;">
                    <i class="fas fa-plus"></i> Add Customer
                </button>
            </form>
        </div>
    </div>

    <!-- Edit Customer Modal -->
    <?php if($edit_customer): ?>
    <div class="modal-overlay active" id="editCustomerModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-edit"></i> Edit Customer</h3>
                <a href="?tab=customers" class="modal-close">&times;</a>
            </div>
            <form action="admin_settings.php" method="POST">
                <input type="hidden" name="update_customer" value="1">
                <input type="hidden" name="customer_id" value="<?php echo $edit_customer['id']; ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($edit_customer['username']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($edit_customer['email']); ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>New Password (leave blank to keep current)</label>
                    <input type="password" name="new_password">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="first_name" value="<?php echo htmlspecialchars($edit_customer['first_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Surname</label>
                        <input type="text" name="surname" value="<?php echo htmlspecialchars($edit_customer['surname'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Mobile</label>
                    <input type="text" name="mobile" value="<?php echo htmlspecialchars($edit_customer['mobile'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" rows="2"><?php echo htmlspecialchars($edit_customer['address'] ?? ''); ?></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>City</label>
                        <input type="text" name="city" value="<?php echo htmlspecialchars($edit_customer['city'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>ZIP Code</label>
                        <input type="text" name="zip_code" value="<?php echo htmlspecialchars($edit_customer['zip_code'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Country</label>
                    <input type="text" name="country" value="<?php echo htmlspecialchars($edit_customer['country'] ?? ''); ?>">
                </div>
                <button type="submit" class="btn-submit" style="width: 100%; margin-top: 15px;">
                    <i class="fas fa-save"></i> Update Customer
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

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
        <a href="admin_settings.php" class="nav-item active">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
        <a href="profile.php" class="nav-item">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
    </nav>

    <script>
        function openAddModal() {
            document.getElementById('addCustomerModal').classList.add('active');
        }
        
        function closeAddModal() {
            document.getElementById('addCustomerModal').classList.remove('active');
        }
        
        function filterCustomers() {
            const searchValue = document.getElementById('customerSearch').value.toLowerCase();
            const customerCards = document.querySelectorAll('.customer-card');
            
            customerCards.forEach(card => {
                const name = card.getAttribute('data-name');
                if (name.includes(searchValue)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        // Close modal when clicking outside
        document.getElementById('addCustomerModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddModal();
            }
        });
    </script>
    <script src="script.js?v=2.1"></script>
</body>
</html>

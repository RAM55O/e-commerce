<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moonchild - Login & Sign Up</title>
    <link rel="stylesheet" href="style.css?v=2.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <h1 class="logo">Moonchild</h1>
        
        <div class="form-container">
            <div class="tabs">
                <button class="tab-btn active" data-tab="login">Login</button>
                <button class="tab-btn" data-tab="signup">Sign Up</button>
            </div>

            <!-- Login Form -->
            <form id="loginForm" class="form active" action="login.php" method="POST">
                <div class="input-group">
                    <label>Email / Username</label>
                    <input type="text" name="email_username" placeholder="Enter your email or username" required>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="submit-btn">Login</button>
                <a href="#" class="forgot-link">Forgot Password?</a>
                
                <div class="divider">
                    <span>OR</span>
                </div>
                
                <div class="social-login">
                    <button type="button" class="social-btn google">
                        <i class="fab fa-google"></i>
                    </button>
                    <button type="button" class="social-btn apple">
                        <i class="fab fa-apple"></i>
                    </button>
                </div>
            </form>

            <!-- Sign Up Form -->
            <form id="signupForm" class="form" action="signup.php" method="POST">
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Enter your username" required>
                </div>
                <div class="input-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="Enter your email" required>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter your password" required>
                </div>
                <div class="input-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" placeholder="Confirm your password" required>
                </div>
                <button type="submit" class="submit-btn">Sign Up</button>
                
                <div class="divider">
                    <span>OR</span>
                </div>
                
                <div class="social-login">
                    <button type="button" class="social-btn google">
                        <i class="fab fa-google"></i>
                    </button>
                    <button type="button" class="social-btn apple">
                        <i class="fab fa-apple"></i>
                    </button>
                </div>
            </form>
        </div>

        <?php if(isset($_GET['error'])): ?>
        <div class="error-message"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <?php if(isset($_GET['success'])): ?>
        <div class="success-message"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
    </div>

    <script src="script.js?v=2.1"></script>
</body>
</html>

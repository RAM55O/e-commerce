-- Create database
CREATE DATABASE IF NOT EXISTS moonchild_db;
USE moonchild_db;

-- Drop existing tables if they exist (to fix structure issues)
DROP TABLE IF EXISTS cart;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS wishlist;
DROP TABLE IF EXISTS coupons;
DROP TABLE IF EXISTS users;

-- Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) DEFAULT NULL,
    surname VARCHAR(50) DEFAULT NULL,
    mobile VARCHAR(20) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    zip_code VARCHAR(20) DEFAULT NULL,
    country VARCHAR(100) DEFAULT NULL,
    user_type ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50) DEFAULT 'fas fa-box',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    discount INT DEFAULT 0,
    image VARCHAR(255),
    category_id INT,
    brand VARCHAR(100),
    stock INT DEFAULT 0,
    rating DECIMAL(2, 1) DEFAULT 0,
    specifications TEXT,
    featured TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Create cart table
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create wishlist table
CREATE TABLE wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    shipping_cost DECIMAL(10, 2) DEFAULT 25.00,
    total_amount DECIMAL(10, 2) NOT NULL,
    shipping_address TEXT,
    shipping_city VARCHAR(100),
    shipping_zip VARCHAR(20),
    shipping_country VARCHAR(100),
    payment_method VARCHAR(50) DEFAULT 'COD',
    status ENUM('pending', 'confirmed', 'processing', 'shipped', 'out_for_delivery', 'delivered', 'cancelled') DEFAULT 'pending',
    tracking_number VARCHAR(100),
    estimated_delivery DATE,
    coupon_code VARCHAR(50) DEFAULT NULL,
    coupon_discount DECIMAL(10, 2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create order_items table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create coupons table
CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    discount_percent INT NOT NULL,
    min_order DECIMAL(10,2) DEFAULT 0,
    description VARCHAR(255) DEFAULT NULL,
    active TINYINT(1) DEFAULT 1
);

INSERT INTO coupons (code, discount_percent, min_order, description, active) VALUES
('WELCOME10', 10, 0, '10% off any order', 1),
('SAVE20', 20, 500, '20% off orders of 500 or more', 1),
('MOBILE15', 15, 0, '15% extra discount on any order', 1);

CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value) VALUES 
('shipping_cost', '25.00'),
('free_shipping_threshold', '100.00'),
('tax_rate', '0');

-- Insert default admin (passwordxa: admin123)
INSERT INTO users (username, email, password, user_type) VALUES 
('admin', 'admin@moonchild.com', 'admin123', 'admin'),
('user', 'admin1@moonchild.com', 'admin123', 'user');


-- Insert categories
INSERT INTO categories (name, icon) VALUES 
('Electronics', 'fas fa-robot'),
('Laptops', 'fas fa-laptop'),
('Clothes', 'fas fa-tshirt'),
('Mobiles', 'fas fa-mobile-alt');

-- Insert sample products

-- Electronics Category (category_id: 1) - 5 products
INSERT INTO products (name, description, price, discount, image, category_id, brand, stock, rating, specifications, featured) VALUES 
('Wireless Neural Earbuds', 'Experience sound like never before with neural-link audio technology.', 399.00, 25, 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=500', 1, 'AudioTech', 75, 4.7, 'Driver: 12mm Neural|Battery: 24 hours|ANC: Advanced AI|Connectivity: Bluetooth 6.0', 1),
('HoloTab Pro', 'Tablet with floating holographic display for immersive entertainment.', 899.00, 15, 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=500', 1, 'HoloTech', 40, 4.3, 'Display: 12.9" Holographic|Processor: M3 Chip|RAM: 16GB|Storage: 256GB', 0),
('Smart VR Headset X', 'Immersive virtual reality headset with 8K resolution and haptic feedback.', 599.00, 30, 'https://images.unsplash.com/photo-1622979135225-d2ba269cf1ac?w=500', 1, 'VirtuTech', 35, 4.5, 'Display: 8K per eye|FOV: 120°|Refresh: 144Hz|Tracking: Inside-out|Weight: 350g', 1),
('AI Smart Speaker Pro', 'Voice-controlled smart speaker with holographic display and 360° sound.', 299.00, 0, 'https://images.unsplash.com/photo-1543512214-318c7553f230?w=500', 1, 'SoundWave', 80, 4.4, 'Speakers: 6 drivers|Power: 100W|AI: Neural Assistant|Connectivity: WiFi 7, Bluetooth 6.0', 0),
('Quantum Gaming Console', 'Next-gen gaming console with ray tracing and cloud gaming support.', 549.00, 20, 'https://images.unsplash.com/photo-1606144042614-b2417e99c4e3?w=500', 1, 'GameTech', 45, 4.6, 'GPU: 16 TFLOPS|Storage: 2TB SSD|Resolution: 8K|Frame Rate: 120fps|VR Ready: Yes', 1);

-- Laptops Category (category_id: 2) - 5 products
INSERT INTO products (name, description, price, discount, image, category_id, brand, stock, rating, specifications, featured) VALUES 
('NeoBook Pro Laptop', 'Ultra-thin laptop with holographic display technology and AI-powered performance for professionals.', 2499.00, 10, 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=500', 2, 'NeoTech', 30, 4.5, 'Display: 15.6" 4K Holographic|Processor: Intel i9 13th Gen|RAM: 32GB|Storage: 1TB SSD|Graphics: RTX 4080', 1),
('UltraSlim Air 15', 'Featherlight laptop with all-day battery life and stunning Retina display.', 1799.00, 0, 'https://images.unsplash.com/photo-1541807084-5c52b6b3adef?w=500', 2, 'ApexTech', 55, 4.4, 'Display: 15.3" Retina|Processor: M3 Pro|RAM: 18GB|Storage: 512GB SSD|Battery: 22 hours', 1),
('Gaming Beast X17', 'High-performance gaming laptop with RGB keyboard and liquid cooling.', 2899.00, 15, 'https://images.unsplash.com/photo-1603302576837-37561b2e2302?w=500', 2, 'AlienForce', 25, 4.7, 'Display: 17.3" 360Hz|Processor: Intel i9 14th Gen|RAM: 64GB|Storage: 2TB SSD|Graphics: RTX 4090', 1),
('ChromeBook Flex', 'Versatile 2-in-1 Chromebook perfect for students and everyday use.', 449.00, 35, 'https://images.unsplash.com/photo-1585909695284-32d2985ac9c0?w=500', 2, 'EduTech', 100, 4.1, 'Display: 14" FHD Touch|Processor: Intel i5|RAM: 8GB|Storage: 128GB eMMC|Battery: 12 hours', 0),
('WorkStation Pro 16', 'Professional workstation laptop for 3D rendering and video editing.', 3499.00, 5, 'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=500', 2, 'ProTech', 20, 4.8, 'Display: 16" 6K Mini-LED|Processor: Xeon W|RAM: 128GB ECC|Storage: 4TB SSD|Graphics: RTX A6000', 0);

-- Clothes Category (category_id: 3) - 5 products
INSERT INTO products (name, description, price, discount, image, category_id, brand, stock, rating, specifications, featured) VALUES 
('Smart LED T-Shirt', 'Wearable tech meets fashion with programmable LED display patterns.', 149.00, 40, 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=500', 3, 'TechWear', 100, 4.0, 'Material: Cotton Blend|LED: 256 RGB LEDs|Battery: 8 hours|Washable: Yes', 1),
('Cyber Jacket', 'Temperature-regulating smart jacket with built-in health monitoring.', 349.00, 20, 'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=500', 3, 'TechWear', 60, 4.1, 'Material: Smart Fabric|Sensors: Heart Rate, Temp|Battery: 72 hours|Water Resistant: Yes', 0),
('Neon Glow Hoodie', 'Stylish hoodie with electroluminescent wire accents for nighttime visibility.', 129.00, 50, 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=500', 3, 'UrbanTech', 85, 4.3, 'Material: Fleece Blend|Glow: EL Wire|Colors: 7 modes|Battery: 10 hours|Sizes: S-XXL', 1),
('Smart Fitness Pants', 'Athletic pants with muscle tracking sensors and posture correction feedback.', 199.00, 0, 'https://images.unsplash.com/photo-1506629082955-511b1aa562c8?w=500', 3, 'FitWear', 70, 4.2, 'Material: Spandex Blend|Sensors: 12 EMG|App: iOS/Android|Washable: Yes|Sizes: XS-XL', 0),
('Holographic Sneakers', 'Futuristic sneakers with color-shifting holographic panels and comfort soles.', 249.00, 25, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=500', 3, 'NeoStep', 90, 4.5, 'Material: Synthetic/Mesh|Sole: Memory Foam|Style: Holographic|Sizes: 6-13|Unisex: Yes', 1);

-- Mobiles Category (category_id: 4) - 5 products
INSERT INTO products (name, description, price, discount, image, category_id, brand, stock, rating, specifications, featured) VALUES 
('Quantum X1 Holographic Phone', 'Experience the future in your hand with the Quantum X1, a revolutionary holographic smartphone. Its stunning edge-to-edge display projects vibrant 3D images and interactive interfaces that float effortlessly above the screen, offering an unparalleled user experience.', 1299.00, 10, 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=500', 4, 'QuantumTech', 50, 4.2, 'Display: 6.7" Holographic AMOLED|Processor: Aether AI Chip|RAM: 16GB|Storage: 512GB|Battery: 5000mAh|Camera: 108MP Triple', 1),
('NexGen Ultra 5G', 'Premium flagship phone with AI camera system and ultra-fast 5G connectivity.', 999.00, 0, 'https://images.unsplash.com/photo-1592899677977-9c10ca588bbd?w=500', 4, 'NexGen', 65, 4.6, 'Display: 6.8" Dynamic AMOLED|Processor: Snapdragon 9 Gen 3|RAM: 12GB|Storage: 256GB|Battery: 5500mAh|Camera: 200MP', 1),
('EcoPhone Green', 'Sustainable smartphone made from recycled materials with solar charging.', 699.00, 30, 'https://images.unsplash.com/photo-1598327105666-5b89351aff97?w=500', 4, 'EcoTech', 80, 4.3, 'Display: 6.5" OLED|Processor: Eco Chip|RAM: 8GB|Storage: 128GB|Battery: 6000mAh Solar|Camera: 64MP', 0),
('FoldX Pro', 'Revolutionary foldable phone with seamless crease-free display.', 1899.00, 15, 'https://images.unsplash.com/photo-1610945415295-d9bbf067e59c?w=500', 4, 'FlexMobile', 30, 4.4, 'Display: 7.6" Foldable AMOLED|Processor: Tensor X2|RAM: 16GB|Storage: 512GB|Battery: 4800mAh|Camera: 50MP Triple', 1),
('Budget King A55', 'Affordable smartphone with premium features at an unbeatable price.', 299.00, 45, 'https://images.unsplash.com/photo-1574944985070-8f3ebc6b79d2?w=500', 4, 'ValueTech', 150, 4.0, 'Display: 6.6" IPS LCD|Processor: Helio G99|RAM: 6GB|Storage: 128GB|Battery: 5000mAh|Camera: 48MP', 0);

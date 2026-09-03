<?php
// Database configuration
$host = 'localhost';
$dbname = 'moonchild_db';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

session_start();

require_once __DIR__ . '/includes/shop_helpers.php';
if (isset($conn) && $conn instanceof PDO) {
    ensure_shop_extras($conn);
}
?>

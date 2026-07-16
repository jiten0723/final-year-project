<?php
// ============================================
// EDUCORE - Database Configuration
// ============================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'educore');
define('BASE_URL', 'http://localhost/edu-core');
define('SITE_NAME', 'EDUCORE');

// eSewa Config (Sandbox)
define('ESEWA_MERCHANT_CODE', 'EPAYTEST');
define('ESEWA_GATEWAY_URL', 'https://uat.esewa.com.np/epay/main');

// PayPal Config (Sandbox)
define('PAYPAL_CLIENT_ID', 'sandbox_client_id');
define('PAYPAL_MODE', 'sandbox');

// OTP settings (seconds)
define('OTP_EXPIRE_SECONDS', 600);  // 10 minutes
define('OTP_RESEND_COOLDOWN', 60);  // 60 seconds between resends

// ── Email / SMTP Config (Gmail App Password) ─────────────────────────────────
// To get Gmail App Password:
// 1. Go to myaccount.google.com → Security → 2-Step Verification → App Passwords
// 2. Generate a password for "Mail" → paste it below
define('SMTP_HOST',     'smtp.gmail.com');
define('SMTP_PORT',     587);
define('SMTP_USER',     'jy574018@gmail.com');
define('SMTP_PASS',     'sfmg dszl jwvw fqil');
define('SMTP_FROM',     'jy574018@gmail.com');
define('SMTP_FROM_NAME','EDUCORE');

// ── Google OAuth Config ───────────────────────────────────────────────────────
// 1. Go to console.cloud.google.com → Create Project → APIs & Services → Credentials
// 2. Create OAuth 2.0 Client ID (Web application)
// 3. Add Authorised redirect URI: http://localhost/edu-core/api/google_auth.php
// Load local environment variables safely
if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    define('GOOGLE_CLIENT_ID',     $env['GOOGLE_CLIENT_ID'] ?? '');
    define('GOOGLE_CLIENT_SECRET', $env['GOOGLE_CLIENT_SECRET'] ?? '');
} else {
    // Fallback placeholders for production environment variables
    define('GOOGLE_CLIENT_ID',     getenv('GOOGLE_CLIENT_ID'));
    define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET'));
}

// Keep your redirect URI definition as it was
define('GOOGLE_REDIRECT_URI',  BASE_URL . '/api/google_auth.php');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}
?>

<?php
// ============================================
// EDUCORE - Authentication Helpers
// ============================================

require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Require login - redirect if not authenticated
 */
function requireLogin($redirect = '/project/login.php') {
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "/login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit();
    }
}

/**
 * Require specific role
 */
function requireRole($role) {
    requireLogin();
    if ($_SESSION['user_role'] !== $role && $_SESSION['user_role'] !== 'admin') {
        header("Location: " . BASE_URL . "/index.php?error=access_denied");
        exit();
    }
}

/**
 * Check if user has role
 */
function hasRole($role) {
    return isLoggedIn() && $_SESSION['user_role'] === $role;
}

/**
 * Register new user — sends OTP email immediately after registration
 */
function registerUser($name, $email, $password, $role = 'student') {
    $db = getDB();

    // Check if email already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Email already registered.'];
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Insert with is_verified=0 — must verify OTP before accessing dashboard
    $stmt = $db->prepare("INSERT INTO users (name, email, password, role, is_verified) VALUES (?, ?, ?, ?, 0)");
    $stmt->execute([$name, $email, $hashedPassword, $role]);
    $userId = $db->lastInsertId();

    // Set session
    $_SESSION['user_id']    = $userId;
    $_SESSION['user_name']  = $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role']  = $role;

    // Invalidate any stale OTPs then generate fresh one
    $db->prepare("UPDATE otp_codes SET used=1 WHERE user_id=? AND used=0")
       ->execute([$userId]);

    // Generate OTP and save to DB
    $code  = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $query = "INSERT INTO otp_codes (user_id, code, expires_at)
              VALUES (?, ?, DATE_ADD(NOW(), INTERVAL " . OTP_EXPIRE_SECONDS . " SECOND))";
    $db->prepare($query)->execute([$userId, $code]);

    // Send OTP email immediately
    require_once __DIR__ . '/mailer.php';
    $mailResult = sendOTPEmail($email, $name, $code);
    if (!$mailResult['success']) {
        error_log('Registration OTP mail failed for ' . $email . ': ' . $mailResult['message']);
    }

    // Welcome notification (shown after verification)
    $db->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'info')")
       ->execute([$userId, "Welcome to EDUCORE, $name! Please verify your account to get started."]);

    return ['success' => true, 'user_id' => $userId, 'role' => $role];
}

/**
 * Login user — checks trusted device cookie first, sends OTP only if not trusted
 */
function loginUser($email, $password) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }

    // Set session
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = $user['role'];

    // ── Check trusted device cookie ──────────────────────────────────────────
    $cookieVal = $_COOKIE['educore_trusted'] ?? '';
    if ($cookieVal && strpos($cookieVal, ':') !== false) {
        [$cookieUid, $cookieToken] = explode(':', $cookieVal, 2);

        if ((int)$cookieUid === (int)$user['id'] && strlen($cookieToken) === 64) {
            $trusted = $db->prepare("
                SELECT id FROM trusted_devices
                WHERE user_id = ? AND token = ? AND expires_at > NOW()
            ");
            $trusted->execute([$user['id'], $cookieToken]);

            if ($trusted->fetch()) {
                // Trusted device — mark verified and skip OTP completely
                $db->prepare("UPDATE users SET is_verified = 1 WHERE id = ?")
                   ->execute([$user['id']]);
                return ['success' => true, 'role' => $user['role'], 'needs_otp' => false];
            }
        }
    }
    // ─────────────────────────────────────────────────────────────────────────

    // Invalidate any previous unused OTPs for this user
    $db->prepare("UPDATE otp_codes SET used=1 WHERE user_id=? AND used=0")
       ->execute([$user['id']]);

    // Generate fresh OTP
    $code  = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $query = "INSERT INTO otp_codes (user_id, code, expires_at)
              VALUES (?, ?, DATE_ADD(NOW(), INTERVAL " . OTP_EXPIRE_SECONDS . " SECOND))";
    $db->prepare($query)->execute([$user['id'], $code]);

    require_once __DIR__ . '/mailer.php';
    $mailResult = sendOTPEmail($user['email'], $user['name'], $code);
    if (!$mailResult['success']) {
        error_log('Login OTP mail failed for ' . $user['email'] . ': ' . $mailResult['message']);
    }

    // Mark as unverified so otp_verify.php gate works
    $db->prepare("UPDATE users SET is_verified = 0 WHERE id = ?")->execute([$user['id']]);

    return ['success' => true, 'role' => $user['role'], 'needs_otp' => true];
}

/**
 * Check if user is enrolled in a course
 */
function isEnrolled($userId, $courseId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ? AND status != 'cancelled'");
    $stmt->execute([$userId, $courseId]);
    return (bool)$stmt->fetch();
}

/**
 * Get course average rating
 */
function getCourseRating($courseId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM reviews WHERE course_id = ?");
    $stmt->execute([$courseId]);
    $result = $stmt->fetch();
    return [
        'avg'   => round($result['avg_rating'] ?? 0, 1),
        'total' => (int)($result['total'] ?? 0)
    ];
}

/**
 * Get unread notification count
 */
function getUnreadNotifications($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int)$stmt->fetch()['cnt'];
}

/**
 * Format price
 */
function formatPrice($price) {
    if ($price == 0) return 'FREE';
    return 'NPR ' . number_format($price, 0);
}

/**
 * Generate star rating HTML
 */
function renderStars($rating, $max = 5) {
    $html = '';
    for ($i = 1; $i <= $max; $i++) {
        if ($i <= $rating) {
            $html .= '<i class="fas fa-star text-yellow-400"></i>';
        } elseif ($i - 0.5 <= $rating) {
            $html .= '<i class="fas fa-star-half-alt text-yellow-400"></i>';
        } else {
            $html .= '<i class="far fa-star text-gray-400"></i>';
        }
    }
    return $html;
}

/**
 * Sanitize output
 */
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Truncate text
 */
function truncate($text, $length = 100) {
    return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
}

/**
 * Time ago helper
 */
function timeAgo($datetime) {
    $now  = new DateTime();
    $past = new DateTime($datetime);
    $diff = $now->diff($past);
    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    return 'Just now';
}
?>

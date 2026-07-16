<?php
// ============================================
// EDUCORE - Google OAuth Callback Handler
// ============================================
// Flow:
//   1. User clicks "Login with Google" on login.php
//   2. Redirected to Google consent screen
//   3. Google redirects back here with ?code=...
//   4. Exchange code for access token
//   5. Fetch user profile from Google
//   6. Create/find user in DB → login
// ============================================
require_once __DIR__ . '/../includes/auth.php';

$db = getDB();

// ── Step 1: Build Google OAuth URL (redirect from login.php) ─────────────────
if (isset($_GET['action']) && $_GET['action'] === 'redirect') {
    $state = bin2hex(random_bytes(16));
    $_SESSION['google_state'] = $state;

    $params = http_build_query([
        'client_id'     => GOOGLE_CLIENT_ID,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'access_type'   => 'online',
        'state'         => $state,
        'prompt'        => 'select_account',
    ]);

    header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
    exit();
}

// ── Step 2: Handle callback from Google ──────────────────────────────────────
if (isset($_GET['error'])) {
    header('Location: ' . BASE_URL . '/login.php?error=google_cancelled');
    exit();
}

if (!isset($_GET['code'], $_GET['state'])) {
    header('Location: ' . BASE_URL . '/login.php?error=google_failed');
    exit();
}

// CSRF check
if (!isset($_SESSION['google_state']) || $_SESSION['google_state'] !== $_GET['state']) {
    header('Location: ' . BASE_URL . '/login.php?error=google_state_mismatch');
    exit();
}
unset($_SESSION['google_state']);

// ── Step 3: Exchange code for access token ───────────────────────────────────
$tokenResponse = httpPost('https://oauth2.googleapis.com/token', [
    'code'          => $_GET['code'],
    'client_id'     => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'grant_type'    => 'authorization_code',
]);

$tokenData = json_decode($tokenResponse, true);

if (empty($tokenData['access_token'])) {
    header('Location: ' . BASE_URL . '/login.php?error=google_token_failed');
    exit();
}

// ── Step 4: Fetch Google user profile ────────────────────────────────────────
$profileResponse = httpGet(
    'https://www.googleapis.com/oauth2/v2/userinfo',
    $tokenData['access_token']
);
$profile = json_decode($profileResponse, true);

if (empty($profile['email'])) {
    header('Location: ' . BASE_URL . '/login.php?error=google_profile_failed');
    exit();
}

$googleId = $profile['id']      ?? '';
$name     = $profile['name']    ?? 'Google User';
$email    = $profile['email']   ?? '';

// ── Step 5: Find or create user ───────────────────────────────────────────────
$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    // Existing user — update google_id if needed and log in
    if (empty($user['google_id'])) {
        $db->prepare("UPDATE users SET google_id=?, is_verified=1 WHERE id=?")
           ->execute([$googleId, $user['id']]);
    }
    if (!$user['is_active']) {
        header('Location: ' . BASE_URL . '/login.php?error=account_banned');
        exit();
    }
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = $user['role'];

} else {
    // New user — register as student automatically
    $hashedPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
    $stmt = $db->prepare("INSERT INTO users (name, email, password, role, google_id, is_verified, is_active) VALUES (?,?,?,'student',?,1,1)");
    $stmt->execute([$name, $email, $hashedPassword, $googleId]);
    $newId = $db->lastInsertId();

    // Welcome notification
    $db->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?,?,'success')")
       ->execute([$newId, "Welcome to EDUCORE, {$name}! Your account was created via Google. 🎉"]);

    $_SESSION['user_id']    = $newId;
    $_SESSION['user_name']  = $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role']  = 'student';
}

// ── Step 6: Redirect to dashboard ────────────────────────────────────────────
$role = $_SESSION['user_role'];
header('Location: ' . BASE_URL . '/dashboard/' . $role . '.php');
exit();

// ── Helpers ───────────────────────────────────────────────────────────────────
function httpPost($url, $data) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

function httpGet($url, $accessToken) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

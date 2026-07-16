<?php
// ============================================
// EDUCORE - OTP Verification Page
// ============================================
require_once __DIR__ . '/includes/auth.php';

// Must be logged in (registered but not verified)
if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

// If already verified, go to dashboard
$db  = getDB();
$uid = $_SESSION['user_id'];

$user = $db->prepare("SELECT * FROM users WHERE id=?");
$user->execute([$uid]);
$user = $user->fetch();

if ($user && $user['is_verified']) {
    header("Location: " . BASE_URL . "/dashboard/" . $_SESSION['user_role'] . ".php");
    exit();
}

$error   = '';
$success = '';

// Get active OTP — was already created and emailed by loginUser()
$existing = $db->prepare("SELECT * FROM otp_codes WHERE user_id=? AND used=0 AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
$existing->execute([$uid]);
$otp = $existing->fetch();

// If no active OTP exists (e.g. direct page load after registration), generate and send one now
if (!$otp) {
    $code  = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $query = "INSERT INTO otp_codes (user_id, code, expires_at) VALUES (?,?,DATE_ADD(NOW(), INTERVAL " . OTP_EXPIRE_SECONDS . " SECOND))";
    $db->prepare($query)->execute([$uid, $code]);
    $otp = $db->prepare("SELECT * FROM otp_codes WHERE user_id=? AND used=0 ORDER BY id DESC LIMIT 1");
    $otp->execute([$uid]);
    $otp = $otp->fetch();

    require_once __DIR__ . '/includes/mailer.php';
    $mailResult = sendOTPEmail($user['email'], $user['name'], $code);
    if (!$mailResult['success']) {
        error_log('OTP mail failed: ' . $mailResult['message']);
        $emailError = 'Could not send OTP email. Check SMTP settings in config/db.php';
    }
}

// Handle OTP submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered = trim($_POST['otp'] ?? '');
    if (empty($entered)) {
        $error = 'Please enter the OTP code.';
    } elseif (strlen($entered) !== 6 || !ctype_digit($entered)) {
        $error = 'OTP must be exactly 6 digits.';
    } else {
        // Verify against DB
        $check = $db->prepare("SELECT * FROM otp_codes WHERE user_id=? AND code=? AND used=0 AND expires_at > NOW()");
        $check->execute([$uid, $entered]);
        $valid = $check->fetch();

        if ($valid) {
            // Mark OTP used
            $db->prepare("UPDATE otp_codes SET used=1 WHERE id=?")->execute([$valid['id']]);
            // Activate user
            $db->prepare("UPDATE users SET is_verified=1 WHERE id=?")->execute([$uid]);
            // Send welcome notification
            $db->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?,?,'success')")
               ->execute([$uid, "Your account has been verified! Welcome to EDUCORE 🎉"]);

            // Send welcome email
            require_once __DIR__ . '/includes/mailer.php';
            sendWelcomeEmail($user['email'], $user['name'], $_SESSION['user_role']);

            // ── Set trusted device cookie (30 days) ──────────────────────────
            $token     = bin2hex(random_bytes(32)); // 64-char secure token
            $expiresAt = date('Y-m-d H:i:s', strtotime('+60 days'));
            $db->prepare("
                INSERT INTO trusted_devices (user_id, token, user_agent, ip_address, expires_at)
                VALUES (?, ?, ?, ?, ?)
            ")->execute([
                $uid,
                $token,
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                $_SERVER['REMOTE_ADDR']     ?? '0.0.0.0',
                $expiresAt
            ]);

            // Secure cookie: name, value, expiry, path, domain, secure, httponly
            setcookie(
                'educore_trusted',
                $uid . ':' . $token,
                [
                    'expires'  => strtotime('+60 days'),
                    'path'     => '/',
                    'secure'   => false, // set true if using HTTPS
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]
            );
            // ─────────────────────────────────────────────────────────────────

            $success = 'Account verified! This device is now trusted for 30 days. Redirecting...';
            header("Refresh: 0; URL=" . BASE_URL . "/dashboard/" . $_SESSION['user_role'] . ".php");
        } else {
            $error = 'Invalid or expired OTP. Please try again.';
        }
    }
}

// Resend OTP
if (isset($_GET['resend'])) {
    // Prevent rapid resends
    $last = $db->prepare("SELECT * FROM otp_codes WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $last->execute([$uid]);
    $last = $last->fetch();
    if ($last) {
        $lastCreated = strtotime($last['created_at']);
        if (time() - $lastCreated < OTP_RESEND_COOLDOWN) {
            header("Location: otp_verify.php?cooldown=1");
            exit();
        }
    }

    $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $query = "INSERT INTO otp_codes (user_id, code, expires_at) VALUES (?,?,DATE_ADD(NOW(), INTERVAL " . OTP_EXPIRE_SECONDS . " SECOND))";
    $stmt = $db->prepare($query);
    $stmt->execute([$uid, $code]);

    // Send new OTP email
    require_once __DIR__ . '/includes/mailer.php';
    sendOTPEmail($user['email'], $user['name'], $code);

    header("Location: otp_verify.php?sent=1");
    exit();
}

$pageTitle = "Verify Your Account";
include __DIR__ . '/includes/header.php';
?>

<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:100px 16px 60px;background:var(--gradient-hero);position:relative;overflow:hidden;">
    <!-- Background orbs -->
    <div style="position:absolute;top:-100px;right:-100px;width:400px;height:400px;background:radial-gradient(circle,rgba(34,197,94,0.08),transparent);border-radius:50%;pointer-events:none;"></div>
    <div style="position:absolute;bottom:-100px;left:-100px;width:400px;height:400px;background:radial-gradient(circle,rgba(139,92,246,0.08),transparent);border-radius:50%;pointer-events:none;"></div>

    <div style="width:100%;max-width:460px;position:relative;z-index:1;" class="animate-fade-up">
        <!-- Logo -->
        <div style="text-align:center;margin-bottom:32px;">
            <a href="<?php echo BASE_URL; ?>/index.php" style="display:inline-flex;align-items:center;gap:10px;text-decoration:none;">
                <div class="logo-icon"><i class="fas fa-graduation-cap"></i></div>
                <span class="logo-text">EDU<span class="logo-accent">CORE</span></span>
            </a>
            <div style="width:72px;height:72px;background:linear-gradient(135deg,#8b5cf6,#3b82f6);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:24px auto 16px;box-shadow:0 0 40px rgba(139,92,246,0.3);">
                <i class="fas fa-shield-alt" style="font-size:28px;color:#fff;"></i>
            </div>
            <h1 style="font-size:24px;font-weight:800;margin-bottom:6px;">Verify Your Account</h1>
            <p style="color:var(--text-muted);font-size:14px;">Enter the 6-digit code to activate your account</p>
        </div>

        <div class="form-card">
            <?php if ($error): ?>
                <div class="alert-custom alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo e($error); ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert-custom alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo e($success); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['sent'])): ?>
                <div class="alert-custom alert-info">
                    <i class="fas fa-info-circle"></i> A new OTP code has been generated.
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['cooldown'])): ?>
                <div class="alert-custom alert-info">
                    <i class="fas fa-info-circle"></i> Please wait a moment before requesting another code.
                </div>
            <?php endif; ?>

            <?php if (isset($emailError)): ?>
                <div class="alert-custom alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo e($emailError); ?>
                    <br><small style="opacity:0.8;">Tip: Set your SMTP credentials in <code>config/db.php</code></small>
                </div>
            <?php endif; ?>

            <!-- Email sent notice -->
            <div style="background:linear-gradient(135deg,rgba(34,197,94,0.08),rgba(59,130,246,0.08));border:1px solid rgba(34,197,94,0.25);border-radius:16px;padding:24px;text-align:center;margin-bottom:24px;">
                <div style="font-size:40px;margin-bottom:10px;">📧</div>
                <div style="font-size:15px;font-weight:700;color:#fff;margin-bottom:6px;">Check your inbox</div>
                <div style="font-size:13px;color:#9ca3af;line-height:1.6;">
                    We sent a 6-digit code to<br>
                    <strong style="color:var(--primary);"><?php echo e($user['email']); ?></strong>
                </div>
                <div style="font-size:12px;color:#6b7280;margin-top:10px;">
                    <i class="fas fa-clock me-1"></i>Code expires in 10 minutes
                </div>
            </div>

            <form method="POST" id="otpForm">
                <div class="form-group">
                    <label class="form-label-custom">Enter OTP Code</label>
                    <div class="input-wrapper">
                        <i class="fas fa-key input-icon"></i>
                        <input type="text" name="otp" id="otpInput" class="form-input-custom"
                            placeholder="123456"
                            maxlength="6"
                            pattern="[0-9]{6}"
                            autocomplete="one-time-code"
                            style="font-size:22px;letter-spacing:8px;font-family:monospace;text-align:center;"
                            oninput="this.value=this.value.replace(/[^0-9]/g,'').substring(0,6)"
                            required>
                    </div>
                    <div style="font-size:12px;color:var(--text-muted);margin-top:6px;text-align:center;">
                        Enter the 6-digit code shown above
                    </div>
                </div>

                <button type="submit" id="verifyBtn" class="btn-primary-custom w-100 justify-content-center" style="font-size:16px;padding:14px;margin-bottom:16px;" onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Verifying...';this.disabled=true;">
                    <i class="fas fa-check-shield"></i> Verify Account
                </button>
            </form>

            <div style="text-align:center;padding-top:12px;border-top:1px solid var(--border);">
                <span style="font-size:13px;color:var(--text-muted);">Didn't get a code?</span>
                <a href="?resend=1" style="font-size:13px;color:var(--primary);font-weight:600;margin-left:6px;">Resend OTP</a>
            </div>

            <div style="text-align:center;margin-top:12px;">
                <a href="<?php echo BASE_URL; ?>/logout.php" style="font-size:13px;color:var(--text-muted);">
                    <i class="fas fa-arrow-left me-1"></i>Back to Login
                </a>
            </div>
        </div>

        <!-- Auto-fill helper -->
        <script>
        // Auto-submit when 6 digits entered
        document.getElementById('otpInput').addEventListener('input', function() {
            if (this.value.length === 6) {
                setTimeout(() => document.getElementById('otpForm').submit(), 100);
            }
        });
        </script>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

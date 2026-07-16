<?php
// ============================================
// EDUCORE - Login Page
// ============================================
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = $_SESSION['user_role'];
    header("Location: " . BASE_URL . "/dashboard/$role.php");
    exit();
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $result = loginUser($email, $password);
        if ($result['success']) {
            if (!empty($result['needs_otp'])) {
                // User exists but hasn't verified OTP
                header("Location: " . BASE_URL . "/otp_verify.php");
                exit();
            }
            $redirect = $_GET['redirect'] ?? BASE_URL . '/dashboard/' . $result['role'] . '.php';
            header("Location: $redirect");
            exit();
        } else {
            $error = $result['message'];
        }
    }
}

$pageTitle = "Login";
include __DIR__ . '/includes/header.php';
?>

<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:100px 16px 60px;background:var(--gradient-hero);position:relative;overflow:hidden;">
    <!-- Background Orbs -->
    <div style="position:absolute;top:-100px;right:-100px;width:400px;height:400px;background:radial-gradient(circle,rgba(34,197,94,0.08),transparent);border-radius:50%;pointer-events:none;"></div>
    <div style="position:absolute;bottom:-100px;left:-100px;width:400px;height:400px;background:radial-gradient(circle,rgba(59,130,246,0.08),transparent);border-radius:50%;pointer-events:none;"></div>

    <div style="width:100%;max-width:460px;position:relative;z-index:1;" class="animate-fade-up">
        <!-- Logo -->
        <div style="text-align:center;margin-bottom:32px;">
            <a href="<?php echo BASE_URL; ?>/index.php" style="display:inline-flex;align-items:center;gap:10px;text-decoration:none;">
                <div class="logo-icon"><i class="fas fa-graduation-cap"></i></div>
                <span class="logo-text">EDU<span class="logo-accent">CORE</span></span>
            </a>
            <h1 style="font-size:26px;font-weight:800;margin-top:24px;margin-bottom:6px;">Welcome Back!</h1>
            <p style="color:var(--text-muted);font-size:15px;">Sign in to continue your learning journey</p>
        </div>

        <!-- Demo credentials hint -->
        <!-- <div style="background:rgba(59,130,246,0.08);border:1px solid rgba(59,130,246,0.2);border-radius:12px;padding:12px 16px;margin-bottom:20px;font-size:13px;">
            <i class="fas fa-info-circle" style="color:#60a5fa;margin-right:6px;"></i>
            <strong>Demo accounts:</strong> jy574018@gmail.com (admin) / neerajdon022@educore.com(teacher) / student@educore.com &mdash; password: <code style="background:rgba(255,255,255,0.1);padding:2px 6px;border-radius:4px;">password</code> -->
        <!-- </div> -->

        <div class="form-card">
        <?php if (isset($_GET['error'])): ?>
                <div class="alert-custom alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php
                    $errs = [
                        'google_cancelled'      => 'Google sign-in was cancelled.',
                        'google_failed'         => 'Google sign-in failed. Please try again.',
                        'google_token_failed'   => 'Could not get Google token. Check your OAuth credentials.',
                        'google_profile_failed' => 'Could not retrieve your Google profile.',
                        'google_state_mismatch' => 'Security check failed. Please try again.',
                        'account_banned'        => 'This account has been suspended.',
                    ];
                    echo e($errs[$_GET['error']] ?? 'An error occurred.');
                    ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert-custom alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo e($error); ?>
                </div>
            <?php endif; ?>

            <!-- Google Sign In (Real OAuth) -->
            <a href="<?php echo BASE_URL; ?>/api/google_auth.php?action=redirect"
               class="payment-method-btn"
               style="text-decoration:none;justify-content:center;margin-bottom:20px;">
                <svg width="20" height="20" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Continue with Google
            </a>

            <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
                <div style="flex:1;height:1px;background:var(--border);"></div>
                <span style="color:var(--text-muted);font-size:13px;">or sign in with email</span>
                <div style="flex:1;height:1px;background:var(--border);"></div>
            </div>

            <form method="POST" id="loginForm" onsubmit="return validateLoginForm()">
                <div class="form-group">
                    <label class="form-label-custom">Email Address</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" name="email" class="form-input-custom"
                            placeholder="you@example.com"
                            value="<?php echo e($_POST['email'] ?? ''); ?>"
                            required autocomplete="email">
                    </div>
                </div>

                <div class="form-group">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                        <label class="form-label-custom" style="margin:0;">Password</label>
                        <a href="#" style="font-size:13px;color:var(--primary);">Forgot password?</a>
                    </div>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password" id="passwordInput" class="form-input-custom"
                            placeholder="Enter your password"
                            required autocomplete="current-password">
                        <i class="fas fa-eye input-eye" id="togglePwd" onclick="togglePassword()"></i>
                    </div>
                </div>

                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;color:var(--text-secondary);">
                        <input type="checkbox" name="remember" style="accent-color:var(--primary);width:16px;height:16px;">
                        Remember me
                    </label>
                </div>

                <button type="submit" id="loginBtn" class="btn-primary-custom w-100 justify-content-center" style="font-size:16px;padding:14px;">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>

            <p style="text-align:center;margin-top:24px;font-size:14px;color:var(--text-muted);">
                Don't have an account?
                <a href="<?php echo BASE_URL; ?>/register.php" style="color:var(--primary);font-weight:600;">Create one free</a>
            </p>
        </div>

        <!-- Quick Login Buttons for Demo -->
        <!-- <div style="margin-top:20px;text-align:center;">
            <p style="color:var(--text-muted);font-size:12px;margin-bottom:10px;">Quick demo login:</p>
            <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;">
                <button onclick="quickLogin('student@educore.com')" style="padding:6px 14px;border-radius:50px;border:1px solid rgba(34,197,94,0.3);background:rgba(34,197,94,0.08);color:var(--primary);font-size:12px;cursor:pointer;transition:all 0.2s;">
                    <i class="fas fa-user-graduate"></i> Student
                </button>
                <button onclick="quickLogin('teacher@educore.com')" style="padding:6px 14px;border-radius:50px;border:1px solid rgba(59,130,246,0.3);background:rgba(59,130,246,0.08);color:#60a5fa;font-size:12px;cursor:pointer;transition:all 0.2s;">
                    <i class="fas fa-chalkboard-teacher"></i> Teacher
                </button>
                <button onclick="quickLogin('jy574018@gmail.com')" style="padding:6px 14px;border-radius:50px;border:1px solid rgba(239,68,68,0.3);background:rgba(239,68,68,0.08);color:#f87171;font-size:12px;cursor:pointer;transition:all 0.2s;">
                    <i class="fas fa-shield-alt"></i> Admin
                </button>
            </div>
        </div> -->
    </div>
</div>

<script>
function togglePassword() {
    const input = document.getElementById('passwordInput');
    const icon  = document.getElementById('togglePwd');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye','fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash','fa-eye');
    }
}

function quickLogin(email) {
    document.querySelector('[name="email"]').value = email;
    document.querySelector('[name="password"]').value = 'password';
    document.getElementById('loginBtn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';
    setTimeout(() => document.getElementById('loginForm').submit(), 300);
}

function validateLoginForm() {
    const btn = document.getElementById('loginBtn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';
    btn.disabled = true;
    return true;
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

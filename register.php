<?php
// ============================================
// EDUCORE - Register Page
// ============================================
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header("Location: " . BASE_URL . "/dashboard/" . $_SESSION['user_role'] . ".php");
    exit();
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $role     = in_array($_POST['role'] ?? '', ['student','teacher']) ? $_POST['role'] : 'student';

    if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
        $error = 'Please fill in all required fields.';
    } elseif (strlen($name) < 2) {
        $error = 'Name must be at least 2 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!isset($_POST['terms'])) {
        $error = 'Please accept the Terms & Conditions.';
    } else {
        $result = registerUser($name, $email, $password, $role);
        if ($result['success']) {
            // Redirect to OTP verification before granting dashboard access
            header("Location: " . BASE_URL . "/otp_verify.php");
            exit();
        } else {
            $error = $result['message'];
        }
    }
}

$pageTitle = "Create Account";
include __DIR__ . '/includes/header.php';
?>

<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:100px 16px 60px;background:var(--gradient-hero);position:relative;overflow:hidden;">
    <div style="position:absolute;top:-150px;right:-100px;width:500px;height:500px;background:radial-gradient(circle,rgba(34,197,94,0.07),transparent);border-radius:50%;pointer-events:none;"></div>
    <div style="position:absolute;bottom:-150px;left:-100px;width:500px;height:500px;background:radial-gradient(circle,rgba(59,130,246,0.07),transparent);border-radius:50%;pointer-events:none;"></div>

    <div style="width:100%;max-width:520px;position:relative;z-index:1;" class="animate-fade-up">
        <!-- Logo -->
        <div style="text-align:center;margin-bottom:32px;">
            <a href="<?php echo BASE_URL; ?>/index.php" style="display:inline-flex;align-items:center;gap:10px;text-decoration:none;">
                <div class="logo-icon"><i class="fas fa-graduation-cap"></i></div>
                <span class="logo-text">EDU<span class="logo-accent">CORE</span></span>
            </a>
            <h1 style="font-size:26px;font-weight:800;margin-top:24px;margin-bottom:6px;">Create Your Account</h1>
            <p style="color:var(--text-muted);font-size:15px;">Join 50,000+ learners on EDUCORE — it's free!</p>
        </div>

        <div class="form-card">
            <?php if ($error): ?>
                <div class="alert-custom alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo e($error); ?>
                </div>
            <?php endif; ?>

            <!-- Google signup (Real OAuth) -->
            <a href="<?php echo BASE_URL; ?>/api/google_auth.php?action=redirect" class="payment-method-btn" style="text-decoration:none;justify-content:center;margin-bottom:20px;">
                <svg width="20" height="20" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                Sign up with Google
            </a>

            <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
                <div style="flex:1;height:1px;background:var(--border);"></div>
                <span style="color:var(--text-muted);font-size:13px;">or register with email</span>
                <div style="flex:1;height:1px;background:var(--border);"></div>
            </div>

            <form method="POST" id="registerForm" onsubmit="return validateRegisterForm()">
                <!-- Role Selection -->
                <div class="form-group">
                    <label class="form-label-custom">I want to join as</label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;" id="roleSelector">
                        <label class="role-selector-card active" id="roleStudent" onclick="selectRole('student')">
                            <input type="radio" name="role" value="student" checked style="display:none;">
                            <div style="font-size:28px;margin-bottom:8px;">🎓</div>
                            <div style="font-weight:700;font-size:14px;">Student</div>
                            <div style="font-size:12px;color:var(--text-muted);">Learn new skills</div>
                        </label>
                        <label class="role-selector-card" id="roleTeacher" onclick="selectRole('teacher')">
                            <input type="radio" name="role" value="teacher" style="display:none;">
                            <div style="font-size:28px;margin-bottom:8px;">👨‍🏫</div>
                            <div style="font-weight:700;font-size:14px;">Teacher</div>
                            <div style="font-size:12px;color:var(--text-muted);">Share your expertise</div>
                        </label>
                    </div>
                </div>

                <!-- Full Name -->
                <div class="form-group">
                    <label class="form-label-custom">Full Name <span style="color:#ef4444;">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" name="name" class="form-input-custom"
                            placeholder="enter your full name"
                            value="<?php echo e($_POST['name'] ?? ''); ?>"
                            required minlength="2" autocomplete="name">
                    </div>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label class="form-label-custom">Email Address <span style="color:#ef4444;">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" name="email" class="form-input-custom"
                            placeholder="you@example.com"
                            value="<?php echo e($_POST['email'] ?? ''); ?>"
                            required autocomplete="email">
                    </div>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label class="form-label-custom">Password <span style="color:#ef4444;">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password" id="pwd" class="form-input-custom"
                            placeholder="Min. 6 characters"
                            required minlength="6"
                            oninput="checkPwdStrength(this.value)"
                            autocomplete="new-password">
                        <i class="fas fa-eye input-eye" onclick="togglePwd('pwd','this')"></i>
                    </div>
                    <!-- Strength bar -->
                    <div style="margin-top:8px;">
                        <div style="height:4px;background:var(--border);border-radius:2px;overflow:hidden;">
                            <div id="pwdStrengthBar" style="height:100%;width:0%;border-radius:2px;transition:all 0.3s;"></div>
                        </div>
                        <div id="pwdStrengthLabel" style="font-size:11px;color:var(--text-muted);margin-top:4px;"></div>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="form-group">
                    <label class="form-label-custom">Confirm Password <span style="color:#ef4444;">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="confirm_password" id="cpwd" class="form-input-custom"
                            placeholder="Re-enter password"
                            required
                            oninput="checkMatch()"
                            autocomplete="new-password">
                        <i class="fas fa-eye input-eye" onclick="togglePwd('cpwd','this')"></i>
                    </div>
                    <div id="matchMsg" style="font-size:12px;margin-top:4px;display:none;"></div>
                </div>

                <!-- Terms -->
                <div style="display:flex;align-items:flex-start;gap:10px;margin-bottom:24px;">
                    <input type="checkbox" name="terms" id="terms" style="margin-top:3px;accent-color:var(--primary);width:16px;height:16px;flex-shrink:0;" required>
                    <label for="terms" style="font-size:13px;color:var(--text-secondary);cursor:pointer;line-height:1.5;">
                        I agree to the <a href="#" style="color:var(--primary);">Terms of Service</a> and <a href="#" style="color:var(--primary);">Privacy Policy</a>. I understand my data will be handled securely.
                    </label>
                </div>

                <button type="submit" id="registerBtn" class="btn-primary-custom w-100 justify-content-center" style="font-size:16px;padding:14px;">
                    <i class="fas fa-user-plus"></i> Create Free Account
                </button>
            </form>

            <p style="text-align:center;margin-top:24px;font-size:14px;color:var(--text-muted);">
                Already have an account?
                <a href="<?php echo BASE_URL; ?>/login.php" style="color:var(--primary);font-weight:600;">Sign in</a>
            </p>
        </div>
    </div>
</div>

<style>
.role-selector-card {
    background: var(--bg-input);
    border: 2px solid var(--border);
    border-radius: var(--radius-md);
    padding: 20px 16px;
    text-align: center;
    cursor: pointer;
    transition: var(--transition);
    display: block;
}
.role-selector-card:hover { border-color: var(--border-hover); }
.role-selector-card.active { border-color: var(--primary); background: rgba(34,197,94,0.06); box-shadow: 0 0 0 1px rgba(34,197,94,0.2); }
</style>

<script>
function selectRole(role) {
    document.querySelectorAll('.role-selector-card').forEach(c => c.classList.remove('active'));
    document.getElementById('role' + role.charAt(0).toUpperCase() + role.slice(1)).classList.add('active');
    document.querySelector(`[name="role"][value="${role}"]`).checked = true;
}

function togglePwd(id) {
    const input = document.getElementById(id);
    const wasHidden = input.type === 'password';
    input.type = wasHidden ? 'text' : 'password';
}

function checkPwdStrength(val) {
    const bar = document.getElementById('pwdStrengthBar');
    const label = document.getElementById('pwdStrengthLabel');
    let strength = 0;
    if (val.length >= 6) strength++;
    if (val.length >= 10) strength++;
    if (/[A-Z]/.test(val) && /[0-9]/.test(val)) strength++;
    if (/[^a-zA-Z0-9]/.test(val)) strength++;
    const levels = [
        { width:'25%', color:'#ef4444', text:'Weak' },
        { width:'50%', color:'#f59e0b', text:'Fair' },
        { width:'75%', color:'#3b82f6', text:'Good' },
        { width:'100%', color:'#22c55e', text:'Strong' },
    ];
    const l = levels[Math.max(0, strength - 1)] || levels[0];
    bar.style.width = l.width; bar.style.background = l.color;
    label.textContent = val.length > 0 ? `Password strength: ${l.text}` : '';
    label.style.color = l.color;
}

function checkMatch() {
    const pwd  = document.getElementById('pwd').value;
    const cpwd = document.getElementById('cpwd').value;
    const msg  = document.getElementById('matchMsg');
    if (!cpwd) { msg.style.display = 'none'; return; }
    msg.style.display = 'block';
    if (pwd === cpwd) {
        msg.textContent = '✓ Passwords match'; msg.style.color = '#22c55e';
    } else {
        msg.textContent = '✗ Passwords do not match'; msg.style.color = '#ef4444';
    }
}

function validateRegisterForm() {
    const pwd  = document.getElementById('pwd').value;
    const cpwd = document.getElementById('cpwd').value;
    if (pwd !== cpwd) { showToast('error', 'Passwords do not match!'); return false; }
    const btn = document.getElementById('registerBtn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
    btn.disabled = true;
    return true;
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

<?php
// ============================================
// EDUCORE - Payment Checkout (eSewa + PayPal Mock)
// ============================================
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$db       = getDB();
$courseId = (int)($_GET['course_id'] ?? 0);

$course = $db->prepare("SELECT co.*, u.name as instructor_name FROM courses co JOIN users u ON u.id=co.instructor_id WHERE co.id=? AND co.status='approved'");
$course->execute([$courseId]);
$course = $course->fetch();

if (!$course) { header("Location: " . BASE_URL . "/courses/index.php"); exit(); }
if ($course['type'] === 'free') { header("Location: " . BASE_URL . "/courses/enroll.php?id=$courseId"); exit(); }
if (isEnrolled($_SESSION['user_id'], $courseId)) { header("Location: " . BASE_URL . "/courses/" . $course['slug']); exit(); }

$pageTitle = "Checkout — " . $course['title'];
include __DIR__ . '/../includes/header.php';
?>

<div style="margin-top:70px;min-height:100vh;padding:60px 0;background:var(--gradient-hero);">
<div class="container">
    <div class="row justify-content-center g-4">

        <!-- Order Summary -->
        <div class="col-lg-5 order-lg-2">
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-xl);padding:28px;position:sticky;top:100px;">
                <h3 style="font-size:16px;font-weight:700;margin-bottom:20px;">Order Summary</h3>
                <div style="display:flex;gap:14px;padding:16px;background:var(--bg-input);border-radius:var(--radius-md);margin-bottom:20px;">
                    <div style="width:64px;height:56px;background:linear-gradient(135deg,#0d1a2e,#0a2010);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:26px;flex-shrink:0;">💻</div>
                    <div>
                        <div style="font-size:14px;font-weight:700;line-height:1.4;"><?php echo e($course['title']); ?></div>
                        <div style="font-size:12px;color:var(--text-muted);margin-top:3px;">by <?php echo e($course['instructor_name']); ?></div>
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:20px;">
                    <div style="display:flex;justify-content:space-between;font-size:14px;color:var(--text-muted);">
                        <span>Original Price</span><span>NPR <?php echo number_format($course['price']*1.3,0); ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:14px;color:#22c55e;">
                        <span>Discount (30%)</span><span>- NPR <?php echo number_format($course['price']*0.3,0); ?></span>
                    </div>
                    <div style="height:1px;background:var(--border);"></div>
                    <div style="display:flex;justify-content:space-between;font-size:18px;font-weight:800;">
                        <span>Total</span><span>NPR <?php echo number_format($course['price'],0); ?></span>
                    </div>
                </div>
                <div style="font-size:12px;color:var(--text-muted);text-align:center;">
                    <i class="fas fa-shield-alt me-1" style="color:var(--primary);"></i>
                    30-day money-back guarantee. No questions asked.
                </div>
            </div>
        </div>

        <!-- Payment Methods -->
        <div class="col-lg-7 order-lg-1">
            <div style="margin-bottom:28px;">
                <a href="<?php echo BASE_URL; ?>/courses/<?php echo e($course['slug']); ?>" style="color:var(--text-muted);font-size:14px;display:inline-flex;align-items:center;gap:6px;">
                    <i class="fas fa-arrow-left"></i> Back to course
                </a>
            </div>
            <h1 style="font-size:1.8rem;font-weight:900;margin-bottom:8px;">Complete Your Purchase</h1>
            <p style="color:var(--text-muted);font-size:15px;margin-bottom:32px;">Choose your preferred payment method</p>

            <!-- Payment Method Selection -->
            <div id="paymentMethods">
                <button class="payment-method-btn" id="btn-esewa" onclick="selectPayment('esewa')">
                    <div style="width:40px;height:40px;background:#60BB46;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:12px;flex-shrink:0;">eSewa</div>
                    <div>
                        <div style="font-weight:700;">eSewa</div>
                        <div style="font-size:12px;color:var(--text-muted);">Nepal's most popular digital wallet</div>
                    </div>
                    <i class="fas fa-chevron-right ms-auto" style="color:var(--text-muted);"></i>
                </button>

                <button class="payment-method-btn" id="btn-paypal" onclick="selectPayment('paypal')">
                    <div style="width:40px;height:40px;background:#003087;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="white"><path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.13 1.67A.641.641 0 0 1 4.763 1h7.404c2.633 0 4.525.578 5.621 1.718.524.548.862 1.135 1.008 1.748.153.645.12 1.383-.1 2.193l-.014.048v.41l.324.195c.271.15.494.337.664.554.284.36.468.815.545 1.352.08.557.054 1.224-.077 1.985-.153.89-.4 1.665-.737 2.298a5.07 5.07 0 0 1-1.163 1.522 4.714 4.714 0 0 1-1.6.895c-.61.196-1.307.295-2.07.295h-.494a1.43 1.43 0 0 0-1.42 1.22l-.108.594-.617 3.908-.028.143a.641.641 0 0 1-.634.54zM19.657 7.28c-.023.13-.05.267-.082.407-.97 4.984-4.294 6.71-8.535 6.71h-2.16a1.05 1.05 0 0 0-1.038.89l-1.107 7.022h3.14l.773-4.906a1.05 1.05 0 0 1 1.04-.89h.655c4.249 0 7.573-1.725 8.54-6.71.407-2.095.197-3.843-.226-5.523z"/></svg>
                    </div>
                    <div>
                        <div style="font-weight:700;">PayPal</div>
                        <div style="font-size:12px;color:var(--text-muted);">Pay securely with PayPal (sandbox)</div>
                    </div>
                    <i class="fas fa-chevron-right ms-auto" style="color:var(--text-muted);"></i>
                </button>
            </div>

            <!-- eSewa Form -->
            <div id="esewa-form" style="display:none;" class="animate-fade-up">
                <div style="background:rgba(96,187,70,0.06);border:1px solid rgba(96,187,70,0.3);border-radius:var(--radius-lg);padding:24px;margin-bottom:20px;">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
                        <div style="width:36px;height:36px;background:#60BB46;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:11px;">eSewa</div>
                        <h3 style="font-size:16px;font-weight:700;margin:0;">Pay with eSewa</h3>
                    </div>
                    <form action="<?php echo BASE_URL; ?>/payment/esewa_process.php" method="POST" id="esewaForm">
                        <input type="hidden" name="tAmt" value="<?php echo $course['price']; ?>">
                        <input type="hidden" name="amt" value="<?php echo $course['price']; ?>">
                        <input type="hidden" name="txAmt" value="0">
                        <input type="hidden" name="psc" value="0">
                        <input type="hidden" name="pdc" value="0">
                        <input type="hidden" name="scd" value="<?php echo ESEWA_MERCHANT_CODE; ?>">
                        <input type="hidden" name="pid" value="EDUCORE-<?php echo $courseId; ?>-<?php echo time(); ?>">
                        <input type="hidden" name="su" value="<?php echo BASE_URL; ?>/payment/success.php?method=esewa&course_id=<?php echo $courseId; ?>">
                        <input type="hidden" name="fu" value="<?php echo BASE_URL; ?>/payment/cancel.php?course_id=<?php echo $courseId; ?>">
                        <input type="hidden" name="course_id" value="<?php echo $courseId; ?>">

                        <div class="form-group">
                            <label class="form-label-custom">eSewa Mobile Number</label>
                            <div class="input-wrapper">
                                <i class="fas fa-phone input-icon"></i>
                                <input type="text" class="form-input-custom" placeholder="98XXXXXXXX" maxlength="10" pattern="[0-9]{10}" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label-custom">eSewa Password / MPIN</label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" class="form-input-custom" placeholder="Enter your eSewa password" required>
                            </div>
                        </div>
                        <div style="background:rgba(96,187,70,0.06);border-radius:8px;padding:12px 16px;font-size:13px;color:var(--text-secondary);margin-bottom:16px;">
                            <i class="fas fa-info-circle me-1" style="color:#60BB46;"></i>
                            This is a <strong>sandbox simulation</strong>. No real payment will be processed.
                        </div>
                        <button type="submit" class="btn-primary-custom w-100 justify-content-center" style="background:linear-gradient(135deg,#60BB46,#45a32f);font-size:16px;padding:14px;">
                            <i class="fas fa-lock me-2"></i>Pay NPR <?php echo number_format($course['price'],0); ?> via eSewa
                        </button>
                    </form>
                </div>
            </div>

            <!-- PayPal Form -->
            <div id="paypal-form" style="display:none;" class="animate-fade-up">
                <div style="background:rgba(0,48,135,0.06);border:1px solid rgba(0,48,135,0.3);border-radius:var(--radius-lg);padding:24px;margin-bottom:20px;">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
                        <div style="width:36px;height:36px;background:#003087;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="white"><path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.13 1.67A.641.641 0 0 1 4.763 1h7.404c2.633 0 4.525.578 5.621 1.718.524.548.862 1.135 1.008 1.748.153.645.12 1.383-.1 2.193l-.014.048v.41l.324.195c.271.15.494.337.664.554.284.36.468.815.545 1.352.08.557.054 1.224-.077 1.985-.153.89-.4 1.665-.737 2.298a5.07 5.07 0 0 1-1.163 1.522 4.714 4.714 0 0 1-1.6.895c-.61.196-1.307.295-2.07.295h-.494a1.43 1.43 0 0 0-1.42 1.22l-.108.594-.617 3.908-.028.143a.641.641 0 0 1-.634.54z"/></svg>
                        </div>
                        <h3 style="font-size:16px;font-weight:700;margin:0;">Pay with PayPal</h3>
                    </div>
                    <form action="<?php echo BASE_URL; ?>/payment/paypal_process.php" method="POST">
                        <input type="hidden" name="course_id" value="<?php echo $courseId; ?>">
                        <input type="hidden" name="amount" value="<?php echo $course['price']; ?>">
                        <div class="form-group">
                            <label class="form-label-custom">PayPal Email</label>
                            <div class="input-wrapper">
                                <i class="fas fa-envelope input-icon"></i>
                                <input type="email" class="form-input-custom" placeholder="you@paypal.com" required>
                            </div>
                        </div>
                        <div style="background:rgba(0,48,135,0.06);border-radius:8px;padding:12px 16px;font-size:13px;color:var(--text-secondary);margin-bottom:16px;">
                            <i class="fas fa-info-circle me-1" style="color:#003087;"></i>
                            PayPal <strong>sandbox mode</strong>. No real charges. Use any email.
                        </div>
                        <button type="submit" class="btn-primary-custom w-100 justify-content-center" style="background:linear-gradient(135deg,#003087,#009cde);font-size:16px;padding:14px;">
                            <i class="fab fa-paypal me-2"></i>Pay $<?php echo number_format($course['price']/133, 2); ?> USD via PayPal
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script>
function selectPayment(method) {
    document.getElementById('esewa-form').style.display  = 'none';
    document.getElementById('paypal-form').style.display = 'none';
    document.querySelectorAll('.payment-method-btn').forEach(b => b.classList.remove('selected'));
    document.getElementById(method + '-form').style.display = 'block';
    document.getElementById('btn-' + method).classList.add('selected');
    document.getElementById(method + '-form').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

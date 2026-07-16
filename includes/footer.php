<?php
// ============================================
// EDUCORE - Global Footer
// ============================================
?>
<!-- ============ FOOTER ============ -->
<footer class="educore-footer">
    <div class="footer-wave">
        <svg viewBox="0 0 1440 80" preserveAspectRatio="none"><path d="M0,40 C360,80 1080,0 1440,40 L1440,0 L0,0 Z" fill="var(--bg-darker)"/></svg>
    </div>
    <div class="container">
        <div class="row g-4 footer-main">
            <!-- Brand -->
            <div class="col-lg-4">
                <a href="<?php echo BASE_URL; ?>/index.php" class="footer-brand">
                    <div class="logo-icon-sm"><i class="fas fa-graduation-cap"></i></div>
                    <span>EDU<span class="logo-accent">CORE</span></span>
                </a>
                <p class="footer-tagline">Empowering learners worldwide with quality education. Build skills that matter, at your own pace.</p>
                <div class="social-links">
                    <a href="#" class="social-btn" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-btn" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-btn" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-btn" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" class="social-btn" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                </div>
            </div>

            <!-- Courses -->
            <div class="col-6 col-md-3 col-lg-2">
                <h5 class="footer-heading">Courses</h5>
                <ul class="footer-links">
                    <li><a href="<?php echo BASE_URL; ?>/courses/index.php?category=programming">Programming</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/courses/index.php?category=design">Design</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/courses/index.php?category=business">Business</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/courses/index.php?category=data-science">Data Science</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/courses/index.php?type=free">Free Courses</a></li>
                </ul>
            </div>

            <!-- Company -->
            <div class="col-6 col-md-3 col-lg-2">
                <h5 class="footer-heading">Company</h5>
                <ul class="footer-links">
                    <li><a href="#">About Us</a></li>
                    <li><a href="#">Careers</a></li>
                    <li><a href="#">Blog</a></li>
                    <li><a href="#">Press</a></li>
                    <li><a href="#">Affiliates</a></li>
                </ul>
            </div>

            <!-- Support -->
            <div class="col-6 col-md-3 col-lg-2">
                <h5 class="footer-heading">Support</h5>
                <ul class="footer-links">
                    <li><a href="#">Help Center</a></li>
                    <li><a href="#">Contact Us</a></li>
                    <li><a href="#">Community</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Use</a></li>
                </ul>
            </div>

            <!-- Newsletter -->
            <div class="col-lg-2">
                <h5 class="footer-heading">Stay Updated</h5>
                <p class="footer-desc">Get new course alerts and tips delivered to your inbox.</p>
                <form class="newsletter-form" onsubmit="subscribeNewsletter(event)">
                    <input type="email" placeholder="Your email" class="newsletter-input" required>
                    <button type="submit" class="newsletter-btn"><i class="fas fa-paper-plane"></i></button>
                </form>
                <div class="footer-badges mt-3">
                    <div class="stat-badge"><i class="fas fa-users"></i><span>50K+<br><small>Students</small></span></div>
                    <div class="stat-badge"><i class="fas fa-book-open"></i><span>200+<br><small>Courses</small></span></div>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <span>&copy; <?php echo date('Y'); ?> EDUCORE. All rights reserved. Built with <i class="fas fa-heart text-danger"></i> for learners everywhere.</span>
            <div class="footer-bottom-links">
                <a href="#">Privacy</a>
                <a href="#">Terms</a>
                <a href="#">Cookies</a>
            </div>
        </div>
    </div>
</footer>

<!-- ============ TOAST NOTIFICATION ============ -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999;" id="toastContainer"></div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>

<script>
// Load notifications on dropdown open
document.getElementById('notifBtn')?.addEventListener('click', loadNotifications);

function loadNotifications() {
    fetch('<?php echo BASE_URL; ?>/api/notifications.php')
        .then(r => r.json())
        .then(data => {
            const list = document.getElementById('notifList');
            if (!list) return;
            if (!data.length) {
                list.innerHTML = '<div class="notif-empty"><i class="fas fa-bell-slash"></i><p>No notifications</p></div>';
                return;
            }
            list.innerHTML = data.map(n => `
                <div class="notif-item ${n.is_read ? '' : 'unread'}" style="color:#fff;">
                    <div class="notif-icon type-${n.type}"><i class="fas fa-${n.type === 'success' ? 'check' : 'info-circle'}"></i></div>
                    <div class="notif-content">
                        <p style="color:#fff;margin:0;font-size:13px;line-height:1.5;">${n.message}</p>
                        <small style="color:#9ca3af;font-size:11px;">${n.time_ago}</small>
                    </div>
                </div>
            `).join('');
        }).catch(() => {});
}

function markAllRead() {
    fetch('<?php echo BASE_URL; ?>/api/notifications.php?action=mark_read', {method:'POST'})
        .then(() => {
            document.querySelectorAll('.notif-badge').forEach(el => el.remove());
            document.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread'));
        });
}

function subscribeNewsletter(e) {
    e.preventDefault();
    showToast('success', 'Subscribed! You\'ll receive updates soon. 🎉');
    e.target.reset();
}

function toggleMobileMenu() {
    document.getElementById('mobileMenu').classList.toggle('open');
}

// Scroll-based navbar effect
window.addEventListener('scroll', () => {
    const nav = document.getElementById('mainNavbar');
    if (nav) nav.classList.toggle('scrolled', window.scrollY > 50);
});
</script>

<?php if (isset($extraScripts)) echo $extraScripts; ?>
</body>
</html>

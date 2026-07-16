// ============================================
// EDUCORE - Main JavaScript
// ============================================

'use strict';

// ---- Toast Notifications ----
function showToast(type, message, duration = 4000) {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const icons = { success: 'check-circle', error: 'times-circle', info: 'info-circle', warning: 'exclamation-triangle' };
    const colors = { success: '#22c55e', error: '#ef4444', info: '#3b82f6', warning: '#f59e0b' };

    const toast = document.createElement('div');
    toast.className = `toast-custom toast-${type} mb-2`;
    toast.innerHTML = `
        <i class="fas fa-${icons[type] || 'info-circle'}" style="color:${colors[type]};font-size:18px;"></i>
        <div style="flex:1;font-size:14px;">${message}</div>
        <button onclick="this.parentElement.remove()" style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:16px;">&times;</button>
    `;
    container.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; toast.style.transform = 'translateX(100%)'; toast.style.transition = 'all 0.3s'; setTimeout(() => toast.remove(), 300); }, duration);
}

// ---- Scroll Animation (Intersection Observer) ----
document.addEventListener('DOMContentLoaded', () => {
    // Animate elements on scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-up');
                entry.target.style.opacity = '1';
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('[data-animate]').forEach(el => {
        el.style.opacity = '0';
        observer.observe(el);
    });

    // Progress bars animation
    document.querySelectorAll('.progress-fill').forEach(bar => {
        const target = bar.getAttribute('data-width') || bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => { bar.style.width = target; }, 300);
    });

    // Counter animation
    document.querySelectorAll('[data-count]').forEach(el => {
        const target = parseInt(el.getAttribute('data-count'));
        let current = 0;
        const step = target / 60;
        const timer = setInterval(() => {
            current += step;
            if (current >= target) { el.textContent = target + '+'; clearInterval(timer); }
            else { el.textContent = Math.floor(current) + '+'; }
        }, 16);
    });

    // Password toggle
    document.querySelectorAll('.input-eye').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = btn.previousElementSibling || btn.parentElement.querySelector('input');
            if (!input) return;
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            btn.className = btn.className.replace(isHidden ? 'fa-eye' : 'fa-eye-slash', isHidden ? 'fa-eye-slash' : 'fa-eye');
        });
    });

    // Active nav link
    const currentPath = window.location.pathname;
    document.querySelectorAll('.nav-link-item').forEach(link => {
        if (link.getAttribute('href') && currentPath.includes(link.getAttribute('href').split('?')[0])) {
            link.classList.add('active');
        }
    });

    // Rating stars interactive
    setupStarRating();
});

// ---- Star Rating Input ----
function setupStarRating() {
    const container = document.querySelector('.stars-input');
    if (!container) return;
    const stars = container.querySelectorAll('.star-input-btn');
    const input = container.closest('form')?.querySelector('input[name="rating"]');
    stars.forEach((star, idx) => {
        star.addEventListener('mouseenter', () => {
            stars.forEach((s, i) => s.style.color = i <= idx ? '#fbbf24' : 'var(--text-muted)');
        });
        star.addEventListener('mouseleave', () => {
            const val = input?.value || 0;
            stars.forEach((s, i) => s.style.color = i < val ? '#fbbf24' : 'var(--text-muted)');
        });
        star.addEventListener('click', () => {
            if (input) input.value = idx + 1;
            stars.forEach((s, i) => { s.style.color = i <= idx ? '#fbbf24' : 'var(--text-muted)'; s.classList.toggle('active', i <= idx); });
        });
    });
}

// ---- Live Search ----
let searchTimer;
function liveSearch(query, containerSelector) {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        const container = document.querySelector(containerSelector);
        if (!container) return;
        const cards = container.querySelectorAll('[data-title]');
        const q = query.toLowerCase().trim();
        cards.forEach(card => {
            const title = (card.getAttribute('data-title') || '').toLowerCase();
            const cat = (card.getAttribute('data-category') || '').toLowerCase();
            const matches = !q || title.includes(q) || cat.includes(q);
            card.style.display = matches ? '' : 'none';
        });
    }, 200);
}

// ---- Filter by Category ----
function filterByTag(tag, containerSelector) {
    const container = document.querySelector(containerSelector);
    if (!container) return;
    const cards = container.querySelectorAll('[data-category]');
    cards.forEach(card => {
        const cat = card.getAttribute('data-category') || '';
        card.style.display = (!tag || tag === 'all' || cat === tag) ? '' : 'none';
    });
    // Update active filter button
    document.querySelectorAll('.tag-filter').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-tag') === tag);
    });
}

// ---- Course Filter Sort ----
function sortCourses(sortBy) {
    const container = document.querySelector('.courses-grid');
    if (!container) return;
    const cards = [...container.querySelectorAll('.course-card')];
    cards.sort((a, b) => {
        if (sortBy === 'price-asc') return parseFloat(a.dataset.price || 0) - parseFloat(b.dataset.price || 0);
        if (sortBy === 'price-desc') return parseFloat(b.dataset.price || 0) - parseFloat(a.dataset.price || 0);
        if (sortBy === 'rating') return parseFloat(b.dataset.rating || 0) - parseFloat(a.dataset.rating || 0);
        return 0;
    });
    cards.forEach(c => container.appendChild(c));
}

// ---- Confirm Dialog ----
function confirmAction(message, callback) {
    if (confirm(message)) callback();
}

// ---- Copy to clipboard ----
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => showToast('success', 'Copied to clipboard!')).catch(() => {
        const ta = document.createElement('textarea');
        ta.value = text; document.body.appendChild(ta); ta.select();
        document.execCommand('copy'); document.body.removeChild(ta);
        showToast('success', 'Copied!');
    });
}

// ---- Form Validation ----
function validateForm(formEl) {
    let valid = true;
    formEl.querySelectorAll('[required]').forEach(field => {
        const err = field.parentElement.querySelector('.field-error');
        if (!field.value.trim()) {
            field.style.borderColor = '#ef4444';
            if (err) err.style.display = 'block';
            valid = false;
        } else {
            field.style.borderColor = '';
            if (err) err.style.display = 'none';
        }
    });
    // Email validation
    const emailField = formEl.querySelector('[type="email"]');
    if (emailField && emailField.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailField.value)) {
        emailField.style.borderColor = '#ef4444';
        valid = false;
    }
    return valid;
}

// ---- AJAX helper ----
function ajax(url, method, data, onSuccess, onError) {
    const opts = { method, headers: { 'Content-Type': 'application/json' } };
    if (data && method !== 'GET') opts.body = JSON.stringify(data);
    fetch(url, opts)
        .then(r => r.json())
        .then(onSuccess)
        .catch(onError || (() => showToast('error', 'Network error. Please try again.')));
}

// ---- Video player helper ----
function playVideo(src) {
    const modal = document.getElementById('videoModal');
    const iframe = document.getElementById('videoFrame');
    if (!modal || !iframe) return;
    iframe.src = src;
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
    modal.addEventListener('hidden.bs.modal', () => { iframe.src = ''; }, { once: true });
}

// ---- Smooth scroll to section ----
function scrollTo(id) {
    const el = document.getElementById(id);
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// ---- Mobile sidebar toggle for dashboard ----
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) sidebar.classList.toggle('sidebar-open');
}

// ---- Countdown timer ----
function startCountdown(endDate, elementId) {
    const el = document.getElementById(elementId);
    if (!el) return;
    function update() {
        const diff = new Date(endDate) - new Date();
        if (diff <= 0) { el.textContent = 'Expired'; return; }
        const h = Math.floor(diff / 3600000);
        const m = Math.floor((diff % 3600000) / 60000);
        const s = Math.floor((diff % 60000) / 1000);
        el.textContent = `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
    }
    update();
    setInterval(update, 1000);
}

// ---- Recommendation Engine (Client-side) ----
function loadRecommendations(userId, containerId, baseUrl) {
    baseUrl = baseUrl || '';
    fetch(`${baseUrl}/api/recommendations.php?user_id=${userId}`)
        .then(r => r.json())
        .then(courses => {
            const container = document.getElementById(containerId);
            if (!container || !courses.length) return;
            container.innerHTML = courses.map(c => `
                <div class="course-card" onclick="window.location='${baseUrl}/courses/detail.php?id=${c.id}'">
                    <div class="course-thumbnail"><div class="course-thumbnail-placeholder">${c.icon || '📚'}</div></div>
                    <div class="course-info">
                        <div class="course-category-tag">${c.category}</div>
                        <h4 class="course-title">${c.title}</h4>
                        <div class="course-footer">
                            <span class="course-price ${c.price == 0 ? 'price-free' : 'price-paid'}">${c.price == 0 ? 'FREE' : 'NPR ' + Number(c.price).toLocaleString()}</span>
                        </div>
                    </div>
                </div>
            `).join('');
        }).catch(() => {});
}

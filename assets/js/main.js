/**
 * PhoenixKA Shop - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    initNavbar();
    initFadeAnimations();
    initFAQ();
    initMobileMenu();
    initSlugPreview();
});

// =====================================================
// NAVBAR SCROLL EFFECT
// =====================================================
function initNavbar() {
    const navbar = document.querySelector('.navbar');
    if (!navbar) return;
    
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
}

// =====================================================
// FADE-IN ANIMATIONS ON SCROLL
// =====================================================
function initFadeAnimations() {
    const elements = document.querySelectorAll('.fade-in');
    if (!elements.length) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

    elements.forEach(el => observer.observe(el));
}

// =====================================================
// FAQ ACCORDION
// =====================================================
function initFAQ() {
    const faqItems = document.querySelectorAll('.faq-item');
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        question.addEventListener('click', () => {
            const isActive = item.classList.contains('active');
            // Close all
            faqItems.forEach(i => i.classList.remove('active'));
            // Toggle clicked
            if (!isActive) item.classList.add('active');
        });
    });
}

// =====================================================
// MOBILE MENU
// =====================================================
function initMobileMenu() {
    const toggle = document.querySelector('.mobile-toggle');
    const menu = document.querySelector('.navbar-menu');
    if (!toggle || !menu) return;

    toggle.addEventListener('click', () => {
        menu.classList.toggle('active');
        toggle.textContent = menu.classList.contains('active') ? '✕' : '☰';
    });

    // Close on link click
    menu.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            menu.classList.remove('active');
            toggle.textContent = '☰';
        });
    });
}

// =====================================================
// SLUG PREVIEW (for store creation)
// =====================================================
function initSlugPreview() {
    const nameInput = document.getElementById('store-name');
    const slugPreview = document.getElementById('slug-preview');
    if (!nameInput || !slugPreview) return;

    nameInput.addEventListener('input', (e) => {
        const slug = e.target.value
            .toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim();
        slugPreview.textContent = slug ? `phoenixka.shop/${slug}` : 'phoenixka.shop/ma-boutique';
    });
}

// =====================================================
// COLOR PICKER for store theme
// =====================================================
function initColorPicker() {
    const colorInputs = document.querySelectorAll('.color-picker-input');
    colorInputs.forEach(input => {
        const preview = input.parentElement.querySelector('.color-preview');
        input.addEventListener('input', () => {
            if (preview) preview.style.backgroundColor = input.value;
        });
    });
}

// =====================================================
// NOTIFICATIONS
// =====================================================
function showNotification(message, type = 'success') {
    const container = document.getElementById('notification-container') || createNotifContainer();
    const notif = document.createElement('div');
    notif.className = `notif notif-${type}`;
    
    const icons = { success: '✓', error: '✕', warning: '⚠', info: 'ℹ' };
    notif.innerHTML = `
        <span class="notif-icon">${icons[type] || icons.info}</span>
        <span class="notif-text">${message}</span>
    `;
    
    container.appendChild(notif);
    setTimeout(() => notif.classList.add('show'), 10);
    setTimeout(() => {
        notif.classList.remove('show');
        setTimeout(() => notif.remove(), 300);
    }, 4000);
}

function createNotifContainer() {
    const c = document.createElement('div');
    c.id = 'notification-container';
    c.style.cssText = 'position:fixed;top:90px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;';
    document.body.appendChild(c);
    return c;
}

// =====================================================
// SMOOTH SCROLL
// =====================================================
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) target.scrollIntoView({ behavior: 'smooth' });
    });
});

// =====================================================
// COUNTER ANIMATION
// =====================================================
function animateCounter(element, target, duration = 2000) {
    let start = 0;
    const increment = target / (duration / 16);
    
    function step() {
        start += increment;
        if (start < target) {
            element.textContent = Math.floor(start).toLocaleString('fr-FR');
            requestAnimationFrame(step);
        } else {
            element.textContent = target.toLocaleString('fr-FR');
        }
    }
    step();
}

// Init counters when visible
const counterObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const el = entry.target;
            const target = parseInt(el.dataset.count || el.textContent.replace(/\D/g, ''));
            if (target) animateCounter(el, target);
            counterObserver.unobserve(el);
        }
    });
}, { threshold: 0.5 });

document.querySelectorAll('[data-count]').forEach(el => counterObserver.observe(el));

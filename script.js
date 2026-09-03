/**
 * Enhanced Shop JavaScript
 * Modern interactive features and animations
 */

// Tab switching functionality
document.addEventListener('DOMContentLoaded', function() {
    // ===== TAB SWITCHING =====
    const tabBtns = document.querySelectorAll('.tab-btn');
    const forms = document.querySelectorAll('.form');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            tabBtns.forEach(b => b.classList.remove('active'));
            forms.forEach(f => f.classList.remove('active'));
            btn.classList.add('active');
            const tabName = btn.getAttribute('data-tab');
            if (tabName === 'login') {
                document.getElementById('loginForm')?.classList.add('active');
            } else {
                document.getElementById('signupForm')?.classList.add('active');
            }
        });
    });

    // ===== FORM VALIDATION =====
    const signupForm = document.getElementById('signupForm');
    if (signupForm) {
        signupForm.addEventListener('submit', function(e) {
            const password = this.querySelector('input[name="password"]')?.value;
            const confirmPassword = this.querySelector('input[name="confirm_password"]')?.value;
            if (password !== confirmPassword) {
                e.preventDefault();
                showToast('Passwords do not match!', 'error');
                return false;
            }
        });
    }

    // ===== HEADER SCROLL EFFECT =====
    const header = document.querySelector('.header');
    if (header) {
        let lastScroll = 0;
        window.addEventListener('scroll', () => {
            const currentScroll = window.pageYOffset;
            if (currentScroll > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
            lastScroll = currentScroll;
        });
    }

    // ===== RIPPLE EFFECT =====
    document.querySelectorAll('.btn-primary, .btn-add-cart, .submit-btn, .btn-buy, .btn-cart, .category-card, .nav-item').forEach(btn => {
        btn.classList.add('ripple');
        btn.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            ripple.classList.add('ripple-effect');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
            ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';
            this.appendChild(ripple);
            setTimeout(() => ripple.remove(), 600);
        });
    });

    // ===== SCROLL ANIMATIONS =====
    const animateOnScroll = () => {
        const elements = document.querySelectorAll('.product-card, .category-card, .cart-item-card, .order-card, .section');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    entry.target.style.animationDelay = `${index * 0.05}s`;
                    entry.target.classList.add('animate-in');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

        elements.forEach(el => {
            el.style.opacity = '0';
            observer.observe(el);
        });
    };
    animateOnScroll();

    // ===== ACCORDION =====
    document.querySelectorAll('.accordion-header').forEach(header => {
        header.addEventListener('click', function() {
            const content = this.nextElementSibling;
            const isActive = content.classList.contains('active');
            
            // Close all accordions
            document.querySelectorAll('.accordion-content').forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.accordion-header').forEach(h => h.classList.remove('active'));
            
            // Open clicked one if it wasn't active
            if (!isActive) {
                content.classList.add('active');
                this.classList.add('active');
            }
        });
    });

    // ===== QUANTITY CONTROLS =====
    document.querySelectorAll('.qty-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.parentElement.querySelector('.qty-input, input[type="number"]');
            if (!input) return;
            
            let value = parseInt(input.value) || 1;
            const isPlus = this.classList.contains('plus') || this.textContent.includes('+');
            
            if (isPlus) {
                value++;
            } else {
                value = Math.max(1, value - 1);
            }
            
            input.value = value;
            
            // Trigger change event for forms
            input.dispatchEvent(new Event('change', { bubbles: true }));
            
            // Animate button
            this.style.transform = 'scale(1.2)';
            setTimeout(() => this.style.transform = '', 150);
        });
    });

    // ===== IMAGE GALLERY =====
    const mainImage = document.querySelector('.main-image img');
    const thumbnails = document.querySelectorAll('.thumbnail');
    
    thumbnails.forEach(thumb => {
        thumb.addEventListener('click', function() {
            thumbnails.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            const img = this.querySelector('img');
            if (mainImage && img) {
                mainImage.style.opacity = '0';
                mainImage.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    mainImage.src = img.src;
                    mainImage.style.opacity = '1';
                    mainImage.style.transform = 'scale(1)';
                }, 200);
            }
        });
    });

    // ===== SEARCH FUNCTIONALITY =====
    const searchInput = document.querySelector('.search-bar input');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            // Add loading state
            this.parentElement.classList.add('searching');
            
            searchTimeout = setTimeout(() => {
                this.parentElement.classList.remove('searching');
                // Search logic here
            }, 300);
        });

        // Search on Enter
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const form = this.closest('form');
                if (form) form.submit();
            }
        });
    }

    // ===== ADD TO CART ANIMATION =====
    document.querySelectorAll('.btn-add-cart, .btn-cart').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (this.closest('form')) return; // Let form submit normally
            
            const icon = document.querySelector('.nav-item.center-btn .nav-icon-wrapper, .icon-btn .badge')?.closest('.nav-item, .icon-btn');
            if (icon) {
                icon.classList.add('cart-bounce');
                setTimeout(() => icon.classList.remove('cart-bounce'), 500);
            }
        });
    });

    // ===== SMOOTH SCROLL FOR ANCHOR LINKS =====
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // ===== LAZY LOADING IMAGES =====
    const lazyImages = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                img.classList.add('loaded');
                imageObserver.unobserve(img);
            }
        });
    });
    lazyImages.forEach(img => imageObserver.observe(img));

    // ===== AUTO-HIDE ALERTS =====
    document.querySelectorAll('.alert, .success-message, .error-message').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    // ===== INPUT FOCUS EFFECTS =====
    document.querySelectorAll('.input-group input, .form-group input, .form-group textarea').forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
            if (this.value) {
                this.parentElement.classList.add('has-value');
            } else {
                this.parentElement.classList.remove('has-value');
            }
        });
    });

    // ===== NUMBER COUNTER ANIMATION =====
    const animateNumbers = () => {
        document.querySelectorAll('.analytics-value, .stat-value, .detail-price').forEach(el => {
            const text = el.textContent;
            const match = text.match(/[\d,.]+/);
            if (!match) return;
            
            const target = parseFloat(match[0].replace(/,/g, ''));
            const prefix = text.substring(0, text.indexOf(match[0]));
            const suffix = text.substring(text.indexOf(match[0]) + match[0].length);
            const duration = 1500;
            const start = performance.now();
            
            const animate = (now) => {
                const elapsed = now - start;
                const progress = Math.min(elapsed / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3); // Ease out cubic
                const current = target * eased;
                
                el.textContent = prefix + formatNumber(current) + suffix;
                
                if (progress < 1) {
                    requestAnimationFrame(animate);
                } else {
                    el.textContent = text; // Reset to original
                }
            };
            
            const observer = new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting) {
                    requestAnimationFrame(animate);
                    observer.disconnect();
                }
            });
            observer.observe(el);
        });
    };
    animateNumbers();

    // ===== PARALLAX EFFECT =====
    const parallaxElements = document.querySelectorAll('.hero-banner');
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        parallaxElements.forEach(el => {
            const rate = scrolled * 0.3;
            el.style.backgroundPositionY = rate + 'px';
        });
    });

    // ===== KEYBOARD NAVIGATION =====
    document.addEventListener('keydown', (e) => {
        // ESC to close modals
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.active').forEach(modal => {
                modal.classList.remove('active');
            });
        }
    });
});

// ===== TOAST NOTIFICATION SYSTEM =====
function showToast(message, type = 'info', duration = 4000) {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const icons = {
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-circle',
        info: 'fas fa-info-circle',
        warning: 'fas fa-exclamation-triangle'
    };

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <i class="${icons[type] || icons.info}"></i>
        <span>${message}</span>
        <button class="toast-close"><i class="fas fa-times"></i></button>
    `;

    container.appendChild(toast);

    // Close button
    toast.querySelector('.toast-close').addEventListener('click', () => {
        closeToast(toast);
    });

    // Auto close
    setTimeout(() => closeToast(toast), duration);

    return toast;
}

function closeToast(toast) {
    toast.style.animation = 'slideInRight 0.4s ease reverse';
    setTimeout(() => toast.remove(), 400);
}

// ===== HELPER FUNCTIONS =====
function formatNumber(num) {
    if (num >= 1000) {
        return num.toLocaleString('en-US', { maximumFractionDigits: 0 });
    }
    return num.toFixed(2);
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// ===== CART BADGE UPDATE =====
function updateCartBadge(count) {
    const badges = document.querySelectorAll('.icon-btn .badge, .nav-item .badge');
    badges.forEach(badge => {
        badge.textContent = count;
        badge.style.animation = 'badgePop 0.3s ease';
        setTimeout(() => badge.style.animation = '', 300);
    });
}

// ===== LOADING OVERLAY =====
function showLoading(element) {
    element?.classList.add('loading');
}

function hideLoading(element) {
    element?.classList.remove('loading');
}

// ===== FORM SUBMISSION ENHANCEMENT =====
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function() {
        const submitBtn = this.querySelector('[type="submit"], .submit-btn, .btn-submit');
        if (submitBtn) {
            submitBtn.disabled = true;
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner" style="width:20px;height:20px;border-width:2px;display:inline-block;margin-right:8px;"></span>Processing...';
            
            // Re-enable after timeout (in case of error)
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }, 10000);
        }
    });
});

// ===== EXPORT FOR EXTERNAL USE =====
window.ShopUtils = {
    showToast,
    closeToast,
    updateCartBadge,
    showLoading,
    hideLoading,
    debounce,
    throttle
};

// ============================================================
// PROFESSIONAL UI ENHANCEMENTS
// ============================================================

// ===== SCROLL PROGRESS INDICATOR =====
function initScrollProgress() {
    const indicator = document.createElement('div');
    indicator.className = 'scroll-indicator';
    indicator.innerHTML = '<div class="scroll-progress"></div>';
    document.body.prepend(indicator);

    const progressBar = indicator.querySelector('.scroll-progress');
    
    window.addEventListener('scroll', throttle(() => {
        const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
        const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const scrolled = (winScroll / height) * 100;
        progressBar.style.width = scrolled + '%';
    }, 10));
}

// ===== CURSOR GLOW EFFECT =====
function initCursorGlow() {
    if (window.matchMedia('(pointer: coarse)').matches) return; // Skip on touch devices
    
    const glow = document.createElement('div');
    glow.className = 'cursor-glow';
    document.body.appendChild(glow);

    let mouseX = 0, mouseY = 0;
    let glowX = 0, glowY = 0;

    document.addEventListener('mousemove', (e) => {
        mouseX = e.clientX;
        mouseY = e.clientY;
    });

    function animateGlow() {
        glowX += (mouseX - glowX) * 0.1;
        glowY += (mouseY - glowY) * 0.1;
        glow.style.left = glowX + 'px';
        glow.style.top = glowY + 'px';
        requestAnimationFrame(animateGlow);
    }
    animateGlow();

    // Hide when mouse leaves window
    document.addEventListener('mouseleave', () => glow.style.opacity = '0');
    document.addEventListener('mouseenter', () => glow.style.opacity = '0.3');
}

// ===== MAGNETIC BUTTON EFFECT =====
function initMagneticButtons() {
    document.querySelectorAll('.magnetic, .btn-gradient, .fab').forEach(btn => {
        btn.addEventListener('mousemove', function(e) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;
            
            this.style.transform = `translate(${x * 0.3}px, ${y * 0.3}px)`;
        });

        btn.addEventListener('mouseleave', function() {
            this.style.transform = '';
        });
    });
}

// ===== PAGE TRANSITION EFFECT =====
function initPageTransitions() {
    const transition = document.createElement('div');
    transition.className = 'page-transition';
    document.body.appendChild(transition);

    // Add transition on link clicks
    document.querySelectorAll('a[href]:not([href^="#"]):not([target="_blank"]):not([data-no-transition])').forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href && !href.startsWith('javascript:') && !href.startsWith('mailto:') && !href.startsWith('tel:')) {
                e.preventDefault();
                transition.classList.add('active');
                setTimeout(() => window.location.href = href, 400);
            }
        });
    });

    // Show page with fade-in
    window.addEventListener('pageshow', () => {
        document.body.style.opacity = '1';
    });
}

// ===== CONFETTI EFFECT =====
function showConfetti(count = 50) {
    const colors = ['#3b82f6', '#06b6d4', '#818cf8', '#0ea5e9', '#60a5fa'];
    
    for (let i = 0; i < count; i++) {
        const confetti = document.createElement('div');
        confetti.className = 'confetti';
        confetti.style.left = Math.random() * 100 + 'vw';
        confetti.style.animationDelay = Math.random() * 2 + 's';
        confetti.style.setProperty('--color', colors[Math.floor(Math.random() * colors.length)]);
        confetti.querySelector('::before')?.style.setProperty('background', colors[Math.floor(Math.random() * colors.length)]);
        document.body.appendChild(confetti);
        
        setTimeout(() => confetti.remove(), 5000);
    }
}

// ===== TYPING EFFECT =====
function typeWriter(element, text, speed = 50) {
    let i = 0;
    element.textContent = '';
    element.classList.add('text-typing');
    
    function type() {
        if (i < text.length) {
            element.textContent += text.charAt(i);
            i++;
            setTimeout(type, speed);
        } else {
            element.classList.remove('text-typing');
        }
    }
    type();
}

// ===== COUNTER ANIMATION =====
function animateCounter(element, target, duration = 2000) {
    const start = 0;
    const startTime = performance.now();
    
    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 4); // Ease out quart
        const current = Math.floor(start + (target - start) * eased);
        
        element.textContent = current.toLocaleString();
        
        if (progress < 1) {
            requestAnimationFrame(update);
        }
    }
    
    requestAnimationFrame(update);
}

// ===== COUNTDOWN TIMER =====
function initCountdown(endDate, container) {
    function updateCountdown() {
        const now = new Date().getTime();
        const distance = new Date(endDate).getTime() - now;
        
        if (distance < 0) {
            container.innerHTML = '<p>Offer Expired!</p>';
            return;
        }
        
        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        container.innerHTML = `
            <div class="countdown-item"><span class="countdown-number">${days}</span><span class="countdown-label">Days</span></div>
            <div class="countdown-item"><span class="countdown-number">${hours}</span><span class="countdown-label">Hours</span></div>
            <div class="countdown-item"><span class="countdown-number">${minutes}</span><span class="countdown-label">Minutes</span></div>
            <div class="countdown-item"><span class="countdown-number">${seconds}</span><span class="countdown-label">Seconds</span></div>
        `;
    }
    
    updateCountdown();
    setInterval(updateCountdown, 1000);
}

// ===== SMOOTH PARALLAX =====
function initParallax() {
    const parallaxElements = document.querySelectorAll('[data-parallax]');
    
    window.addEventListener('scroll', throttle(() => {
        const scrolled = window.pageYOffset;
        
        parallaxElements.forEach(el => {
            const speed = el.dataset.parallax || 0.5;
            const offset = scrolled * speed;
            el.style.transform = `translateY(${offset}px)`;
        });
    }, 10));
}

// ===== TILT EFFECT =====
function initTiltEffect() {
    document.querySelectorAll('.img-tilt, [data-tilt]').forEach(el => {
        el.addEventListener('mousemove', function(e) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            const rotateX = (y - centerY) / 10;
            const rotateY = (centerX - x) / 10;
            
            this.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
        });

        el.addEventListener('mouseleave', function() {
            this.style.transform = '';
        });
    });
}

// ===== TEXT REVEAL ANIMATION =====
function initTextReveal() {
    document.querySelectorAll('.text-reveal').forEach(el => {
        const text = el.textContent;
        el.innerHTML = '';
        
        text.split('').forEach((char, i) => {
            const span = document.createElement('span');
            span.textContent = char === ' ' ? '\u00A0' : char;
            span.style.animationDelay = `${i * 0.03}s`;
            el.appendChild(span);
        });
    });
}

// ===== COPY TO CLIPBOARD =====
function copyToClipboard(text, successMessage = 'Copied!') {
    navigator.clipboard.writeText(text).then(() => {
        showToast(successMessage, 'success', 2000);
    }).catch(() => {
        showToast('Failed to copy', 'error');
    });
}

// ===== IMAGE LAZY LOAD WITH BLUR =====
function initLazyLoadBlur() {
    document.querySelectorAll('img[data-src]').forEach(img => {
        img.style.filter = 'blur(10px)';
        img.style.transition = 'filter 0.5s ease';
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const newImg = new Image();
                    newImg.onload = () => {
                        img.src = img.dataset.src;
                        img.style.filter = 'blur(0)';
                    };
                    newImg.src = img.dataset.src;
                    observer.unobserve(img);
                }
            });
        });
        
        observer.observe(img);
    });
}

// ===== FORM VALIDATION =====
function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('[required]');
    
    inputs.forEach(input => {
        const container = input.closest('.input-pro') || input.parentElement;
        
        if (!input.value.trim()) {
            container.classList.add('invalid');
            container.classList.remove('valid');
            isValid = false;
        } else if (input.type === 'email' && !isValidEmail(input.value)) {
            container.classList.add('invalid');
            container.classList.remove('valid');
            isValid = false;
        } else {
            container.classList.add('valid');
            container.classList.remove('invalid');
        }
    });
    
    return isValid;
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

// ===== PASSWORD STRENGTH CHECKER =====
function checkPasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.length >= 12) strength++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;
    
    const levels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
    const colors = ['#ff6b6b', '#0ea5e9', '#ffa500', '#06b6d4', '#3b82f6'];
    
    return {
        score: strength,
        level: levels[Math.min(strength, 4)],
        color: colors[Math.min(strength, 4)]
    };
}

// ===== SMOOTH SCROLL TO ELEMENT =====
function smoothScrollTo(element, offset = 0) {
    const targetPosition = element.getBoundingClientRect().top + window.pageYOffset - offset;
    
    window.scrollTo({
        top: targetPosition,
        behavior: 'smooth'
    });
}

// ===== MODAL SYSTEM =====
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// ===== THEME TOGGLE =====
function initThemeToggle() {
    const toggle = document.querySelector('.theme-toggle');
    if (!toggle) return;
    
    const savedTheme = localStorage.getItem('theme') || 'dark';
    if (savedTheme === 'light') {
        document.body.classList.add('light-mode');
        toggle.classList.remove('dark');
    } else {
        toggle.classList.add('dark');
    }
    
    toggle.addEventListener('click', () => {
        document.body.classList.toggle('light-mode');
        toggle.classList.toggle('dark');
        
        const currentTheme = document.body.classList.contains('light-mode') ? 'light' : 'dark';
        localStorage.setItem('theme', currentTheme);
    });
}

// ===== DROPDOWN TOGGLE =====
function initDropdowns() {
    document.querySelectorAll('.dropdown-pro').forEach(dropdown => {
        const trigger = dropdown.querySelector('.dropdown-trigger');
        
        trigger?.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.classList.toggle('active');
        });
    });
    
    document.addEventListener('click', () => {
        document.querySelectorAll('.dropdown-pro.active').forEach(d => d.classList.remove('active'));
    });
}

// ===== SKELETON LOADER =====
function showSkeleton(container, type = 'card') {
    const templates = {
        card: `
            <div class="skeleton-pro skeleton-image"></div>
            <div class="skeleton-pro skeleton-title"></div>
            <div class="skeleton-pro skeleton-text"></div>
            <div class="skeleton-pro skeleton-text short"></div>
        `,
        list: `
            <div style="display:flex;gap:16px;align-items:center;">
                <div class="skeleton-pro skeleton-avatar"></div>
                <div style="flex:1;">
                    <div class="skeleton-pro skeleton-text"></div>
                    <div class="skeleton-pro skeleton-text short"></div>
                </div>
            </div>
        `,
        text: `
            <div class="skeleton-pro skeleton-title"></div>
            <div class="skeleton-pro skeleton-text"></div>
            <div class="skeleton-pro skeleton-text"></div>
            <div class="skeleton-pro skeleton-text short"></div>
        `
    };
    
    container.innerHTML = templates[type] || templates.card;
}

// ===== INITIALIZE PROFESSIONAL FEATURES =====
document.addEventListener('DOMContentLoaded', () => {
    // Initialize all professional effects
    initScrollProgress();
    initCursorGlow();
    initMagneticButtons();
    initPageTransitions();
    initParallax();
    initTiltEffect();
    initTextReveal();
    initLazyLoadBlur();
    initThemeToggle();
    initDropdowns();
    
    // Initialize counters on scroll
    document.querySelectorAll('[data-counter]').forEach(el => {
        const observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting) {
                animateCounter(el, parseInt(el.dataset.counter));
                observer.disconnect();
            }
        });
        observer.observe(el);
    });
    
    // Add success confetti on order pages
    if (document.querySelector('.order-success')) {
        setTimeout(() => showConfetti(100), 500);
    }
});

// ===== EXPORT PROFESSIONAL UTILS =====
window.ShopPro = {
    showConfetti,
    typeWriter,
    animateCounter,
    initCountdown,
    copyToClipboard,
    validateForm,
    checkPasswordStrength,
    smoothScrollTo,
    openModal,
    closeModal,
    showSkeleton
};

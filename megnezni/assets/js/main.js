/* ══════════════════════════════════════════
   FOGLALAS.HU – Main JavaScript
══════════════════════════════════════════ */

'use strict';

// ── DOM Ready ──
document.addEventListener('DOMContentLoaded', () => {
    initNavbar();
    initMobileNav();
    initSmoothScroll();
    initRevealAnimations();
    initCounterAnimations();
    initFaqAccordion();
    initCategoryTabs();
    initContactForm();
    initBackToTop();
    initActiveNavLink();
});

/* ══════════════════════════════════════════
   NAVBAR
══════════════════════════════════════════ */
function initNavbar() {
    const navbar = document.getElementById('navbar');
    if (!navbar) return;

    let lastScrollY = 0;
    let ticking = false;

    const onScroll = () => {
        lastScrollY = window.scrollY;
        if (!ticking) {
            requestAnimationFrame(() => {
                navbar.classList.toggle('scrolled', lastScrollY > 40);
                ticking = false;
            });
            ticking = true;
        }
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
}

/* ══════════════════════════════════════════
   MOBILE NAV
══════════════════════════════════════════ */
function initMobileNav() {
    const toggle    = document.getElementById('navToggle');
    const mobileNav = document.getElementById('mobileNav');
    if (!toggle || !mobileNav) return;

    toggle.addEventListener('click', () => {
        const isOpen = mobileNav.classList.toggle('open');
        toggle.classList.toggle('open', isOpen);
        document.body.style.overflow = isOpen ? 'hidden' : '';
    });

    mobileNav.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            mobileNav.classList.remove('open');
            toggle.classList.remove('open');
            document.body.style.overflow = '';
        });
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && mobileNav.classList.contains('open')) {
            mobileNav.classList.remove('open');
            toggle.classList.remove('open');
            document.body.style.overflow = '';
        }
    });
}

/* ══════════════════════════════════════════
   SMOOTH SCROLL
══════════════════════════════════════════ */
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', e => {
            const target = document.querySelector(anchor.getAttribute('href'));
            if (!target) return;
            e.preventDefault();
            const offset = 80;
            const top = target.getBoundingClientRect().top + window.scrollY - offset;
            window.scrollTo({ top, behavior: 'smooth' });
        });
    });
}

/* ══════════════════════════════════════════
   AKTÍV NAV LINK (scroll alapján)
══════════════════════════════════════════ */
function initActiveNavLink() {
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.nav-link[href^="#"]');
    if (!sections.length || !navLinks.length) return;

    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                navLinks.forEach(link => {
                    link.classList.toggle(
                        'active',
                        link.getAttribute('href') === '#' + entry.target.id
                    );
                });
            }
        });
    }, { rootMargin: '-40% 0px -55% 0px' });

    sections.forEach(s => observer.observe(s));
}

/* ══════════════════════════════════════════
   REVEAL ANIMÁCIÓK
══════════════════════════════════════════ */
function initRevealAnimations() {
    const reveals = document.querySelectorAll('.reveal');
    if (!reveals.length) return;

    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12 });

    reveals.forEach(el => observer.observe(el));
}

/* ══════════════════════════════════════════
   COUNTER ANIMÁCIÓ
══════════════════════════════════════════ */
function initCounterAnimations() {
    const counters = document.querySelectorAll('[data-count]');
    if (!counters.length) return;

    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const el        = entry.target;
            const target    = parseInt(el.dataset.count);
            const suffix    = el.dataset.suffix || '';
            const duration  = 2000;
            const step      = 16;
            const steps     = duration / step;
            const increment = target / steps;
            let current     = 0;

            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                el.textContent = Math.floor(current).toLocaleString('hu-HU') + suffix;
            }, step);

            observer.unobserve(el);
        });
    }, { threshold: 0.5 });

    counters.forEach(el => observer.observe(el));
}

/* ══════════════════════════════════════════
   FAQ ACCORDION
══════════════════════════════════════════ */
function initFaqAccordion() {
    const items = document.querySelectorAll('.faq-item');
    if (!items.length) return;

    // Magasság cache-elése betöltéskor – egyszer olvasunk
    items.forEach(item => {
        const answer = item.querySelector('.faq-answer');
        if (!answer) return;
        answer.dataset.height  = answer.scrollHeight + 'px';
        answer.style.maxHeight = '0';
    });

    document.querySelectorAll('.faq-question').forEach(btn => {
        btn.addEventListener('click', () => {
            const item   = btn.closest('.faq-item');
            const answer = item.querySelector('.faq-answer');
            const isOpen = item.classList.contains('open');

            // Összes bezárása – csak írás, nincs layout olvasás
            items.forEach(i => {
                i.classList.remove('open');
                const a = i.querySelector('.faq-answer');
                if (a) a.style.maxHeight = '0';
            });

            // Ha nem volt nyitva, kinyitjuk cache-elt magassággal
            if (!isOpen) {
                item.classList.add('open');
                answer.style.maxHeight = answer.dataset.height;
            }
        });
    });
}

/* ══════════════════════════════════════════
   KATEGÓRIA TABS (rendszerek szűrése)
══════════════════════════════════════════ */
function initCategoryTabs() {
    const tabs  = document.querySelectorAll('.cat-tab');
    const cards = document.querySelectorAll('.sys-card');
    if (!tabs.length) return;

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            const cat = tab.dataset.cat;

            requestAnimationFrame(() => {
                cards.forEach(card => {
                    const show = cat === 'all' || card.dataset.cat === cat;
                    card.classList.toggle('cat-hidden', !show);
                    card.classList.toggle('cat-visible', show);
                });
            });
        });
    });
}

/* ══════════════════════════════════════════
   CONTACT FORM (AJAX)
══════════════════════════════════════════ */
function initContactForm() {
    const form = document.getElementById('contactForm');
    if (!form) return;

    form.addEventListener('submit', async e => {
        e.preventDefault();

        const btn     = form.querySelector('[type="submit"]');
        const origTxt = btn.innerHTML;

        btn.disabled  = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Küldés...';

        try {
            const res = await fetch(BASE_URL + '/api/contact.php', {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify(Object.fromEntries(new FormData(form))),
});
            const data = await res.json();

            if (data.success) {
                showToast('Üzenet sikeresen elküldve! Hamarosan felvesszük a kapcsolatot.', 'success');
                form.reset();
            } else {
                const errors = data.errors?.join('<br>') || 'Hiba történt!';
                showToast(errors, 'error');
            }
        } catch {
            showToast('Hálózati hiba! Kérjük próbáld újra.', 'error');
        } finally {
            btn.disabled  = false;
            btn.innerHTML = origTxt;
        }
    });
}

/* ══════════════════════════════════════════
   BACK TO TOP
══════════════════════════════════════════ */
function initBackToTop() {
    const btn = document.getElementById('backToTop');
    if (!btn) return;

    let ticking = false;
    let lastY   = 0;

    window.addEventListener('scroll', () => {
        lastY = window.scrollY;
        if (!ticking) {
            requestAnimationFrame(() => {
                btn.classList.toggle('show', lastY > 500);
                ticking = false;
            });
            ticking = true;
        }
    }, { passive: true });

    btn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

/* ══════════════════════════════════════════
   TOAST ÉRTESÍTŐ
══════════════════════════════════════════ */
function showToast(message, type = 'success') {
    document.querySelectorAll('.toast').forEach(t => t.remove());

    const icon  = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `<i class="fas ${icon}"></i> <span>${message}</span>`;
    document.body.appendChild(toast);

    requestAnimationFrame(() => {
        requestAnimationFrame(() => toast.classList.add('show'));
    });

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 400);
    }, 4500);
}

/* ══════════════════════════════════════════
   HERO TYPING EFFECT (opcionális)
══════════════════════════════════════════ */
function initTypingEffect(elementId, words, speed = 100) {
    const el = document.getElementById(elementId);
    if (!el) return;

    let wordIndex = 0;
    let charIndex = 0;
    let deleting  = false;

    const type = () => {
        const word    = words[wordIndex];
        const current = deleting
            ? word.substring(0, charIndex - 1)
            : word.substring(0, charIndex + 1);

        el.textContent = current;
        charIndex = deleting ? charIndex - 1 : charIndex + 1;

        let delay = deleting ? speed / 2 : speed;

        if (!deleting && charIndex === word.length) {
            delay    = 2000;
            deleting = true;
        } else if (deleting && charIndex === 0) {
            deleting  = false;
            wordIndex = (wordIndex + 1) % words.length;
            delay     = 400;
        }

        setTimeout(type, delay);
    };

    type();
}

/* ══════════════════════════════════════════
   PARALLAX (enyhe, teljesítmény-barát)
═══════════════════════════════════���══════ */
function initParallax() {
    const el = document.querySelector('.hero-bg-gradient');
    if (!el || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    let ticking = false;
    window.addEventListener('scroll', () => {
        if (!ticking) {
            requestAnimationFrame(() => {
                const y = window.scrollY * 0.3;
                el.style.transform = `translateY(${y}px)`;
                ticking = false;
            });
            ticking = true;
        }
    }, { passive: true });
}

// Parallax csak asztali gépen
if (window.matchMedia('(min-width: 1024px)').matches) {
    initParallax();
}

/* ══════════════════════════════════════════
   STICKY CTA – system.php
══════════════════════════════════════════ */
function initStickyCta() {
    const stickyCta = document.getElementById('stickyCta');
    if (!stickyCta) return;

    const hero = document.querySelector('.sys-hero');
    if (!hero) return;

    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            stickyCta.style.transform = entry.isIntersecting
                ? 'translateY(100%)'
                : 'translateY(0)';
        });
    }, { threshold: 0.2 });

    observer.observe(hero);
}
document.addEventListener('DOMContentLoaded', initStickyCta);
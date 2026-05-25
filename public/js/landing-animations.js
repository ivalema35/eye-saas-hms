/**
 * Eye HMS SaaS — Landing Page Animations
 * Uses GSAP 3.12.5 + ScrollTrigger
 *
 * Dependencies: gsap.min.js, ScrollTrigger.min.js (loaded in app.blade.php)
 */

(function () {
    'use strict';

    // Bail if GSAP is not loaded
    if (typeof gsap === 'undefined') {
        console.warn('[landing-animations] GSAP not loaded — skipping animations.');
        return;
    }

    // ──────────────────────────────────────────────
    // 1. Premium Glassmorphism Navbar Entrance
    // ──────────────────────────────────────────────
    gsap.from('.pub-nav-glass', {
        y: -100,
        opacity: 0,
        duration: 1,
        ease: 'power3.out',
    });

    // ──────────────────────────────────────────────
    // 2. Navbar Shadow on Scroll (GSAP-powered)
    // ──────────────────────────────────────────────
    const nav = document.querySelector('.pub-nav-glass');
    if (nav) {
        ScrollTrigger.create({
            start: 'top -10px',
            onToggle: (self) => {
                nav.classList.toggle('scrolled', self.isActive);
            },
        });
    }

    // ──────────────────────────────────────────────
    // 3. Scroll-Triggered Fade-In (replaces old IO)
    // ──────────────────────────────────────────────
    if (typeof ScrollTrigger !== 'undefined') {
        gsap.utils.toArray('.pub-animate, .pub-animate-fade').forEach((el) => {
            gsap.from(el, {
                scrollTrigger: {
                    trigger: el,
                    start: 'top 88%',
                    toggleActions: 'play none none none',
                },
                y: 36,
                opacity: 0,
                duration: 0.7,
                ease: 'power2.out',
            });
        });
    }

    // ──────────────────────────────────────────────
    // 4. Mobile Nav Toggle (same behaviour as before)
    // ──────────────────────────────────────────────
    (function () {
        const toggle = document.getElementById('mobileToggle');
        const links = document.getElementById('pubNavLinks');
        const icon = document.getElementById('mobileToggleIcon');
        if (!toggle) return;

        toggle.addEventListener('click', function () {
            const open = links.classList.toggle('open');
            icon.className = open ? 'fa-solid fa-xmark' : 'fa-solid fa-bars';
        });

        document.addEventListener('click', function (e) {
            if (!toggle.contains(e.target) && !links.contains(e.target)) {
                links.classList.remove('open');
                icon.className = 'fa-solid fa-bars';
            }
        });

        links.querySelectorAll('a').forEach(function (a) {
            a.addEventListener('click', function () {
                links.classList.remove('open');
                icon.className = 'fa-solid fa-bars';
            });
        });
    })();
})();

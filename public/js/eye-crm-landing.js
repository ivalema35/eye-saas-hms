/**
 * Eye HMS CRM landing — mast, mobile nav, nav active underline, reveal
 */
(function () {
    'use strict';

    var body = document.body;
    if (!body || !body.classList.contains('ecrm-body')) return;

    body.classList.add('js-on');

    var mast = document.getElementById('ecrmMast');
    var prog = document.getElementById('ecrmScrollProgress');
    var burger = document.getElementById('ecrmBurger');
    var nav = document.getElementById('ecrmNav');
    var icon = document.getElementById('ecrmBurgerIcon');
    var mastH = function () {
        return mast ? mast.offsetHeight : 76;
    };

    /* ——— Active nav underline (section / page) ——— */
    var navLinks = nav
        ? Array.prototype.slice.call(nav.querySelectorAll('a[data-nav]'))
        : [];
    var sectionOrder = ['modules', 'workflow', 'roles', 'faq'];

    function setNavActive(key) {
        if (!navLinks.length) return;
        navLinks.forEach(function (a) {
            var on = a.getAttribute('data-nav') === key;
            a.classList.toggle('is-on', on);
            if (on) {
                a.setAttribute('aria-current', 'true');
            } else {
                a.removeAttribute('aria-current');
            }
        });
    }

    function pathIsPricing() {
        var p = (window.location.pathname || '').toLowerCase();
        return p.indexOf('/pricing') !== -1;
    }

    function updateNavFromScroll() {
        if (!navLinks.length) return;

        if (pathIsPricing()) {
            setNavActive('pricing');
            return;
        }

        var hasSections = sectionOrder.some(function (id) {
            return document.getElementById(id);
        });
        if (!hasSections) {
            return;
        }

        var y = (window.scrollY || 0) + mastH() + 24;
        var active = 'home';

        for (var i = 0; i < sectionOrder.length; i++) {
            var el = document.getElementById(sectionOrder[i]);
            if (!el) continue;
            var top = el.getBoundingClientRect().top + (window.scrollY || 0);
            if (top <= y) {
                active = sectionOrder[i];
            }
        }

        if ((window.scrollY || 0) < 80) {
            active = 'home';
        }

        setNavActive(active);
    }

    function onScroll() {
        var y = window.scrollY || 0;
        if (mast) mast.classList.toggle('is-scrolled', y > 10);
        if (prog) {
            var h = document.documentElement.scrollHeight - window.innerHeight;
            prog.style.width = (h > 0 ? (y / h) * 100 : 0) + '%';
        }
        updateNavFromScroll();
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('hashchange', function () {
        var hash = (window.location.hash || '').replace('#', '');
        if (hash && sectionOrder.indexOf(hash) !== -1) {
            setNavActive(hash);
        } else {
            updateNavFromScroll();
        }
    });

    navLinks.forEach(function (a) {
        a.addEventListener('click', function () {
            var key = a.getAttribute('data-nav');
            if (key) setNavActive(key);
        });
    });

    if (pathIsPricing()) {
        setNavActive('pricing');
    } else {
        var initHash = (window.location.hash || '').replace('#', '');
        if (initHash && sectionOrder.indexOf(initHash) !== -1) {
            setNavActive(initHash);
        } else {
            updateNavFromScroll();
        }
    }

    if (burger && nav) {
        burger.addEventListener('click', function () {
            var open = nav.classList.toggle('is-open');
            burger.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (icon) icon.className = open ? 'fa-solid fa-xmark' : 'fa-solid fa-bars';
        });
        document.addEventListener('click', function (e) {
            if (!burger.contains(e.target) && !nav.contains(e.target)) {
                nav.classList.remove('is-open');
                burger.setAttribute('aria-expanded', 'false');
                if (icon) icon.className = 'fa-solid fa-bars';
            }
        });
        nav.querySelectorAll('a').forEach(function (a) {
            a.addEventListener('click', function () {
                nav.classList.remove('is-open');
                burger.setAttribute('aria-expanded', 'false');
                if (icon) icon.className = 'fa-solid fa-bars';
            });
        });
    }

    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var els = Array.prototype.slice.call(document.querySelectorAll('.ecrm-reveal'));

    if (reduce) {
        els.forEach(function (el) {
            el.classList.add('is-in');
        });
        return;
    }

    function revealEl(el) {
        el.classList.add('is-in');
    }

    if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(
            function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        revealEl(entry.target);
                        io.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.12, rootMargin: '0px 0px -8% 0px' }
        );

        els.forEach(function (el) {
            if (el.getBoundingClientRect().top < window.innerHeight * 0.9) {
                revealEl(el);
            } else {
                io.observe(el);
            }
        });
    } else {
        els.forEach(revealEl);
    }
})();

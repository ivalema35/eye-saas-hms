<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Eye HMS SaaS — Smart Hospital Management for Eye Clinics')</title>
    <meta name="description" content="@yield('meta_description', 'Complete cloud-based Hospital Management System for eye clinics \x26 ophthalmology hospitals in India. Patient management, OPD scheduling, surgery, billing \x26 more — all in one platform.')">
    <meta name="keywords" content="@yield('meta_keywords', 'eye hospital management software, ophthalmology HMS, eye clinic software India, hospital management system, OPD management, patient management system, cloud HMS India')">
    <meta name="robots" content="index, follow">

    {{-- Open Graph --}}
    <meta property="og:type"        content="website">
    <meta property="og:site_name"   content="Eye HMS SaaS">
    <meta property="og:title"       content="@yield('og_title', 'Eye HMS SaaS — Smart Hospital Management for Eye Clinics')">
    <meta property="og:description" content="@yield('og_description', 'Complete cloud-based Hospital Management System for eye clinics and ophthalmology hospitals in India.')">
    <meta property="og:url"         content="{{ url()->current() }}">

    {{-- Twitter Card --}}
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="@yield('og_title', 'Eye HMS SaaS — Smart Hospital Management for Eye Clinics')">
    <meta name="twitter:description" content="@yield('og_description', 'Complete cloud-based Hospital Management System for eye clinics and ophthalmology hospitals in India.')">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/design-system.css') }}">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
          integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uf0aJSUYjaQfXArGPgql7EiSBEeP4MNFxZJR2A=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />

    @stack('styles')
</head>
<body>

{{-- ============================================================
     Navigation
============================================================ --}}
<nav class="pub-nav" id="pubNav">
    <a href="{{ route('home') }}" class="pub-nav-brand">
        <div class="brand-icon-box"><i class="fa-solid fa-eye"></i></div>
        <span class="brand-text">Eye</span><span class="brand-accent">HMS</span>
    </a>

    <div class="pub-nav-links" id="pubNavLinks">
        <a href="{{ route('home') }}"
           class="nav-link-item {{ request()->routeIs('home') ? 'active' : '' }}">Features</a>
        <a href="{{ route('pricing') }}"
           class="nav-link-item {{ request()->routeIs('pricing') ? 'active' : '' }}">Pricing</a>
        <a href="{{ route('home') }}#faq"
           class="nav-link-item">FAQ</a>
    </div>

    <div class="pub-nav-cta">
        <a href="{{ route('login') }}" class="hms-btn hms-btn-sm pub-nav-login-btn">
            <i class="fa-solid fa-right-to-bracket"></i> Login
        </a>
        <a href="{{ route('register.show') }}" class="hms-btn hms-btn-primary hms-btn-sm">
            <i class="fa-solid fa-rocket"></i> Start Free Trial
        </a>
        <button class="pub-nav-mobile-toggle" id="mobileToggle" aria-label="Menu">
            <i class="fa-solid fa-bars" id="mobileToggleIcon"></i>
        </button>
    </div>
</nav>

{{-- ============================================================
     Page Content
============================================================ --}}
@yield('content')

{{-- ============================================================
     Footer
============================================================ --}}
<footer class="pub-footer">
    <div class="pub-footer-inner">
        <div class="pub-footer-grid">
            {{-- Brand column --}}
            <div>
                <a href="{{ route('home') }}" class="pub-footer-brand">
                    <div class="fb-icon"><i class="fa-solid fa-eye"></i></div>
                    <span>Eye<span style="color:var(--hms-teal)">HMS</span></span>
                </a>
                <p class="pub-footer-desc">
                    A complete cloud-based Hospital Management System built specifically for ophthalmology clinics. Manage patients, appointments, OT, billing and more — all in one place.
                </p>
                <div class="pub-footer-social">
                    <a href="#" aria-label="Twitter"><i class="fa-brands fa-twitter"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="fa-brands fa-linkedin"></i></a>
                    <a href="#" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
                </div>
            </div>

            {{-- Product --}}
            <div class="pub-footer-col">
                <h4>Product</h4>
                <ul class="pub-footer-links">
                    <li><a href="{{ route('home') }}#features">Features</a></li>
                    <li><a href="{{ route('pricing') }}">Pricing</a></li>
                    <li><a href="{{ route('register.show') }}">Start Free Trial</a></li>
                    <li><a href="{{ route('home') }}#faq">FAQ</a></li>
                </ul>
            </div>

            {{-- Company --}}
            <div class="pub-footer-col">
                <h4>Company</h4>
                <ul class="pub-footer-links">
                    <li><a href="#">About Us</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                    <li><a href="#">Contact</a></li>
                </ul>
            </div>

            {{-- Contact --}}
            <div class="pub-footer-col">
                <h4>Contact</h4>
                <ul class="pub-footer-links">
                    <li><a href="mailto:support@eyehms.com"><i class="fa-solid fa-envelope" style="width:14px"></i> support@eyehms.com</a></li>
                    <li><a href="tel:+918000000000"><i class="fa-solid fa-phone" style="width:14px"></i> +91 80000 00000</a></li>
                    <li style="color:rgba(255,255,255,.5);font-size:.875rem">
                        <i class="fa-solid fa-clock" style="width:14px"></i> Mon – Sat, 9am – 6pm IST
                    </li>
                </ul>
            </div>
        </div>

        <div class="pub-footer-bottom">
            <p class="pub-footer-copy">&copy; {{ date('Y') }} Eye HMS SaaS. All rights reserved.</p>
            <div class="pub-footer-legal">
                <a href="#">Privacy</a>
                <a href="#">Terms</a>
                <a href="{{ route('superadmin.login') }}">Admin</a>
            </div>
        </div>
    </div>
</footer>

<script>
    // Sticky nav shadow on scroll
    (function () {
        const nav = document.getElementById('pubNav');
        window.addEventListener('scroll', function () {
            nav.classList.toggle('scrolled', window.scrollY > 10);
        }, { passive: true });
    }());

    // Mobile nav toggle
    (function () {
        const toggle = document.getElementById('mobileToggle');
        const links = document.getElementById('pubNavLinks');
        const icon = document.getElementById('mobileToggleIcon');
        if (!toggle) { return; }
        toggle.addEventListener('click', function () {
            const open = links.classList.toggle('open');
            icon.className = open ? 'fa-solid fa-xmark' : 'fa-solid fa-bars';
        });
        // Close on outside click
        document.addEventListener('click', function (e) {
            if (!toggle.contains(e.target) && !links.contains(e.target)) {
                links.classList.remove('open');
                icon.className = 'fa-solid fa-bars';
            }
        });
        // Close when a nav link is clicked (mobile UX)
        links.querySelectorAll('a').forEach(function (a) {
            a.addEventListener('click', function () {
                links.classList.remove('open');
                icon.className = 'fa-solid fa-bars';
            });
        });
    }());

    // Scroll-triggered fade-in (IntersectionObserver)
    (function () {
        if (!('IntersectionObserver' in window)) {
            // Fallback: just show everything
            document.querySelectorAll('.pub-animate, .pub-animate-fade').forEach(function (el) {
                el.classList.add('is-visible');
            });
            return;
        }
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

        document.querySelectorAll('.pub-animate, .pub-animate-fade').forEach(function (el) {
            observer.observe(el);
        });
    }());
</script>

@stack('scripts')
</body>
</html>

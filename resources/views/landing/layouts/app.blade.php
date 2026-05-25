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
     Navigation — Glassmorphism Premium Navbar
============================================================ --}}
@include('landing.components.navbar')

{{-- ============================================================
     Page Content
============================================================ --}}
<main class="page-content-wrap" id="pageContent">
@yield('content')
</main>

{{-- ============================================================
     Footer
============================================================ --}}
<footer class="pub-footer">
    <div class="footer-deco" aria-hidden="true">
        <span class="footer-deco-blob footer-deco-blob-1"></span>
        <span class="footer-deco-blob footer-deco-blob-2"></span>
        <span class="footer-deco-cross" aria-hidden="true">
            <i class="fa-solid fa-plus"></i>
        </span>
        <span class="footer-deco-eye" aria-hidden="true">
            <i class="fa-solid fa-eye"></i>
        </span>
    </div>
    <div class="pub-footer-inner">
        <div class="pub-footer-grid">
            {{-- Brand column --}}
            <div class="footer-animate">
                <a href="{{ route('home') }}" class="pub-footer-brand">
                    <div class="fb-icon"><i class="fa-solid fa-eye"></i></div>
                    <span>Eye<span style="color:var(--hms-teal)">HMS</span></span>
                </a>
                <p class="pub-footer-desc">
                    A complete cloud-based Hospital Management System built specifically for ophthalmology clinics. Manage patients, appointments, OT, billing and more — all in one place.
                </p>
                <div class="pub-footer-social">
                    <a href="#" aria-label="Twitter" title="Twitter">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true">
                            <path d="M22 5.92c-.66.29-1.37.49-2.12.58.76-.45 1.34-1.16 1.61-2.01-.71.42-1.5.72-2.34.88A3.66 3.66 0 0 0 12.4 8.5c0 .29.03.58.1.86C8.07 9.16 4.2 7.24 1.67 4.05c-.32.56-.5 1.2-.5 1.88 0 1.3.66 2.45 1.67 3.12-.61-.02-1.18-.19-1.68-.46v.05c0 1.8 1.25 3.3 2.9 3.64-.48.13-.98.18-1.49.07.42 1.3 1.64 2.24 3.08 2.27A7.36 7.36 0 0 1 2 19.54 10.5 10.5 0 0 0 7.29 21c6.88 0 10.64-5.9 10.64-11.02v-.5c.73-.53 1.34-1.2 1.83-1.96-.67.3-1.38.5-2.11.59.76-.47 1.34-1.25 1.61-2.08z"/>
                        </svg>
                    </a>
                    <a href="#" aria-label="LinkedIn" title="LinkedIn">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true">
                            <rect x="2" y="3" width="20" height="18" rx="2" ry="2" fill="none" stroke="currentColor" stroke-width="0"/>
                            <path d="M6.94 9H9v8H6.94zM8 5.75a1.17 1.17 0 1 1 0 2.34 1.17 1.17 0 0 1 0-2.34zM10.5 9h2.5v1.1c.35-.66 1.2-1.1 2.1-1.1 2.24 0 2.9 1.5 2.9 3.45V17h-2.9v-3.1c0-.74 0-1.7-1.04-1.7-1.04 0-1.2.82-1.2 1.68V17H10.5z"/>
                        </svg>
                    </a>
                    <a href="#" aria-label="YouTube" title="YouTube">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true">
                            <rect x="2" y="5" width="20" height="14" rx="4" ry="4" fill="none" stroke="currentColor" stroke-width="0"/>
                            <path d="M10 8.5l6 3.5-6 3.5z"/>
                        </svg>
                    </a>
                </div>
            </div>

            {{-- Product --}}
            <div class="pub-footer-col footer-animate">
                <h4>Product</h4>
                <ul class="pub-footer-links">
                    <li><a href="{{ route('home') }}#features">Features</a></li>
                    <li><a href="{{ route('pricing') }}">Pricing</a></li>
                    <li><a href="{{ route('register.show') }}">Start Free Trial</a></li>
                    <li><a href="{{ route('home') }}#faq">FAQ</a></li>
                </ul>
            </div>

            {{-- Company --}}
            <div class="pub-footer-col footer-animate">
                <h4>Company</h4>
                <ul class="pub-footer-links">
                    <li><a href="#">About Us</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                    <li><a href="#">Contact</a></li>
                </ul>
            </div>

            {{-- Contact --}}
            <div class="pub-footer-col footer-animate">
                <h4>Contact</h4>
                <ul class="pub-footer-links">
                    <li><a href="mailto:support@eyehms.com"><i class="fa-solid fa-envelope" style="width:14px"></i> support@eyehms.com</a></li>
                    <li><a href="tel:+918000000000"><i class="fa-solid fa-phone" style="width:14px"></i> +91 80000 00000</a></li>
                    <li style="color:rgba(255,255,255,.5);font-size:.875rem">
                        <i class="fa-solid fa-clock footer-clock-icon" style="width:14px"></i> Mon – Sat, 9am – 6pm IST
                    </li>
                </ul>
            </div>
        </div>

        <div class="pub-footer-bottom footer-animate">
            <p class="pub-footer-copy">&copy; {{ date('Y') }} Eye HMS SaaS. All rights reserved.</p>
            <div class="pub-footer-legal">
                <a href="#">Privacy</a>
                <a href="#">Terms</a>
                <a href="{{ route('superadmin.login') }}">Admin</a>
            </div>
            
        </div>
    </div>
</footer>

{{-- GSAP Core + ScrollTrigger --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>

{{-- Landing Page Animations --}}
<script src="{{ asset('js/landing-animations.js') }}"></script>

@stack('scripts')
<script>
(function () {
    var prog = document.getElementById('navScrollProgress');
    if (prog) {
        window.addEventListener('scroll', function () {
            var scrollTop = window.scrollY;
            var docHeight = document.documentElement.scrollHeight - window.innerHeight;
            var pct = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
            prog.style.width = pct + '%';
        }, { passive: true });
    }

    var scrollTopBtn = document.getElementById('footerScrollTop');
    if (scrollTopBtn) {
        scrollTopBtn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    if ('IntersectionObserver' in window) {
        document.querySelectorAll('.footer-animate').forEach(function (el) {
            var obs = new IntersectionObserver(function (entries) {
                entries.forEach(function (e) {
                    if (e.isIntersecting) {
                        e.target.classList.add('is-visible');
                    }
                });
            }, { threshold: 0.1 });
            obs.observe(el);
        });
    } else {
        document.querySelectorAll('.footer-animate').forEach(function (el) {
            el.classList.add('is-visible');
        });
    }
}());
</script>
</body>
</html>

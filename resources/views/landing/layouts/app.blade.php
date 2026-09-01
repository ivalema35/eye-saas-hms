<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'EYENOSIS — Eye Hospital CRM Platform')</title>
    <meta name="description"
        content="@yield('meta_description', 'Multi-tenant Eye Hospital Management SaaS CRM: patients, OPD, examinations, OT, billing, roles and reports — one cloud platform.')">
    <meta name="keywords"
        content="@yield('meta_keywords', 'eye hospital CRM, ophthalmology HMS, eye clinic software, OT management software, multi-tenant hospital SaaS')">
    <meta name="robots" content="index, follow">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="EYENOSIS">
    <meta property="og:title" content="@yield('og_title', 'EYENOSIS — Eye Hospital CRM Platform')">
    <meta property="og:description"
        content="@yield('og_description', 'Complete multi-tenant CRM / EYENOSIS for eye hospitals and clinics.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('og_title', 'EYENOSIS — Eye Hospital CRM Platform')">
    <meta name="twitter:description"
        content="@yield('og_description', 'Complete multi-tenant CRM / EYENOSIS for eye hospitals and clinics.')">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/design-system.css') }}">
    <link rel="stylesheet" href="{{ asset('css/eye-crm-landing.css') }}">
    @if (request()->routeIs('register.*'))
        <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
    @endif
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="icon" type="image/png" href="{{ platform_favicon_url() }}">
    @stack('styles')
</head>

<body class="ecrm-body">

    @include('landing.components.navbar')

    <main class="ecrm-main" id="pageContent">
        @yield('content')
    </main>

    @include('landing.components.footer')

    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script src="{{ asset('js/eye-crm-landing.js') }}"></script>
    <script src="{{ asset('js/intl-phone-input.js') }}"></script>
    @stack('scripts')
</body>

</html>
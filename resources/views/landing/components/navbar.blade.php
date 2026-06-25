<nav class="pub-nav-glass" id="pubNavGlass">
    <span class="nav-scroll-progress" id="navScrollProgress" aria-hidden="true"></span>
    <span class="nav-heartbeat-line" aria-hidden="true"></span>
    {{-- Brand / Logo --}}
    <a href="{{ route('home') }}" class="pub-nav-brand">
        <div class="brand-icon-box">
            <img src="{{ platform_logo_url() }}" alt="Eye HMS" class="brand-logo-img">
        </div>
        <span class="brand-text">Eye</span><span class="brand-accent">HMS</span>
    </a>

    {{-- Center Nav Links --}}
    <div class="pub-nav-links" id="pubNavLinks">
        <a href="{{ route('home') }}"
           class="nav-link-item {{ request()->routeIs('home') ? 'active' : '' }}">Features</a>
        <a href="{{ route('pricing') }}"
           class="nav-link-item {{ request()->routeIs('pricing') ? 'active' : '' }}">Pricing</a>
        <a href="{{ route('home') }}#faq"
           class="nav-link-item">FAQ</a>
    </div>

    {{-- Right CTA Buttons --}}
    <div class="pub-nav-cta">
        <a href="{{ route('login') }}" class="glass-login-link">
            <i class="fa-solid fa-right-to-bracket"></i> Login
        </a>
        <a href="{{ route('register.show') }}" class="glass-trial-btn">
            <i class="fa-solid fa-rocket"></i> Start Free Trial
        </a>
        
    </div>
</nav>

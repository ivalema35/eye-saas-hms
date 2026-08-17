<header class="ecrm-mast" id="ecrmMast">
    <div class="ecrm-mast-progress" id="ecrmScrollProgress" aria-hidden="true"></div>
    <div class="ecrm-mast-inner">
        <a href="{{ route('home') }}" class="ecrm-brand">
            <span class="ecrm-brand-mark">
                <img src="{{ platform_logo_url() }}" alt="Eye Nosis" class="brand-logo-img">
            </span>
            <span class="ecrm-brand-text">
                <small>Hospital CRM SaaS</small>
            </span>
        </a>

        <div class="ecrm-mast-right">
            <nav class="ecrm-nav" id="ecrmNav" aria-label="Primary">
                <a href="{{ route('home') }}" data-nav="home"
                    class="{{ request()->routeIs('home') ? 'is-on' : '' }}">Home</a>
                <a href="{{ route('home') }}#modules" data-nav="modules">Modules</a>
                <a href="{{ route('home') }}#workflow" data-nav="workflow">Workflow</a>
                <a href="{{ route('home') }}#roles" data-nav="roles">Roles</a>
                <a href="{{ route('pricing') }}" data-nav="pricing"
                    class="{{ request()->routeIs('pricing') ? 'is-on' : '' }}">Pricing</a>
                <a href="{{ route('home') }}#faq" data-nav="faq">FAQ</a>
                <a href="{{ route('login') }}" class="ecrm-nav-mobile-only">Login</a>
                <a href="{{ route('register.show') }}" class="ecrm-nav-mobile-only">Free trial</a>
            </nav>

            <div class="ecrm-mast-actions">
                <div class="ecrm-cta-pill" role="group" aria-label="Account actions">
                    <a href="{{ route('login') }}" class="ecrm-cta-pill-item">Login</a>
                    <span class="ecrm-cta-pill-divider" aria-hidden="true"></span>
                    <a href="{{ route('register.show') }}" class="ecrm-cta-pill-item">Free trial</a>
                </div>
                <button type="button" class="ecrm-burger" id="ecrmBurger" aria-label="Open menu" aria-expanded="false"
                    aria-controls="ecrmNav">
                    <i class="fa-solid fa-bars" id="ecrmBurgerIcon"></i>
                </button>
            </div>
        </div>
    </div>
</header>
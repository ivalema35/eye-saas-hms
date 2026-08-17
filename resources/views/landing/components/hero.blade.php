<section class="ecrm-hero" aria-label="Hero">
    <div class="ecrm-hero-bg" aria-hidden="true">
        <span class="ecrm-orb ecrm-orb-a"></span>
        <span class="ecrm-orb ecrm-orb-b"></span>
        <span class="ecrm-orb ecrm-orb-c"></span>
    </div>
    <div class="ecrm-container ecrm-hero-grid">
        <div class="ecrm-hero-copy ecrm-reveal">
            <p class="ecrm-hero-brand">EYE<em>NOSIS</em></p>
            <h1>
                One SaaS CRM for your
                <span>entire eye hospital</span>
            </h1>
            <p class="ecrm-hero-lead">
                Register patients, run OPD exams, counsel packages, collect OT payments,
                manage ward &amp; surgery, discharge invoices and reports — role-aware desks,
                hospital isolation, cloud access.
            </p>
            <div class="ecrm-hero-ctas">
                <a href="{{ route('register.show') }}" class="ecrm-btn ecrm-btn-primary ecrm-btn-lg">
                    Start 14-Day Free Trial
                </a>
                <a href="{{ route('home') }}#modules" class="ecrm-btn ecrm-btn-soft ecrm-btn-lg">
                    Explore modules
                </a>
            </div>
            <ul class="ecrm-hero-stats">
                <li>
                    <strong>No card</strong>
                    <span>Needed for free trial</span>
                </li>
                <li>
                    <strong>{{ platform_currency_symbol() }}0</strong>
                    <span>Setup fee</span>
                </li>
                <li>
                    <strong>Roles</strong>
                    <span>Hospital workspace</span>
                </li>
            </ul>
        </div>

        <div class="ecrm-hero-stage ecrm-reveal">
            <figure class="ecrm-hero-shot">
                <img src="{{ asset('images/landing/hero-crm-visual.png') }}?v=5"
                    alt="EYE NOSIS CRM on desktop and mobile — hospital dashboard and patient registration" width="1200"
                    height="900" loading="eager" decoding="async">
            </figure>
        </div>
    </div>
</section>
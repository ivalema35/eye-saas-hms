<section class="ecrm-hero" aria-label="Hero">
    <div class="ecrm-hero-bg" aria-hidden="true">
        <span class="ecrm-orb ecrm-orb-a"></span>
        <span class="ecrm-orb ecrm-orb-b"></span>
        <span class="ecrm-orb ecrm-orb-c"></span>
    </div>
    <div class="ecrm-container ecrm-hero-grid">
        <div class="ecrm-hero-copy ecrm-reveal">
            <p class="ecrm-hero-eyebrow">The Complete Digital Platform for Ophthalmology</p>
            <h1>Run Your Eye Hospital. Simplify Every Workflow.</h1>
            <p class="ecrm-hero-lead">
                EYENOSIS.com connects your entire ophthalmology operation — from reception and
                patient registration to consultation, counselling, admission, ward, OT, surgery,
                billing, discharge and follow-up — in one powerful platform with access across
                Web, Android, iOS and Tablet.
            </p>
            <div class="ecrm-hero-ctas">
                <a href="{{ route('register.show') }}" class="ecrm-btn ecrm-btn-primary ecrm-btn-lg">
                    Start {{ $platformTrialDays }}-Day Free Trial
                </a>
                <a href="{{ route('home') }}#modules" class="ecrm-btn ecrm-btn-soft ecrm-btn-lg">
                    Explore modules
                </a>
            </div>
            <p class="ecrm-hero-apps">
                <span>Web App</span>
                <span>Android App (Mobile/Tablet)</span>
                <span>iOS App (Mobile/Tablet)</span>
            </p>
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
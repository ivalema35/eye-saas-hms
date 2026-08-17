<footer class="ecrm-footer">
    <div class="ecrm-container">
        <div class="ecrm-footer-shell">
            <div class="ecrm-footer-grid">
                <div class="ecrm-footer-brand">
                    <a href="{{ route('home') }}" class="ecrm-brand">
                        <span class="ecrm-brand-mark">
                            <img src="{{ platform_logo_url() }}" alt="Eye Nosis" class="brand-logo-img">
                        </span>
                    </a>
                    <p>Multi-tenant cloud CRM for ophthalmology clinics — patients, OPD, exams, OT, billing and
                        role-based hospital desks in one platform.</p>
                    <a href="{{ route('register.show') }}" class="ecrm-footer-cta">
                        Start free trial <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
                <div class="ecrm-footer-col">
                    <h4>Product</h4>
                    <ul>
                        <li><a href="{{ route('home') }}#modules">Modules</a></li>
                        <li><a href="{{ route('home') }}#workflow">Workflow</a></li>
                        <li><a href="{{ route('pricing') }}">Pricing</a></li>
                        <li><a href="{{ route('register.show') }}">Free trial</a></li>
                    </ul>
                </div>
                <div class="ecrm-footer-col">
                    <h4>Access</h4>
                    <ul>
                        <li><a href="{{ route('login') }}">Hospital login</a></li>
                        <li><a href="{{ route('register.show') }}">Register hospital</a></li>
                        <li><a href="{{ route('superadmin.login') }}">Platform admin</a></li>
                        <li><a href="{{ route('home') }}#faq">FAQ</a></li>
                    </ul>
                </div>
                <div class="ecrm-footer-col">
                    <h4>CRM desks</h4>
                    <ul>
                        <li><a href="{{ route('home') }}#roles">Reception &amp; doctor</a></li>
                        <li><a href="{{ route('home') }}#roles">Counsellor &amp; OT</a></li>
                        <li><a href="{{ route('home') }}#roles">Accountant &amp; ward</a></li>
                        <li><a href="{{ route('home') }}#roles">Discharge &amp; reports</a></li>
                    </ul>
                </div>
            </div>
            <div class="ecrm-footer-bottom">
                <p>&copy; {{ date('Y') }} EYENOSIS. All rights reserved.</p>
                <div class="ecrm-footer-links">
                    <a href="{{ route('home') }}">Home</a>
                    <a href="{{ route('pricing') }}">Pricing</a>
                    <a href="{{ route('superadmin.login') }}">Login</a>
                </div>
            </div>
        </div>
    </div>
</footer>
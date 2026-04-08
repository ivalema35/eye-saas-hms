@extends('landing.layouts.app')

@section('title', 'Eye HMS SaaS — Smart Hospital Management for Eye Clinics in India')
@section('meta_description', 'Cloud-based Eye Hospital Management System for ophthalmology clinics in India. Manage patients, OPD appointments, surgery scheduling, billing & reports — all in one platform. Start free 14-day trial.')
@section('meta_keywords', 'eye hospital management software India, ophthalmology HMS, eye clinic software, hospital management system, OPD scheduling, patient management ophthalmology, cloud HMS India, eye care software')
@section('og_title', 'Eye HMS SaaS — Smart Hospital Management for Eye Clinics')
@section('og_description', 'Complete cloud-based Hospital Management System for eye clinics & ophthalmology hospitals in India. Patient management, OPD, surgery, billing — all in one.')

@section('content')

{{-- ============================================================
     Hero Section
============================================================ --}}
<section class="pub-hero" aria-label="Hero">
    <div class="pub-hero-inner">

        {{-- Left: Content --}}
        <div class="pub-hero-content">
            <div class="pub-hero-badge">
                <span class="badge-pulse"></span>
                &#x1F1EE;&#x1F1F3; Trusted by Indian Eye Hospitals
            </div>
            <h1>Run Your Eye Hospital<br><span class="h1-grad">Smarter &amp; Faster</span></h1>
            <p class="pub-hero-desc">
                Complete cloud-based Hospital Management System built specifically for ophthalmology clinics.
                Patient records, OPD scheduling, surgery, billing &amp; analytics — all in one platform.
            </p>
            <div class="hero-cta-row">
                <a href="{{ route('register.show') }}" class="btn-hero-primary">
                    <i class="fa-solid fa-rocket"></i> Start 14-Day Free Trial
                </a>
                <a href="#features" class="btn-hero-outline">
                    <i class="fa-solid fa-play-circle"></i> See Features
                </a>
            </div>
            <div class="hero-trust-row">
                <span class="hero-trust-item"><i class="fa-solid fa-check-circle"></i> No credit card</span>
                <span class="hero-trust-item"><i class="fa-solid fa-check-circle"></i> Free 14-day trial</span>
                <span class="hero-trust-item"><i class="fa-solid fa-check-circle"></i> &#x20B9;0 setup cost</span>
                <span class="hero-trust-item"><i class="fa-solid fa-check-circle"></i> Cancel anytime</span>
            </div>
        </div>

        {{-- Right: Dashboard Mockup --}}
        <div class="pub-hero-mockup">
            <div class="mockup-glow"></div>
            <div class="mockup-browser" role="img" aria-label="Eye HMS dashboard preview">
                {{-- Tab bar --}}
                <div class="mockup-chrome">
                    <div class="mockup-dots">
                        <span class="md-red"></span>
                        <span class="md-amber"></span>
                        <span class="md-green"></span>
                    </div>
                    <div class="mockup-urlbar">app.eyehms.com/dashboard</div>
                </div>
                {{-- App layout --}}
                <div class="mockup-app">
                    {{-- Sidebar --}}
                    <div class="mockup-sidebar">
                        <div class="mock-nav-item active">
                            <div class="mock-nav-dot"></div><div class="mock-nav-bar"></div>
                        </div>
                        <div class="mock-nav-item"><div class="mock-nav-dot"></div><div class="mock-nav-bar"></div></div>
                        <div class="mock-nav-item"><div class="mock-nav-dot"></div><div class="mock-nav-bar"></div></div>
                        <div class="mock-nav-item"><div class="mock-nav-dot"></div><div class="mock-nav-bar"></div></div>
                        <div class="mock-nav-item"><div class="mock-nav-dot"></div><div class="mock-nav-bar"></div></div>
                        <div class="mock-nav-item"><div class="mock-nav-dot"></div><div class="mock-nav-bar"></div></div>
                    </div>
                    {{-- Content --}}
                    <div class="mockup-content">
                        <div class="mock-page-title"></div>
                        {{-- Stat cards --}}
                        <div class="mock-stats-row">
                            <div class="mock-stat-card">
                                <div class="mock-stat-top"></div>
                                <div class="mock-stat-val"></div>
                                <div class="mock-stat-lbl"></div>
                            </div>
                            <div class="mock-stat-card">
                                <div class="mock-stat-top g"></div>
                                <div class="mock-stat-val g"></div>
                                <div class="mock-stat-lbl"></div>
                            </div>
                            <div class="mock-stat-card">
                                <div class="mock-stat-top o"></div>
                                <div class="mock-stat-val o"></div>
                                <div class="mock-stat-lbl"></div>
                            </div>
                            <div class="mock-stat-card">
                                <div class="mock-stat-top t"></div>
                                <div class="mock-stat-val t"></div>
                                <div class="mock-stat-lbl"></div>
                            </div>
                        </div>
                        {{-- Chart --}}
                        <div class="mock-chart-card">
                            <div class="mock-chart-header"></div>
                            <div class="mock-bars">
                                <div class="mock-bar" style="height:35%;background:#D6EAF8"></div>
                                <div class="mock-bar" style="height:60%;background:#2980B9"></div>
                                <div class="mock-bar" style="height:45%;background:#D6EAF8"></div>
                                <div class="mock-bar" style="height:80%;background:#1B4F72"></div>
                                <div class="mock-bar" style="height:55%;background:#2980B9"></div>
                                <div class="mock-bar" style="height:70%;background:#1ABC9C"></div>
                                <div class="mock-bar" style="height:90%;background:#27AE60"></div>
                            </div>
                        </div>
                        {{-- Table --}}
                        <div class="mock-table-card">
                            <div class="mock-table-head">
                                <div class="mock-th"></div><div class="mock-th"></div>
                                <div class="mock-th"></div><div class="mock-th"></div>
                            </div>
                            <div class="mock-table-row"><div class="mock-td"></div><div class="mock-td"></div><div class="mock-td"></div><div class="mock-td"></div></div>
                            <div class="mock-table-row"><div class="mock-td"></div><div class="mock-td"></div><div class="mock-td"></div><div class="mock-td"></div></div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Floating badges --}}
            <div class="mockup-float mockup-float-tl">
                <i class="fa-solid fa-circle-check"></i> 28 patients today
            </div>
            <div class="mockup-float mockup-float-br">
                <i class="fa-solid fa-arrow-trend-up"></i> Revenue +23% this month
            </div>
        </div>

    </div>
</section>

{{-- ============================================================
     Trust Strip
============================================================ --}}
<div class="pub-trust-strip" aria-label="Key statistics">
    <div class="pub-trust-inner pub-animate-fade">
        <div class="trust-stat">
            <div class="trust-stat-num">500<span style="color:var(--hms-teal)">+</span></div>
            <div class="trust-stat-label">Hospitals Registered</div>
        </div>
        <div class="trust-stat">
            <div class="trust-stat-num">14</div>
            <div class="trust-stat-label">Day Free Trial</div>
        </div>
        <div class="trust-stat">
            <div class="trust-stat-num">99.9<span style="color:var(--hms-success)">%</span></div>
            <div class="trust-stat-label">Uptime Guaranteed</div>
        </div>
        <div class="trust-stat">
            <div class="trust-stat-num">&#x20B9;0</div>
            <div class="trust-stat-label">Setup / Installation</div>
        </div>
        <div class="trust-stat">
            <div class="trust-stat-num">24/7</div>
            <div class="trust-stat-label">Cloud Access</div>
        </div>
    </div>
</div>

{{-- ============================================================
     Features Section
============================================================ --}}
<section class="pub-section pub-section-grey" id="features" aria-labelledby="features-heading">
    <div class="pub-section-inner">
        <div class="pub-section-header">
            <span class="section-eyebrow"><i class="fa-solid fa-star"></i> Features</span>
            <h2 id="features-heading">Everything Your Eye Clinic Needs</h2>
            <p>All modules built specifically for ophthalmology practice management — no extra configuration required.</p>
        </div>
        <div class="features-grid pub-animate-group">
            <article class="feature-card pub-animate">
                <div class="feature-icon-box fi-blue"><i class="fa-solid fa-user-injured"></i></div>
                <h3>Patient Management</h3>
                <p>Complete patient records with eye-specific history, medical records, visit tracking, and family linkage for multi-patient households.</p>
            </article>
            <article class="feature-card pub-animate">
                <div class="feature-icon-box fi-teal"><i class="fa-solid fa-calendar-check"></i></div>
                <h3>OPD Appointment Scheduling</h3>
                <p>Smart appointment booking with doctor availability, configurable slot durations, SMS/email reminders, and walk-in management.</p>
            </article>
            <article class="feature-card pub-animate">
                <div class="feature-icon-box fi-orange"><i class="fa-solid fa-eye"></i></div>
                <h3>Eye Examination Module</h3>
                <p>Comprehensive eye exam forms — visual acuity, refraction, slit lamp, fundus, IOP, corneal mapping & diagnosis with custom fields.</p>
            </article>
            <article class="feature-card pub-animate">
                <div class="feature-icon-box fi-green"><i class="fa-solid fa-scalpel"></i></div>
                <h3>OT &amp; Surgery Module</h3>
                <p>Operation Theatre scheduling, surgical notes, implant tracking, post-op care plans, and OT utilization &amp; efficiency reports.</p>
            </article>
            <article class="feature-card pub-animate">
                <div class="feature-icon-box fi-purple"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                <h3>GST Billing &amp; Invoicing</h3>
                <p>GST-compliant invoicing, multi-mode payment tracking, insurance claims, automated receipt generation &amp; revenue reports.</p>
            </article>
            <article class="feature-card pub-animate">
                <div class="feature-icon-box fi-red"><i class="fa-solid fa-chart-line"></i></div>
                <h3>Analytics &amp; Reports</h3>
                <p>Real-time dashboards, patient statistics, doctor performance, revenue trends, and exportable reports for informed decisions.</p>
            </article>
        </div>
    </div>
</section>

{{-- ============================================================
     How It Works
============================================================ --}}
<section class="pub-section" aria-labelledby="steps-heading">
    <div class="pub-section-inner">
        <div class="pub-section-header">
            <span class="section-eyebrow"><i class="fa-solid fa-list-ol"></i> How It Works</span>
            <h2 id="steps-heading">Get Started in 3 Simple Steps</h2>
            <p>From registration to running your clinic — setup takes less than 5 minutes.</p>
        </div>
        <div class="steps-grid pub-animate-group">
            <div class="step-item pub-animate">
                <div class="step-num">1</div>
                <h3>Register Your Hospital</h3>
                <p>Fill a simple form with your hospital name, contact info and choose a plan. Your dedicated subdomain is ready instantly.</p>
            </div>
            <div class="step-item pub-animate">
                <div class="step-num">2</div>
                <h3>Configure Your Clinic</h3>
                <p>Add doctors, configure OPD timings, billing items and departments. Pre-built templates make setup fast.</p>
            </div>
            <div class="step-item pub-animate">
                <div class="step-num">3</div>
                <h3>Start Managing Patients</h3>
                <p>Register patients, book appointments, conduct exams and generate bills. Everything in one place, from day one.</p>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
     Testimonials
============================================================ --}}
<section class="pub-section pub-section-grey" aria-labelledby="testimonials-heading">
    <div class="pub-section-inner">
        <div class="pub-section-header">
            <span class="section-eyebrow"><i class="fa-solid fa-quote-left"></i> Testimonials</span>
            <h2 id="testimonials-heading">Loved by Eye Care Professionals</h2>
            <p>What our customers say about Eye HMS SaaS</p>
        </div>
        <div class="testimonials-grid pub-animate-group">
            <article class="testimonial-card pub-animate">
                <div class="t-quote-icon">&ldquo;</div>
                <div class="t-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                <p class="t-text">Eye HMS transformed how we manage our clinic. Patient records are instant, appointment scheduling is smooth, and billing is error-free. Highly recommended!</p>
                <div class="t-author">
                    <div class="t-avatar">DR</div>
                    <div>
                        <div class="t-name">Dr. Ravi Sharma</div>
                        <div class="t-role">Ophthalmologist, Sharma Eye Centre, Mumbai</div>
                    </div>
                </div>
            </article>
            <article class="testimonial-card pub-animate">
                <div class="t-quote-icon">&ldquo;</div>
                <div class="t-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                <p class="t-text">We switched from paper records to Eye HMS and it was the best decision. The OT module is especially impressive — everything tracked and organised perfectly.</p>
                <div class="t-author">
                    <div class="t-avatar">AP</div>
                    <div>
                        <div class="t-name">Dr. Anita Patel</div>
                        <div class="t-role">Medical Director, Vision Plus Hospital, Ahmedabad</div>
                    </div>
                </div>
            </article>
            <article class="testimonial-card pub-animate">
                <div class="t-quote-icon">&ldquo;</div>
                <div class="t-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                <p class="t-text">As a multi-doctor clinic, managing appointments was always a headache. Eye HMS solved that completely. The analytics dashboard gives us real clarity on our performance.</p>
                <div class="t-author">
                    <div class="t-avatar">SK</div>
                    <div>
                        <div class="t-name">Dr. Suresh Kumar</div>
                        <div class="t-role">Clinic Owner, Eye Care Clinic, Bangalore</div>
                    </div>
                </div>
            </article>
        </div>
    </div>
</section>

{{-- ============================================================
     Pricing Preview
============================================================ --}}
<section class="pub-section" id="pricing" aria-labelledby="pricing-heading">
    <div class="pub-section-inner">
        <div class="pub-section-header">
            <span class="section-eyebrow"><i class="fa-solid fa-tag"></i> Pricing</span>
            <h2 id="pricing-heading">Simple, Transparent Pricing</h2>
            <p>Start with a 14-day free trial — no credit card required. Upgrade anytime.</p>
        </div>
        <div class="pricing-grid pub-animate-group">
            {{-- Monthly --}}
            <div class="pricing-card pub-animate">
                <h3>Monthly</h3>
                <p class="pricing-desc">Pay as you go, cancel anytime</p>
                <div class="pricing-price">
                    <span class="p-sym">&#x20B9;</span>{{ number_format($pricing['monthly']['price']) }}<span class="p-period">/mo</span>
                </div>
                <div class="pricing-divider"></div>
                <ul class="pricing-features">
                    <li><i class="fa-solid fa-check"></i> All modules included</li>
                    <li><i class="fa-solid fa-check"></i> Unlimited patients</li>
                    <li><i class="fa-solid fa-check"></i> Up to 10 users</li>
                    <li><i class="fa-solid fa-check"></i> Email support</li>
                    <li><i class="fa-solid fa-check"></i> Daily backups</li>
                </ul>
                <a href="{{ route('register.show', ['plan' => 'monthly']) }}" class="hms-btn hms-btn-secondary hms-btn-block">
                    Get Started
                </a>
            </div>

            {{-- Quarterly (popular) --}}
            <div class="pricing-card popular pub-animate">
                <div class="popular-ribbon">Most Popular</div>
                <h3>Quarterly</h3>
                <p class="pricing-desc">Save 10% — great for growing clinics</p>
                <div class="pricing-price">
                    <span class="p-sym">&#x20B9;</span>{{ number_format($pricing['quarterly']['price']) }}<span class="p-period">/qtr</span>
                </div>
                <div class="pricing-original">&#x20B9;{{ number_format($pricing['quarterly']['original']) }}</div>
                <div class="pricing-save">Save &#x20B9;{{ number_format($pricing['quarterly']['save']) }}</div>
                <ul class="pricing-features">
                    <li><i class="fa-solid fa-check"></i> All modules included</li>
                    <li><i class="fa-solid fa-check"></i> Unlimited patients</li>
                    <li><i class="fa-solid fa-check"></i> Up to 10 users</li>
                    <li><i class="fa-solid fa-check"></i> Priority email + chat</li>
                    <li><i class="fa-solid fa-check"></i> Daily backups</li>
                </ul>
                <a href="{{ route('register.show', ['plan' => 'quarterly']) }}" class="hms-btn hms-btn-primary hms-btn-block">
                    Get Started
                </a>
            </div>

            {{-- Yearly --}}
            <div class="pricing-card pub-animate">
                <h3>Yearly</h3>
                <p class="pricing-desc">Maximum savings — 20% off</p>
                <div class="pricing-price">
                    <span class="p-sym">&#x20B9;</span>{{ number_format($pricing['yearly']['price']) }}<span class="p-period">/yr</span>
                </div>
                <div class="pricing-original">&#x20B9;{{ number_format($pricing['yearly']['original']) }}</div>
                <div class="pricing-save">Save &#x20B9;{{ number_format($pricing['yearly']['save']) }}</div>
                <ul class="pricing-features">
                    <li><i class="fa-solid fa-check"></i> All modules included</li>
                    <li><i class="fa-solid fa-check"></i> Unlimited patients</li>
                    <li><i class="fa-solid fa-check"></i> Unlimited users</li>
                    <li><i class="fa-solid fa-check"></i> Priority + phone support</li>
                    <li><i class="fa-solid fa-check"></i> Real-time backups</li>
                </ul>
                <a href="{{ route('register.show', ['plan' => 'yearly']) }}" class="hms-btn hms-btn-secondary hms-btn-block">
                    Get Started
                </a>
            </div>
        </div>
        <p style="text-align:center;margin-top:1.75rem;font-size:.875rem;color:var(--hms-text-muted)">
            Need a full feature comparison?
            <a href="{{ route('pricing') }}" style="color:var(--hms-primary);font-weight:600">View Pricing Page &rarr;</a>
        </p>
    </div>
</section>

{{-- ============================================================
     FAQ
============================================================ --}}
<section class="pub-section pub-section-grey" id="faq" aria-labelledby="faq-heading">
    <div class="pub-section-inner">
        <div class="pub-section-header">
            <span class="section-eyebrow"><i class="fa-solid fa-circle-question"></i> FAQ</span>
            <h2 id="faq-heading">Frequently Asked Questions</h2>
            <p>Have a question? We have answers.</p>
        </div>
        <div class="faq-list" role="list">
            <div class="faq-item" role="listitem">
                <button class="faq-btn" type="button" aria-expanded="false">
                    What happens after the 14-day free trial ends?
                    <span class="faq-icon"><i class="fa-solid fa-plus"></i></span>
                </button>
                <div class="faq-body">
                    After your trial ends, you have a 7-day grace period to choose a plan. During grace, you can view all records but cannot add new data. Pick any plan to restore full access immediately.
                </div>
            </div>
            <div class="faq-item" role="listitem">
                <button class="faq-btn" type="button" aria-expanded="false">
                    Is my hospital data safe and private?
                    <span class="faq-icon"><i class="fa-solid fa-plus"></i></span>
                </button>
                <div class="faq-body">
                    Yes. All data is encrypted in transit (TLS) and at rest. Each hospital's data is completely isolated from others through multi-tenancy. We perform daily automated backups with 30-day retention.
                </div>
            </div>
            <div class="faq-item" role="listitem">
                <button class="faq-btn" type="button" aria-expanded="false">
                    Can I switch or upgrade plans later?
                    <span class="faq-icon"><i class="fa-solid fa-plus"></i></span>
                </button>
                <div class="faq-body">
                    Absolutely. You can change your billing cycle (monthly, quarterly, or yearly) at any time from the Settings page. The new plan takes effect from your next billing date.
                </div>
            </div>
            <div class="faq-item" role="listitem">
                <button class="faq-btn" type="button" aria-expanded="false">
                    Do I need to install any software?
                    <span class="faq-icon"><i class="fa-solid fa-plus"></i></span>
                </button>
                <div class="faq-body">
                    No installation needed. Eye HMS is 100% cloud-based. Open any modern browser on your computer, tablet, or mobile phone — and you are ready to go.
                </div>
            </div>
            <div class="faq-item" role="listitem">
                <button class="faq-btn" type="button" aria-expanded="false">
                    What payment methods do you accept?
                    <span class="faq-icon"><i class="fa-solid fa-plus"></i></span>
                </button>
                <div class="faq-body">
                    We support all major Indian payment methods via Razorpay — UPI, credit/debit cards (Visa, Mastercard, RuPay), net banking, and popular wallets.
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
     CTA Banner
============================================================ --}}
<section class="pub-cta-banner" aria-label="Call to action">
    <div class="pub-cta-banner-inner">
        <h2>Ready to Transform Your Practice?</h2>
        <p>Join hundreds of eye care professionals across India who trust Eye HMS to deliver better patient outcomes and streamlined operations.</p>
        <div class="cta-btns">
            <a href="{{ route('register.show') }}" class="btn-hero-primary">
                <i class="fa-solid fa-rocket"></i> Start Free 14-Day Trial
            </a>
            <a href="{{ route('pricing') }}" class="btn-hero-outline">
                <i class="fa-solid fa-tag"></i> View Pricing
            </a>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
    (function () {
        document.querySelectorAll('.faq-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var item = btn.closest('.faq-item');
                var isOpen = item.classList.contains('open');
                document.querySelectorAll('.faq-item.open').forEach(function (el) {
                    el.classList.remove('open');
                    el.querySelector('.faq-btn').setAttribute('aria-expanded', 'false');
                });
                if (!isOpen) {
                    item.classList.add('open');
                    btn.setAttribute('aria-expanded', 'true');
                }
            });
        });
    }());
</script>
@endpush

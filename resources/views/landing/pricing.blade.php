@extends('landing.layouts.app')

@section('title', 'Pricing — Eye HMS SaaS | Affordable Eye Clinic Management Software')
@section('meta_description', 'Transparent pricing for Eye HMS SaaS — cloud-based eye hospital management software. Monthly, quarterly, and yearly plans with 14-day free trial. No credit card required.')
@section('og_title', 'Eye HMS SaaS Pricing — Start Free for 14 Days')

@section('content')

{{-- Hero --}}
<section class="pub-hero" style="min-height:40vh;padding-bottom:3rem;">
    <div class="pub-hero-inner" style="grid-template-columns:1fr;text-align:center;padding-top:3.5rem;">
        <div class="pub-hero-content" style="justify-self:center;">
            <div class="pub-hero-badge">
                <span class="badge-pulse"></span> Simple &amp; Transparent Pricing
            </div>
            <h1>No Hidden Fees.<br><span class="h1-grad">Just Results.</span></h1>
            <p class="pub-hero-desc" style="margin:0 auto 2rem;">Start with a 14-day free trial &mdash; all features included. No credit card required. Cancel anytime.</p>
            <div class="hero-cta-row" style="justify-content:center;">
                <a href="{{ route('register.show') }}" class="btn-hero-primary">
                    <i class="fa-solid fa-rocket"></i> Start Free Trial
                </a>
            </div>
        </div>
    </div>
</section>

{{-- Plan Cards --}}
<section class="pub-section">
    <div class="pub-section-inner">
        <div class="pricing-grid">
            {{-- Monthly --}}
            <div class="pricing-card">
                <h3>Monthly</h3>
                <p class="pricing-desc">Pay as you go, no commitment</p>
                <div class="pricing-price">Rs.{{ number_format($pricing['monthly']['price']) }}<span>/month</span></div>
                <div class="pricing-divider"></div>
                <ul class="pricing-features">
                    <li><i class="fa-solid fa-check"></i> All modules included</li>
                    <li><i class="fa-solid fa-check"></i> Unlimited patients</li>
                    <li><i class="fa-solid fa-check"></i> Up to 10 user accounts</li>
                    <li><i class="fa-solid fa-check"></i> Email support</li>
                    <li><i class="fa-solid fa-check"></i> Daily automatic backups</li>
                    <li><i class="fa-solid fa-check"></i> Reports and analytics</li>
                </ul>
                <a href="{{ route('register.show', ['plan' => 'monthly']) }}" class="hms-btn hms-btn-secondary hms-btn-block">Choose Monthly</a>
            </div>

            {{-- Quarterly --}}
            <div class="pricing-card popular">
                <div class="popular-ribbon">Most Popular</div>
                <h3>Quarterly</h3>
                <p class="pricing-desc">Save 10% — best value for growing clinics</p>
                <div class="pricing-price">Rs.{{ number_format($pricing['quarterly']['price']) }}<span>/quarter</span></div>
                <div class="pricing-original">Rs.{{ number_format($pricing['quarterly']['original']) }}</div>
                <div class="pricing-save">Save Rs.{{ number_format($pricing['quarterly']['save']) }}</div>
                <ul class="pricing-features">
                    <li><i class="fa-solid fa-check"></i> All modules included</li>
                    <li><i class="fa-solid fa-check"></i> Unlimited patients</li>
                    <li><i class="fa-solid fa-check"></i> Up to 10 user accounts</li>
                    <li><i class="fa-solid fa-check"></i> Priority email + chat support</li>
                    <li><i class="fa-solid fa-check"></i> Daily automatic backups</li>
                    <li><i class="fa-solid fa-check"></i> Reports and analytics</li>
                </ul>
                <a href="{{ route('register.show', ['plan' => 'quarterly']) }}" class="hms-btn hms-btn-primary hms-btn-block">Choose Quarterly</a>
            </div>

            {{-- Yearly --}}
            <div class="pricing-card">
                <h3>Yearly</h3>
                <p class="pricing-desc">Maximum savings — 20% off</p>
                <div class="pricing-price">Rs.{{ number_format($pricing['yearly']['price']) }}<span>/year</span></div>
                <div class="pricing-original">Rs.{{ number_format($pricing['yearly']['original']) }}</div>
                <div class="pricing-save">Save Rs.{{ number_format($pricing['yearly']['save']) }}</div>
                <ul class="pricing-features">
                    <li><i class="fa-solid fa-check"></i> All modules included</li>
                    <li><i class="fa-solid fa-check"></i> Unlimited patients</li>
                    <li><i class="fa-solid fa-check"></i> Unlimited user accounts</li>
                    <li><i class="fa-solid fa-check"></i> Priority + phone support</li>
                    <li><i class="fa-solid fa-check"></i> Real-time backups</li>
                    <li><i class="fa-solid fa-check"></i> Advanced analytics</li>
                </ul>
                <a href="{{ route('register.show', ['plan' => 'yearly']) }}" class="hms-btn hms-btn-secondary hms-btn-block">Choose Yearly</a>
            </div>
        </div>
    </div>
</section>

{{-- Feature Comparison --}}
<section class="pub-section pub-section-grey">
    <div class="pub-section-inner">
        <div class="pub-section-header">
            <span class="section-eyebrow"><i class="fa-solid fa-table"></i> Compare</span>
            <h2>Feature Comparison</h2>
            <p>All plans include every module. Differences are in users, support, and billing cycle.</p>
        </div>
        <div class="hms-table-wrap">
            <table class="comparison-table">
                <thead>
                    <tr>
                        <th>Feature</th>
                        <th>Monthly</th>
                        <th class="popular-col">Quarterly &#x2605;</th>
                        <th>Yearly</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach([
                        'Patient Management',
                        'Appointments',
                        'Eye Examination',
                        'OT and Surgery',
                        'Billing and Invoicing',
                        'Reports and Analytics',
                    ] as $feature)
                    <tr>
                        <td>{{ $feature }}</td>
                        <td class="ct-yes"><i class="fa-solid fa-check"></i></td>
                        <td class="popular-col ct-yes"><i class="fa-solid fa-check"></i></td>
                        <td class="ct-yes"><i class="fa-solid fa-check"></i></td>
                    </tr>
                    @endforeach
                    <tr>
                        <td>Max User Accounts</td>
                        <td>10</td>
                        <td class="popular-col">10</td>
                        <td style="font-weight:700;color:var(--hms-primary)">Unlimited</td>
                    </tr>
                    <tr>
                        <td>Support Level</td>
                        <td>Email</td>
                        <td class="popular-col">Email + Chat</td>
                        <td>Email + Chat + Phone</td>
                    </tr>
                    <tr>
                        <td>Backup Frequency</td>
                        <td>Daily</td>
                        <td class="popular-col">Daily</td>
                        <td style="font-weight:700;color:var(--hms-primary)">Real-time</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

{{-- CTA Banner --}}
<section class="pub-cta-banner">
    <div class="pub-cta-banner-inner">
        <h2>Ready to Transform Your Practice?</h2>
        <p>Start your 14-day free trial today. All modules included — no credit card required.</p>
        <div class="cta-btns">
            <a href="{{ route('register.show') }}" class="btn-hero-primary">
                <i class="fa-solid fa-rocket"></i> Start Free Trial
            </a>
            <a href="{{ route('home') }}#features" class="btn-hero-outline">
                <i class="fa-solid fa-star"></i> See All Features
            </a>
        </div>
    </div>
</section>

@endsection

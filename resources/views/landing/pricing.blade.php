@extends('landing.layouts.app')

@section('title', 'Pricing — EYENOSIS | Eye Hospital CRM Plans')
@section('meta_description')
Transparent pricing for EYENOSIS SaaS multi-tenant eye hospital CRM. Monthly, quarterly and yearly plans with {{ $platformTrialLabel }} free trial.
@endsection
@section('og_title')
EYENOSIS Pricing — Start Free for {{ $platformTrialDays }} Days
@endsection

@section('content')

    <section class="ecrm-page-hero">
        <div class="ecrm-container ecrm-reveal">
            <span class="ecrm-kicker">Pricing</span>
            <h1>Simple plans for hospital CRM SaaS</h1>
            <p>All modules included on every plan — OPD, exams, OT pipeline, billing and roles. {{ $platformTrialLabel }} free trial, no card
                required.</p>
            <a href="{{ route('register.show') }}" class="ecrm-btn ecrm-btn-primary ecrm-btn-lg">
                <i class="fa-solid fa-rocket"></i> Start Free Trial
            </a>
        </div>
    </section>

    <section class="ecrm-section">
        <div class="ecrm-container">
            <div class="ecrm-pricing">
                <article class="ecrm-price ecrm-reveal">
                    <h3>Monthly</h3>
                    <p class="desc">Pay as you go, no commitment</p>
                    <div class="amount">
                        <span
                            class="sym">{{ platform_currency_symbol() }}</span>{{ number_format($pricing['monthly']['price']) }}
                        <span class="per">/month</span>
                    </div>
                    <ul>
                        <li><i class="fa-solid fa-check"></i> All modules included</li>
                        <li><i class="fa-solid fa-check"></i> Unlimited patients</li>
                        <li><i class="fa-solid fa-check"></i> Up to 10 user accounts</li>
                        <li><i class="fa-solid fa-check"></i> Email support</li>
                        <li><i class="fa-solid fa-check"></i> Daily automatic backups</li>
                        <li><i class="fa-solid fa-check"></i> Reports and analytics</li>
                    </ul>
                    <a href="{{ route('register.show', ['plan' => 'monthly']) }}"
                        class="ecrm-btn ecrm-btn-outline ecrm-btn-block">Choose Monthly</a>
                </article>

                <article class="ecrm-price featured ecrm-reveal">
                    <div class="ribbon">Most Popular</div>
                    <h3>Quarterly</h3>
                    <p class="desc">Save 10% — best value for growing clinics</p>
                    <div class="amount">
                        <span
                            class="sym">{{ platform_currency_symbol() }}</span>{{ number_format($pricing['quarterly']['price']) }}
                        <span class="per">/quarter</span>
                    </div>
                    <div class="save">
                        <s>{{ platform_currency_symbol() }}{{ number_format($pricing['quarterly']['original']) }}</s>
                        <span>Save {{ platform_currency_symbol() }}{{ number_format($pricing['quarterly']['save']) }}</span>
                    </div>
                    <ul>
                        <li><i class="fa-solid fa-check"></i> All modules included</li>
                        <li><i class="fa-solid fa-check"></i> Unlimited patients</li>
                        <li><i class="fa-solid fa-check"></i> Up to 10 user accounts</li>
                        <li><i class="fa-solid fa-check"></i> Priority email + chat support</li>
                        <li><i class="fa-solid fa-check"></i> Daily automatic backups</li>
                        <li><i class="fa-solid fa-check"></i> Reports and analytics</li>
                    </ul>
                    <a href="{{ route('register.show', ['plan' => 'quarterly']) }}"
                        class="ecrm-btn ecrm-btn-primary ecrm-btn-block">Choose Quarterly</a>
                </article>

                <article class="ecrm-price ecrm-reveal">
                    <h3>Yearly</h3>
                    <p class="desc">Maximum savings — 20% off</p>
                    <div class="amount">
                        <span
                            class="sym">{{ platform_currency_symbol() }}</span>{{ number_format($pricing['yearly']['price']) }}
                        <span class="per">/year</span>
                    </div>
                    <div class="save">
                        <s>{{ platform_currency_symbol() }}{{ number_format($pricing['yearly']['original']) }}</s>
                        <span>Save {{ platform_currency_symbol() }}{{ number_format($pricing['yearly']['save']) }}</span>
                    </div>
                    <ul>
                        <li><i class="fa-solid fa-check"></i> All modules included</li>
                        <li><i class="fa-solid fa-check"></i> Unlimited patients</li>
                        <li><i class="fa-solid fa-check"></i> Unlimited user accounts</li>
                        <li><i class="fa-solid fa-check"></i> Priority + phone support</li>
                        <li><i class="fa-solid fa-check"></i> Real-time backups</li>
                        <li><i class="fa-solid fa-check"></i> Reports and analytics</li>
                    </ul>
                    <a href="{{ route('register.show', ['plan' => 'yearly']) }}"
                        class="ecrm-btn ecrm-btn-outline ecrm-btn-block">Choose Yearly</a>
                </article>
            </div>
        </div>
    </section>

@endsection
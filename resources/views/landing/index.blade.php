@extends('landing.layouts.app')

@section('title', 'EYENOSIS — Eye Hospital CRM Platform')
@section('meta_description')
Cloud multi-tenant Eye Hospital CRM: patients, OPD, primary/secondary exam, OT counselling & surgery, accountant, ward, discharge billing and reports. Start free {{ $platformTrialLabel }} trial.
@endsection
@section('meta_keywords', 'eye hospital CRM, ophthalmology HMS, OT management, multi-tenant hospital SaaS, eye clinic software India')
@section('og_title', 'EYENOSIS — Eye Hospital CRM Platform')
@section('og_description', 'Complete multi-tenant CRM / HMS for eye hospitals — OPD, OT, billing, roles and reports.')

@section('content')

    @include('landing.components.hero')

    {{-- Module strip --}}
    <section class="ecrm-strip ecrm-reveal" aria-label="Platform highlights">
        <div class="ecrm-container ecrm-strip-inner">
            <div><i class="fa-solid fa-hospital-user"></i><span>Multi-tenant hospitals</span></div>
            <div><i class="fa-solid fa-stethoscope"></i><span>Clinical exams</span></div>
            <div><i class="fa-solid fa-scalpel"></i><span>Full OT pipeline</span></div>
            <div><i class="fa-solid fa-file-invoice-dollar"></i><span>Billing &amp; refunds</span></div>
            <div><i class="fa-solid fa-user-shield"></i><span>Role permissions</span></div>
            <div><i class="fa-solid fa-chart-pie"></i><span>Ops reports</span></div>
        </div>
    </section>

    {{-- Before / With EYENOSIS (IVClasses-style split cards) --}}
    <section class="ecrm-section ecrm-compare-section" id="transform" aria-labelledby="compare-heading">
        <div class="ecrm-container">
            <div class="ecrm-head ecrm-reveal">
                <span class="ecrm-kicker">Before → With EYENOSIS</span>
                <h2 id="compare-heading">Stop running your hospital on paper</h2>
                <p>See the difference between scattered registers and one live eye-hospital CRM.</p>
            </div>

            <div class="ecrm-compare-stack">
                {{-- Before --}}
                <article class="ecrm-compare-card ecrm-reveal">
                    <div class="ecrm-compare-media">
                        <img src="{{ asset('images/landing/before-manual-desk.png') }}?v=2"
                            alt="Stressed hospital receptionist buried in patient paper files and MRD registers" width="900"
                            height="675" loading="lazy" decoding="async">
                    </div>
                    <div class="ecrm-compare-copy">
                        <span class="ecrm-compare-pill">Before EYENOSIS</span>
                        <h3>Manual work slows your hospital every day</h3>
                        <p>Paper registers, WhatsApp follow-ups and scattered Excel sheets waste staff time and hide the
                            real patient picture.</p>
                        <ul class="ecrm-compare-list ecrm-compare-list--bad">
                            <li><i class="fa-solid fa-xmark" aria-hidden="true"></i> Patient follow-ups lost in chats &amp; slips</li>
                            <li><i class="fa-solid fa-xmark" aria-hidden="true"></i> MRD files hard to find at the desk</li>
                            <li><i class="fa-solid fa-xmark" aria-hidden="true"></i> OPD / OT status unclear across roles</li>
                            <li><i class="fa-solid fa-xmark" aria-hidden="true"></i> Billing &amp; reports take hours to reconcile</li>
                            <li><i class="fa-solid fa-xmark" aria-hidden="true"></i> No single view of clinic operations</li>
                        </ul>
                    </div>
                </article>

                {{-- With --}}
                <article class="ecrm-compare-card ecrm-compare-card--reverse ecrm-reveal">
                    <div class="ecrm-compare-copy">
                        <span class="ecrm-compare-pill">With EYENOSIS</span>
                        <h3>Everything runs on one live SaaS CRM</h3>
                        <p>Reception, doctors, OT, accounts and discharge share one cloud platform — role-aware desks,
                            hospital isolation, live queues.</p>
                        <ul class="ecrm-compare-list ecrm-compare-list--good">
                            <li><i class="fa-solid fa-check" aria-hidden="true"></i> Patient → OPD → exam → OT in one CRM</li>
                            <li><i class="fa-solid fa-check" aria-hidden="true"></i> Live queues for every hospital role</li>
                            <li><i class="fa-solid fa-check" aria-hidden="true"></i> Package payments, refunds &amp; invoices</li>
                            <li><i class="fa-solid fa-check" aria-hidden="true"></i> Clinical history on the patient timeline</li>
                            <li><i class="fa-solid fa-check" aria-hidden="true"></i> Ops reports without spreadsheet chaos</li>
                        </ul>
                    </div>
                    <div class="ecrm-compare-media">
                        <img src="{{ asset('images/landing/with-digital-crm.png') }}?v=2"
                            alt="Hospital receptionist using EYENOSIS CRM for patient registration at a clean desk" width="900"
                            height="675" loading="lazy" decoding="async">
                    </div>
                </article>
            </div>
        </div>
    </section>

    {{-- CRM Modules --}}
    <section class="ecrm-section" id="modules" aria-labelledby="modules-heading">
        <div class="ecrm-container">
            <div class="ecrm-head ecrm-reveal">
                <span class="ecrm-kicker">CRM modules</span>
                <h2 id="modules-heading">Built from real hospital desks</h2>
                <p>Each module maps to how your clinic actually works — not a generic hospital template.</p>
            </div>
            <div class="ecrm-cards">
                <article class="ecrm-card ecrm-card--blue ecrm-reveal">
                    <div class="ecrm-card-ico" aria-hidden="true"><i class="fa-solid fa-id-card"></i></div>
                    <h3>Patient CRM</h3>
                    <p>Registration, walk-in / phone, search, history timeline, referrals and case fees.</p>
                    <ul>
                        <li>Patient master &amp; visits</li>
                        <li>Reception ownership</li>
                        <li>Print / history surfaces</li>
                    </ul>
                </article>
                <article class="ecrm-card ecrm-card--teal ecrm-reveal">
                    <div class="ecrm-card-ico" aria-hidden="true"><i class="fa-solid fa-eye"></i></div>
                    <h3>Clinical exams</h3>
                    <p>Primary &amp; secondary examination workflows with diagnosis and prescriptions.</p>
                    <ul>
                        <li>Doctor queues</li>
                        <li>Exam printouts</li>
                        <li>Recommend surgery → OT</li>
                    </ul>
                </article>
                <article class="ecrm-card ecrm-card--indigo ecrm-reveal">
                    <div class="ecrm-card-ico" aria-hidden="true"><i class="fa-solid fa-calendar-check"></i></div>
                    <h3>OPD &amp; appointments</h3>
                    <p>OPD desk flow with OT appointment intake for conversion into the surgical pipeline.</p>
                    <ul>
                        <li>Queue management</li>
                        <li>OT appointments</li>
                        <li>FOC controls</li>
                    </ul>
                </article>
                <article class="ecrm-card ecrm-card--orange ecrm-reveal">
                    <div class="ecrm-card-ico" aria-hidden="true"><i class="fa-solid fa-comments-dollar"></i></div>
                    <h3>OT counselling</h3>
                    <p>Package selection, lens options, estimates and counselling before payment.</p>
                    <ul>
                        <li>Package masters</li>
                        <li>Cost breakdown</li>
                        <li>Consent handoff</li>
                    </ul>
                </article>
                <article class="ecrm-card ecrm-card--green ecrm-reveal">
                    <div class="ecrm-card-ico" aria-hidden="true"><i class="fa-solid fa-wallet"></i></div>
                    <h3>Accountant desk</h3>
                    <p>OT package payments, payment verified queue, surgery-refuse refunds, money report.</p>
                    <ul>
                        <li>Collect package fee</li>
                        <li>Full refund flow</li>
                        <li>Net collection view</li>
                    </ul>
                </article>
                <article class="ecrm-card ecrm-card--navy ecrm-reveal">
                    <div class="ecrm-card-ico" aria-hidden="true"><i class="fa-solid fa-bed-pulse"></i></div>
                    <h3>Ward → OT → discharge</h3>
                    <p>Ward vitals &amp; dilation, assistant lens, doctor surgery, discharge invoices &amp; slips.</p>
                    <ul>
                        <li>Ward management</li>
                        <li>OT assistant / surgeon</li>
                        <li>Billing desk prints</li>
                    </ul>
                </article>
            </div>
        </div>
    </section>

    {{-- Image + detail sections --}}
    <section class="ecrm-section ecrm-section-alt ecrm-details-section" id="details" aria-label="Platform details">
        <div class="ecrm-container">
            <div class="ecrm-head ecrm-reveal">
                <span class="ecrm-kicker">Platform depth</span>
                <h2>Clinical, OT and SaaS — designed as one CRM</h2>
                <p>Real desk workflows, status-driven OT and hospital isolation — not a bolted-on module list.</p>
            </div>
            <div class="ecrm-detail-stack">
                <div class="ecrm-detail ecrm-reveal">
                    <div class="ecrm-detail-media">
                        <img src="{{ asset('images/landing/detail-clinical.png') }}"
                            alt="Clinical exam CRM — primary examination workspace" width="1200" height="900" loading="lazy"
                            decoding="async">
                    </div>
                    <div class="ecrm-detail-copy">
                        <span class="ecrm-kicker">Clinical CRM</span>
                        <h3>Exams that stay on the patient timeline</h3>
                        <p>Doctors work queues by stage. Findings, diagnosis and prescriptions stay attached to the same
                            registration — ready for history and OT recommendation.</p>
                        <ul class="ecrm-ticks">
                            <li>Primary &amp; secondary stages</li>
                            <li>Printable clinical sheets</li>
                            <li>Seamless hand-off to OT counselling</li>
                        </ul>
                    </div>
                </div>

                <div class="ecrm-detail reverse ecrm-reveal">
                    <div class="ecrm-detail-media">
                        <img src="{{ asset('images/landing/detail-ot.png') }}"
                            alt="OT surgery pipeline CRM — counselling to discharge" width="1200" height="900"
                            loading="lazy" decoding="async">
                    </div>
                    <div class="ecrm-detail-copy">
                        <span class="ecrm-kicker">OT CRM</span>
                        <h3>Surgery journey under control</h3>
                        <p>From counselled package to payment, ward prep, assistant lens, operated status and discharge desk
                            — every role sees the right queue.</p>
                        <ul class="ecrm-ticks">
                            <li>Payment verify → ward → ready → operated</li>
                            <li>Refunds when surgery is refused</li>
                            <li>Invoices, slips &amp; follow-up printables</li>
                        </ul>
                    </div>
                </div>

                <div class="ecrm-detail ecrm-reveal">
                    <div class="ecrm-detail-media">
                        <img src="{{ asset('images/landing/detail-saas.png') }}"
                            alt="Multi-tenant hospital SaaS — isolated workspaces" width="1200" height="900" loading="lazy"
                            decoding="async">
                    </div>
                    <div class="ecrm-detail-copy">
                        <span class="ecrm-kicker">SaaS foundation</span>
                        <h3>Multi-tenant by design</h3>
                        <p>Each hospital gets an isolated workspace with its own staff, masters and operational data —
                            managed under platform subscriptions.</p>
                        <ul class="ecrm-ticks">
                            <li>Hospital admin settings</li>
                            <li>Permission-aware menus</li>
                            <li>Trial → paid plans</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Workflow --}}
    <section class="ecrm-section" id="workflow" aria-labelledby="workflow-heading">
        <div class="ecrm-container">
            <div class="ecrm-head ecrm-reveal">
                <span class="ecrm-kicker">Workflow</span>
                <h2 id="workflow-heading">From walk-in to follow-up</h2>
                <p>A continuous CRM path across OPD and OT — the right role owns each step.</p>
            </div>
            <ol class="ecrm-steps">
                <li class="ecrm-reveal"><span class="ecrm-step-num">01</span><strong>Register</strong><em>Reception</em>
                </li>
                <li class="ecrm-reveal"><span class="ecrm-step-num">02</span><strong>Examine</strong><em>Doctor</em></li>
                <li class="ecrm-reveal"><span class="ecrm-step-num">03</span><strong>Counsel</strong><em>Counsellor</em>
                </li>
                <li class="ecrm-reveal"><span class="ecrm-step-num">04</span><strong>Collect</strong><em>Accountant</em>
                </li>
                <li class="ecrm-reveal"><span class="ecrm-step-num">05</span><strong>Prep</strong><em>Ward</em></li>
                <li class="ecrm-reveal"><span class="ecrm-step-num">06</span><strong>Operate</strong><em>OT team</em></li>
                <li class="ecrm-reveal"><span class="ecrm-step-num">07</span><strong>Discharge</strong><em>Billing desk</em>
                </li>
            </ol>
        </div>
    </section>

    {{-- Roles --}}
    <section class="ecrm-section ecrm-section-alt" id="roles" aria-labelledby="roles-heading">
        <div class="ecrm-container">
            <div class="ecrm-head ecrm-reveal">
                <span class="ecrm-kicker">Role-based CRM</span>
                <h2 id="roles-heading">Desks your staff already understand</h2>
                <p>Permission-aware navigation for every hospital role — fewer wrong screens, faster work.</p>
            </div>
            <div class="ecrm-roles">
                <article class="ecrm-reveal">
                    <div class="ecrm-role-ico" aria-hidden="true"><i class="fa-solid fa-headset"></i></div>
                    <h3>Reception</h3>
                    <p>Register, FOC request, OPD queue, phone appointments.</p>
                </article>
                <article class="ecrm-reveal">
                    <div class="ecrm-role-ico" aria-hidden="true"><i class="fa-solid fa-user-doctor"></i></div>
                    <h3>Doctor</h3>
                    <p>Primary / secondary exams, surgery recommend, OT doctor queue.</p>
                </article>
                <article class="ecrm-reveal">
                    <div class="ecrm-role-ico" aria-hidden="true"><i class="fa-solid fa-hand-holding-heart"></i></div>
                    <h3>Counsellor</h3>
                    <p>Package counselling, consent handoff into billing.</p>
                </article>
                <article class="ecrm-reveal">
                    <div class="ecrm-role-ico" aria-hidden="true"><i class="fa-solid fa-calculator"></i></div>
                    <h3>Accountant</h3>
                    <p>OT payment, refunds, ward list, money summary.</p>
                </article>
                <article class="ecrm-reveal">
                    <div class="ecrm-role-ico" aria-hidden="true"><i class="fa-solid fa-bed"></i></div>
                    <h3>Ward</h3>
                    <p>Vitals, dilation, medicine groups for OT prep.</p>
                </article>
                <article class="ecrm-reveal">
                    <div class="ecrm-role-ico" aria-hidden="true"><i class="fa-solid fa-user-nurse"></i></div>
                    <h3>OT assistant</h3>
                    <p>Ready queue, lens detail, assist surgery flow.</p>
                </article>
                <article class="ecrm-reveal">
                    <div class="ecrm-role-ico" aria-hidden="true"><i class="fa-solid fa-file-invoice"></i></div>
                    <h3>Discharge</h3>
                    <p>Invoices, medicine / lens slips, certificates.</p>
                </article>
                <article class="ecrm-reveal">
                    <div class="ecrm-role-ico" aria-hidden="true"><i class="fa-solid fa-user-tie"></i></div>
                    <h3>Hospital admin</h3>
                    <p>Users, masters, settings, collection overview, reports.</p>
                </article>
            </div>
        </div>
    </section>

    {{-- Pricing --}}
    <section class="ecrm-section" id="pricing" aria-labelledby="pricing-heading">
        <div class="ecrm-container">
            <div class="ecrm-head ecrm-reveal">
                <span class="ecrm-kicker">Pricing</span>
                <h2 id="pricing-heading">Transparent SaaS plans</h2>
                <p>Start with a {{ $platformTrialLabel }} free trial — no credit card required. Upgrade anytime.</p>
            </div>
            <div class="ecrm-pricing">
                <article class="ecrm-price ecrm-reveal">
                    <h3>Monthly</h3>
                    <p class="desc">Pay as you go, cancel anytime</p>
                    <div class="amount">
                        <span
                            class="sym">{{ platform_currency_symbol() }}</span>{{ number_format($pricing['monthly']['price']) }}
                        <span class="per">/mo</span>
                    </div>
                    <ul>
                        <li><i class="fa-solid fa-check"></i> All modules included</li>
                        <li><i class="fa-solid fa-check"></i> Unlimited patients</li>
                        <li><i class="fa-solid fa-check"></i> Up to 10 users</li>
                        <li><i class="fa-solid fa-check"></i> Email support</li>
                        <li><i class="fa-solid fa-check"></i> Daily backups</li>
                    </ul>
                    <a href="{{ route('register.show', ['plan' => 'monthly']) }}"
                        class="ecrm-btn ecrm-btn-outline ecrm-btn-block">Get Started</a>
                </article>
                <article class="ecrm-price featured ecrm-reveal">
                    <div class="ribbon">Most Popular</div>
                    <h3>Quarterly</h3>
                    <p class="desc">Save 10% — great for growing clinics</p>
                    <div class="amount">
                        <span
                            class="sym">{{ platform_currency_symbol() }}</span>{{ number_format($pricing['quarterly']['price']) }}
                        <span class="per">/qtr</span>
                    </div>
                    <div class="save">
                        <s>{{ platform_currency_symbol() }}{{ number_format($pricing['quarterly']['original']) }}</s>
                        <span>Save {{ platform_currency_symbol() }}{{ number_format($pricing['quarterly']['save']) }}</span>
                    </div>
                    <ul>
                        <li><i class="fa-solid fa-check"></i> All modules included</li>
                        <li><i class="fa-solid fa-check"></i> Unlimited patients</li>
                        <li><i class="fa-solid fa-check"></i> Up to 10 users</li>
                        <li><i class="fa-solid fa-check"></i> Priority email + chat</li>
                        <li><i class="fa-solid fa-check"></i> Daily backups</li>
                    </ul>
                    <a href="{{ route('register.show', ['plan' => 'quarterly']) }}"
                        class="ecrm-btn ecrm-btn-primary ecrm-btn-block">Get Started</a>
                </article>
                <article class="ecrm-price ecrm-reveal">
                    <h3>Yearly</h3>
                    <p class="desc">Maximum savings — 20% off</p>
                    <div class="amount">
                        <span
                            class="sym">{{ platform_currency_symbol() }}</span>{{ number_format($pricing['yearly']['price']) }}
                        <span class="per">/yr</span>
                    </div>
                    <div class="save">
                        <s>{{ platform_currency_symbol() }}{{ number_format($pricing['yearly']['original']) }}</s>
                        <span>Save {{ platform_currency_symbol() }}{{ number_format($pricing['yearly']['save']) }}</span>
                    </div>
                    <ul>
                        <li><i class="fa-solid fa-check"></i> All modules included</li>
                        <li><i class="fa-solid fa-check"></i> Unlimited patients</li>
                        <li><i class="fa-solid fa-check"></i> Unlimited users</li>
                        <li><i class="fa-solid fa-check"></i> Priority + phone support</li>
                        <li><i class="fa-solid fa-check"></i> Real-time backups</li>
                    </ul>
                    <a href="{{ route('register.show', ['plan' => 'yearly']) }}"
                        class="ecrm-btn ecrm-btn-outline ecrm-btn-block">Get Started</a>
                </article>
            </div>
            <p class="ecrm-price-more">
                Prefer a dedicated page?
                <a href="{{ route('pricing') }}">Open full pricing →</a>
            </p>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="ecrm-section ecrm-section-alt" id="faq" aria-labelledby="faq-heading">
        <div class="ecrm-container ecrm-faq-wrap">
            <div class="ecrm-head ecrm-reveal">
                <span class="ecrm-kicker">FAQ</span>
                <h2 id="faq-heading">Frequently asked questions</h2>
                <p>Trials, multi-tenant data, plans and access.</p>
            </div>
            <div class="ecrm-faq" role="list">
                <div class="ecrm-faq-item" role="listitem">
                    <button type="button" class="ecrm-faq-btn" aria-expanded="false">
                        What happens after the {{ $platformTrialLabel }} free trial ends?
                        <i class="fa-solid fa-plus"></i>
                    </button>
                    <div class="ecrm-faq-body">
                        After your trial ends, you have a 7-day grace period to choose a plan. During grace, you can view
                        records but cannot add new data. Pick any plan to restore full access.
                    </div>
                </div>
                <div class="ecrm-faq-item" role="listitem">
                    <button type="button" class="ecrm-faq-btn" aria-expanded="false">
                        Is each hospital’s data isolated?
                        <i class="fa-solid fa-plus"></i>
                    </button>
                    <div class="ecrm-faq-body">
                        Yes. Eyenosis is multi-tenant: each hospital workspace is tenant-scoped, with role-based staff
                        access
                        inside that hospital.
                    </div>
                </div>
                <div class="ecrm-faq-item" role="listitem">
                    <button type="button" class="ecrm-faq-btn" aria-expanded="false">
                        Can I change plans later?
                        <i class="fa-solid fa-plus"></i>
                    </button>
                    <div class="ecrm-faq-body">
                        Yes. Active hospitals can change billing cycle (monthly, quarterly, yearly) from settings according
                        to platform subscription rules.
                    </div>
                </div>
                <div class="ecrm-faq-item" role="listitem">
                    <button type="button" class="ecrm-faq-btn" aria-expanded="false">
                        Do I need to install software?
                        <i class="fa-solid fa-plus"></i>
                    </button>
                    <div class="ecrm-faq-body">
                        No. Eye HMS is cloud-based — use a modern browser on desktop, tablet or phone.
                    </div>
                </div>
                <div class="ecrm-faq-item" role="listitem">
                    <button type="button" class="ecrm-faq-btn" aria-expanded="false">
                        How do subscriptions get paid?
                        <i class="fa-solid fa-plus"></i>
                    </button>
                    <div class="ecrm-faq-body">
                        Platform subscriptions run through Razorpay (UPI, cards, net banking and wallets, subject to gateway
                        availability).
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection

@push('scripts')
    <script>
        (function () {
            document.querySelectorAll('.ecrm-faq-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var item = btn.closest('.ecrm-faq-item');
                    var open = item.classList.contains('is-open');
                    document.querySelectorAll('.ecrm-faq-item.is-open').forEach(function (el) {
                        el.classList.remove('is-open');
                        el.querySelector('.ecrm-faq-btn').setAttribute('aria-expanded', 'false');
                    });
                    if (!open) {
                        item.classList.add('is-open');
                        btn.setAttribute('aria-expanded', 'true');
                    }
                });
            });
        }());
    </script>
@endpush
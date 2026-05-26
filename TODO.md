# SuperAdmin Panel — Design Alignment TODO

**Goal:** SuperAdmin panel ka design exactly hospital admin panel jaisa karo — same colors, same icon set (Bootstrap Icons only), same components (hms-card, hms-btn, hms-table), same typography.

**Rule:** Koi bhi naya `fa-solid`, `fa-regular`, `fa-brands` icon allowed nahi. Sirf `bi bi-*` (Bootstrap Icons).

---

## AUDIT SUMMARY

### Root Issues Found
| Issue | Affected Files | Priority |
|-------|---------------|----------|
| Font Awesome icons (`fa-solid`) | 12 out of 14 SA files | 🔴 Critical |
| SA-specific custom CSS classes (`sa-premium-btn`, `sa-plan-card`, `sa-modal`) | plans/index.blade.php | 🟠 High |
| `sa-premium-card` wrapper instead of `hms-card` | plans/index.blade.php | 🟠 High |
| Inline empty state (no `x-empty-state` component) | audit-logs | 🟡 Medium |
| Password toggle uses FA eye icon | profile/index.blade.php | 🟡 Medium |
| JS strings mein hard-coded FA icons | notifications/index.blade.php | 🟡 Medium |

### FA → BI Complete Mapping (project-wide)
| Font Awesome | Bootstrap Icon | Use |
|---|---|---|
| `fa-solid fa-filter` | `bi bi-funnel-fill` | Filter buttons |
| `fa-solid fa-search` | `bi bi-search` | Search |
| `fa-solid fa-plus` | `bi bi-plus-lg` | Add actions |
| `fa-solid fa-eye` | `bi bi-eye-fill` | View / password toggle |
| `fa-solid fa-eye-slash` | `bi bi-eye-slash-fill` | Hide password |
| `fa-solid fa-pen-to-square` | `bi bi-pencil-fill` | Edit |
| `fa-solid fa-arrow-left` | `bi bi-arrow-left` | Back |
| `fa-solid fa-arrow-up-right-from-square` | `bi bi-box-arrow-up-right` | Open in new tab |
| `fa-solid fa-check` | `bi bi-check-lg` | Approve |
| `fa-solid fa-ban` | `bi bi-ban` | Suspend/Block |
| `fa-solid fa-trash` | `bi bi-trash3-fill` | Delete/Remove |
| `fa-solid fa-floppy-disk` | `bi bi-floppy-fill` | Save |
| `fa-solid fa-xmark` | `bi bi-x-lg` | Close/Cancel |
| `fa-solid fa-shield-halved` | `bi bi-shield-fill` | Security/Audit |
| `fa-solid fa-paper-plane` | `bi bi-send-fill` | Send notification |
| `fa-solid fa-clock-rotate-left` | `bi bi-clock-history` | History |
| `fa-solid fa-bell-slash` | `bi bi-bell-slash-fill` | No notifications |
| `fa-solid fa-users` | `bi bi-people-fill` | All users/hospitals |
| `fa-solid fa-user-check` | `bi bi-person-check-fill` | Selected users |
| `fa-solid fa-layer-group` | `bi bi-layers-fill` | Plans/Layers |
| `fa-solid fa-star` | `bi bi-star-fill` | Popular badge |
| `fa-solid fa-circle-check` | `bi bi-check-circle-fill` | Feature checkmark |
| `fa-solid fa-circle-user` | `bi bi-person-circle` | Profile/Account |
| `fa-solid fa-lock` | `bi bi-lock-fill` | Password/Security |
| `fa-solid fa-key` | `bi bi-key-fill` | Change password |
| `fa-solid fa-gear` | `bi bi-gear-fill` | Settings |
| `fa-solid fa-credit-card` | `bi bi-credit-card-fill` | Razorpay/Payment config |
| `fa-solid fa-circle-info` | `bi bi-info-circle-fill` | Info alert |
| `fa-solid fa-indian-rupee-sign` | `bi bi-currency-rupee` | Rupee/Price |
| `fa-solid fa-hospital-user` | `bi bi-hospital-fill` | Hospital/Create hospital |
| `fa-solid fa-envelope` | `bi bi-envelope-fill` | Email/SMTP |
| `fa-solid fa-archive` | `bi bi-archive-fill` | Archive |
| `fa-solid fa-rotate-right` | `bi bi-arrow-clockwise` | Reactivate |
| `fa-solid fa-bolt` | `bi bi-lightning-fill` | Quick actions |
| `fa-solid fa-calendar-check` | `bi bi-calendar-check-fill` | Subscription |
| `fa-solid fa-receipt` | `bi bi-receipt-cutoff` | Payment receipt |

---

## PHASE 1 — Font Awesome → Bootstrap Icons (All Files)
**Priority: 🔴 Critical | Estimated: 2-3 hours**
**Rule:** Har `fa-solid fa-*` ko `bi bi-*` se replace karo. JS strings mein bhi.

### 1.1 `tenants/index.blade.php`
- [ ] Line 8: `fa-solid fa-plus` → `bi bi-plus-lg` (Add Hospital button)
- [ ] Line 17: `fa-solid fa-filter` → `bi bi-funnel-fill` (Filter section label)
- [ ] Line 41: `fa-solid fa-search` → `bi bi-search` (Filter button)
- [ ] Line 54: `fa-solid fa-hospital-user` → `bi bi-hospital-fill` (Card title)
- [ ] Line 108: `fa-solid fa-eye` → `bi bi-eye-fill` (View Details icon)
- [ ] Line 111: `fa-solid fa-pen-to-square` → `bi bi-pencil-fill` (Edit icon)
- [ ] Line 117: `fa-solid fa-check` → `bi bi-check-lg` (Activate button)
- [ ] Line 127: `fa-solid fa-ban` → `bi bi-ban` (Suspend button)

### 1.2 `tenants/show.blade.php`
- [ ] Line 8: `fa-solid fa-arrow-left` → `bi bi-arrow-left`
- [ ] Line 11: `fa-solid fa-pen-to-square` → `bi bi-pencil-fill`
- [ ] Line 14: `fa-solid fa-arrow-up-right-from-square` → `bi bi-box-arrow-up-right`
- [ ] Line 67: `fa-solid fa-hospital` → `bi bi-hospital-fill` (Card title)
- [ ] Line 98: `fa-solid fa-bolt` → `bi bi-lightning-fill` (Quick Actions title)
- [ ] Line 107: `fa-solid fa-check` → `bi bi-check-lg` (Activate)
- [ ] Line 116: `fa-solid fa-ban` → `bi bi-ban` (Suspend)
- [ ] Line 125: `fa-solid fa-rotate-right` → `bi bi-arrow-clockwise` (Reactivate)
- [ ] Line 137: `fa-solid fa-clock-rotate-left` → `bi bi-clock-history` (Extend Grace)
- [ ] Line 147: `fa-solid fa-archive` → `bi bi-archive-fill` (Archive)
- [ ] Line 161: `fa-solid fa-calendar-check` → `bi bi-calendar-check-fill` (Subscription History)
- [ ] Line 170: `fa-solid fa-calendar-xmark` → `bi bi-calendar-x-fill` (Empty state)
- [ ] Line 210: `fa-solid fa-indian-rupee-sign` → `bi bi-currency-rupee` (Payment History)
- [ ] Line 220: `fa-solid fa-receipt` → `bi bi-receipt-cutoff` (Empty state)

### 1.3 `tenants/create.blade.php`
- [ ] Line 7: `fa-solid fa-arrow-left` → `bi bi-arrow-left`
- [ ] Line 118: `fa-solid fa-hospital-user` → `bi bi-hospital-fill` (Create button)

### 1.4 `tenants/edit.blade.php`
- [ ] Line 7: `fa-solid fa-arrow-left` → `bi bi-arrow-left`
- [ ] Check all other icons in form and action buttons

### 1.5 `payments/index.blade.php`
- [ ] Stat card icons (bi icons already set but verify)
- [ ] Filter section: `fa-solid fa-filter` → `bi bi-funnel-fill`
- [ ] Filter button: `fa-solid fa-search` → `bi bi-search`
- [ ] Table action icons
- [ ] Empty state icon

### 1.6 `subscriptions/index.blade.php`
- [ ] All remaining FA icons → BI equivalents
- [ ] Filter + table icons

### 1.7 `audit-logs/index.blade.php`
- [ ] Line 10: `fa-solid fa-filter` → `bi bi-funnel-fill`
- [ ] Line 38: `fa-solid fa-filter` → `bi bi-funnel-fill` (button)
- [ ] Line 49: `fa-solid fa-shield-halved` → `bi bi-shield-fill`
- [ ] Line 105: `fa-solid fa-shield-halved` → `bi bi-shield-fill` (inline empty state)
- [ ] **Bonus:** Replace inline empty state (lines 102-108) with `<x-empty-state>` component

### 1.8 `notifications/index.blade.php`
- [ ] Line 13: `fa-solid fa-paper-plane` → `bi bi-send-fill`
- [ ] Line 76: `fa-solid fa-users` → `bi bi-people-fill` (in HTML)
- [ ] Line 83: `fa-solid fa-paper-plane` → `bi bi-send-fill` (Send button)
- [ ] Line 93: `fa-solid fa-clock-rotate-left` → `bi bi-clock-history`
- [ ] Line 144: `fa-solid fa-bell-slash` → `bi bi-bell-slash-fill` (inline empty state)
- [ ] Line 168 (JS): `fa-solid fa-user-check` → `bi bi-person-check-fill`
- [ ] Line 170 (JS): `fa-solid fa-users` → `bi bi-people-fill`
- [ ] Line 179 (JS): `fa-solid fa-user-check` → `bi bi-person-check-fill`
- [ ] **Bonus:** Replace inline empty state with `<x-empty-state>` component

### 1.9 `plans/index.blade.php`
- [ ] Line 8: `fa-solid fa-pen-to-square` → `bi bi-pencil-fill` (Edit Pricing button)
- [ ] Line 19: `fa-solid fa-layer-group` → `bi bi-layers-fill` (Card icon)
- [ ] Line 51: `fa-solid fa-circle-check` → `bi bi-check-circle-fill` (feature list)
- [ ] Line 58: `fa-solid fa-star` → `bi bi-star-fill` (Most Popular badge)
- [ ] Line 75: `fa-solid fa-circle-check` → `bi bi-check-circle-fill` (feature list)
- [ ] Line 98: `fa-solid fa-circle-check` → `bi bi-check-circle-fill` (feature list)
- [ ] Line 108: `fa-solid fa-pen-to-square` → `bi bi-pencil-fill` (modal header)
- [ ] Line 109: `fa-solid fa-xmark` → `bi bi-x-lg` (modal close)
- [ ] Line 163/164: `fa-solid fa-trash` → `bi bi-trash3-fill` (remove feature)
- [ ] Line 169: `fa-solid fa-plus` → `bi bi-plus-lg` (Add Feature)
- [ ] Line 181: `fa-solid fa-floppy-disk` → `bi bi-floppy-fill` (Save)
- [ ] JS line 333: `fa-solid fa-trash` → `bi bi-trash3-fill`

### 1.10 `profile/index.blade.php`
- [ ] Line 14: `fa-solid fa-circle-user` → `bi bi-person-circle`
- [ ] Line 55: `fa-solid fa-floppy-disk` → `bi bi-floppy-fill`
- [ ] Line 75: `fa-solid fa-lock` → `bi bi-lock-fill`
- [ ] Line 91, 106, 121: `fa-solid fa-eye` → `bi bi-eye-fill` (3 password toggles)
- [ ] Line 129: `fa-solid fa-key` → `bi bi-key-fill`
- [ ] **JS toggle:** `icon.classList.replace('fa-eye', 'fa-eye-slash')` → `icon.classList.replace('bi-eye-fill', 'bi-eye-slash-fill')`

### 1.11 `settings/index.blade.php`
- [ ] Line 16: `fa-solid fa-gear` → `bi bi-gear-fill`
- [ ] Line 51: `fa-solid fa-credit-card` → `bi bi-credit-card-fill`
- [ ] Line 57: `fa-solid fa-shield-halved` → `bi bi-shield-fill`
- [ ] Line 87: `fa-solid fa-envelope` → `bi bi-envelope-fill`
- [ ] Line 93: `fa-solid fa-circle-info` → `bi bi-info-circle-fill`
- [ ] Line 138: `fa-solid fa-indian-rupee-sign` → `bi bi-currency-rupee`
- [ ] Line 143: `fa-solid fa-layer-group` → `bi bi-layers-fill`
- [ ] Line 171: `fa-solid fa-floppy-disk` → `bi bi-floppy-fill`

### 1.12 `auth/login.blade.php`
- [ ] Check all icons and replace FA with BI

---

## PHASE 2 — Plans Page Redesign (Hospital Admin Style)
**Priority: 🟠 High | Estimated: 3-4 hours**

Plans page abhi SA-specific custom CSS use kar rahi hai jo hospital admin se match nahi karti. Puri page hospital admin design system pe move karni hai.

### 2.1 Button Style Fix
- [ ] `sa-premium-btn sa-premium-btn-primary` → `hms-btn hms-btn-primary`
- [ ] `sa-premium-btn sa-premium-btn-secondary` → `hms-btn hms-btn-outline`

### 2.2 Banner Card Fix
- [ ] `sa-premium-card` + `sa-premium-card-header` → standard `hms-card` + `hms-card-header` structure
- [ ] `sa-premium-card-icon` → `hms-stat-icon hsi-blue` style icon box

### 2.3 Plan Cards
- [ ] `.sa-plan-card` — Already has good design but:
  - Remove `box-shadow` (hospital uses none)
  - Border style align with `hms-card` border (`1px solid rgba(27,79,114,.1)`)
- [ ] `.sa-plan-card-popular` — Keep highlight border, adjust to `border-color: #1B4F72`
- [ ] Feature checkmark: `fa-solid fa-circle-check` → `bi bi-check-circle-fill`

### 2.4 Modal Redesign
- [ ] `.sa-modal` — Keep native `<dialog>` but style like hospital admin modal pattern:
  - Use `hms-card` style (no border-radius overrides)
  - Header: match `hms-card-header` style
  - Footer buttons: `hms-btn` classes
- [ ] `.sa-modal-close` → align with hospital admin close button style

---

## PHASE 3 — Page-Specific Design Polish
**Priority: 🟡 Medium | Estimated: 2-3 hours**

### 3.1 Dashboard (`dashboard.blade.php`)
- [ ] Verify all stat card icons are BI (already partially done)
- [ ] Chart card headers — verify icon alignment
- [ ] Recent activity table — row hover, badge styles
- [ ] Empty states — use `<x-empty-state>` component

### 3.2 Payments (`payments/index.blade.php`)
- [ ] Stat cards: verify `flex-direction: row`, colored icon boxes visible
- [ ] Filter card: match hospital admin filter style exactly
- [ ] Table: verify `.hms-table` proper header/row styling

### 3.3 Subscriptions (`subscriptions/index.blade.php`)
- [ ] Same as payments — stat card layout verify
- [ ] Table structure align

### 3.4 Audit Logs (`audit-logs/index.blade.php`)
- [ ] Replace inline `<td colspan>` empty state with `<x-empty-state>` component
- [ ] Pagination styling check

### 3.5 Notifications (`notifications/index.blade.php`)
- [ ] Compose form — verify all form classes match hospital admin (`hms-form-group`, `hms-label`, `hms-input`)
- [ ] Replace inline empty state with `<x-empty-state>` component

### 3.6 Profile (`profile/index.blade.php`)
- [ ] Password toggle JS fix (after Phase 1 icon change)
- [ ] Form layout matches hospital admin settings pages

### 3.7 Settings (`settings/index.blade.php`)
- [ ] Alert components: `hms-alert hms-alert-warning` and `hms-alert hms-alert-info` — verify these CSS classes exist in SA layout
- [ ] Form grid layout match

---

## PHASE 4 — Layout & Sidebar Final Polish
**Priority: 🟡 Medium | Estimated: 1-2 hours**

### 4.1 Sidebar (`superadmin/layouts/app.blade.php`)
- [ ] Verify all sidebar nav icons are BI (already done in previous session)
- [ ] MANAGEMENT group items — verify icons look correct in browser
- [ ] SYSTEM group — verify Profile and Settings icons
- [ ] Logout button — verify red style working
- [ ] Sidebar scrolls if nav items overflow

### 4.2 Navbar
- [ ] Hamburger icon: verify `bi bi-list` working
- [ ] Logo/brand icon: `bi bi-eye-fill` verify
- [ ] Super Admin dropdown: verify BI icons
- [ ] Dropdown: Profile link → `route('superadmin.profile.index')` verify

### 4.3 Page Header
- [ ] `@section('page-header')` rendering — check font size matches hospital admin
- [ ] `@section('page-actions')` buttons alignment — right side
- [ ] Breadcrumb — currently not present in SA (hospital has it) — add if needed

---

## PHASE 5 — Auth Login Page
**Priority: 🟢 Low | Estimated: 1 hour**

### 5.1 `auth/login.blade.php`
- [ ] Audit current design
- [ ] Replace FA icons with BI
- [ ] Verify login card matches hospital admin login style
- [ ] Eye toggle for password field — BI icons

---

## PHASE 6 — CSS Cleanup (`superadmin.css`)
**Priority: 🟢 Low | Estimated: 1 hour**

- [ ] Remove any remaining `sa-premium-btn` CSS if Phase 2 complete
- [ ] Remove `sa-premium-card` CSS if Phase 2 complete
- [ ] Keep only SA-specific overrides that are truly needed
- [ ] Verify no duplicate CSS with `hospital.css` or `design-system.css`
- [ ] Check `.hms-alert` classes exist (warning, info) — add to SA CSS if missing

---

## DEVELOPMENT ORDER (Recommended)

```
Phase 1 → Phase 2 → Phase 3 → Phase 4 → Phase 5 → Phase 6
  Icon     Plans     Polish   Layout    Login    CSS Clean
  Fix      Page      Pages    Sidebar   Page     up
```

**Phase 1 sabse pehle** kyunki icon mismatches har page pe dikh rahi hain aur screenshots mein yahi sabse badi problem hai.

---

## QUICK TEST CHECKLIST (Each Phase ke baad)

- [ ] Hospitals list page — icons visible, table correct
- [ ] Hospital detail page — all buttons have icons
- [ ] Plans page — plan cards, modal, feature list all BI icons
- [ ] Payments page — stat cards horizontal with colored icons
- [ ] Subscriptions page — same as payments
- [ ] Audit logs — filter, table, empty state
- [ ] Notifications — compose form, history table
- [ ] Profile — password toggles work
- [ ] Settings — all form sections visible
- [ ] Login page — eye icon on password

---

## FILES REFERENCE

```
resources/views/superadmin/
├── layouts/
│   └── app.blade.php          ← Sidebar + Navbar (Phase 4)
├── auth/
│   └── login.blade.php        ← Phase 5
├── dashboard.blade.php         ← Phase 3.1
├── tenants/
│   ├── index.blade.php        ← Phase 1.1
│   ├── show.blade.php         ← Phase 1.2
│   ├── create.blade.php       ← Phase 1.3
│   └── edit.blade.php         ← Phase 1.4
├── payments/
│   └── index.blade.php        ← Phase 1.5 + 3.2
├── subscriptions/
│   └── index.blade.php        ← Phase 1.6 + 3.3
├── audit-logs/
│   └── index.blade.php        ← Phase 1.7 + 3.4
├── notifications/
│   └── index.blade.php        ← Phase 1.8 + 3.5
├── plans/
│   └── index.blade.php        ← Phase 1.9 + Phase 2
├── profile/
│   └── index.blade.php        ← Phase 1.10 + 3.6
└── settings/
    └── index.blade.php        ← Phase 1.11 + 3.7
```

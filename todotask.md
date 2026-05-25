# HMS SaaS — SuperAdmin Panel: Todo Task List

> **Date:** 2026-05-23  
> **Goal:** SuperAdmin panel fully banana + design/theme ko hospital panel ke saath align karna

---

## Overview

### Kya Hai Project?
Eye HMS SaaS ek multi-tenant Hospital Management System hai jisme:
- **Platform (Landing):** Hospital registration, pricing, unified login
- **Hospital Panel:** OPD, Clinical, OT, Medicines, Reports, Settings (slug-based routing)
- **SuperAdmin Panel:** Platform management — hospitals, subscriptions, payments, audit, notifications

### SuperAdmin Panel — Current State

| Feature | Status | Notes |
|---|---|---|
| Layout (Navbar + Sidebar) | ✅ Done | Dark theme (`#0D2137` sidebar) |
| Login / Logout | ✅ Done | `/superadmin/login` |
| Dashboard | ✅ Done | Stats + 4 charts |
| Hospitals CRUD | ✅ Done | List, Create, Edit, Show, Activate, Suspend, Extend, Reactivate |
| Subscriptions List | ✅ Done | Basic list, no management |
| Payments List | ✅ Done | List + offline payment + invoice |
| Audit Logs | ✅ Done | Basic list |
| Notifications | ✅ Done | List + send |
| Settings | ✅ Done | View + update |

### Design Gap Analysis

**Hospital Panel Theme:**
- Sidebar: `rgba(255,255,255,0.78)` — white glassmorphism + `backdrop-filter: blur(12px)`
- Primary color: `#1B4F72` (Deep Healthcare Blue)
- Background: `#ffffff`
- Sidebar brand: `#1B4F72` dark strip
- Nav items: hover = solid `#1B4F72` fill

**SuperAdmin Panel Theme:**
- Sidebar: `#0D2137` — dark navy (different!)
- Main BG: `#F0F4F8` (cool gray)
- Page content uses `hms-card/hms-table` (design-system.css) — inconsistent with `sa-premium-*` used in dashboard
- Missing: `SweetAlert2`, `Bootstrap Icons`, `premium-theme.css`

---

## Task List

### Phase 1 — Design & Theme Alignment (Priority: HIGH)

- [x] **T1.1** — SuperAdmin layout (`superadmin/layouts/app.blade.php`) mein hospital panel jaise theme adapt karo: ✅ DONE
  - Sidebar: dark navy → white glassmorphism (`rgba(255,255,255,0.78)` + blur)
  - Navbar: dark gradient → white glass (same as `hms-navbar`)
  - Nav link hover/active: hospital panel jaisa style
  - `SweetAlert2` add karo toast notifications ke liye
  - `Bootstrap Icons` add karo (hospital panel use karta hai)
  - `premium-theme.css` add karo
  - Navbar user dropdown add kiya (with profile + settings + logout)
  - Sidebar mein Plans + Profile links add kiye

- [x] **T1.2** — `public/css/superadmin.css` update karo: ✅ DONE
  - Sidebar CSS: dark `#0D2137` → white glassmorphism `rgba(255,255,255,0.78)` + blur
  - Navbar CSS: dark gradient → white glass
  - Nav item `.active` state: hospital panel jaise (left-bar indicator)
  - Nav hover: solid primary fill, white text
  - Background: `#F0F4F8` → `#ffffff`
  - SweetAlert2 toast CSS

- [ ] **T1.3** — Tenants/Subscriptions/Payments/Audit/Notifications/Settings pages:
  - Content mein `hms-card` → `sa-premium-card` mein migrate karo ya design consistent karo
  - Tables `sa-premium-table` class use karein
  - Flash messages `SweetAlert2` toast use karein (layout mein add hoga T1.1 mein)

---

### Phase 2 — Missing Features / Enhancements (Priority: HIGH)

- [x] **T2.1** — **Plan Management** (NEW PAGE): ✅ DONE
  - Route: `/superadmin/plans` ✅
  - Controller: `SuperAdmin\PlanController` ✅
  - View: `superadmin/plans/index.blade.php` — 3 plan cards (Monthly/Quarterly/Yearly) ✅
  - Features: Live pricing display, Edit Pricing modal (price/discounts/trial/grace/features list) ✅
  - Sidebar mein "Plans" link added ✅
  - Data stored in `platform_settings` (consistent with existing architecture)

- [ ] **T2.2** — **Subscriptions Page Enhancement:**
  - Subscription extend/cancel actions
  - Filter by status (active/expired/canceled)
  - Filter by hospital
  - Manual subscription assign karo to hospital

- [ ] **T2.3** — **Hospital Detail Page Enhancement** (`tenants/show.blade.php`):
  - Subscription history tab
  - Payment history tab
  - Audit log tab (hospital-specific)
  - Login-as-hospital feature (impersonation / magic link)
  - Quick stats: total patients, total OT cases

- [ ] **T2.4** — **Revenue Reports** (NEW PAGE):
  - Route: `/superadmin/reports`
  - Monthly/Quarterly/Yearly revenue breakdown
  - Export to CSV/Excel
  - Per-hospital revenue

- [x] **T2.5** — **SuperAdmin Profile** (NEW PAGE): ✅ DONE
  - Route: `/superadmin/profile` ✅
  - Controller: `SuperAdmin\ProfileController` ✅
  - View: `superadmin/profile/index.blade.php` ✅
  - Features: Name/email update, password change (with current password verify), show/hide toggle ✅
  - Navbar user dropdown added (Profile → Settings → Logout) ✅

- [ ] **T2.6** — **Notification Templates:**
  - Pre-built templates: Trial Expiry, Subscription Renewal, Welcome
  - Send to all / specific hospitals
  - Email + in-app notification

---

### Phase 3 — Polish & QoL (Priority: MEDIUM)

- [ ] **T3.1** — Responsive design check karo SuperAdmin panel:
  - Mobile sidebar toggle properly kaam kare
  - Stats grid proper collapse ho
  - Tables horizontally scroll karein

- [ ] **T3.2** — Pagination styling:
  - Custom pagination view `superadmin.pagination` banao
  - Hospital panel jaisi styling

- [ ] **T3.3** — Empty states:
  - Sab list pages pe `x-empty-state` component use karo consistently

- [ ] **T3.4** — Audit Log Enhancement:
  - Filter by hospital, action type, date range
  - Export audit log to CSV

- [ ] **T3.5** — Dashboard Enhancement:
  - "Quick Actions" section add karo (Add Hospital, Send Notification, View Expiring)
  - Expiring-this-week hospitals ki list dashboard pe show karo

---

## Design Token Reference

| Token | Hospital Panel | SuperAdmin Panel (After Update) |
|---|---|---|
| Sidebar BG | `rgba(255,255,255,0.78)` + blur | Same → adopt |
| Sidebar Brand | `#1B4F72` strip | Keep as is |
| Primary | `#1B4F72` | Same |
| Accent | `#27AE60` | Same |
| Body BG | `#ffffff` | `#ffffff` (change from `#F0F4F8`) |
| Navbar BG | `rgba(255,255,255,0.78)` + blur | Same → adopt |
| Active Nav | `rgba(27,79,114,0.12)` + left border | Same → adopt |
| Hover Nav | Solid `#1B4F72` fill, white text | Same → adopt |

---

## File Locations Reference

| File | Purpose |
|---|---|
| `resources/views/superadmin/layouts/app.blade.php` | SA layout shell |
| `public/css/superadmin.css` | SA-specific styles |
| `public/css/design-system.css` | Shared components (`hms-card`, `hms-table`, etc.) |
| `public/css/hospital.css` | Hospital shell styles |
| `public/css/premium-theme.css` | Premium card/table styles |
| `app/Http/Controllers/SuperAdmin/` | SA controllers |
| `routes/web.php` | SA routes (prefix: `/superadmin`) |
| `app/Models/Platform/` | Platform models (Tenant, Subscription, Payment, etc.) |

---

## Start Order (Recommended)

1. **T1.1 + T1.2** — Layout + CSS theme update (foundation)
2. **T1.3** — Page content consistency
3. **T2.5** — SuperAdmin profile + navbar dropdown
4. **T2.1** — Plan Management (new feature)
5. **T2.3** — Hospital detail page enhancement
6. **T2.2** — Subscriptions enhancement
7. **T2.4** — Revenue reports
8. **T3.x** — Polish tasks

---

*Note: DOCX requirements file (`HMS_SaaS_Requirements_v3_Full_final.docx`) binary hai, directly read nahi ho sakta. Yeh todo list codebase analysis + common HMS SaaS patterns se banai gayi hai. Agar docx mein additional requirements hain toh us hisaab se update karo.*

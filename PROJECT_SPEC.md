# Semrushtoolz.com — Complete Project Specification

> **Domain:** [semrushtoolz.com](https://semrushtoolz.com)  
> **Type:** Group Buy Membership Platform (Custom Build)  
> **Last Updated:** June 15, 2026  
> **Status:** Planning / Pre-Development Reference Document

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Products & Plans](#2-products--plans)
3. [Access Methods](#3-access-methods)
4. [Tech Stack (Recommended)](#4-tech-stack-recommended)
5. [Public Website Pages](#5-public-website-pages)
6. [Authentication Pages](#6-authentication-pages)
7. [User Dashboard — Full Feature List](#7-user-dashboard--full-feature-list)
8. [Admin Panel — Full Feature List](#8-admin-panel--full-feature-list)
9. [Payment System — Detailed Flow](#9-payment-system--detailed-flow)
10. [Email Verification Cron (UPI Auto-Verify)](#10-email-verification-cron-upi-auto-verify)
11. [Subscription & Access Engine](#11-subscription--access-engine)
12. [Affiliate System](#12-affiliate-system)
13. [Notification System](#13-notification-system)
14. [Security & Abuse Prevention](#14-security--abuse-prevention)
15. [Database Schema (Core Entities)](#15-database-schema-core-entities)
16. [API Modules Overview](#16-api-modules-overview)
17. [Background Jobs & Cron Tasks](#17-background-jobs--cron-tasks)
18. [Email Templates Required](#18-email-templates-required)
19. [Development Phases](#19-development-phases)
20. [Future Scope (Out of MVP)](#20-future-scope-out-of-mvp)
21. [Open Decisions / Config Needed](#21-open-decisions--config-needed)

---

## 1. Project Overview

Semrushtoolz.com is a **fully custom group buy membership platform** where users purchase shared access to premium SEO tools at reduced prices.

### What We Sell (ONLY These — Nothing Else)

| # | Product Name | Tools Included | Access Type |
|---|-------------|----------------|-------------|
| 1 | **Semrush Plan** | Semrush only | Cloud System (One-Click) |
| 2 | **Ahrefs Plan** | Ahrefs only | Cloud System (One-Click) |
| 3 | **Combo Plan** | Semrush + Ahrefs | Cloud System (One-Click) |
| 4 | **Ahrefs Bar** | Ahrefs (via browser bar) | Browser Extension |

### Core Principles

- **One-click access** for Semrush, Ahrefs, and Combo — no extension required (cloud-based proxy/session system)
- **Ahrefs Bar only** requires a browser extension
- **Fully custom** user dashboard + admin panel (no WordPress, no off-the-shelf panel)
- **Same design language** across public site, user dashboard, and admin panel (based on current `index.html` design)
- **Three payment methods:** PayPal, Custom UPI (email-verified auto), Offline Manual Payment

---

## 2. Products & Plans

### 2.1 Plan Structure (Per Product)

Each of the 4 products above will have billing duration options:

| Duration | Example Label |
|----------|----------------|
| Monthly | 1 Month |
| Quarterly | 3 Months (optional discount) |
| Yearly | 12 Months (optional discount) |

> Admin can enable/disable durations and set prices individually per product per duration.

### 2.2 Plan Fields (Admin Configurable)

- Plan name (e.g., "Semrush Monthly")
- Product type: `semrush` | `ahrefs` | `combo` | `ahrefs_bar`
- Price (INR for UPI/Offline, USD for PayPal — or single currency with conversion)
- Duration in days (30 / 90 / 365)
- Description & feature bullet points
- Badge label (e.g., "Best Value", "Popular") — optional
- Active / Inactive status
- Sort order on shop page
- Max concurrent sessions per user (default: 1)
- Trial allowed: yes/no

### 2.3 Combo Plan Rules

- Combo = access to **both** Semrush and Ahrefs cloud tools
- Combo does **NOT** include Ahrefs Bar extension (separate product)
- If user has Combo + buys Ahrefs Bar separately → both work independently

### 2.4 Ahrefs Bar Plan Rules

- Separate subscription from regular Ahrefs cloud plan
- Requires extension install + activation
- Extension validates user subscription via API token
- Extension version managed from admin panel

---

## 3. Access Methods

### 3.1 Cloud System (Semrush, Ahrefs, Combo)

> **Already built:** Cloud proxy runs on **Golang** (separate service). Laravel app does NOT host the proxy — it only validates the user and calls the Go API.

```
User clicks "Access Now"
  → Laravel checks: authenticated? active subscription? tool allowed for plan?
  → Laravel sends request to Go Cloud Proxy API (user id, tool, session metadata)
  → Go service validates + approves → returns access URL / session token
  → Laravel logs session locally → redirects user to tool (new tab)
  → Go service handles: seats, sessions, proxy, timeouts, account rotation
  → On session end: Go notifies Laravel (webhook) OR Laravel polls status
```

**Laravel responsibilities (membership panel):**
- One-click "Access Now" button on Tools page and Dashboard
- Subscription & plan validation before calling Go API
- HTTP client to Go Cloud Proxy API (request + handle response/errors)
- Local session log (user_id, tool, started_at, go_session_id, status)
- Display tool status from Go API (online / maintenance / offline)
- Display seat availability from Go API (e.g., "7/15 seats in use")
- Maintenance mode toggle (Laravel flag OR delegated to Go — TBD when API spec shared)
- Error handling: subscription expired, Go rejected, seats full, service down

**Go Cloud Proxy responsibilities (existing service):**
- Actual proxy/session for Semrush & Ahrefs
- Seat management & concurrent user limits
- Account pool, rotation & health checks
- Session timeouts (idle + max duration)
- Queue when seats full
- Approve/deny access requests from Laravel

**Go API integration (details TBD — user will provide):**
- Base URL (e.g., `https://proxy.semrushtoolz.com` or internal IP)
- Auth method (API key, HMAC, internal token — TBD)
- Endpoints (expected): request access, get status, end session, seat count
- Request payload fields (user_id, tool, email, plan_type — TBD)
- Response fields (access_url, session_id, expires_at — TBD)
- Error codes (seats_full, maintenance, unauthorized — TBD)

### 3.2 Ahrefs Bar Extension

```
User installs extension → Extension reads API token from user account →
Validates subscription → Injects Ahrefs access via bar interface
```

**Features needed:**
- Extension download page with install guide (Chrome primary, Firefox optional)
- Extension activation: user copies API key from dashboard → pastes in extension
- Extension checks subscription status on each use
- Admin: upload new extension versions, force update notice
- Extension status on user dashboard: Installed / Not Installed / Outdated
- Block access if subscription expired

### 3.3 Go Cloud Proxy — Integration Notes (Pending API Spec)

| Item | Status |
|------|--------|
| Go proxy service | ✅ Already built |
| Laravel → Go API client | 🔲 To build when API spec shared |
| API documentation | 🔲 User will provide later |

**Placeholder — fill when API spec is shared:**

```
GO_PROXY_BASE_URL=
GO_PROXY_API_KEY=
GO_PROXY_TIMEOUT=30

# Expected endpoints (confirm with actual Go API):
# POST /access/request     — request tool access for user
# GET  /access/status/:id  — check session status
# DELETE /access/:id       — end session
# GET  /tools/semrush/seats — seat availability
# GET  /tools/ahrefs/seats
# GET  /health             — service health check
```

---

## 4. Tech Stack (Confirmed)

### Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    Laravel 11 App (Main)                     │
│  Public site · Auth · Dashboard · Admin · Payments · Cron   │
│  MySQL · Redis · Livewire/Blade                             │
└──────────────────────────┬──────────────────────────────────┘
                           │ HTTP API calls
                           ▼
┌─────────────────────────────────────────────────────────────┐
│              Go Cloud Proxy (Already Built)                  │
│  Semrush/Ahrefs one-click access · Seats · Sessions         │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│              Ahrefs Bar Extension (Browser)                  │
│  Calls Laravel API for subscription validation              │
└─────────────────────────────────────────────────────────────┘
```

| Layer | Technology | Notes |
|-------|-----------|-------|
| **Main App (Membership Panel)** | **Laravel 11 + Livewire 3** | Public site, auth, dashboard, admin, payments |
| **Cloud Tool Access** | **Golang (existing)** | Laravel calls Go API — proxy NOT rebuilt |
| **Database** | MySQL | Users, orders, subscriptions, sessions log |
| **Cache / Sessions / Queue** | Redis | Sessions, cache, job queue |
| **Frontend** | Blade + Livewire 3 | Same design as `index.html`; SEO-friendly SSR |
| **Auth** | Laravel Sanctum / sessions | bcrypt passwords, 2FA optional |
| **PayPal** | PayPal REST API + webhooks | Payment confirmation |
| **UPI Auto-Verify** | PHP IMAP + Laravel Scheduler | Cron every 30 sec |
| **Email Sending** | SMTP / Resend | Transactional emails |
| **Ahrefs Bar Extension** | Chrome extension | Validates via Laravel API |
| **Hosting** | VPS + Cloudflare CDN | Laravel + Go on same or separate VPS |
| **Cron** | Laravel Scheduler | UPI verify, expiry, reminders |

### Why this split works

- **Laravel** = everything business (users, money, admin, affiliate, SEO pages)
- **Go (existing)** = everything performance-critical (proxy, seats, tool sessions)
- No need to rebuild cloud proxy — only integrate via API
- Best of both: fast MVP on Laravel + fast tool access on Go

---

## 5. Public Website Pages

### 5.1 Homepage (`/`)

**Purpose:** Convert visitors into registered/paying users.

**Sections:**
- Hero section
  - Headline: value proposition (e.g., "Premium SEO Tools at Fraction of the Cost")
  - CTA buttons: "View Plans" + "Get Started Free" (signup)
  - Trust badges (users count, uptime, support)
- Products showcase (4 cards)
  - Semrush Plan
  - Ahrefs Plan
  - Combo Plan (highlighted as Best Value)
  - Ahrefs Bar
  - Each card: price starting from, key features, "Subscribe" CTA
- How It Works (3 steps)
  1. Choose a plan
  2. Pay via PayPal / UPI / Offline
  3. One-click access instantly
- Features section
  - One-click cloud access
  - No extension needed (except Ahrefs Bar)
  - 24/7 access
  - Affordable pricing
- Pricing table (all plans × all durations)
- FAQ section (accordion)
- Testimonials (optional, admin-managed)
- Footer
  - Links: Terms, Privacy, Refund Policy, Contact, Login, Signup
  - Social links
  - Copyright

**Admin manageable:** Hero text, product cards, FAQ, testimonials, banners/announcements.

---

### 5.2 Pricing Page (`/pricing`)

- Full comparison table: all 4 products × all durations
- Feature comparison per plan
- CTA per plan → redirects to checkout (login required if not authenticated)
- Coupon code input (optional pre-checkout)

---

### 5.3 How It Works Page (`/how-it-works`)

- Detailed guide: signup → payment → access
- Separate section for cloud tools vs Ahrefs Bar extension
- Screenshots / illustrations
- Troubleshooting basics

---

### 5.4 Tools Info Page (`/tools`) — Public marketing page

- Semrush features overview
- Ahrefs features overview
- Why group buy model
- Not the same as dashboard "My Tools" page

---

### 5.5 About Page (`/about`)

- About Semrushtoolz
- Mission / trust building

---

### 5.6 Contact Page (`/contact`)

- Contact form (name, email, subject, message)
- WhatsApp / Telegram / Email links
- Creates support ticket in system OR sends email to admin

---

### 5.7 Legal Pages

| Page | URL |
|------|-----|
| Terms of Service | `/terms` |
| Privacy Policy | `/privacy` |
| Refund Policy | `/refund-policy` |

> Admin can edit content via CMS or static pages.

---

### 5.8 Blog / Announcements (Optional — Phase 2)

- Admin posts: new features, maintenance notices, offers
- URL: `/blog` and `/blog/{slug}`

---

### 5.9 Status Page (Optional)

- Tool uptime status (Semrush cloud, Ahrefs cloud)
- Public maintenance announcements

---

## 6. Authentication Pages

### 6.1 Login Page (`/login`)

**Fields:**
- Email
- Password
- Remember me checkbox
- Forgot password link

**Features:**
- Email + password authentication
- Show/hide password toggle
- Rate limiting (max 5 failed attempts → 15 min lockout)
- Redirect to dashboard after login
- Redirect to checkout if user came from a plan CTA
- Social login: NOT in MVP (future scope)
- 2FA prompt if enabled on account
- "Don't have an account? Sign up" link
- Error messages: invalid credentials, account suspended, email not verified

**Design:** Same brand design system (Inter font, orange accent, clean card layout).

---

### 6.2 Signup Page (`/register`)

**Fields:**
- Full name
- Email
- Password (with strength indicator)
- Confirm password
- Referral code (optional — auto-fill from `?ref=CODE` URL param)
- Terms & conditions acceptance checkbox

**Features:**
- Email uniqueness validation
- Password rules: min 8 chars, 1 uppercase, 1 number (configurable)
- Email verification (send verification link on signup)
- Account created but limited access until email verified (configurable)
- Auto-generate affiliate referral code for new user
- Welcome email sent on successful registration
- "Already have an account? Login" link
- CAPTCHA / honeypot spam protection

---

### 6.3 Forgot Password (`/forgot-password`)

- Email input → send reset link
- Reset link expires in 1 hour
- Email template with secure token link

---

### 6.4 Reset Password (`/reset-password?token=...`)

- New password + confirm password
- Token validation (single use, expiry check)
- Success → redirect to login

---

### 6.5 Email Verification (`/verify-email?token=...`)

- Verify token on click
- Show success/error page
- Resend verification email option (from dashboard if not verified)

---

### 6.6 2FA Verification Page (`/2fa`) — if enabled

- TOTP 6-digit code input (Google Authenticator)
- Backup codes option
- "Trust this device for 30 days" checkbox

---

## 7. User Dashboard — Full Feature List

> Design: Based on current `index.html` layout — sidebar navigation, header with search + notifications, card-based content area.

**Base URL:** `/dashboard` (all sub-pages under this)

---

### 7.1 Dashboard Home (`/dashboard`)

| Feature | Details |
|---------|---------|
| Welcome greeting | "Welcome back, {name}" |
| Active plan card | Current plan name (Semrush / Ahrefs / Combo / Ahrefs Bar) |
| Expiry card | Expiry date + days remaining |
| Status card | Active / Expired / Suspended / Pending |
| Quick access tools | Cards for each tool user has access to |
| Access Now button | One-click for cloud tools |
| Recent activity | Last 10 actions (login, tool access, payment) |
| Announcement banner | Admin-managed promo/maintenance banner |
| Expiry warning | Show alert if < 7 days remaining |
| Email verification alert | If email not verified |

---

### 7.2 Shop / Plans Page (`/dashboard/shop`)

| Feature | Details |
|---------|---------|
| Current plan display | What user already has |
| 4 product cards | Semrush, Ahrefs, Combo, Ahrefs Bar |
| Duration selector | Monthly / Quarterly / Yearly per product |
| Price display | Per duration |
| Feature list per plan | Bullet points |
| Combo highlighted | "Best Value" badge |
| Subscribe button | → Checkout flow |
| Upgrade option | If user has lower plan, show upgrade with price difference |
| Cannot buy duplicate | If already active, show "Already Subscribed" or "Extend" |
| Coupon code input | Apply discount before checkout |

---

### 7.3 Checkout Page (`/dashboard/checkout`)

| Feature | Details |
|---------|---------|
| Order summary | Plan name, duration, price, discount, total |
| Payment method selector | PayPal / UPI (Auto) / Offline (Manual) |
| Terms acceptance | Required checkbox |
| Place Order button | Creates pending order |

**After order created → redirect based on payment method** (see Section 9).

---

### 7.4 My Tools Page (`/dashboard/tools`)

| Feature | Details |
|---------|---------|
| Semrush card | Show only if user has Semrush or Combo plan |
| Ahrefs card | Show only if user has Ahrefs or Combo plan |
| Ahrefs Bar card | Show only if user has Ahrefs Bar plan |
| Tool status badge | Online / Offline / Maintenance |
| Seat availability | "X/Y seats in use" (real-time) |
| Access Now button | Cloud tools — opens session in new tab |
| Queue indicator | If seats full, show queue position |
| Session timer | If active session, show remaining time |
| Last accessed | Timestamp of last tool use |
| Usage instructions | Collapsible how-to per tool |
| Ahrefs Bar section | Extension install status + download link + API key |

---

### 7.5 Orders Page (`/dashboard/orders`)

| Feature | Details |
|---------|---------|
| Order list | All orders with: date, plan, amount, payment method, status |
| Order status | Pending / Awaiting Payment / Verifying / Completed / Failed / Refunded / Cancelled |
| Order detail view | Full breakdown, payment proof upload (for offline) |
| Invoice download | PDF invoice for completed orders |
| Retry payment | For failed/pending orders |
| Upload payment proof | For offline method — screenshot upload |
| Filter & search | By status, date range |

---

### 7.6 Billing / Payment History (`/dashboard/billing`)

| Feature | Details |
|---------|---------|
| Payment history | All successful payments |
| Active subscriptions | Current plan details |
| Renewal date | Next billing date (if auto-renew enabled) |
| Auto-renew toggle | On/off per subscription (PayPal only in MVP) |
| Extend subscription | Quick link to shop |

---

### 7.7 Profile Page (`/dashboard/profile`)

| Feature | Details |
|---------|---------|
| Edit name | Full name update |
| Edit email | With re-verification |
| Edit phone | Optional field |
| Change password | Current + new + confirm |
| Avatar | Initials-based (no upload in MVP) |
| Account created date | Display only |
| Referral code | Display user's own code |
| Delete account | Request account deletion (with confirmation) |

---

### 7.8 Affiliate Page (`/dashboard/affiliates`)

| Feature | Details |
|---------|---------|
| Referral link | Unique URL: `semrushtoolz.com/register?ref={code}` |
| Referral code | Copy button |
| Commission rate | Display current rate (e.g., 20%) |
| Stats cards | Total referrals, successful conversions, total earned, pending payout |
| Referral table | List of referred users (name masked), date, plan purchased, commission |
| Earnings table | Date, amount, status (Pending / Approved / Paid) |
| Payout request | Request withdrawal when balance ≥ minimum threshold |
| Payout method | UPI ID / PayPal email / Bank details (user saves once) |
| Payout history | Past payout requests and status |
| Marketing tips | Pre-written share text templates |

---

### 7.9 Extensions Page (`/dashboard/extensions`)

> Primarily for Ahrefs Bar users, but page visible to all.

| Feature | Details |
|---------|---------|
| Ahrefs Bar extension | Download link (Chrome .crx or Chrome Web Store) |
| Install guide | Step-by-step with screenshots |
| API activation key | User's unique key for extension |
| Copy API key button | One-click copy |
| Extension status | Detected / Not detected (check via API ping) |
| Version info | Current version vs latest |
| Update notice | If outdated, prompt to update |
| Troubleshooting FAQ | Common extension issues |

---

### 7.10 Support Page (`/dashboard/support`)

| Feature | Details |
|---------|---------|
| Create ticket | Subject, category, message, attachment |
| Ticket categories | Billing, Tool Access, Extension, Account, Other |
| My tickets list | Status: Open / Replied / Closed |
| Ticket detail | Conversation thread with admin replies |
| FAQ accordion | Common questions |
| Contact info | WhatsApp, Telegram, email links |

---

### 7.11 Settings Page (`/dashboard/settings`)

| Feature | Details |
|---------|---------|
| Email notifications toggle | On/off |
| Expiry reminder toggle | On/off |
| Login alert toggle | On/off |
| 2FA setup | Enable/disable TOTP |
| Language | English (Hindi — future) |
| Theme | Light / Dark |
| Timezone | Dropdown |
| Active sessions | List devices, logout specific session |
| Logout all devices | Button |

---

### 7.12 Notifications (Header Component)

| Feature | Details |
|---------|---------|
| Bell icon with unread count | In header |
| Notification dropdown | Last 20 notifications |
| Notification types | Payment, expiry, tool status, affiliate, system |
| Mark as read | Single or all |
| Click action | Navigate to relevant page |

---

### 7.13 Global Header Features

| Feature | Details |
|---------|---------|
| Search bar | Search tools, pages, FAQ |
| Notification bell | See 7.12 |
| Help icon | Link to support |
| User menu dropdown | Profile, Settings, Logout |
| Subscription badge | Show plan name in header |

---

### 7.14 Global Sidebar Navigation

| Nav Item | Page |
|----------|------|
| Dashboard | Home |
| Shop | Plans & purchase |
| My Tools | Tool access |
| Orders | Order history |
| Extensions | Ahrefs Bar |
| Affiliates | Referral program |
| Support | Tickets & FAQ |
| Profile | Account info |
| Settings | Preferences |
| Logout | Sign out |

---

## 8. Admin Panel — Full Feature List

> Design: Same UI system as user dashboard, different sidebar, admin-only routes.  
> **Base URL:** `/admin`

---

### 8.1 Admin Authentication

| Feature | Details |
|---------|---------|
| Separate admin login | `/admin/login` |
| Admin roles | Super Admin, Support Admin, Finance Admin |
| Role permissions | See section 8.18 |
| Admin 2FA | Required for Super Admin |
| Admin activity log | Every action logged |

---

### 8.2 Admin Dashboard Home (`/admin`)

| Feature | Details |
|---------|---------|
| Stats cards | Total users, active subscriptions, revenue today/week/month, open tickets |
| Revenue chart | Last 30 days |
| New signups chart | Last 30 days |
| Active sessions | Live count per tool |
| Recent orders | Last 10 orders |
| Pending actions | Awaiting UPI verification, offline payment proofs, payout requests |
| System health | Email cron status, cloud account health, PayPal webhook status |

---

### 8.3 User Management (`/admin/users`)

| Feature | Details |
|---------|---------|
| User list | Search, filter (active/expired/suspended), sort, pagination |
| User detail view | Full profile, subscriptions, orders, sessions, login history |
| Create user manually | Admin can create account |
| Edit user | Name, email, phone |
| Suspend / Unsuspend user | With reason |
| Ban user | Permanent, with reason |
| Assign plan manually | Grant subscription without payment |
| Extend subscription | Add days to expiry |
| Revoke subscription | Remove access immediately |
| Reset password | Send reset link or set manually |
| Force logout | Kill all active sessions |
| View login history | IP, device, timestamp |
| View tool access history | Tool, duration, IP |
| Internal notes | Admin-only notes on user |
| Impersonate user | Login as user for support (log this action) |

---

### 8.4 Plans & Products Management (`/admin/plans`)

| Feature | Details |
|---------|---------|
| Plan list | All plans with status, price, duration |
| Create plan | Name, product type, price, duration, description, features |
| Edit plan | All fields |
| Enable / Disable plan | Toggle visibility in shop |
| Delete plan | Soft delete (keep for order history) |
| Pricing per duration | Manage monthly/quarterly/yearly prices per product |
| Plan features editor | Bullet points list |
| Badge label | "Best Value", "Popular", etc. |
| Sort order | Drag or number for shop display |

---

### 8.5 Tools & Cloud Management (`/admin/tools`)

> Cloud accounts, seats, and proxy config live in the **Go service**. Laravel admin either proxies Go admin API or shows read-only status — depends on Go API capabilities (TBD).

| Feature | Details | Owner |
|---------|---------|-------|
| Tool status overview | Semrush / Ahrefs online or offline | Go API → Laravel display |
| Seat usage | Live seat count per tool | Go API → Laravel display |
| Active sessions list | Who is using which tool | Go API → Laravel display |
| Force kill session | Kick user from tool session | Go API call from Laravel admin |
| Maintenance mode | Disable user access per tool | Go API or Laravel flag (TBD) |
| Session logs | Past sessions with duration, IP | Laravel local log + Go data |
| Cloud account pool | Add/edit/disable premium accounts | **Go service** (may expose admin API) |
| Health check | Account cookie/session validation | **Go service** |
| Go service health | Is proxy reachable? | Laravel pings `GET /health` |

---

### 8.6 Seat & Session Management (`/admin/sessions`)

| Feature | Details |
|---------|---------|
| Live sessions dashboard | Real-time: who is using which tool |
| Seat usage per tool | X/Y seats in use per tool per account |
| Queue management | View and manage waiting users |
| Session detail | User, tool, start time, IP, duration |
| Force end session | Admin kill button |
| Session history | Filterable log |

---

### 8.7 Orders Management (`/admin/orders`)

| Feature | Details |
|---------|---------|
| All orders list | Filter by status, payment method, date, user |
| Order detail | Full order info, payment proof, verification log |
| Manually approve order | For offline payments after verification |
| Manually reject order | With reason sent to user |
| Refund order | Mark as refunded, revoke subscription |
| Add admin note | Internal note on order |
| Export orders | CSV export by date range |

---

### 8.8 Payment Management (`/admin/payments`)

#### PayPal
| Feature | Details |
|---------|---------|
| PayPal transaction log | All PayPal payments |
| Webhook event log | Payment completed, failed, refunded events |
| PayPal settings | Client ID, Secret, Webhook ID (config) |

#### UPI Auto-Verify
| Feature | Details |
|---------|---------|
| UPI settings | UPI ID, QR code image, payment email to monitor |
| Email connection settings | IMAP host, port, email, password/app-password |
| Verification log | Every cron run result: matched / no match / error |
| Pending UPI orders | Orders awaiting email verification |
| Manually approve UPI order | Override if auto-verify missed |
| Manually reject UPI order | With reason |

#### Offline Payment
| Feature | Details |
|---------|---------|
| Offline payment settings | QR code image, Binance ID, WhatsApp/contact number, instructions text |
| Pending offline orders | Awaiting proof upload or admin review |
| Review payment proof | View uploaded screenshot |
| Approve / Reject | With notification to user |

---

### 8.9 Coupon Management (`/admin/coupons`)

| Feature | Details |
|---------|---------|
| Create coupon | Code, discount type (percentage / fixed), value |
| Usage limit | Total uses allowed |
| Per-user limit | Max uses per user |
| Valid from / until | Date range |
| Applicable plans | All or specific plans |
| Enable / Disable | Toggle |
| Usage history | Who used, when, which order |

---

### 8.10 Affiliate Management (`/admin/affiliates`)

| Feature | Details |
|---------|---------|
| Affiliate settings | Commission % (default), minimum payout threshold |
| All affiliates list | User, referrals count, total earned, pending balance |
| Referral log | All referral events |
| Commission log | All commission entries with status |
| Approve commission | Manual approval if auto-approval disabled |
| Payout requests queue | Pending payout requests |
| Process payout | Mark as paid, enter transaction reference |
| Reject payout | With reason |
| Fraud flags | Same IP referrer + referred, self-referral detection |

---

### 8.11 Extension Management (`/admin/extensions`)

| Feature | Details |
|---------|---------|
| Current extension version | Version number, release date |
| Upload new version | .crx / .zip file |
| Changelog | Per version notes |
| Force update flag | Block old versions |
| Extension analytics | Active installs, version distribution |

---

### 8.12 Support Tickets (`/admin/tickets`)

| Feature | Details |
|---------|---------|
| All tickets inbox | Filter: open / replied / closed |
| Ticket detail | Full conversation |
| Reply to ticket | Admin response |
| Assign ticket | To specific admin |
| Change status | Open / Replied / Closed |
| Change priority | Low / Medium / High |
| Canned responses | Pre-written reply templates |
| Close ticket | With optional closing note |

---

### 8.13 Announcements & CMS (`/admin/cms`)

| Feature | Details |
|---------|---------|
| Homepage banner | Text, link, enable/disable |
| Dashboard announcement | Banner for logged-in users |
| FAQ management | Add / edit / delete / reorder FAQ items |
| Legal pages editor | Terms, Privacy, Refund Policy content |
| Testimonials | Add / edit / delete (optional) |
| Email templates | Edit template content (see Section 18) |

---

### 8.14 Notifications Management (`/admin/notifications`)

| Feature | Details |
|---------|---------|
| Send broadcast notification | To all users or specific group |
| Notification log | All sent notifications |

---

### 8.15 Reports & Analytics (`/admin/reports`)

| Feature | Details |
|---------|---------|
| Revenue report | By day/week/month, by payment method, by plan |
| Subscription report | Active, expired, new, churned |
| Tool usage report | Most accessed tool, peak hours, avg session duration |
| User growth report | Signups over time |
| Affiliate report | Top affiliates, commission paid |
| Export all reports | CSV download |

---

### 8.16 System Settings (`/admin/settings`)

| Feature | Details |
|---------|---------|
| Site name | Semrushtoolz |
| Site URL | semrushtoolz.com |
| Logo upload | Brand logo |
| Support email | support@semrushtoolz.com |
| Support WhatsApp / Telegram | Contact numbers/links |
| Default currency | INR / USD |
| PayPal credentials | Client ID, Secret |
| UPI payment email | Email to monitor for payments |
| IMAP credentials | For email reading cron |
| Offline payment details | QR, Binance ID, contact |
| SMTP settings | Email sending config |
| Session timeouts | Idle timeout, max session duration |
| Seat limits | Global defaults per tool |
| Maintenance mode | Whole site on/off |
| Registration enabled | On/off |
| Email verification required | On/off |
| Affiliate enabled | On/off |
| Commission rate | Default % |

---

### 8.17 Admin Logs (`/admin/logs`)

| Feature | Details |
|---------|---------|
| Admin activity log | Who did what, when |
| User login log | All login attempts |
| Payment verification log | UPI cron results |
| System error log | Application errors |
| Webhook log | PayPal webhook events |

---

### 8.18 Admin Roles & Permissions

| Permission | Super Admin | Support Admin | Finance Admin |
|-----------|-------------|---------------|---------------|
| User management | ✅ | ✅ (no ban) | ❌ |
| Plan management | ✅ | ❌ | ❌ |
| Tool/cloud management | ✅ | ❌ | ❌ |
| Orders view | ✅ | ✅ | ✅ |
| Orders approve/reject | ✅ | ❌ | ✅ |
| Refunds | ✅ | ❌ | ✅ |
| Affiliate payouts | ✅ | ❌ | ✅ |
| Support tickets | ✅ | ✅ | ❌ |
| System settings | ✅ | ❌ | ❌ |
| Reports | ✅ | ❌ | ✅ |
| Admin management | ✅ | ❌ | ❌ |

---

## 9. Payment System — Detailed Flow

### 9.1 Payment Method 1: PayPal

```
User selects plan → Checkout → Selects PayPal →
Redirected to PayPal approval → User pays →
PayPal webhook fires "PAYMENT.CAPTURE.COMPLETED" →
Backend verifies webhook signature →
Order marked COMPLETED → Subscription activated →
User notified → Redirect to dashboard
```

**Order states for PayPal:**
`PENDING` → `PROCESSING` → `COMPLETED` | `FAILED`

**Admin config needed:**
- PayPal Client ID + Secret (sandbox + live)
- Webhook URL registered in PayPal dashboard
- Currency: USD (or INR if supported)

---

### 9.2 Payment Method 2: UPI (Custom Auto-Verify)

> No payment gateway — custom built UPI flow with email-based auto verification.

```
User selects plan → Checkout → Selects UPI →
Order created (status: AWAITING_PAYMENT) →
UPI QR code displayed (dynamic or static) →
Page shows: UPI ID, exact amount, order reference number →
User pays via any UPI app →
Payment notification email arrives in monitored inbox →
Cron job (every 30 sec) reads email →
Parses: amount, UPI ref, payer info →
Matches against pending orders →
If match found → Order COMPLETED → Subscription activated →
User notified (page auto-refreshes or polling shows success)
```

**UPI Payment Page must show:**
- Order ID (unique, e.g., `STZ-20260615-0042`)
- Exact amount to pay (₹999.00 — exact match required)
- UPI QR code (generated or static)
- UPI ID text (copy button)
- Countdown timer (order expires in 30 minutes if not paid)
- "I have paid" button (optional — triggers faster check)
- Live status indicator: "Waiting for payment..." → "Verifying..." → "Success!"
- Polling: frontend checks order status every 10 seconds

**Email parsing rules (cron):**
- Connect to IMAP inbox (e.g., `payments@semrushtoolz.com`)
- Read unread emails from bank/UPI notification senders
- Parse amount (₹999.00 → 999.00)
- Parse UPI transaction reference number
- Match: amount EXACTLY equals pending order amount AND order is within 30-min window
- On match: mark order complete, mark email as read, activate subscription
- On no match: log attempt, continue
- Duplicate protection: same email not processed twice

**Order states for UPI:**
`AWAITING_PAYMENT` → `VERIFYING` → `COMPLETED` | `EXPIRED` | `FAILED`

---

### 9.3 Payment Method 3: Offline Manual Payment

```
User selects plan → Checkout → Selects Offline →
Order created (status: AWAITING_PROOF) →
Page shows:
  - QR code (static — admin uploaded)
  - Binance ID (for crypto payment)
  - WhatsApp / Telegram contact number
  - Payment instructions text
  - Exact amount + order reference to mention in payment message
User makes payment externally →
User uploads payment screenshot OR contacts admin via WhatsApp →
Admin reviews in admin panel →
Admin approves → Order COMPLETED → Subscription activated →
Admin rejects → Order REJECTED → User notified with reason
```

**Offline Payment Page must show:**
- Order ID
- Exact amount
- QR code image
- Binance ID with copy button
- WhatsApp / Telegram link (click to chat with pre-filled message including order ID)
- Upload payment proof button (image file — JPG/PNG, max 5MB)
- Status: "Awaiting Payment" → "Under Review" → "Approved" / "Rejected"
- Instructions text (admin configurable)

**Order states for Offline:**
`AWAITING_PROOF` → `UNDER_REVIEW` → `COMPLETED` | `REJECTED`

---

### 9.4 Coupon Code Flow (All Payment Methods)

```
User enters coupon at checkout → API validates →
Shows discounted price → Order created with discount recorded →
Payment flow continues with discounted amount
```

---

## 10. Email Verification Cron (UPI Auto-Verify)

### Cron Job: `upi-payment-verifier`

| Setting | Value |
|---------|-------|
| Run interval | Every 30 seconds |
| Method | IMAP email reader |
| Inbox | Configured payment email (admin settings) |
| On success | Activate subscription, notify user, mark email read |
| On failure | Log to `payment_verification_logs` table |
| Duplicate guard | Transaction ref stored — never process twice |
| Timeout | Orders expire after 30 minutes if unpaid |
| Error handling | If IMAP connection fails, log error, retry next run |
| Admin alert | Email admin if cron fails 5+ consecutive times |

### Email Parsing Patterns to Support

- Google Pay notifications
- PhonePe notifications
- Paytm notifications
- Bank SMS-to-email formats (SBI, HDFC, ICICI, etc.)
- UPI collect request confirmations

> Admin should be able to add/edit regex patterns for new email formats from admin panel.

---

## 11. Subscription & Access Engine

### 11.1 Subscription Lifecycle

```
Payment completed → Subscription record created →
start_date = now, end_date = now + plan duration →
User gets tool access based on product type →
Daily cron checks expiry →
7 days before: send reminder →
3 days before: send reminder →
1 day before: send reminder →
On expiry: subscription status = EXPIRED → Access revoked →
Grace period (optional, configurable, e.g., 1 day) →
After grace: fully blocked
```

### 11.2 Subscription States

| State | Meaning |
|-------|---------|
| `ACTIVE` | Paid and within valid period |
| `EXPIRED` | Past end date |
| `SUSPENDED` | Admin manually suspended |
| `PENDING` | Order placed, payment not confirmed |
| `CANCELLED` | User or admin cancelled |

### 11.3 Access Check Logic

```
On every tool access request:
1. Is user authenticated?
2. Does user have ACTIVE subscription for this tool?
3. Is tool in maintenance mode? → Deny with message
4. Are seats available? → If no, add to queue
5. Create session record
6. Return cloud access URL / session token
7. On session end or timeout → Release seat
```

### 11.4 Plan Stacking Rules

| Scenario | Behavior |
|----------|----------|
| User has Semrush, buys Ahrefs | Both active independently |
| User has Semrush, buys Combo | Combo replaces Semrush (extend expiry) |
| User has Combo, buys Ahrefs Bar | Both active independently |
| User extends same plan | Expiry date extended from current expiry (not from today) |

---

## 12. Affiliate System

### 12.1 How It Works

```
User A shares referral link → User B registers with ref code →
User B purchases a plan → Commission calculated →
Added to User A's pending balance →
Admin approves (or auto-approve after X days) →
User A requests payout → Admin processes → Paid
```

### 12.2 Commission Rules

| Setting | Default |
|---------|---------|
| Commission type | Percentage of order value |
| Default rate | 20% (admin configurable) |
| Minimum payout | ₹500 or $10 (configurable) |
| Auto-approve commission | After 7 days (configurable) |
| Self-referral | Blocked (same IP or same email domain) |

### 12.3 Referral Tracking

- Cookie-based: `ref` code stored in cookie for 30 days on first visit
- On registration: cookie value auto-applied as referral code
- On order completion: commission record created

---

## 13. Notification System

### 13.1 In-App Notifications

| Event | Notification |
|-------|-------------|
| Payment completed | "Your {plan} subscription is now active!" |
| Payment failed | "Payment failed for order #{id}. Please retry." |
| UPI payment received | "Payment received! Activating your plan..." |
| Subscription expiring | "Your plan expires in {X} days" |
| Subscription expired | "Your plan has expired. Renew now." |
| New tool maintenance | "Semrush is under maintenance" |
| Affiliate commission | "You earned ₹{amount} commission!" |
| Payout processed | "Your payout of ₹{amount} has been processed" |
| Ticket reply | "Admin replied to your support ticket" |
| Extension update | "New Ahrefs Bar version available" |

### 13.2 Email Notifications

All in-app notifications also sent as email (if user has email notifications enabled).

---

## 14. Security & Abuse Prevention

| Feature | Details |
|---------|---------|
| Password hashing | bcrypt |
| JWT + httpOnly cookies | No token in localStorage |
| Rate limiting | Login, API, payment endpoints |
| CSRF protection | All forms |
| Email verification | Required before full access |
| 2FA (TOTP) | Optional for users, required for admin |
| Session management | One active cloud session per user per tool |
| IP logging | All logins and tool access |
| Suspicious login alert | New device/IP email notification |
| Admin impersonation log | Full audit trail |
| Payment proof validation | Manual review for offline |
| Duplicate payment protection | Same transaction ref blocked |
| Referral fraud detection | Same IP check |
| Maintenance mode | Per tool and whole site |
| HTTPS only | Enforced |
| Input sanitization | All user inputs |
| File upload validation | Type + size check for payment proofs |

---

## 15. Database Schema (Core Entities)

```
users
  id, name, email, password_hash, phone, role, status,
  email_verified_at, referral_code, referred_by_user_id,
  two_factor_secret, created_at, updated_at

admins
  id, user_id, role (super/support/finance), permissions, created_at

plans
  id, name, product_type, price, duration_days, description,
  features (JSON), badge, sort_order, is_active, created_at

subscriptions
  id, user_id, plan_id, status, start_date, end_date,
  auto_renew, created_at, updated_at

orders
  id, user_id, plan_id, order_number, amount, discount, total,
  payment_method (paypal/upi/offline), status, coupon_id,
  payment_proof_url, admin_note, created_at, updated_at

payments
  id, order_id, method, transaction_id, amount, status,
  gateway_response (JSON), verified_at, created_at

payment_verification_logs
  id, order_id, email_subject, email_body, parsed_amount,
  parsed_ref, match_result, created_at

cloud_accounts
  id, tool (semrush/ahrefs), label, credentials (encrypted),
  proxy_url, seat_limit, is_active, priority, health_status,
  last_health_check, created_at

tool_sessions
  id, user_id, tool, cloud_account_id, status, ip_address,
  started_at, ended_at, end_reason

seat_queue
  id, user_id, tool, position, joined_at, status

coupons
  id, code, type (percent/fixed), value, usage_limit,
  per_user_limit, valid_from, valid_until, is_active

coupon_usages
  id, coupon_id, user_id, order_id, used_at

affiliate_commissions
  id, referrer_user_id, referred_user_id, order_id,
  amount, rate, status (pending/approved/paid), created_at

affiliate_payouts
  id, user_id, amount, method, account_details (encrypted),
  status, admin_note, processed_at, created_at

tickets
  id, user_id, subject, category, status, priority,
  created_at, updated_at

ticket_messages
  id, ticket_id, sender_type (user/admin), message,
  attachment_url, created_at

notifications
  id, user_id, type, title, message, is_read, action_url,
  created_at

announcements
  id, title, message, type, target (all/dashboard/homepage),
  is_active, created_at, expires_at

extension_versions
  id, version, file_url, changelog, force_update, released_at

admin_logs
  id, admin_id, action, entity_type, entity_id, details (JSON),
  ip_address, created_at

email_templates
  id, slug, subject, body_html, variables (JSON), updated_at

system_settings
  id, key, value, group, updated_at

user_sessions
  id, user_id, token_hash, ip_address, user_agent,
  last_active_at, created_at
```

---

## 16. API Modules Overview

```
Auth          POST /api/auth/register, /login, /logout, /forgot-password, /reset-password
User          GET/PUT /api/user/profile, /api/user/settings
Plans         GET /api/plans, GET /api/plans/:id
Orders        POST /api/orders, GET /api/orders, GET /api/orders/:id
Payments      POST /api/payments/paypal/create, GET /api/payments/upi/:orderId
              POST /api/payments/offline/upload-proof
              POST /api/webhooks/paypal
Tools         GET /api/tools, POST /api/tools/:tool/access, DELETE /api/tools/session
              (access endpoint internally calls Go Cloud Proxy API)

Go Proxy      POST {GO_PROXY}/access/request   — Laravel → Go (server-side only)
(client)      GET  {GO_PROXY}/tools/{tool}/seats
              DELETE {GO_PROXY}/access/{session_id}
              GET  {GO_PROXY}/health
              (exact endpoints TBD when user shares Go API spec)
Extensions    GET /api/extensions/version, POST /api/extensions/validate-key
Affiliates    GET /api/affiliates/stats, POST /api/affiliates/payout-request
Tickets       POST /api/tickets, GET /api/tickets, POST /api/tickets/:id/reply
Notifications GET /api/notifications, PUT /api/notifications/read
Coupons       POST /api/coupons/validate

Admin         All admin endpoints prefixed /api/admin/...
              (users, plans, orders, tools, sessions, affiliates, tickets, settings, reports)
```

---

## 17. Background Jobs & Cron Tasks

| Job | Interval | Purpose |
|-----|----------|---------|
| `upi-payment-verifier` | Every 30 sec | Read payment email, match & verify UPI orders |
| `subscription-expiry-check` | Daily at 00:00 | Expire subscriptions, revoke access |
| `expiry-reminder` | Daily at 09:00 | Send 7/3/1 day reminders |
| `cloud-account-health-check` | Every 15 min | Validate cloud account cookies/sessions |
| `stale-session-cleanup` | Every 5 min | Kill idle/expired tool sessions, release seats |
| `affiliate-auto-approve` | Daily | Auto-approve commissions after X days |
| `order-expiry-cleanup` | Every 5 min | Expire unpaid UPI orders after 30 min |
| `email-queue-processor` | Every 1 min | Send queued emails |
| `extension-version-check` | Daily | Flag users with outdated extension |

---

## 18. Email Templates Required

| Slug | Trigger |
|------|---------|
| `welcome` | User registers |
| `email-verification` | Registration / email change |
| `password-reset` | Forgot password |
| `order-created` | Order placed (pending payment) |
| `payment-success` | Payment confirmed, subscription active |
| `payment-failed` | Payment failed |
| `subscription-expiring` | 7/3/1 days before expiry |
| `subscription-expired` | Plan expired |
| `affiliate-commission` | Commission earned |
| `payout-processed` | Payout sent |
| `ticket-reply` | Admin replied to ticket |
| `login-alert` | New device login |
| `admin-new-order` | New offline order awaiting review |
| `admin-cron-failure` | UPI cron failed multiple times |

---

## 19. Development Phases

### Phase 1 — Foundation (Week 1–3)
- [ ] Project setup (framework, DB, Redis)
- [ ] Database migrations
- [ ] Auth system (register, login, email verify, forgot password)
- [ ] Public homepage, pricing page
- [ ] Admin login + basic admin dashboard
- [ ] Plans CRUD (admin)
- [ ] User dashboard layout (reuse index.html design)

### Phase 2 — Payments (Week 3–5)
- [ ] Shop page + checkout flow
- [ ] PayPal integration + webhook
- [ ] UPI custom flow + QR page + polling
- [ ] UPI email cron (30 sec verifier)
- [ ] Offline payment flow + proof upload
- [ ] Order management (user + admin)
- [ ] Coupon system
- [ ] Invoice generation (PDF)

### Phase 3 — Subscription & Tool Access (Week 5–7)
- [ ] Subscription engine (activate, expire, extend)
- [ ] Cloud account management (admin)
- [ ] Tool access flow (one-click)
- [ ] Seat management + session tracking
- [ ] Queue system
- [ ] Stale session cleanup cron
- [ ] Cloud account health check cron
- [ ] My Tools page (fully functional)

### Phase 4 — Ahrefs Bar Extension (Week 7–8)
- [ ] Extension development (Chrome)
- [ ] API key validation endpoint
- [ ] Extension download page
- [ ] Extension version management (admin)
- [ ] Extension status on dashboard

### Phase 5 — Affiliate System (Week 8–9)
- [ ] Referral code generation
- [ ] Referral tracking (cookie + registration)
- [ ] Commission calculation on order
- [ ] Affiliate dashboard (user)
- [ ] Payout request flow
- [ ] Affiliate management (admin)

### Phase 6 — Support & Notifications (Week 9–10)
- [ ] Support ticket system (user + admin)
- [ ] FAQ management (admin)
- [ ] In-app notification system
- [ ] Email notification system
- [ ] All email templates
- [ ] Profile + Settings pages

### Phase 7 — Admin Advanced (Week 10–12)
- [ ] User management (full)
- [ ] Reports & analytics
- [ ] Admin roles & permissions
- [ ] Admin logs
- [ ] CMS (homepage content, legal pages)
- [ ] System settings panel
- [ ] Security hardening

### Phase 8 — Polish & Launch (Week 12–14)
- [ ] Mobile responsive testing
- [ ] Performance optimization
- [ ] SEO (meta tags, sitemap, robots.txt)
- [ ] Cloudflare setup
- [ ] SSL
- [ ] End-to-end testing all payment flows
- [ ] Production deployment
- [ ] Monitoring setup

---

## 20. Future Scope (Out of MVP)

> Not building now — documented for future reference.

- Additional tools (Moz, SurferSEO, etc.)
- Mobile app
- Auto-renewal for UPI (via mandate)
- Razorpay / Stripe integration (proper gateway)
- Multi-language (Hindi)
- Dark mode (already in settings UI plan)
- Blog system
- Live chat integration (Tawk.to)
- WhatsApp notification via API
- Telegram bot for order alerts (admin)
- Wallet system (add balance, pay from wallet)
- Gift plans (buy plan for someone else)
- API access for power users
- Telegram community integration

---

## 21. Open Decisions / Config Needed

> Fill these in before development starts.

| # | Decision | Options | Chosen |
|---|----------|---------|--------|
| 1 | Tech stack | Laravel 11 + Livewire 3 + Go proxy (existing) | ✅ **Decided** |
| 2 | Database | MySQL / PostgreSQL | TBD |
| 3 | Semrush plan price (monthly) | ₹___ | TBD |
| 4 | Ahrefs plan price (monthly) | ₹___ | TBD |
| 5 | Combo plan price (monthly) | ₹___ | TBD |
| 6 | Ahrefs Bar price (monthly) | ₹___ | TBD |
| 7 | PayPal currency | USD / INR | TBD |
| 8 | UPI payment email address | payments@semrushtoolz.com? | TBD |
| 9 | UPI ID | ___@upi | TBD |
| 10 | Offline Binance ID | TBD | TBD |
| 11 | Offline WhatsApp number | TBD | TBD |
| 12 | Affiliate commission % | Default 20% | TBD |
| 13 | Minimum payout threshold | ₹500? | TBD |
| 14 | Seat limit per cloud account | 10 / 15 / 20? | TBD |
| 15 | Max session duration | 2 hours? | TBD |
| 16 | Idle timeout | 30 min? | TBD |
| 17 | UPI order expiry time | 30 min? | TBD |
| 18 | Grace period after expiry | 0 / 1 / 3 days? | TBD |
| 19 | Email verification required? | Yes / No | TBD |
| 20 | Hosting provider | TBD | TBD |
| 21 | Go proxy API base URL | TBD | TBD |
| 22 | Go proxy auth method | API key / HMAC / internal | TBD |
| 23 | Go proxy API documentation | User will provide | TBD |

---

## Quick Reference — Product Access Matrix

| Product | Semrush Cloud | Ahrefs Cloud | Ahrefs Bar Extension |
|---------|--------------|--------------|----------------------|
| Semrush Plan | ✅ | ❌ | ❌ |
| Ahrefs Plan | ❌ | ✅ | ❌ |
| Combo Plan | ✅ | ✅ | ❌ |
| Ahrefs Bar Plan | ❌ | ❌ | ✅ |

---

*This document is the single source of truth for Semrushtoolz.com development. Update this file whenever a feature decision is made.*

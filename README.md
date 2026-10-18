# 🏠 EthioRent — Property Rental Marketplace

A PHP/MySQL rental house marketplace for **Adama City, Ethiopia** (plain PHP, no framework). Landlords list properties and tenants browse/search them; there is a landlord dashboard and an admin console for managing users, houses, and approvals.

**Stack:** PHP · Apache (XAMPP/LAMPP) · MySQL (mysqli) · HTML/CSS/JS

---

## ✅ Requirements

- **XAMPP** (or LAMPP on Linux) with Apache + MySQL running
- PHP **7.4+** (project developed on **8.2**)
- Project folder placed inside `htdocs/`
  - Windows: `C:\xampp\htdocs\`
  - Linux: `/opt/lampp/htdocs/`

---

## 🚀 Installation (First-Time Setup)

### 1️⃣ Start XAMPP
- **XAMPP Control Panel** → Start **Apache** and **MySQL**.

### 2️⃣ Configure Database Credentials
Match MySQL credentials in `includes/db.php` (or copy `config/config_secrets.example.php` to `config/config_secrets.php`):
```php
$host   = 'localhost';
$user   = 'root';
$pass   = '';          // default empty in XAMPP — change if yours has a password
$dbname = 'rental_db'; // database name (the app creates it for you)
```

### 3️⃣ Run the One-Click Setup
Open in your browser:
```
http://localhost/udmp_adama_rental_house/setup.php
```
This **automatically**:
- Creates the `rental_db` database
- Creates all required tables (`users`, `houses`, `requests`, `admin_invites`, `app_config`)
- Creates the `uploads/` folder for property images

> ⚠️ **Important:** `setup.php` does **NOT** create an admin account for you. You must register one using the setup key (next step).

### 4️⃣ Create the First Super Admin
1. Go to `register.php`.
2. Fill in your name, email, and password.
3. Tick **"I'm the site administrator"** to reveal the **Admin Setup Key** field.
4. Paste the key from step 3 and submit.
5. That account becomes the **Super Admin**.
6. The setup key is **destroyed** after this — it cannot be reused.

> Everyone else who registers normally (no key) becomes a **landlord**.

### 5️⃣ View Your Site
```
http://localhost/udmp_adama_rental_house/
```
The site loads the landing page (Home). Click **Browse** to see/search properties.

---

## 📣 Advertising (Admin)

Administrators can run scheduled banner advertisements on the homepage.

1. Sign in as an admin → **Advertising** in the admin sidebar (`admin_manage_ads.php`).
2. Create an advertisement: title, optional description, banner image (JPG, PNG,
   WebP or GIF · max 4 MB), **optionally a promo video** (MP4, WebM, OGG or MOV ·
   max 20 MB), optional target URL, **start date & time** and **end date & time**,
   plus a status (Published / Draft / Inactive).
3. Published advertisements appear on the homepage (`Home.php`) automatically
   inside their schedule — they switch on when the start time is reached and
   switch off by themselves once the end time passes. Deactivating or deleting
   one hides it immediately.

### Optional promo video

The banner image is still required (it doubles as the video poster), but a video
is optional per advertisement. A video is validated by **container signature**
(`ftyp` for MP4/MOV, EBML for WebM, `OggS` for OGG) and the payload is scanned
for script markers before saving, so a renamed `.php`/`.html` file is rejected.
Videos live in `uploads/`, are played **muted with controls** inside the card
(only when the card scrolls into view), and are deleted automatically when the
video is replaced, removed (tick *Remove this video*) or when the advertisement
itself is deleted.

### Visitor counting

Every advertisement card reports what happened to it:

| Metric | Meaning |
| --- | --- |
| **Visitors** | distinct people who really saw the ad (salted one-way hash of the IP) |
| **Views** | counted impressions — a card must stay ≥50% visible for 1 second; a refresh is not counted twice for the same visitor on the same day |
| **Clicks** | the card or its *Learn more* link was opened |
| **Today** | distinct visitors counted since midnight (EAT) |

The browser sends the beacon to `ad_track.php`, which accepts **POST only**,
requires a same-origin request, and ignores any advertisement that is not live at
that exact moment. Only `sha256(salt|IP)` is stored — **raw IP addresses are
never written** to the database. The per-advertisement numbers and the totals are
shown in the *Visitors* column of the admin console, and deleting an
advertisement also deletes its statistics.

**Time-zone strategy (documented):** all advertisement times are stored and
compared in **Africa/Addis_Ababa — EAT, UTC+3** (Ethiopia has no daylight
saving) and evaluated with the **server clock** in `ads_now()`
(`includes/ads.php`). The browser's clock or JavaScript timers are never used.
An advertisement is visible only when:

```
status = 'published'  AND  start_datetime <= now  AND  end_datetime > now
```

Dates are displayed in the admin console as `Mmm d, YYYY · HH:MM` (24-hour).

**Database:** the `advertisements` and `advertisement_views` tables are created
idempotently by `includes/db.php` and `setup.php` (the `video_path` column is
added automatically to older installations). For an existing install you can also
run the additive migration
`database/migrations/2026-10-17_create_advertisements.sql` (nothing is dropped or
altered).

**Security:** every administrative action is re-checked server-side (admin
session), protected by CSRF tokens, executed with prepared statements; target
URLs are restricted to `http://` / `https://`; images are validated by extension
*and* real MIME type, videos by container signature plus a script scan, both saved
under random names in `uploads/` (which blocks script execution); advertisement
text is HTML-escaped when rendered; the analytics beacon is POST-only,
same-origin and only counts live ads.

---

## 💳 Service-Fee Payments & Secure Rental Acceptance

An owner must pay a **service fee** (default **2% of the first month's rent**)
before they are allowed to accept a tenant's rental request. Paying the fee does
**not** auto-accept — the owner still clicks *Accept* explicitly. Only after the
fee is **verified** *and* the owner has **accepted** does the requesting tenant
see the owner's **name, phone, exact map/coordinates and house number**.

```
tenant requests  →  owner sees 2% fee  →  owner pays  →  fee verified
                 →  owner clicks Accept  →  tenant sees owner contact
```

### What stays private (server-side, not CSS)

Until payment is verified *and* the request is accepted, the listing page never
emits the owner name, phone, coordinates, map link or house number — for guests,
for other tenants and for the owner viewing their own listing. The check is
`payment_contact_revealed()` (`includes/payments.php`), enforced on every render
and on the accept endpoint, not in the browser.

### How the fee is computed

`fee = houses.amount × 0.02`, computed **server-side** from the stored rent; the
rent and fee are snapshotted into the payment row in **ETB** at creation time so
the browser can never change the amount. Examples: 5000→100, 8000→160,
12000→240 ETB.

### Payment gateways

The integration is **provider-agnostic** with three adapters, selected by
`PAYMENT_GATEWAY` (or auto-detected):

| Provider | When used | Real money? |
|----------|-----------|-------------|
| `chapa`  | `CHAPA_SECRET_KEY` is set | yes (sandbox or live) |
| `test`   | no gateway credentials configured | **no** — local sandbox |
| `manual` | offline payment an admin verifies by hand | no (recorded separately) |

* **Chapa** (https://dashboard.chapa.co) was chosen because it supports ETB and
  offers a test mode. Payment is re-verified **server-side** with the gateway API
  before anything is marked paid; a browser "success" parameter is never trusted.
* **Test mode** lets you exercise the whole flow with no real money: after
  starting a payment you land on `payment_checkout.php`, where you click
  *Simulate success* or *Simulate failed*. It is clearly labelled and is only
  reachable while no Chapa key is configured.
* **Manual** verification is done by an admin in *Service-Fee Payments*
  (`admin_manage_payments.php`) and is recorded with
  `verification_source='manual'`, kept visibly distinct from gateway payments.

Callbacks (`payment_callback.php`), the webhook (`payment_webhook.php`) and
verification are **idempotent** — repeating them never double-credits. If a
`CHAPA_WEBHOOK_SECRET` is set, the `x-chapa-signature` HMAC is checked as
defence-in-depth; independent server-side re-verification always happens anyway.

### Configuration

Add these to `config/config_secrets.php` (gitignored — **never** paste secrets
into source or commit them). All are optional; with none set the app runs in
`test` mode.

```php
define('PAYMENT_GATEWAY', 'chapa');   // chapa | test | manual (optional)
define('PAYMENT_FEE_RATE', 0.02);     // 2% (optional; defaults to 0.02)
define('PAYMENT_CURRENCY', 'ETB');    // optional; defaults to ETB
define('CHAPA_MERCHANT_ID', '769457'); // public business id (not a secret)
define('CHAPA_SECRET_KEY', 'CHASECK_TEST-...');
define('CHAPA_WEBHOOK_SECRET', '...'); // optional signature check
```

`APP_BASE_URL` (already in the secrets file) is used to build the Chapa
callback/return URLs in local development.

### Local test walkthrough (no real money)

1. Log in as a landlord who owns a house and open **Dashboard**.
2. Have a tenant (or a second account) submit a rental request for that house.
3. On the request, click **Pay service fee** → the sandbox checkout opens.
   Use **Simulate success** to verify, or **Simulate failed** then retry.
4. After the fee is verified, **Accept** unlocks. Accept the request.
5. Open the listing as the **requesting tenant** — owner name, phone, map and
   house number are now visible. Another tenant still sees them hidden.

### Database

The `rental_payments` table and `rental_requests.accepted_at` column are created
idempotently by `includes/db.php` and `setup.php`. For an existing install you can
also run the additive migration
`database/migrations/2026-10-17_create_rental_payments.sql` (nothing is dropped
or altered). Payments store `owner_id`, `tenant_id`, `house_id`,
`rental_request_id`, the rent/fee snapshot, a unique `reference`, the gateway
transaction id, status, verification source and timestamps, with foreign keys to
the request, house, owner and tenant so a payment for one request can never
unlock another.

### Limitations / notes

* Real payments require a Chapa account and a public HTTPS callback URL; locally,
  use **test mode** (or the **manual** admin fallback).
* Webhook delivery requires Chapa to reach your `payment_webhook.php`; a redirect
  verification runs on the browser return regardless.
* Rent changes after a payment is created do not change the snapshotted fee of
  that payment; a new attempt snapshots the current rent.

---

## 🔧 Troubleshooting

| Issue | Solution |
|-------|----------|
| **"Connection failed"** | Check `$pass` in `includes/db.php` (XAMPP default is empty) and that MySQL is started. |
| **Database/tables missing** | Run `setup.php` once to create everything. |
| **Can't log in as admin** | You must **register the first Super Admin** via `setup.php` key (see Installation step 4). |
| **Setup key not showing** | The key only shows while **no admin exists** — once an admin is created it's destroyed. |
| **Landlord promoted but not admin** | They must enter the invite key at `admin_key.php` (they may have clicked "Skip for now"). |
| **Port 80/443 in use** | Stop Skype/Teams/IIS or change Apache ports in XAMPP. |
| **White screen / PHP errors** | Enable error reporting in `php.ini` or check Apache logs. |
| **Uploads not saving** | Ensure `uploads/` exists and is writable (Apache needs write access). |

---

**Need help?** Check the Apache/MySQL logs via the XAMPP Control Panel ("Logs" button), or open an issue on the repository.



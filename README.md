# TCO donation website + PHP backend

This package adds a PHP backend to the TCO website with:

- Contact form messages sent to `nevalipibrax@gmail.com` through authenticated SMTP.
- Flutterwave hosted checkout in UGX for card payments (Visa/Mastercard) and Uganda mobile money (`mobilemoneyuganda`, subject to Flutterwave account approval and availability).
- Server-side transaction verification before marking a donation successful.
- A signed Flutterwave webhook endpoint and a small SQLite donation ledger.

**Important:** This is an integration-ready implementation, not a live payment account. No bank details, SMTP password, Flutterwave keys, or live credentials were supplied, so no live transaction can be processed until the deployment steps below are completed. Never put secret keys in HTML/JavaScript or commit them to source control.

## Requirements

- PHP 8.1+ with `curl`, `pdo_sqlite`, `sqlite3`, `mbstring`, and `openssl` extensions.
- Composer.
- HTTPS on the live domain.
- An approved Flutterwave merchant account enabled for Uganda UGX card and mobile money collections.
- SMTP credentials for an email account authorized to send mail (ideally an address on TCO's own domain).

## Project layout

- `public/` — web document root; upload its contents to your hosting `public_html` (or configure the domain document root to this folder).
- `private/` — config and SQLite database; keep this folder outside the public web root.
- `vendor/` — Composer dependencies; keep outside the public web root.

If your hosting provider requires `public_html` as the document root, place `private/` and `vendor/` one directory above it. The PHP endpoints expect this layout. Do not put `private/` or `vendor/` inside a publicly browsable folder.

## Install

1. Upload the project so the `public/` folder is the website document root, with `private/` and `vendor/` beside it.
2. From the project root run:

   ```bash
   composer install --no-dev --optimize-autoloader
   ```

3. Copy `private/config.example.php` to `private/config.php` and set the values below. Keep `config.php` private and do not share it publicly.
4. Set `TCO_SITE_URL` to the exact HTTPS website origin, for example `https://yourdomain.ug` (no trailing slash).
5. Add Flutterwave credentials from the Flutterwave dashboard:
   - `FLW_SECRET_KEY` — secret API key (use test key while testing).
   - `FLW_SECRET_HASH` — webhook secret hash configured in Flutterwave dashboard. Use a long random value.
6. Add SMTP settings from your mail provider:
   - `SMTP_HOST`, `SMTP_PORT` (usually 587), `SMTP_USERNAME`, `SMTP_PASSWORD`, `SMTP_ENCRYPTION` (`tls`), `SMTP_FROM_EMAIL`, `SMTP_FROM_NAME`.
   - `SMTP_FROM_EMAIL` should be an address the SMTP provider permits you to send as. Incoming enquiries and verified donation notices are delivered to `nevalipibrax@gmail.com`.
7. In the Flutterwave dashboard, add this webhook URL and the exact same secret hash:

   `https://yourdomain.ug/api/webhook.php`

   Enable charge-completed events and webhook retries if those settings are available.
8. In Flutterwave account settings, make sure card and Uganda mobile money methods are enabled. The implementation requests `card, mobilemoneyuganda` for UGX. Flutterwave may hide a method if the merchant account is not approved or the method is unavailable for that account.
9. Complete merchant KYC and configure TCO's organization bank account as the **settlement/payout account in the Flutterwave dashboard**. Do not put bank account numbers or card data in the public website. Flutterwave handles card data on its hosted checkout page and settles funds to the verified bank account according to its terms and settlement schedule.
10. Test with Flutterwave test credentials and test payment instruments first. Only switch to live credentials after TCO has completed provider onboarding and a real low-value test donation has been reconciled.

## Email behaviour

- The contact form posts to `/api/contact.php` and sends an enquiry to `nevalipibrax@gmail.com`.
- A donation notification is sent to the same inbox only after the payment has been verified server-side.
- PHP `mail()` is not used; authenticated SMTP is required for reliable delivery.

## Payment behaviour

- Donation currency is UGX; minimum amount is UGX 1,000.
- The hosted checkout supports the payment methods enabled for TCO's Flutterwave account. In Uganda, Flutterwave documents `card` and `mobilemoneyuganda` for UGX. Visa/Mastercard are processed as card payments; MTN/Airtel options depend on the gateway's supported network list and account activation.
- The donor is redirected to Flutterwave's hosted payment page. TCO's server creates the transaction reference and verifies the transaction after return/webhook.
- A webhook signature is checked before processing. The server then calls Flutterwave's transaction verification endpoint and checks the reference, status, currency and amount.
- Monthly recurring giving is intentionally disabled in the interface; recurring billing needs a separately configured payment plan and explicit donor consent.
- “Bank account” in this integration means TCO's settlement bank account configured in the Flutterwave merchant dashboard. The currently documented Uganda UGX checkout options are card and Uganda mobile money; do not advertise bank-transfer checkout for Uganda unless Flutterwave enables it for TCO's account.

## Operational notes

- The SQLite database is created at `private/data/tco.sqlite`. Back it up securely and restrict file permissions.
- Set `private/` and database directory permissions so PHP can write the database but the public web cannot read it.
- Configure HTTPS, routine backups, server updates, and spam/rate limiting (for example Cloudflare Turnstile) before public launch.
- Add the official TCO registration/licensing details, safeguarding/contact policy, and real project reports before soliciting public donations.
- The website's impact/campaign content should only include verified project targets, budgets and results. No fabricated fundraising totals are included.

## Endpoints

- `POST /api/contact.php`
- `POST /api/create-checkout.php`
- `POST /api/webhook.php` (Flutterwave webhook)
- `GET /payment-return.php` (server-side verification after redirect)

## Official provider references

- Flutterwave Standard checkout: https://developer.flutterwave.com/reference/checkout
- Flutterwave payment methods: https://developer.flutterwave.com/docs/payment-methods
- Uganda mobile money: https://developer.flutterwave.com/docs/uganda
- Webhook signatures and verification: https://developer.flutterwave.com/docs/webhooks

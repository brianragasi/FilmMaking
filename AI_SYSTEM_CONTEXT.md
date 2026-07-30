# EcoCart AI System Context

Give this file to any developer or AI assistant before they edit EcoCart.

## Project Purpose

EcoCart is a class filmmaking system for the short DDoS-awareness film
**OVERLOAD**. It combines a believable ecommerce website with harmless,
scripted filming controls.

The project must look convincing on camera, but it must never perform real
attacks, generate abusive traffic, stop the hosting account, or expose real
infrastructure controls.

## Technology

- PHP 8 and MySQL
- HTML and browser JavaScript
- Tailwind CSS CLI with daisyUI
- XAMPP for local development
- GoogieHost for the public PHP website
- GitHub Actions and FTPS for deployment
- Electron for the local attacker-terminal filming prop

## Users And Permissions

### Customer

- Registers and signs in through `login.php`.
- Shops through `index.php`.
- Uses the cart and `checkout.php`.
- Opens only their own account and orders.

### Operations Admin

- Signs in with an account whose role is `admin`.
- Opens `admin.php`.
- Uses the cinematic production console.
- Must not open `director.php` or control filming states.

### Production Director

- Uses a separate account whose role is `director`.
- Opens `director.php`.
- Must not open `admin.php`.
- Is the only role allowed to POST changes to `scene-state.php`.

Never merge the admin and Director roles.

## Director Remote

The Director interface must remain simple. It has exactly three controls:

1. **Open website** sets the cue to `restored`.
2. **Start sale** sets the cue to `sale_live`.
3. **Shut down website** sets the cue to `outage`.

Do not add pre-take, raise-traffic, checkout-loading, customer-refresh,
recovery, or runbook buttons to the Director Remote.

### Customer-Screen Results

- `restored`: customer screens automatically show the working storefront.
- `sale_live`: the storefront automatically shows the full-screen
  **SALE IS LIVE!** announcement and keeps the sale ribbon active.
- `outage`: customer screens automatically show the fictional HTTP 503 page.

Customers do not need to refresh manually. The lightweight public GET request
to `scene-state.php` synchronizes screens. These cues only alter application
output; GoogieHost remains online.

## Attacker Terminal

The attacker terminal is a local Electron filming prop:

- Main files: `main.js`, `preload.js`, `index.html`, and `assets/app.js`.
- It accepts random actor keystrokes and reveals prepared commands.
- It displays simulated request counts and terminal output.
- It sends no attack traffic and must remain harmless.
- It is not a real shell and must not execute typed commands.
- It is intentionally excluded from GoogieHost deployment.

Do not upload `attacker-terminal.php`, Electron source, installers, or attacker
assets to public hosting. Do not add real DDoS logic, scanners, credential
collection, shell execution, remote control, or network flooding.

## Customer Account Contract

Registration and login must follow these rules:

1. A new valid registration inserts one customer row and immediately signs in.
2. The account page displays a clear success notice after registration.
3. Repeating registration with the same email and same password is safe: the
   existing customer is signed in instead of receiving a duplicate-email trap.
4. The same email with a different password shows an existing-account message
   and a direct link to the sign-in form.
5. A verified password signs the user in even when optional metadata updates,
   such as `last_login_at`, fail on an older production schema.
6. Passwords are stored only with `password_hash()` and checked with
   `password_verify()`.
7. Never log, commit, display, or hard-code passwords.

The account implementation lives primarily in:

- `login.php`
- `account.php`
- `includes/auth.php`
- `database/schema.sql`

## Scene-State Implementation

- Cue definitions and persistence: `includes/scene.php`
- Public GET and Director-only POST endpoint: `scene-state.php`
- Customer synchronization: `assets/scene-client.js`
- Director UI behavior: `assets/director.js`
- Custom outage page: `includes/customer-outage.php`

Preserve file locking and CSRF validation when changing scene state.

## Deployment Rules

Production deployment is defined in:

```text
.github/workflows/deploy-googiehost.yml
```

Database, FTP, admin, and Director credentials come from GitHub Actions
secrets. `includes/config.local.php` is generated during deployment and must
never be committed.

Updating a GitHub secret does not deploy it automatically. Run the deployment
workflow or push a commit after changing secrets.

Files excluded by the workflow must remain excluded, especially:

- `.git`, `.github` build internals, and `node_modules`
- local config and logs
- database scripts and Markdown documentation
- Electron and attacker-terminal runtime files
- installers and release folders

## Editing Rules For AI Assistants

Before editing:

1. Run `git status` and inspect recent commits.
2. Read the complete files involved in the requested behavior.
3. Preserve unrelated user work.
4. Keep the PHP/MySQL/XAMPP/GoogieHost stack.

While editing:

1. Keep customer, admin, and Director permissions separate.
2. Keep all attacker behavior simulated and local.
3. Do not expose filming controls to customers.
4. Do not add fake service-status or attack language to the customer store.
5. Keep the Director Remote limited to its three controls.
6. Use prepared SQL statements and CSRF validation.
7. Do not hard-code production credentials.

Before merging:

```bash
npm run build:css
php -l login.php
php -l account.php
php -l director.php
php -l scene-state.php
php -l includes/auth.php
php -l includes/scene.php
node --check assets/app-public.js
node --check assets/director.js
node --check assets/scene-client.js
git diff --check
```

Also test these workflows in a browser:

1. Register a new customer and see the success notice.
2. Sign out and sign in with that customer.
3. Repeat registration using the same email and password.
4. Confirm a wrong password does not sign in.
5. Confirm an admin cannot open `director.php`.
6. Confirm a Director cannot open `admin.php`.
7. Confirm all three Director controls update customer screens.

## Definition Of Done

A change is complete only when it works locally, preserves role boundaries,
contains no real attack capability, passes syntax/build checks, and has a
clear browser-visible result. A merged pull request is not proof that the live
site changed; the GoogieHost deployment must also finish successfully.

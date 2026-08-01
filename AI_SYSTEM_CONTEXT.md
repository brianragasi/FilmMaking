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
- Reads product discussions publicly and posts only while signed in.
- Can react to comments and soft-delete only comments they authored.
- Can customize a display name, profile note, initials color, and optional
  profile picture.
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
- Can moderate product discussions with search, rating and product filters,
  account/storefront shortcuts, and single or bulk removal from public view.
- Can review customer profiles, correct inappropriate display content, remove
  profile pictures, suspend accounts with a reason, and restore accounts.

Never merge the admin and Director roles.

## Director Remote

The Director interface must remain simple. It has exactly three controls:

1. **Open website** sets the cue to `restored`.
2. **Start sale** sets the cue to `sale_live`.
3. **Shut down website** sets the cue to `outage`.

Do not add pre-take, raise-traffic, checkout-loading, customer-refresh,
recovery, or runbook buttons to the Director Remote.

Discussion and customer-safety moderation are separate collapsible management
areas. They are not filming cues and must not compete visually with the three
scene controls.

### Customer-Screen Results

- `restored`: customer screens automatically show the working storefront.
- `sale_live`: the storefront automatically shows the full-screen
  **SALE IS LIVE!** announcement, keeps the sale ribbon active, and arms the
  scripted checkout freeze. A valid Place Order click then shows the endless
  checkout spinner without submitting an order.
- `outage`: already-open storefront and checkout pages stay visually unchanged.
  If checkout is submitted, its loading screen spins indefinitely. The actor's
  manual browser refresh then requests PHP again and shows the fictional HTTP
  503 page. This preserves the screenplay's freeze-then-refresh beat.

The lightweight public GET request to `scene-state.php` synchronizes the cue,
but it must not replace an already-open customer page during `outage`. These
cues only alter application output; GoogieHost remains online.

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
3. A newly created customer is offered profile setup with clear **Set up
   profile now** and **Later** actions; neither choice traps the customer.
4. Repeating registration with the same email and same password is safe: the
   existing customer is signed in instead of receiving a duplicate-email trap.
5. The same email with a different password shows an existing-account message
   and a direct link to the sign-in form.
6. A verified password signs the user in even when optional metadata updates,
   such as `last_login_at`, fail on an older production schema.
7. Passwords are stored only with `password_hash()` and checked with
   `password_verify()`.
8. Never log, commit, display, or hard-code passwords.

The account implementation lives primarily in:

- `login.php`
- `account.php`
- `includes/auth.php`
- `database/schema.sql`

## Product Discussions And Profiles

- Product pages live at `product.php?id=PRODUCT_ID`.
- Everyone may read comments; only signed-in database customers may post.
- Each post has a 1-5 star rating and a maximum 1,000-character body.
- Posting uses CSRF validation, prepared statements, and a short session rate
  limit. Product queries are capped and indexed for low-cost shared hosting.
- Signed-in customers can independently toggle Helpful, Love, and Funny
  reactions. The indexed `product_discussion_reactions` table prevents
  duplicate reactions from the same customer.
- Reaction requests return JSON so the selected state and count update without
  reloading the product page. The POST form remains the no-JavaScript fallback.
- Customers may soft-delete only their own active comments. Director deletion
  remains a separate moderation permission and supports guarded bulk removal.
- Profiles use an optional image or initials with one of six preset colors.
- Profile images are limited to JPEG, PNG, or WebP files up to 2 MB. Uploads
  receive random server-side names, are validated as images, and are stored in
  `uploads/profiles/` with script execution disabled.
- The Director can edit customer names and notes, remove inappropriate images,
  suspend accounts with a visible reason, and restore them later through
  `director-customer-action.php`.
- Director deletion is a soft delete through
  `director-discussion-action.php`; deleted comments leave public views.
- Shared logic is in `includes/discussions.php`.

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
php -l profile-setup.php
php -l product.php
php -l discussion-action.php
php -l director-discussion-action.php
php -l director-customer-action.php
php -l director.php
php -l scene-state.php
php -l includes/auth.php
php -l includes/scene.php
php -l includes/discussions.php
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
8. During `sale_live`, submit a valid cart and confirm checkout keeps spinning.
9. Trigger `outage` and confirm the open screen stays frozen, then manually
   refresh and confirm the ERROR 503 page appears.
10. Confirm guests can read but cannot post product comments.
11. Confirm a customer can post and the Director can remove the comment.
12. Confirm a customer can toggle each reaction and cannot delete another
    customer's comment.
13. Confirm a customer can upload a valid profile photo and invalid or oversized
    files are rejected.
14. Confirm the Director can remove a photo, suspend the customer, block that
    customer's login, and restore the account.

## Definition Of Done

A change is complete only when it works locally, preserves role boundaries,
contains no real attack capability, passes syntax/build checks, and has a
clear browser-visible result. A merged pull request is not proof that the live
site changed; the GoogieHost deployment must also finish successfully.

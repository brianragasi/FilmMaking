# EcoCart Script Alignment Notes

Source reviewed: `final-allgend-with-the-template-of-DIGI.docx.pdf`

The workspace does not currently contain a separate `.docx` source file. The available document is the 17-page PDF export.

## What The Script Requires

The screenplay is not only a basic online shop. It needs an ecommerce system that can support these visible story beats:

- Big Blowout Sale countdown and product browsing.
- Multiple community shopper groups preparing carts before noon.
- A checkout attempt that freezes or shows `SERVER ERROR. PLEASE TRY AGAIN.`
- An attacker/botnet visual with repeated fake requests.
- An EcoCart IT/admin view showing abnormal traffic, rate limiting, filtering, and recovery.
- A later incident meeting view showing the mitigation plan.

## User And Actor Breakdown

Named story roles:

- Sarah - legitimate student customer
- Seller / EcoCart Owner - business owner
- Store Manager - sales/store operations
- Server Admin 1 - traffic monitoring and response
- Server Admin 2 - filtering/security-provider coordination
- Cybersecurity Analyst - confirms DDoS and prevention controls
- Incident Response Lead - coordinates response and future plan
- Attacker - fictional hidden attacker

Core named roles total: 8 people.

Visible legitimate shopper groups:

- Classmates / Students: 10-12
- Construction Workers: 5-6
- Pick Me Rider: 1
- Nanays and Barangay Residents: 6-8
- Mother and Family Members: 3

Visible online shopper total: 25-30 people.

Additional ensemble:

- Store Employees and Customers: 5-7
- Botnet Representatives: flexible; may be played by actors from earlier scenes

Production note from the script: approximately 30-40 students/actors, because ensemble actors may appear in more than one short scene.

## Current System Alignment

Implemented pages:

- `index.php` - storefront, sale countdown, shopper groups, script-matched products
- `checkout.php` - cart and checkout, with simulated DDoS server-error state
- `admin.php` - operations dashboard, safe terminal simulator, user/role counts, impact montage, incident meeting plan

Script-matched products now include:

- School supplies
- Shoes
- Headset
- Safety boots
- Safety helmet
- Tool set
- Motorcycle phone holder
- Rain gear
- Kalha cooking pot
- Curtains
- Baby formula
- Diapers
- Baby clothes
- Feeding bottles

## Remaining Optional Improvements

- Add a separate full-screen `attacker.php` page for filming the hidden attacker scene.
- Add a `scene-mode.php` or query parameter for camera shots, such as `?scene=classroom`, `?scene=barangay`, or `?scene=mother`.
- Add fake screen overlays for split-screen recording: loading spinner, server error, restored services notification.
- Add a simple incident-report PDF/print page for the Chapter 5 meeting screen.

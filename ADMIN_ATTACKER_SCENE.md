# EcoCart Admin and Attacker Scene

This focused version uses only the Attacker, Server Admin 1, and Server Admin 2.
The commands are shown on screen but are not spoken aloud. Actors only say the
short dialogue after each console response appears.

## Screens

- Server admins use `admin.php` on the deployed EcoCart website.
- The attacker opens `/traffic-control` on the same deployed website, then uses
  the full-screen icon in the top bar so the browser address bar is not visible
  during filming.
- Open both before the take so their state changes can stay synchronized.

## Keyboard Rule

- Open the required screen.
- Press any random keys. The correct command appears automatically.
- Press Enter at any point.
- Wait for the console response before speaking.
- The reset icon prepares both screens for another take.

## Scene 1 - Unknown Room

The ATTACKER sits in a dark room. Four device groups are represented by signs
or screens labeled REQUEST, REFRESH, CONNECT, and LOAD PAGE.

ATTACKER

Let's see how their server handles this.

The Attacker types random keys. The screen first selects the deployed EcoCart
website:

`targetctl select --host [deployed EcoCart host] --service ecocart`

The console plainly reports `Target selected: EcoCart ecommerce`. The Attacker
types again:

`nodectl attach --pool device-48 --groups req,refresh,connect,page`

The console reports `48 devices connected to the EcoCart target` and `All
devices are ready`. The four groups raise their devices or signs.

ATTACKER

All connected.

The Attacker types again:

`trafficctl run --target ecocart --routes /products,/cart,/checkout --ramp`

The output begins at 2,400 requests per minute and gradually rises. The four
groups repeat their movements faster each time the number increases.

## Scene 2 - EcoCart IT Office

SERVER ADMIN 1 is already watching the production dashboard. The starting
traffic is 2,340 requests per minute.

SERVER ADMIN 1

Normal pa ang traffic. Website, cart, and checkout are ready.

The number rises to 3,200, then 4,900, then 7,400.

SERVER ADMIN 1

Traffic is climbing faster than normal sale traffic.

It rises to 12,800 and then 24,100. Checkout begins slowing down.

SERVER ADMIN 2

Checkout is starting to delay. Dili na ni normal customer activity.

The traffic reaches 43,800 and finally 68,420 requests per minute.

SERVER ADMIN 1

We are at sixty-eight thousand requests per minute. Customers are already
timing out.

## Scene 3 - Find the Repeated Requests

SERVER ADMIN 2 types random keys:

`show repeated requests`

The console shows repeated checkout, cart, and product requests.

SERVER ADMIN 2

Most of the traffic is repeating the same actions. Real customers are still
trying to get through.

Cut to the Attacker. The Attacker types:

`trafficctl rate --target ecocart --set 92000rpm`

ATTACKER

Faster. Keep it going.

The attacker console reports that EcoCart checkout is timing out.

## Scene 4 - Check Customer Safety

SERVER ADMIN 1 types:

`check customer accounts and orders`

The console reports no suspicious account access and no unauthorized order
changes.

SERVER ADMIN 1

Customer accounts and existing orders are safe. The attack is targeting the
website's availability.

SERVER ADMIN 2

The fake requests are taking the space meant for real customers.

## Scene 5 - Slow the Attack

SERVER ADMIN 1 types:

`slow repeated traffic`

The console reports that rate limiting is active.

SERVER ADMIN 1

Rate limiting is active. Repeated requests are being slowed.

The blocked-request counter begins increasing.

SERVER ADMIN 2 types:

`start traffic filtering`

The console reports that suspicious traffic is being blocked and that the
checkout waiting requests dropped from 1,842 to 126.

SERVER ADMIN 2

Filtering is active. Clean customer traffic is returning to checkout.

Cut to the attacker terminal. It reports that the upstream rejection rate is
rising and most repeated requests are being blocked.

ATTACKER

They're filtering it.

## Scene 6 - Verify Recovery

SERVER ADMIN 2 types:

`test website cart and checkout`

The console reports:

- Website test passed.
- Customer cart items remained saved.
- Checkout completed successfully.

SERVER ADMIN 2

Website passed. Customer carts are still saved. Checkout is responding again.

SERVER ADMIN 1

Services restored. Keep monitoring for thirty minutes.

Cut to the Attacker. The Attacker types:

`trafficctl stop --target ecocart --all`

The attacker screen drops to zero and the device groups lower their signs.

## What Is Happening

1. The attacker connects many controlled devices.
2. Those devices repeatedly request EcoCart pages at the same time.
3. Fake requests compete with legitimate customer requests.
4. EcoCart checkout becomes slow and eventually times out.
5. The admins identify the repeated traffic pattern.
6. They confirm customer accounts and orders were not changed.
7. Rate limiting slows sources that repeat too quickly.
8. Traffic filtering separates suspicious requests from real customers.
9. The admins test the website, saved carts, and checkout before declaring
   recovery.

The attacker console is fictional and does not send network traffic. It exists
only to visualize the screenplay's REQUEST, REFRESH, CONNECT, and LOAD PAGE
device groups.

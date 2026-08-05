# EcoCart Admin and Attacker Scene Guide

This guide matches the commands currently shown by the SWARMGRID C2 Electron
app and the EcoCart Production Console. Both terminals are cinematic
simulations. They do not execute shell commands, send network traffic, or
control GoogieHost.

## How the typing works

- Press ordinary keys and the next scripted command appears naturally.
- The command runs automatically when it is complete, or the actor can press
  Enter.
- The output appears by itself. Actors only need to remember the short spoken
  cue for each beat.
- Use the reset icon before another take.

## Attacker scene: SWARMGRID C2

Open the installed SWARMGRID C2 desktop app. The selected target must read
`ecocart.whf.bz`.

### 1. Select EcoCart

Command:

```text
swarm target add --name ecocart --url https://ecocart.whf.bz --routes products,cart,checkout
```

What the screen means: EcoCart and the three customer routes are loaded into
the campaign. No requests have started yet.

Attacker cue: "EcoCart is selected. Products, cart, and checkout."

### 2. Attach the device pool

Command:

```text
swarm nodes attach --campaign blackout --pool device-48
```

What the screen means: 48 simulated device sessions are divided among request,
refresh, connect, and page-load groups.

Attacker cue: "All forty-eight are connected."

### 3. Start the campaign

Command:

```text
swarm campaign start blackout --profile repeated-web --ramp
```

What the screen means: repeated web requests begin and rise in stages. The
rate, accepted count, and rejected count update on screen.

Attacker cue: "Start it. Let the traffic climb."

### 4. Increase the rate

Command:

```text
swarm campaign scale blackout --rate 92000rpm
```

What the screen means: the simulated request rate reaches 92,000 per minute;
checkout timeouts and service errors appear.

Attacker cue: "Push it higher."

### 5. Hold the rate

Command:

```text
swarm campaign hold blackout
```

What the screen means: the campaign remains at the current rate while the
attacker watches whether EcoCart starts rejecting requests.

Attacker cue: "Hold it there."

### 6. Stop and detach

Command:

```text
swarm campaign stop blackout --detach
```

What the screen means: the simulated workers stop and all device sessions are
detached.

Attacker cue: "They are filtering us. Disconnect."

## Admin scene: EcoCart Production Console

Open `admin.php`. The console begins empty except for `Awaiting command`, so no
command appears to have been typed before the actor starts.

### 1. Watch live traffic

```text
sudo /opt/ecocart/bin/traffic-watch --routes products,cart,checkout --follow
```

Admin cue: "I am watching the three customer routes. Traffic is rising above
the sale baseline."

### 2. Find the repeated requests

```text
sudo /opt/ecocart/bin/request-top --window 90s --group source,route --limit 4
```

Admin cue: "Eighty-two percent is repeating across four source groups. Real
customers are still mixed in."

### 3. Check accounts and orders

```text
sudo /opt/ecocart/bin/integrity-check --scope accounts,orders --since 10m
```

Admin cue: "Accounts and orders are clean. This is an availability attack, not
a data breach."

### 4. Apply the emergency policy

```text
sudo /opt/ecocart/bin/edge-policy apply sale-emergency --match repeating --atomic
```

Admin cue: "Emergency limits are active. Repeated requests are being
throttled."

### 5. Enable traffic filtering

```text
sudo /opt/ecocart/bin/traffic-filter enable --profile sale-ddos --preserve sessions
```

Admin cue: "Filtering is active. Clean customer sessions are being preserved."

### 6. Verify recovery

```text
sudo /opt/ecocart/bin/smoke-test --host ecocart.whf.bz --routes storefront,cart,checkout --preserve-cart
```

Admin cue: "Storefront, cart, and checkout passed. Keep monitoring for thirty
minutes."

## Continuity notes

- The Electron app is local and display-only. It does not need internet access
  for the scene.
- `ecocart.whf.bz` is shown as the production target for continuity with the
  GoogieHost scene.
- Do not claim that SWARMGRID caused real traffic on the hosted website. The
  Director controls the website's scripted outage separately.
- Keep both computers' clocks and scene order consistent between shots.

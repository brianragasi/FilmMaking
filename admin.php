<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/products.php';

$operator = require_admin();
auth_no_store();

$orderCount = 0;
$orderTotal = 0.0;

if ($pdo = db()) {
    try {
        $orderCount = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
        $orderTotal = (float) $pdo->query('SELECT COALESCE(SUM(subtotal), 0) FROM orders')->fetchColumn();
    } catch (Throwable $error) {
        $orderCount = 0;
        $orderTotal = 0.0;
    }
}

$sourceRows = [
    ['ip' => '203.0.113.42', 'region' => 'SG edge', 'signature' => 'GET /checkout repeat', 'rate' => '4,280/s'],
    ['ip' => '198.51.100.17', 'region' => 'JP edge', 'signature' => 'POST /cart burst', 'rate' => '3,940/s'],
    ['ip' => '192.0.2.88', 'region' => 'US edge', 'signature' => 'GET /products loop', 'rate' => '3,610/s'],
    ['ip' => '203.0.113.106', 'region' => 'DE edge', 'signature' => 'TLS reconnect flood', 'rate' => '2,890/s'],
];

$customerImpact = [
    ['name' => 'Campus carts', 'sessions' => '12 sessions', 'route' => '/checkout', 'icon' => 'graduation-cap'],
    ['name' => 'Worksite buyers', 'sessions' => '6 sessions', 'route' => '/cart', 'icon' => 'hard-hat'],
    ['name' => 'Rider and home', 'sessions' => '9 sessions', 'route' => '/products', 'icon' => 'bike'],
    ['name' => 'Family essentials', 'sessions' => '3 sessions', 'route' => '/checkout', 'icon' => 'baby'],
];
?>
<!doctype html>
<html lang="en" data-theme="business">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="EcoCart production operations center.">
    <title>EcoCart Operations | Production</title>
    <link href="public/output.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="icon" href="data:,">
</head>
<body class="ops-body min-h-screen bg-[#07101c] text-slate-100">
    <header class="border-b border-slate-700/70 bg-[#091421]">
        <div class="app-shell flex min-h-[68px] items-center gap-4">
            <div class="flex items-center gap-3">
                <span class="grid h-9 w-9 place-items-center rounded-lg bg-cyan-400 text-slate-950">
                    <i data-lucide="activity" class="h-5 w-5"></i>
                </span>
                <div>
                    <p class="text-sm font-black">EcoCart Operations</p>
                    <p class="text-[10px] font-bold uppercase text-slate-400">Commerce reliability</p>
                </div>
            </div>
            <span class="hidden h-6 w-px bg-slate-700 sm:block"></span>
            <div class="hidden items-center gap-2 sm:flex">
                <span class="rounded bg-rose-500/15 px-2 py-1 text-[10px] font-black uppercase text-rose-300 ring-1 ring-rose-400/30">Production</span>
                <span class="rounded bg-slate-800 px-2 py-1 text-[10px] font-bold text-slate-300">PH-MNL-1</span>
            </div>
            <div class="ml-auto flex items-center gap-3 text-xs">
                <div class="hidden text-right md:block">
                    <p class="font-bold text-slate-200" data-ops-clock>--:--:--</p>
                    <p class="text-[10px] text-slate-500">Asia/Manila</p>
                </div>
                <span class="hidden h-7 w-px bg-slate-700 md:block"></span>
                <div class="hidden text-right lg:block">
                    <p class="font-bold"><?= htmlspecialchars((string) $operator['name']) ?></p>
                    <p class="text-[10px] text-emerald-400">Authenticated operator</p>
                </div>
                <a class="btn btn-square btn-sm border-slate-700 bg-slate-800 text-slate-200 hover:border-cyan-400 hover:bg-slate-700" href="index.php" aria-label="Open storefront" title="Open storefront">
                    <i data-lucide="external-link" class="h-4 w-4"></i>
                </a>
                <form method="post" action="logout.php">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <button class="btn btn-square btn-sm border-slate-700 bg-slate-800 text-slate-200 hover:border-rose-400 hover:bg-slate-700" type="submit" aria-label="Sign out" title="Sign out">
                        <i data-lucide="log-out" class="h-4 w-4"></i>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="app-shell py-5">
        <section class="mb-4 flex flex-col gap-4 rounded-lg border border-emerald-500/30 bg-emerald-500/10 p-4 lg:flex-row lg:items-center" data-incident-banner>
            <div class="flex min-w-0 items-start gap-3">
                <span class="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-emerald-400/15 text-emerald-300" data-incident-icon>
                    <i data-lucide="shield-check" class="h-5 w-5"></i>
                </span>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-black" data-incident-title>All production services operational</p>
                        <span class="rounded bg-emerald-400/15 px-2 py-0.5 text-[10px] font-black uppercase text-emerald-300" data-incident-severity>Normal</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-400" data-incident-copy>Edge telemetry is within the expected Big Blowout Sale baseline.</p>
                </div>
            </div>
            <div class="ml-auto flex flex-wrap items-center gap-2">
                <span class="text-[10px] font-bold uppercase text-slate-500">Incident</span>
                <span class="font-mono text-xs font-bold text-slate-300" data-incident-id>None</span>
                <button class="hidden" type="button" data-ops-start aria-hidden="true" tabindex="-1">
                    <i data-lucide="radio" class="h-4 w-4"></i>
                    Start live trace
                </button>
                <button class="btn btn-square btn-sm border-slate-600 bg-slate-800 text-slate-300 hover:bg-slate-700" type="button" data-ops-reset aria-label="Reset operations view" title="Reset operations view">
                    <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                </button>
            </div>
        </section>

        <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <article class="ops-metric">
                <div class="flex items-center justify-between">
                    <p>Inbound requests</p><i data-lucide="arrow-down-to-line" class="h-4 w-4 text-cyan-400"></i>
                </div>
                <strong data-metric="requests">2,340</strong>
                <span data-metric-note="requests">req/min baseline</span>
            </article>
            <article class="ops-metric">
                <div class="flex items-center justify-between">
                    <p>Error rate</p><i data-lucide="triangle-alert" class="h-4 w-4 text-amber-400"></i>
                </div>
                <strong data-metric="errors">0.18%</strong>
                <span data-metric-note="errors">within SLO</span>
            </article>
            <article class="ops-metric">
                <div class="flex items-center justify-between">
                    <p>Checkout response</p><i data-lucide="timer" class="h-4 w-4 text-indigo-400"></i>
                </div>
                <strong data-metric="latency">184 ms</strong>
                <span data-metric-note="latency">checkout API</span>
            </article>
            <article class="ops-metric">
                <div class="flex items-center justify-between">
                    <p>Blocked traffic</p><i data-lucide="shield-ban" class="h-4 w-4 text-rose-400"></i>
                </div>
                <strong data-metric="blocked">0</strong>
                <span data-metric-note="blocked">requests filtered</span>
            </article>
            <article class="ops-metric">
                <div class="flex items-center justify-between">
                    <p>Checkout</p><span class="status-dot" data-checkout-dot></span>
                </div>
                <strong data-metric="checkout">Online</strong>
                <span data-metric-note="checkout">99.98% available</span>
            </article>
        </section>

        <section class="mt-4 grid gap-4 xl:grid-cols-[1.35fr_0.9fr]">
            <article class="ops-panel" data-traffic>
                <div class="ops-panel-heading">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2>Website traffic</h2>
                            <span class="rounded bg-slate-800 px-2 py-0.5 text-[10px] font-bold text-slate-400">LIVE</span>
                        </div>
                        <p>Incoming requests per minute across the storefront.</p>
                    </div>
                    <div class="flex items-center gap-4 text-[10px] font-bold uppercase">
                        <span class="flex items-center gap-1.5 text-cyan-300"><i class="h-2 w-2 rounded-sm bg-cyan-400"></i> Accepted</span>
                        <span class="flex items-center gap-1.5 text-rose-300"><i class="h-2 w-2 rounded-sm bg-rose-500"></i> Dropped</span>
                    </div>
                </div>
                <div class="relative mt-5">
                    <div class="ops-chart-grid" aria-hidden="true">
                        <span>80k</span><span>60k</span><span>40k</span><span>20k</span><span>0</span>
                    </div>
                    <div class="ops-traffic-bars pl-10" data-traffic-bars>
                        <?php for ($i = 0; $i < 36; $i++): ?>
                            <span class="ops-traffic-bar" style="height: <?= 15 + (($i * 7) % 18) ?>%"></span>
                        <?php endfor; ?>
                    </div>
                    <div class="ml-10 mt-2 flex justify-between text-[9px] font-bold text-slate-600">
                        <span>-90 sec</span><span>-60 sec</span><span>-30 sec</span><span>Now</span>
                    </div>
                </div>
                <div class="mt-5 grid grid-cols-2 gap-px overflow-hidden rounded-lg bg-slate-700 md:grid-cols-4">
                    <div class="bg-[#0d1927] p-3"><p class="text-[10px] uppercase text-slate-500">GET /products</p><strong class="mt-1 block text-sm" data-route="products">742 rpm</strong></div>
                    <div class="bg-[#0d1927] p-3"><p class="text-[10px] uppercase text-slate-500">POST /cart</p><strong class="mt-1 block text-sm" data-route="cart">316 rpm</strong></div>
                    <div class="bg-[#0d1927] p-3"><p class="text-[10px] uppercase text-slate-500">POST /checkout</p><strong class="mt-1 block text-sm" data-route="checkout">128 rpm</strong></div>
                    <div class="bg-[#0d1927] p-3"><p class="text-[10px] uppercase text-slate-500">Server load</p><strong class="mt-1 block text-sm text-emerald-400" data-route="saturation">18%</strong></div>
                </div>
            </article>

            <article class="ops-panel flex min-h-[430px] flex-col">
                <div class="ops-panel-heading">
                    <div>
                        <h2>Production console</h2>
                        <p>website / cart / checkout</p>
                    </div>
                    <div class="flex gap-1.5">
                        <span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span>
                        <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                    </div>
                </div>
                <div class="ops-terminal mt-4 flex-1" data-terminal aria-live="polite"></div>
                <form class="mt-3 flex items-center gap-2 rounded-lg border border-slate-700 bg-[#050b12] px-3" data-command-form>
                    <span class="hidden font-mono text-xs text-cyan-400 sm:inline">admin@ecocart:~$</span>
                    <input class="h-10 min-w-0 flex-1 bg-transparent font-mono text-xs text-slate-200 outline-none" data-command-input autocomplete="off" spellcheck="false" placeholder="shell ready" autofocus>
                    <button class="btn btn-square btn-xs border-0 bg-slate-800 text-slate-300" type="submit" aria-label="Run command" title="Run command"><i data-lucide="corner-down-left" class="h-3.5 w-3.5"></i></button>
                </form>
            </article>
        </section>

        <section class="mt-4 grid gap-4 xl:grid-cols-[1.05fr_0.95fr]">
            <article class="ops-panel">
                <div class="ops-panel-heading">
                    <div>
                        <h2>Incident response runbook</h2>
                        <p>Complete each control in order and preserve evidence.</p>
                    </div>
                    <span class="font-mono text-xs font-bold text-slate-400" data-runbook-progress>0 / 5 complete</span>
                </div>
                <div class="mt-4 space-y-2">
                    <?php
                    $steps = [
                        ['inspect', 'Inspect repeated requests', 'See which actions are being repeated and how often.', 'scan-search'],
                        ['classify', 'Check accounts and orders', 'Make sure customer information has not been changed.', 'file-search'],
                        ['limit', 'Slow repeated traffic', 'Reduce requests that repeat too quickly.', 'gauge'],
                        ['scrub', 'Filter incoming traffic', 'Separate suspicious requests from real customers.', 'shield-check'],
                        ['verify', 'Test website and checkout', 'Confirm the store, carts, and ordering are working.', 'circle-check-big'],
                    ];
                    ?>
                    <?php foreach ($steps as $index => $step): ?>
                        <div class="runbook-step" data-runbook-step="<?= $step[0] ?>">
                            <span class="runbook-index"><?= $index + 1 ?></span>
                            <i data-lucide="<?= $step[3] ?>" class="h-5 w-5 shrink-0 text-slate-500" data-step-icon></i>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-black"><?= $step[1] ?></p>
                                <p class="mt-0.5 text-xs text-slate-500"><?= $step[2] ?></p>
                            </div>
                            <span class="text-[10px] font-black uppercase text-slate-500" data-step-status>Waiting</span>
                            <button class="hidden" type="button" data-ops-action="<?= $step[0] ?>" disabled aria-hidden="true" tabindex="-1">
                                Run
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>

            <article class="ops-panel">
                <div class="ops-panel-heading">
                    <div>
                        <h2>Service path</h2>
                        <p>Current health across the checkout request path.</p>
                    </div>
                    <span class="rounded bg-emerald-400/15 px-2 py-1 text-[10px] font-black uppercase text-emerald-300" data-service-summary>Healthy</span>
                </div>
                <div class="mt-6 flex items-center justify-between gap-2">
                    <?php
                    $services = [
                        ['edge', 'Entry', 'cloud'],
                        ['waf', 'Traffic filter', 'shield'],
                        ['app', 'Website', 'boxes'],
                        ['checkout', 'Checkout', 'credit-card'],
                        ['db', 'Orders', 'database'],
                    ];
                    ?>
                    <?php foreach ($services as $index => $service): ?>
                        <div class="min-w-0 flex-1 text-center">
                            <span class="service-node mx-auto" data-service-node="<?= $service[0] ?>"><i data-lucide="<?= $service[2] ?>" class="h-5 w-5"></i></span>
                            <p class="mt-2 truncate text-[10px] font-bold text-slate-400"><?= $service[1] ?></p>
                        </div>
                        <?php if ($index < count($services) - 1): ?><span class="h-px w-4 shrink-0 bg-slate-700 sm:w-8" data-service-link></span><?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <div class="mt-7 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-lg border border-slate-700 bg-slate-900/60 p-3">
                        <p class="text-[10px] font-bold uppercase text-slate-500">Active sessions</p>
                        <p class="mt-1 text-lg font-black">30</p>
                    </div>
                    <div class="rounded-lg border border-slate-700 bg-slate-900/60 p-3">
                        <p class="text-[10px] font-bold uppercase text-slate-500">Orders recorded</p>
                        <p class="mt-1 text-lg font-black"><?= $orderCount ?></p>
                    </div>
                    <div class="rounded-lg border border-slate-700 bg-slate-900/60 p-3">
                        <p class="text-[10px] font-bold uppercase text-slate-500">Recorded value</p>
                        <p class="mt-1 text-lg font-black"><?= peso($orderTotal) ?></p>
                    </div>
                </div>
                <div class="mt-4 rounded-lg border border-slate-700 bg-slate-900/60 p-4">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-black uppercase text-slate-400">Security finding</p>
                        <span class="text-[10px] font-bold text-slate-500" data-breach-status>Pending analysis</span>
                    </div>
                    <p class="mt-2 text-sm leading-6 text-slate-300" data-security-finding>No evidence has been collected for the current trace.</p>
                </div>
            </article>
        </section>

        <section class="mt-4 grid gap-4 xl:grid-cols-[1.25fr_0.75fr]">
            <article class="ops-panel overflow-hidden p-0">
                <div class="ops-panel-heading p-4">
                    <div>
                        <h2>Repeated request groups</h2>
                        <p>Sources sending the same website actions unusually often.</p>
                    </div>
                    <span class="rounded bg-slate-800 px-2 py-1 text-[10px] font-bold text-slate-400" data-source-count>Awaiting trace</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead class="bg-slate-900/80 text-[10px] uppercase text-slate-500">
                            <tr><th>Source</th><th>Location</th><th>Repeated action</th><th>Requests</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sourceRows as $source): ?>
                                <tr class="border-slate-800 text-xs opacity-45" data-source-row>
                                    <td class="font-mono text-slate-300"><?= $source['ip'] ?></td>
                                    <td><?= $source['region'] ?></td>
                                    <td class="font-mono text-slate-400"><?= $source['signature'] ?></td>
                                    <td class="font-bold text-rose-300"><?= $source['rate'] ?></td>
                                    <td><span class="rounded bg-slate-800 px-2 py-1 text-[9px] font-black uppercase text-slate-500" data-source-verdict>Unreviewed</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="ops-panel">
                <div class="ops-panel-heading">
                    <div>
                        <h2>Customer impact</h2>
                        <p>Legitimate sessions by storefront journey.</p>
                    </div>
                    <span class="text-xs font-black text-emerald-400" data-impact-summary>0 affected</span>
                </div>
                <div class="mt-4 space-y-2">
                    <?php foreach ($customerImpact as $group): ?>
                        <div class="flex items-center gap-3 rounded-lg border border-slate-700 bg-slate-900/60 p-3" data-impact-row>
                            <span class="grid h-9 w-9 place-items-center rounded-lg bg-slate-800 text-slate-400"><i data-lucide="<?= $group['icon'] ?>" class="h-4 w-4"></i></span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs font-black"><?= $group['name'] ?></p>
                                <p class="text-[10px] text-slate-500"><?= $group['sessions'] ?> on <?= $group['route'] ?></p>
                            </div>
                            <span class="text-[9px] font-black uppercase text-emerald-400" data-impact-state>Normal</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
        </section>
    </main>

    <script src="assets/app.js"></script>
</body>
</html>

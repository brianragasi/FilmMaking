<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/products.php';
require_once __DIR__ . '/includes/scene.php';

$manager = require_store_manager();
auth_no_store();
$sceneState = read_scene_state();
$catalog = products();
$orderCount = 0;
$orderTotal = 0.0;
$recentOrders = [];
$stockTotal = 0;
$lowStockCount = 0;
$departments = [];

foreach ($catalog as $product) {
    $stock = (int) ($product['stock'] ?? 0);
    $category = (string) ($product['category'] ?? 'Other');
    $stockTotal += $stock;
    $lowStockCount += $stock < 15 ? 1 : 0;
    if (!isset($departments[$category])) {
        $departments[$category] = ['products' => 0, 'stock' => 0];
    }
    $departments[$category]['products']++;
    $departments[$category]['stock'] += $stock;
}
uasort($departments, static fn (array $left, array $right): int => $right['stock'] <=> $left['stock']);

if ($pdo = db()) {
    try {
        $orderCount = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
        $orderTotal = (float) $pdo->query('SELECT COALESCE(SUM(subtotal), 0) FROM orders')->fetchColumn();
        $recentOrders = $pdo->query('SELECT id, customer_name, subtotal, status, created_at FROM orders ORDER BY id DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $error) {
        $recentOrders = [];
    }
}

$cue = (string) $sceneState['cue'];
$statusLabel = match ($cue) {
    'sale_live' => 'Sale live',
    'outage' => 'Storefront unavailable',
    default => 'Ready for launch',
};
$statusTone = match ($cue) {
    'sale_live' => 'bg-rose-500 text-white',
    'outage' => 'bg-amber-400 text-slate-950',
    default => 'bg-emerald-400 text-slate-950',
};
?>
<!doctype html>
<html lang="en" data-theme="business">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="EcoCart store launch and sales operations.">
    <title>Store Operations | EcoCart</title>
    <link href="public/output-public.css?v=<?= htmlspecialchars((string) @filemtime(__DIR__ . '/public/output-public.css')) ?>" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="icon" href="data:,">
</head>
<body class="min-h-screen bg-[#0b0f14] text-slate-100" data-manager-console data-scene-endpoint="scene-state.php" data-scene-cue="<?= htmlspecialchars($cue) ?>" data-scene-revision="<?= (int) $sceneState['revision'] ?>">
    <header class="border-b border-white/10 bg-[#0f141b]">
        <div class="mx-auto flex min-h-[68px] w-[min(1240px,calc(100%_-_32px))] items-center gap-4">
            <span class="grid h-9 w-9 place-items-center rounded-lg bg-rose-500 text-white"><i data-lucide="store" class="h-5 w-5"></i></span>
            <div><p class="font-black">EcoCart Store Operations</p><p class="text-[10px] font-bold uppercase text-slate-500">Big Blowout launch desk</p></div>
            <span class="hidden h-6 w-px bg-white/10 sm:block"></span>
            <span class="hidden rounded bg-slate-800 px-2 py-1 text-[10px] font-black uppercase text-slate-300 sm:inline">Sales floor</span>
            <div class="ml-auto flex items-center gap-2">
                <div class="mr-2 hidden text-right md:block"><p class="text-xs font-bold"><?= htmlspecialchars((string) $manager['name']) ?></p><p class="text-[10px] text-slate-500">Store Manager</p></div>
                <a class="btn btn-square btn-sm border-slate-700 bg-slate-800 text-slate-200 hover:border-rose-400" href="index.php" aria-label="Open storefront" title="Open storefront"><i data-lucide="external-link" class="h-4 w-4"></i></a>
                <form method="post" action="logout.php"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>"><button class="btn btn-square btn-sm border-slate-700 bg-slate-800 text-slate-200 hover:border-rose-400" type="submit" aria-label="Sign out" title="Sign out"><i data-lucide="log-out" class="h-4 w-4"></i></button></form>
            </div>
        </div>
    </header>

    <main class="mx-auto w-[min(1240px,calc(100%_-_32px))] py-6">
        <?php if (isset($_GET['denied'])): ?><div class="mb-4 flex items-center gap-3 rounded-lg border border-amber-300/30 bg-amber-300/10 p-4 text-sm font-bold text-amber-200" role="alert"><i data-lucide="shield-alert" class="h-5 w-5 shrink-0"></i>Your Store Manager account does not have access to that area.</div><?php endif; ?>
        <section class="overflow-hidden rounded-lg border border-white/10 bg-[#121821]">
            <div class="grid lg:grid-cols-[1fr_360px]">
                <div class="p-6 sm:p-8">
                    <div class="flex flex-wrap items-center gap-3"><span class="rounded px-3 py-1 text-[10px] font-black uppercase <?= $statusTone ?>" data-manager-status><?= htmlspecialchars($statusLabel) ?></span><span class="text-xs text-slate-500" data-manager-clock>--:--:--</span></div>
                    <p class="mt-6 text-xs font-black uppercase text-rose-400">Big Blowout Sale</p>
                    <h1 class="mt-2 max-w-2xl text-3xl font-black leading-tight sm:text-5xl" data-manager-headline><?= $cue === 'sale_live' ? 'The sale is open.' : ($cue === 'outage' ? 'Customer ordering is paused.' : 'Customers are lining up.') ?></h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-400" data-manager-summary><?= $cue === 'sale_live' ? 'Watch orders and stock as shoppers move through checkout.' : ($cue === 'outage' ? 'Hold sale-floor announcements until the website returns.' : 'Visitor interest is rising. Pricing, stock, and checkout are prepared for launch.') ?></p>
                </div>
                <div class="border-t border-white/10 bg-[#0d1219] p-6 lg:border-l lg:border-t-0">
                    <p class="text-[10px] font-black uppercase text-slate-500" data-manager-countdown-label>Sale launch window</p>
                    <p class="mt-3 font-mono text-5xl font-black text-white" data-manager-countdown>04:59</p>
                    <div class="mt-6 h-1.5 overflow-hidden rounded-full bg-slate-800"><div class="h-full w-[72%] bg-rose-500" data-manager-progress></div></div>
                    <div class="mt-4 flex items-center justify-between text-xs"><span class="text-slate-500">Launch readiness</span><span class="font-black text-emerald-300" data-manager-readiness>All checks passed</span></div>
                </div>
            </div>
        </section>

        <section class="mt-4 grid overflow-hidden rounded-lg border border-white/10 bg-[#121821] sm:grid-cols-2 xl:grid-cols-4" aria-label="Store activity">
            <div class="border-b border-white/10 p-5 sm:border-r xl:border-b-0"><div class="flex items-center justify-between"><p class="text-[10px] font-black uppercase text-slate-500">Online visitors</p><i data-lucide="users-round" class="h-4 w-4 text-cyan-300"></i></div><p class="mt-3 text-3xl font-black" data-manager-visitors>2,340</p><p class="mt-1 text-xs font-bold text-emerald-300" data-manager-visitor-change>Rising before launch</p></div>
            <div class="border-b border-white/10 p-5 xl:border-b-0 xl:border-r"><div class="flex items-center justify-between"><p class="text-[10px] font-black uppercase text-slate-500">Carts prepared</p><i data-lucide="shopping-cart" class="h-4 w-4 text-amber-300"></i></div><p class="mt-3 text-3xl font-black" data-manager-carts>1,286</p><p class="mt-1 text-xs text-slate-500">Shoppers ready to order</p></div>
            <div class="border-b border-white/10 p-5 sm:border-r sm:border-b-0"><div class="flex items-center justify-between"><p class="text-[10px] font-black uppercase text-slate-500">Orders received</p><i data-lucide="receipt-text" class="h-4 w-4 text-rose-300"></i></div><p class="mt-3 text-3xl font-black"><?= number_format($orderCount) ?></p><p class="mt-1 text-xs text-slate-500"><?= peso($orderTotal) ?> recorded</p></div>
            <div class="p-5"><div class="flex items-center justify-between"><p class="text-[10px] font-black uppercase text-slate-500">Units available</p><i data-lucide="package-check" class="h-4 w-4 text-emerald-300"></i></div><p class="mt-3 text-3xl font-black"><?= number_format($stockTotal) ?></p><p class="mt-1 text-xs <?= $lowStockCount ? 'text-amber-300' : 'text-emerald-300' ?>"><?= $lowStockCount ?> low-stock item<?= $lowStockCount === 1 ? '' : 's' ?></p></div>
        </section>

        <div class="mt-4 grid gap-4 lg:grid-cols-[minmax(0,1.45fr)_minmax(330px,0.75fr)]">
            <section class="rounded-lg border border-white/10 bg-[#121821]">
                <div class="flex flex-wrap items-end justify-between gap-3 border-b border-white/10 p-5"><div><h2 class="font-black">Visitor momentum</h2><p class="mt-1 text-xs text-slate-500">Active shoppers approaching the sale window</p></div><div class="flex items-center gap-2 text-[10px] font-black uppercase text-cyan-300"><span class="h-2 w-2 rounded-full bg-cyan-300"></span> Live</div></div>
                <div class="p-5">
                    <div class="grid h-48 grid-cols-16 items-end gap-2 border-b border-l border-white/10 px-3 pb-3" data-manager-chart>
                        <?php foreach ([18, 24, 22, 30, 34, 31, 39, 45, 43, 52, 57, 63, 68, 72, 78, 84] as $height): ?><span class="block min-h-2 rounded-t bg-cyan-400/70 transition-all duration-500" style="height: <?= $height ?>%" data-manager-chart-bar></span><?php endforeach; ?>
                    </div>
                    <div class="mt-3 flex justify-between text-[10px] font-bold uppercase text-slate-600"><span>Earlier</span><span>Sale window</span><span>Now</span></div>
                </div>
            </section>

            <section class="rounded-lg border border-white/10 bg-[#121821]">
                <div class="border-b border-white/10 p-5"><h2 class="font-black">Launch checklist</h2><p class="mt-1 text-xs text-slate-500">What the store team confirms before opening</p></div>
                <ul class="divide-y divide-white/10 px-5">
                    <?php foreach ([['badge-percent', 'Sale pricing', 'Published'], ['boxes', 'Inventory', count($catalog) . ' products ready'], ['credit-card', 'Checkout', 'Ready for orders'], ['ticket-percent', 'Discount code', 'BIGBLOWOUT active']] as $check): ?>
                        <li class="flex items-center gap-3 py-4"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-emerald-400/10 text-emerald-300"><i data-lucide="<?= $check[0] ?>" class="h-4 w-4"></i></span><div class="min-w-0 flex-1"><p class="text-sm font-bold"><?= htmlspecialchars($check[1]) ?></p><p class="text-xs text-slate-500"><?= htmlspecialchars($check[2]) ?></p></div><i data-lucide="circle-check" class="h-4 w-4 text-emerald-300"></i></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        </div>

        <div class="mt-4 grid gap-4 lg:grid-cols-2">
            <section class="rounded-lg border border-white/10 bg-[#121821]">
                <div class="border-b border-white/10 p-5"><h2 class="font-black">Department readiness</h2><p class="mt-1 text-xs text-slate-500">Stock prepared across the Big Blowout catalog</p></div>
                <div class="divide-y divide-white/10 px-5">
                    <?php foreach ($departments as $name => $stats): $share = $stockTotal ? max(8, min(100, (int) round(((int) $stats['stock'] / $stockTotal) * 260))) : 8; ?>
                        <div class="grid grid-cols-[110px_1fr_auto] items-center gap-4 py-3"><p class="truncate text-sm font-bold"><?= htmlspecialchars((string) $name) ?></p><div class="h-1.5 overflow-hidden rounded-full bg-slate-800"><div class="h-full bg-rose-500" style="width: <?= $share ?>%"></div></div><p class="w-20 text-right text-xs text-slate-400"><?= (int) $stats['stock'] ?> units</p></div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="rounded-lg border border-white/10 bg-[#121821]">
                <div class="border-b border-white/10 p-5"><h2 class="font-black">Latest orders</h2><p class="mt-1 text-xs text-slate-500">Newest activity recorded by the storefront</p></div>
                <?php if ($recentOrders): ?><ul class="divide-y divide-white/10 px-5"><?php foreach ($recentOrders as $order): ?><li class="flex items-center gap-3 py-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-slate-800 text-slate-300"><i data-lucide="receipt" class="h-4 w-4"></i></span><div class="min-w-0 flex-1"><p class="truncate text-sm font-bold">#<?= (int) $order['id'] ?> &middot; <?= htmlspecialchars((string) $order['customer_name']) ?></p><p class="text-xs text-slate-500"><?= htmlspecialchars(date('M j, g:i A', strtotime((string) $order['created_at']))) ?></p></div><div class="text-right"><p class="text-sm font-black"><?= peso((float) $order['subtotal']) ?></p><p class="text-[10px] uppercase text-amber-300"><?= htmlspecialchars((string) $order['status']) ?></p></div></li><?php endforeach; ?></ul><?php else: ?><div class="flex min-h-52 flex-col items-center justify-center p-6 text-center"><span class="grid h-12 w-12 place-items-center rounded-lg bg-slate-800 text-slate-400"><i data-lucide="inbox" class="h-6 w-6"></i></span><p class="mt-4 font-black">Waiting for the first order</p><p class="mt-1 text-xs text-slate-500">New sale orders will appear here.</p></div><?php endif; ?>
            </section>
        </div>
    </main>

    <script src="assets/manager.js?v=<?= htmlspecialchars((string) @filemtime(__DIR__ . '/assets/manager.js')) ?>"></script>
    <script>lucide.createIcons();</script>
</body>
</html>

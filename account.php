<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/products.php';

$user = require_login();
auth_no_store();
$firstName = trim(explode(' ', (string) $user['name'])[0] ?? (string) $user['name']);

$role = (string) ($user['role'] ?? 'customer');
$isAdmin = in_array($role, ['admin', 'director'], true);
$roleLabel = match ($role) {
    'director' => 'Production director',
    'admin' => 'Operations admin',
    default => 'Customer',
};
$memberSince = null;
$orderCount = 0;
$orderSpend = 0.0;
$recentOrders = [];

if ($pdo = db()) {
    try {
        $createdStmt = $pdo->prepare('SELECT created_at FROM users WHERE id = :id LIMIT 1');
        $createdStmt->execute(['id' => (int) $user['id']]);
        $memberSince = $createdStmt->fetchColumn() ?: null;

        if ($isAdmin) {
            $orderCount = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
            $orderSpend = (float) $pdo->query('SELECT COALESCE(SUM(subtotal), 0) FROM orders')->fetchColumn();
            $recentOrders = $pdo->query('SELECT id, customer_name, email, subtotal, status, cart_json, created_at FROM orders ORDER BY id DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $statsStmt = $pdo->prepare('SELECT COUNT(*), COALESCE(SUM(subtotal), 0) FROM orders WHERE email = :email');
            $statsStmt->execute(['email' => (string) $user['email']]);
            [$orderCount, $orderSpend] = array_map('floatval', (array) $statsStmt->fetch(PDO::FETCH_NUM));
            $orderCount = (int) $orderCount;

            $ordersStmt = $pdo->prepare('SELECT id, customer_name, email, subtotal, status, cart_json, created_at FROM orders WHERE email = :email ORDER BY id DESC LIMIT 5');
            $ordersStmt->execute(['email' => (string) $user['email']]);
            $recentOrders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Throwable $error) {
        $recentOrders = [];
    }
}
?>
<!doctype html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Manage your EcoCart account.">
    <title>My Account | EcoCart</title>
    <link href="public/output-public.css?v=<?= htmlspecialchars((string) @filemtime(__DIR__ . '/public/output-public.css')) ?>" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="icon" href="data:,">
</head>
<body class="flex min-h-screen flex-col bg-[#f4f5f7] text-slate-950">
    <div class="bg-rose-600 text-white">
        <div class="app-shell flex min-h-9 items-center justify-center gap-2 text-[10px] font-black uppercase">
            <i data-lucide="badge-check" class="h-3.5 w-3.5"></i>
            Account session protected
        </div>
    </div>

    <header class="border-b border-slate-200 bg-white">
        <div class="app-shell flex min-h-[72px] items-center gap-4">
            <a class="flex items-center gap-2" href="index.php" aria-label="EcoCart home">
                <span class="grid h-10 w-10 place-items-center rounded-lg bg-slate-950 text-white"><i data-lucide="leaf" class="h-5 w-5"></i></span>
                <span class="text-2xl font-black">Eco<span class="text-rose-600">Cart.</span></span>
            </a>
            <nav class="ml-auto flex items-center gap-2" aria-label="Account actions">
                <?php if ($isAdmin): ?>
                    <a class="btn btn-sm border-cyan-200 bg-cyan-50 text-cyan-800 hover:bg-cyan-100" href="admin.php">
                        <i data-lucide="activity" class="h-4 w-4"></i> Operations
                    </a>
                <?php endif; ?>
                <?php if ($role === 'director'): ?>
                    <a class="btn btn-sm border-rose-200 bg-rose-50 text-rose-800 hover:bg-rose-100" href="director.php">
                        <i data-lucide="clapperboard" class="h-4 w-4"></i> Director
                    </a>
                <?php endif; ?>
                <a class="btn btn-sm border-slate-200 bg-white text-slate-700 hover:border-slate-950" href="index.php">
                    <i data-lucide="store" class="h-4 w-4"></i> Store
                </a>
            </nav>
        </div>
    </header>

    <main class="app-shell w-full flex-1 py-6 sm:py-8">
        <?php if (isset($_GET['denied'])): ?>
            <div class="mb-5 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900" role="alert">
                <div class="flex items-start gap-3">
                    <i data-lucide="shield-alert" class="mt-0.5 h-5 w-5 shrink-0"></i>
                    <p>Your account does not have access to that area.</p>
                </div>
            </div>
        <?php endif; ?>

        <section class="mx-auto max-w-5xl overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="grid bg-slate-950 text-white md:grid-cols-[1fr_280px]">
                <div class="flex items-center gap-4 p-6 sm:p-8">
                    <span class="grid h-14 w-14 shrink-0 place-items-center rounded-lg bg-rose-600 text-xl font-black">
                        <?= htmlspecialchars(strtoupper(substr($firstName, 0, 1))) ?>
                    </span>
                    <div>
                        <p class="text-xs font-black uppercase text-rose-400">My EcoCart</p>
                        <h1 class="mt-1 text-3xl font-black leading-tight">Hi, <?= htmlspecialchars($firstName) ?>.</h1>
                        <p class="mt-2 max-w-xl text-sm leading-6 text-slate-300">Your account is ready whenever you return to the sale.</p>
                    </div>
                </div>
                <div class="border-t border-white/10 p-6 md:border-l md:border-t-0 sm:p-8">
                    <p class="text-xs font-black uppercase text-slate-400">Signed in as</p>
                    <p class="mt-2 break-all text-sm font-bold"><?= htmlspecialchars((string) $user['email']) ?></p>
                    <p class="mt-4 inline-flex rounded-full bg-emerald-400/12 px-3 py-1 text-xs font-black uppercase text-emerald-300">
                        <?= htmlspecialchars($roleLabel) ?>
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 divide-y divide-slate-100 border-b border-slate-100 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                <div class="flex items-center gap-3 p-5">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-rose-100 text-rose-600"><i data-lucide="shopping-bag" class="h-5 w-5"></i></span>
                    <div><p class="text-xl font-black"><?= number_format($orderCount) ?></p><p class="text-xs font-bold text-slate-500"><?= $isAdmin ? 'Store orders' : 'Orders placed' ?></p></div>
                </div>
                <div class="flex items-center gap-3 p-5">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-emerald-100 text-emerald-700"><i data-lucide="wallet" class="h-5 w-5"></i></span>
                    <div><p class="text-xl font-black"><?= peso($orderSpend) ?></p><p class="text-xs font-bold text-slate-500"><?= $isAdmin ? 'Store revenue' : 'Total spent' ?></p></div>
                </div>
                <div class="flex items-center gap-3 p-5">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-cyan-100 text-cyan-700"><i data-lucide="calendar-days" class="h-5 w-5"></i></span>
                    <div><p class="text-xl font-black"><?= $memberSince ? htmlspecialchars(date('M Y', strtotime((string) $memberSince))) : 'New' ?></p><p class="text-xs font-bold text-slate-500">Member since</p></div>
                </div>
            </div>

            <div class="grid gap-8 p-6 sm:p-8 lg:grid-cols-[1fr_260px]">
                <div>
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 place-items-center rounded-lg bg-emerald-100 text-emerald-700"><i data-lucide="shield-check" class="h-5 w-5"></i></span>
                        <div>
                            <h2 class="font-black">Account details</h2>
                            <p class="text-xs text-slate-500">Saved for checkout</p>
                        </div>
                    </div>

                    <dl class="mt-5 divide-y divide-slate-100 border-y border-slate-100">
                        <div class="grid gap-1 py-3 sm:grid-cols-[132px_1fr]"><dt class="text-xs font-black uppercase text-slate-400">Name</dt><dd class="font-bold"><?= htmlspecialchars((string) $user['name']) ?></dd></div>
                        <div class="grid gap-1 py-3 sm:grid-cols-[132px_1fr]"><dt class="text-xs font-black uppercase text-slate-400">Email</dt><dd class="break-all font-bold"><?= htmlspecialchars((string) $user['email']) ?></dd></div>
                        <div class="grid gap-1 py-3 sm:grid-cols-[132px_1fr]"><dt class="text-xs font-black uppercase text-slate-400">Account type</dt><dd class="font-bold"><?= htmlspecialchars($roleLabel) ?></dd></div>
                    </dl>
                </div>

                <aside class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <h2 class="text-sm font-black">Quick actions</h2>
                    <div class="mt-4 grid gap-2">
                        <a class="btn min-h-10 border-0 bg-rose-600 text-white hover:bg-rose-700" href="index.php#products"><i data-lucide="shopping-bag" class="h-4 w-4"></i> Shop products</a>
                        <a class="btn min-h-10 border-slate-200 bg-white hover:border-slate-950" href="checkout.php"><i data-lucide="credit-card" class="h-4 w-4"></i> Checkout</a>
                        <form method="post" action="logout.php">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                            <button class="btn min-h-10 w-full border-rose-200 bg-white text-rose-700 hover:border-rose-600 hover:bg-rose-50" type="submit"><i data-lucide="log-out" class="h-4 w-4"></i> Sign out</button>
                        </form>
                    </div>
                </aside>
            </div>

            <div class="border-t border-slate-100 p-6 sm:p-8">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 place-items-center rounded-lg bg-slate-950 text-white"><i data-lucide="receipt" class="h-5 w-5"></i></span>
                        <div>
                            <h2 class="font-black"><?= $isAdmin ? 'Recent store orders' : 'Recent orders' ?></h2>
                            <p class="text-xs text-slate-500"><?= $isAdmin ? 'Latest orders across EcoCart' : 'Your latest EcoCart orders' ?></p>
                        </div>
                    </div>
                    <a class="text-xs font-black text-rose-600 hover:text-rose-700" href="index.php#products">Shop again &rarr;</a>
                </div>

                <?php if ($recentOrders): ?>
                    <ul class="mt-5 divide-y divide-slate-100 border-y border-slate-100">
                        <?php foreach ($recentOrders as $order): ?>
                            <?php
                                $items = json_decode((string) ($order['cart_json'] ?? '[]'), true);
                                $itemCount = 0;
                                foreach (is_array($items) ? $items : [] as $it) { $itemCount += (int) ($it['quantity'] ?? 0); }
                                $status = (string) ($order['status'] ?? 'Pending');
                                $statusClass = $status === 'Delivered' ? 'bg-emerald-100 text-emerald-700' : ($status === 'Shipped' ? 'bg-cyan-100 text-cyan-700' : 'bg-amber-100 text-amber-700');
                            ?>
                            <li class="flex flex-wrap items-center gap-3 py-4">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-500"><i data-lucide="package" class="h-5 w-5"></i></span>
                                <div class="min-w-0 flex-1">
                                    <p class="font-bold">Order #<?= (int) $order['id'] ?><?= $isAdmin ? ' &middot; ' . htmlspecialchars((string) $order['customer_name']) : '' ?></p>
                                    <p class="text-xs text-slate-500"><?= htmlspecialchars(date('M j, Y', strtotime((string) $order['created_at']))) ?> &middot; <?= $itemCount ?> item<?= $itemCount === 1 ? '' : 's' ?></p>
                                </div>
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase <?= $statusClass ?>"><?= htmlspecialchars($status) ?></span>
                                <p class="w-24 shrink-0 text-right font-black"><?= peso((float) $order['subtotal']) ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="mt-5 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                        <span class="mx-auto grid h-12 w-12 place-items-center rounded-lg bg-rose-100 text-rose-600"><i data-lucide="shopping-cart" class="h-6 w-6"></i></span>
                        <p class="mt-3 font-black">No orders yet.</p>
                        <p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">Your Big Blowout orders will appear here once you check out.</p>
                        <a class="btn mt-4 border-0 bg-slate-950 text-white hover:bg-rose-600" href="index.php#products">Browse sale items</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer class="mt-6 border-t border-slate-200 bg-white">
        <div class="app-shell flex flex-wrap items-center justify-between gap-3 py-5 text-xs text-slate-500">
            <p>&copy; <?= date('Y') ?> EcoCart</p>
            <div class="flex items-center gap-4"><span>Secure order handling</span><span>Customer support: +63 917 555 0142</span></div>
        </div>
    </footer>

    <script>lucide.createIcons();</script>
</body>
</html>

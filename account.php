<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/products.php';
require_once __DIR__ . '/includes/discussions.php';

$user = refresh_authenticated_user(require_login());
auth_no_store();
$accountNotice = isset($_SESSION['account_notice']) && is_string($_SESSION['account_notice']) ? $_SESSION['account_notice'] : null;
unset($_SESSION['account_notice']);

$profile = customer_profile($user);
$avatarUrl = profile_avatar_url($profile);
$firstName = trim(explode(' ', (string) $user['name'])[0] ?? (string) $user['name']);
$role = (string) ($user['role'] ?? 'customer');
$isAdmin = $role === 'admin';
$roleLabel = match ($role) {
    'director' => 'Production director',
    'admin' => 'Operations admin',
    default => 'Customer',
};
$memberSince = null;
$orderCount = 0;
$orderSpend = 0.0;
$commentCount = 0;
$recentOrders = [];

if ($pdo = db()) {
    try {
        if ((int) $user['id'] > 0) {
            $createdStmt = $pdo->prepare('SELECT created_at FROM ecocart_users WHERE id = :id LIMIT 1');
            $createdStmt->execute(['id' => (int) $user['id']]);
            $memberSince = $createdStmt->fetchColumn() ?: null;
            $commentStmt = $pdo->prepare('SELECT COUNT(*) FROM product_discussions WHERE user_id = :id AND is_deleted = 0');
            $commentStmt->execute(['id' => (int) $user['id']]);
            $commentCount = (int) $commentStmt->fetchColumn();
        }
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

$profileComplete = $avatarUrl || trim((string) $profile['bio']) !== '';
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
<body class="min-h-screen bg-[#eef0f4] text-slate-950">
    <div class="bg-slate-950 text-white"><div class="mx-auto flex min-h-8 w-[min(1180px,calc(100%_-_32px))] items-center justify-center gap-2 text-[10px] font-black uppercase"><i data-lucide="shield-check" class="h-3.5 w-3.5 text-emerald-300"></i> Account session protected</div></div>
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex min-h-[70px] w-[min(1180px,calc(100%_-_32px))] items-center gap-4">
            <a class="flex items-center gap-2" href="index.php"><span class="grid h-10 w-10 place-items-center rounded-lg bg-slate-950 text-white"><i data-lucide="leaf" class="h-5 w-5"></i></span><span class="text-2xl font-black">Eco<span class="text-rose-600">Cart.</span></span></a>
            <nav class="ml-auto flex items-center gap-2" aria-label="Account actions">
                <?php if ($role === 'admin'): ?><a class="btn btn-sm border-cyan-200 bg-cyan-50 text-cyan-800" href="admin.php"><i data-lucide="activity" class="h-4 w-4"></i> Operations</a><?php endif; ?>
                <?php if ($role === 'director'): ?><a class="btn btn-sm border-rose-200 bg-rose-50 text-rose-800" href="director.php"><i data-lucide="clapperboard" class="h-4 w-4"></i> Director</a><?php endif; ?>
                <a class="btn btn-sm border-slate-200 bg-white" href="index.php"><i data-lucide="store" class="h-4 w-4"></i> Store</a>
            </nav>
        </div>
    </header>

    <main class="mx-auto w-[min(1180px,calc(100%_-_32px))] py-5 sm:py-7">
        <?php if ($accountNotice): ?><div class="mb-5 flex items-center gap-3 rounded-lg border border-emerald-300 bg-emerald-50 p-4 text-sm font-bold text-emerald-900" role="status"><i data-lucide="circle-check" class="h-5 w-5 shrink-0"></i><?= htmlspecialchars($accountNotice) ?></div><?php endif; ?>
        <?php if (isset($_GET['denied'])): ?><div class="mb-5 flex items-center gap-3 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm font-bold text-amber-900" role="alert"><i data-lucide="shield-alert" class="h-5 w-5 shrink-0"></i>Your account does not have access to that area.</div><?php endif; ?>

        <section class="overflow-hidden rounded-lg bg-slate-950 text-white shadow-xl">
            <div class="grid lg:grid-cols-[1fr_460px]">
                <div class="flex items-center gap-5 p-6 sm:p-8">
                    <div class="h-20 w-20 shrink-0 overflow-hidden rounded-lg <?= htmlspecialchars(avatar_class((string) $profile['avatar_style'])) ?>">
                        <?php if ($avatarUrl): ?><img class="h-full w-full object-cover" src="<?= htmlspecialchars($avatarUrl) ?>" alt="<?= htmlspecialchars((string) $user['name']) ?> profile picture"><?php else: ?><span class="grid h-full w-full place-items-center text-3xl font-black"><?= htmlspecialchars(profile_initial((string) $user['name'])) ?></span><?php endif; ?>
                    </div>
                    <div class="min-w-0"><p class="text-[10px] font-black uppercase text-rose-400">My EcoCart</p><h1 class="mt-1 text-3xl font-black leading-tight">Hi, <?= htmlspecialchars($firstName) ?>.</h1><p class="mt-2 line-clamp-2 max-w-xl text-sm leading-6 text-slate-400"><?= trim((string) $profile['bio']) !== '' ? htmlspecialchars((string) $profile['bio']) : 'Your account is ready for the next Big Blowout find.' ?></p></div>
                </div>
                <div class="grid grid-cols-3 border-t border-white/10 lg:border-l lg:border-t-0">
                    <div class="grid content-center border-r border-white/10 p-4 text-center"><p class="text-2xl font-black"><?= number_format($orderCount) ?></p><p class="mt-1 text-[10px] font-bold uppercase text-slate-500">Orders</p></div>
                    <div class="grid content-center border-r border-white/10 p-4 text-center"><p class="text-xl font-black text-emerald-300"><?= peso($orderSpend) ?></p><p class="mt-1 text-[10px] font-bold uppercase text-slate-500">Spent</p></div>
                    <div class="grid content-center p-4 text-center"><p class="text-2xl font-black text-cyan-300"><?= number_format($commentCount) ?></p><p class="mt-1 text-[10px] font-bold uppercase text-slate-500">Discussions</p></div>
                </div>
            </div>
        </section>

        <div class="mt-5 grid gap-5 lg:grid-cols-[minmax(0,1fr)_340px]">
            <section class="rounded-lg border border-slate-200 bg-white">
                <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 p-5 sm:px-6">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-rose-100 text-rose-700"><i data-lucide="contact" class="h-5 w-5"></i></span>
                    <div><h2 class="font-black">Customer profile</h2><p class="text-xs text-slate-500">Identity shown beside product discussions</p></div>
                    <?php if ($role === 'customer'): ?><a class="btn btn-sm ml-auto border-slate-200 bg-white hover:border-rose-300 hover:bg-rose-50" href="profile-setup.php?next=account.php"><i data-lucide="pencil" class="h-4 w-4"></i> Edit profile</a><?php endif; ?>
                </div>
                <div class="grid gap-x-8 gap-y-4 p-5 sm:grid-cols-2 sm:p-6">
                    <div><p class="text-[10px] font-black uppercase text-slate-400">Display name</p><p class="mt-1 font-black"><?= htmlspecialchars((string) $user['name']) ?></p></div>
                    <div class="min-w-0"><p class="text-[10px] font-black uppercase text-slate-400">Email address</p><p class="mt-1 break-words text-sm font-black sm:text-base"><?= htmlspecialchars((string) $user['email']) ?></p></div>
                    <div><p class="text-[10px] font-black uppercase text-slate-400">Account type</p><p class="mt-1 font-black"><?= htmlspecialchars($roleLabel) ?></p></div>
                    <div><p class="text-[10px] font-black uppercase text-slate-400">Member since</p><p class="mt-1 font-black"><?= $memberSince ? htmlspecialchars(date('F Y', strtotime((string) $memberSince))) : 'New member' ?></p></div>
                    <div class="sm:col-span-2"><p class="text-[10px] font-black uppercase text-slate-400">Profile note</p><p class="mt-1 text-sm font-semibold leading-6 text-slate-600"><?= trim((string) $profile['bio']) !== '' ? htmlspecialchars((string) $profile['bio']) : 'No profile note yet. Add one to make your product comments feel more personal.' ?></p></div>
                </div>
                <?php if ($role === 'customer' && !$profileComplete): ?><div class="flex flex-col gap-3 border-t border-amber-200 bg-amber-50 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-6"><div class="flex items-center gap-3"><i data-lucide="sparkles" class="h-5 w-5 text-amber-700"></i><p class="text-xs font-bold text-amber-900">Your profile is almost there. Add a photo or short note.</p></div><a class="text-xs font-black text-amber-800 underline" href="profile-setup.php?next=account.php">Finish profile</a></div><?php endif; ?>
            </section>

            <aside class="rounded-lg border border-slate-200 bg-white p-5">
                <div class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-lg bg-slate-950 text-white"><i data-lucide="zap" class="h-5 w-5"></i></span><div><h2 class="font-black">Quick actions</h2><p class="text-xs text-slate-500">Continue where you left off</p></div></div>
                <div class="mt-5 grid gap-2">
                    <a class="btn min-h-11 border-0 bg-rose-600 text-white hover:bg-rose-700" href="index.php#products"><i data-lucide="shopping-bag" class="h-4 w-4"></i> Shop products</a>
                    <a class="btn min-h-11 border-slate-200 bg-white" href="checkout.php"><i data-lucide="credit-card" class="h-4 w-4"></i> Open checkout</a>
                    <?php if ($role === 'customer'): ?><a class="btn min-h-11 border-slate-200 bg-white" href="profile-setup.php?next=account.php"><i data-lucide="image-plus" class="h-4 w-4"></i> Change photo</a><?php endif; ?>
                    <form method="post" action="logout.php"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>"><button class="btn min-h-11 w-full border-rose-200 bg-white text-rose-700 hover:bg-rose-50" type="submit"><i data-lucide="log-out" class="h-4 w-4"></i> Sign out</button></form>
                </div>
            </aside>
        </div>

        <section class="mt-5 rounded-lg border border-slate-200 bg-white">
            <div class="flex items-center gap-3 border-b border-slate-100 p-5 sm:px-6"><span class="grid h-10 w-10 place-items-center rounded-lg bg-cyan-100 text-cyan-700"><i data-lucide="package-check" class="h-5 w-5"></i></span><div><h2 class="font-black"><?= $isAdmin ? 'Recent store orders' : 'Recent orders' ?></h2><p class="text-xs text-slate-500"><?= $isAdmin ? 'Latest orders across EcoCart' : 'Your latest purchases and their status' ?></p></div><a class="ml-auto text-xs font-black text-rose-600" href="index.php#products">Shop again &rarr;</a></div>
            <?php if ($recentOrders): ?>
                <ul class="divide-y divide-slate-100 px-5 sm:px-6">
                    <?php foreach ($recentOrders as $order): ?>
                        <?php $items = json_decode((string) ($order['cart_json'] ?? '[]'), true); $itemCount = 0; foreach (is_array($items) ? $items : [] as $item) { $itemCount += (int) ($item['quantity'] ?? 0); } $status = (string) ($order['status'] ?? 'Pending'); $statusClass = $status === 'Delivered' ? 'bg-emerald-100 text-emerald-700' : ($status === 'Shipped' ? 'bg-cyan-100 text-cyan-700' : 'bg-amber-100 text-amber-700'); ?>
                        <li class="flex flex-wrap items-center gap-3 py-4"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-500"><i data-lucide="package" class="h-5 w-5"></i></span><div class="min-w-0 flex-1"><p class="font-bold">Order #<?= (int) $order['id'] ?><?= $isAdmin ? ' &middot; ' . htmlspecialchars((string) $order['customer_name']) : '' ?></p><p class="text-xs text-slate-500"><?= htmlspecialchars(date('M j, Y', strtotime((string) $order['created_at']))) ?> &middot; <?= $itemCount ?> item<?= $itemCount === 1 ? '' : 's' ?></p></div><span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase <?= $statusClass ?>"><?= htmlspecialchars($status) ?></span><p class="w-24 shrink-0 text-right font-black"><?= peso((float) $order['subtotal']) ?></p></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="flex flex-col items-center justify-between gap-4 p-6 text-center sm:flex-row sm:text-left"><div class="flex flex-col items-center gap-3 sm:flex-row"><span class="grid h-12 w-12 place-items-center rounded-lg bg-rose-50 text-rose-600"><i data-lucide="shopping-cart" class="h-6 w-6"></i></span><div><p class="font-black">Your first order starts here.</p><p class="mt-1 text-sm text-slate-500">Browse the sale, add your essentials, and they will appear in this timeline.</p></div></div><a class="btn shrink-0 border-0 bg-slate-950 text-white" href="index.php#products">Browse sale items</a></div>
            <?php endif; ?>
        </section>
    </main>

    <footer class="mt-6 border-t border-slate-200 bg-white"><div class="mx-auto flex flex-wrap items-center justify-between gap-3 py-5 text-xs text-slate-500 w-[min(1180px,calc(100%_-_32px))]"><p>&copy; <?= date('Y') ?> EcoCart</p><p>Secure account &middot; Saved cart &middot; Customer discussions</p></div></footer>
    <script>lucide.createIcons();</script>
</body>
</html>

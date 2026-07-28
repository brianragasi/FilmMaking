<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$user = require_login();
auth_no_store();
$firstName = trim(explode(' ', (string) $user['name'])[0] ?? (string) $user['name']);
?>
<!doctype html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Manage your EcoCart account.">
    <title>My Account | EcoCart</title>
    <link href="public/output.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="icon" href="data:,">
</head>
<body class="min-h-screen bg-[#f4f5f7] text-slate-950">
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
                <?php if (($user['role'] ?? '') === 'admin'): ?>
                    <a class="btn btn-sm border-cyan-200 bg-cyan-50 text-cyan-800 hover:bg-cyan-100" href="admin.php">
                        <i data-lucide="activity" class="h-4 w-4"></i> Operations
                    </a>
                <?php endif; ?>
                <a class="btn btn-sm border-slate-200 bg-white text-slate-700 hover:border-slate-950" href="index.php">
                    <i data-lucide="store" class="h-4 w-4"></i> Store
                </a>
            </nav>
        </div>
    </header>

    <main class="app-shell py-8 sm:py-12">
        <?php if (isset($_GET['denied'])): ?>
            <div class="mb-5 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900" role="alert">
                <div class="flex items-start gap-3">
                    <i data-lucide="shield-alert" class="mt-0.5 h-5 w-5 shrink-0"></i>
                    <p>Your account does not have access to that area.</p>
                </div>
            </div>
        <?php endif; ?>

        <section class="grid overflow-hidden rounded-lg border border-slate-200 bg-white lg:grid-cols-[0.8fr_1.2fr]">
            <div class="bg-slate-950 p-7 text-white sm:p-10">
                <span class="grid h-14 w-14 place-items-center rounded-lg bg-rose-600 text-xl font-black">
                    <?= htmlspecialchars(strtoupper(substr($firstName, 0, 1))) ?>
                </span>
                <p class="mt-8 text-xs font-black uppercase text-rose-400">My EcoCart</p>
                <h1 class="mt-2 text-3xl font-black">Hi, <?= htmlspecialchars($firstName) ?>.</h1>
                <p class="mt-3 max-w-sm text-sm leading-6 text-slate-300">Your account is ready whenever you return to the sale.</p>
            </div>

            <div class="p-7 sm:p-10">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-emerald-100 text-emerald-700"><i data-lucide="shield-check" class="h-5 w-5"></i></span>
                    <div>
                        <h2 class="font-black">Account details</h2>
                        <p class="text-xs text-slate-500">Signed in securely</p>
                    </div>
                </div>

                <dl class="mt-7 divide-y divide-slate-100 border-y border-slate-100">
                    <div class="grid gap-1 py-4 sm:grid-cols-[150px_1fr]"><dt class="text-xs font-black uppercase text-slate-400">Name</dt><dd class="font-bold"><?= htmlspecialchars((string) $user['name']) ?></dd></div>
                    <div class="grid gap-1 py-4 sm:grid-cols-[150px_1fr]"><dt class="text-xs font-black uppercase text-slate-400">Email</dt><dd class="break-all font-bold"><?= htmlspecialchars((string) $user['email']) ?></dd></div>
                    <div class="grid gap-1 py-4 sm:grid-cols-[150px_1fr]"><dt class="text-xs font-black uppercase text-slate-400">Account type</dt><dd class="font-bold"><?= ($user['role'] ?? '') === 'admin' ? 'Operations administrator' : 'Customer' ?></dd></div>
                </dl>

                <div class="mt-7 flex flex-wrap gap-3">
                    <a class="btn border-0 bg-rose-600 text-white hover:bg-rose-700" href="index.php#products"><i data-lucide="shopping-bag" class="h-4 w-4"></i> Shop products</a>
                    <a class="btn border-slate-200 bg-white hover:border-slate-950" href="checkout.php"><i data-lucide="credit-card" class="h-4 w-4"></i> Go to checkout</a>
                    <form method="post" action="logout.php">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                        <button class="btn btn-ghost text-slate-500 hover:bg-slate-100" type="submit"><i data-lucide="log-out" class="h-4 w-4"></i> Sign out</button>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <script>lucide.createIcons();</script>
</body>
</html>

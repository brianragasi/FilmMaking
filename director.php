<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/scene.php';
require_once __DIR__ . '/includes/discussions.php';

$director = require_director();
auth_no_store();

$sceneState = read_scene_state();
$recentDiscussions = recent_product_discussions();
$moderationProducts = [];
foreach ($recentDiscussions as $moderationComment) {
    $moderationProducts[(int) $moderationComment['product_id']] = (string) ($moderationComment['product_name'] ?: 'Product #' . $moderationComment['product_id']);
}
asort($moderationProducts, SORT_NATURAL | SORT_FLAG_CASE);
$customerProfiles = director_customer_profiles();
$customerNotice = isset($_SESSION['director_customer_notice']) && is_array($_SESSION['director_customer_notice'])
    ? $_SESSION['director_customer_notice']
    : null;
unset($_SESSION['director_customer_notice']);
$directorNotice = isset($_SESSION['director_notice']) && is_array($_SESSION['director_notice'])
    ? $_SESSION['director_notice']
    : null;
unset($_SESSION['director_notice']);
$cueOrder = ['restored', 'sale_live', 'outage'];
$cueScripts = [
    'restored' => [
        'number' => '01',
        'icon' => 'power',
        'button' => 'Open website',
        'title' => 'Website is open',
        'short' => 'EcoCart is available to every customer screen.',
        'result' => 'Customer screens return to the working EcoCart storefront.',
        'tone' => 'emerald',
    ],
    'sale_live' => [
        'number' => '02',
        'icon' => 'badge-percent',
        'button' => 'Start sale',
        'title' => 'Sale is live',
        'short' => 'The Big Blowout Sale announcement appears on customer screens.',
        'result' => 'A bold SALE IS LIVE NOW takeover appears, then the storefront continues.',
        'tone' => 'rose',
    ],
    'outage' => [
        'number' => '03',
        'icon' => 'server-crash',
        'button' => 'Shut down website',
        'title' => 'Website is down',
        'short' => 'Customer screens switch to the EcoCart server-error scene.',
        'result' => 'The storefront is replaced by the filmed ERROR 503 screen.',
        'tone' => 'amber',
    ],
];

$currentCue = isset($cueScripts[$sceneState['cue']])
    ? (string) $sceneState['cue']
    : 'restored';
?>
<!doctype html>
<html lang="en" data-theme="business">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="EcoCart director website remote.">
    <title>Director Remote | EcoCart</title>
    <link href="public/output-public.css?v=<?= htmlspecialchars((string) @filemtime(__DIR__ . '/public/output-public.css')) ?>" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="icon" href="data:,">
</head>
<body class="min-h-screen bg-[#090b10] text-slate-100" data-director-console data-scene-endpoint="scene-state.php" data-csrf-token="<?= htmlspecialchars(csrf_token()) ?>" data-initial-state="<?= htmlspecialchars(json_encode(scene_public_payload($sceneState)), ENT_QUOTES, 'UTF-8') ?>">
    <header class="border-b border-white/10 bg-[#11141a]">
        <div class="mx-auto flex min-h-[68px] w-[min(1180px,calc(100%_-_32px))] items-center gap-3">
            <span class="grid h-9 w-9 place-items-center rounded-lg bg-rose-600 text-white">
                <i data-lucide="clapperboard" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-sm font-black">OVERLOAD</p>
                <p class="text-[10px] font-bold uppercase text-slate-500">Director website remote</p>
            </div>
            <div class="ml-auto flex items-center gap-2">
                <span class="hidden rounded bg-white/5 px-2 py-1 text-[10px] font-black uppercase text-slate-400 sm:inline-flex">
                    Director account
                </span>
                <a class="btn btn-square btn-sm border-white/10 bg-white/5 text-slate-300 hover:border-cyan-400" href="index.php" target="_blank" aria-label="Open storefront" title="Open storefront">
                    <i data-lucide="store" class="h-4 w-4"></i>
                </a>
                <form method="post" action="logout.php">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <button class="btn btn-square btn-sm border-white/10 bg-white/5 text-slate-300 hover:border-rose-400" type="submit" aria-label="Sign out" title="Sign out">
                        <i data-lucide="log-out" class="h-4 w-4"></i>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="mx-auto grid min-h-[calc(100vh-68px)] w-[min(1180px,calc(100%_-_32px))] content-start py-8 sm:py-10">
        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
            <section class="overflow-hidden rounded-lg border border-white/10 bg-[#151820]">
                <div class="border-b border-white/10 p-5 sm:p-7">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-[10px] font-black uppercase text-slate-500">Website control</p>
                        <span class="rounded bg-emerald-400/10 px-2 py-0.5 text-[9px] font-black uppercase text-emerald-300" data-save-state>Synced</span>
                    </div>
                    <h1 class="mt-2 text-3xl font-black sm:text-4xl">Choose what happens on screen.</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-400">Only the storefront display changes. Use one control when the matching moment happens in the film.</p>
                </div>

                <div class="grid gap-3 p-4 sm:grid-cols-3 sm:p-5">
                    <?php foreach ($cueOrder as $cue): ?>
                        <?php $script = $cueScripts[$cue]; ?>
                        <?php
                        $buttonClass = match ($script['tone']) {
                            'emerald' => 'border-emerald-400/25 bg-emerald-400/10 hover:border-emerald-300 hover:bg-emerald-400/15',
                            'rose' => 'border-rose-400/25 bg-rose-400/10 hover:border-rose-300 hover:bg-rose-400/15',
                            default => 'border-amber-400/25 bg-amber-400/10 hover:border-amber-300 hover:bg-amber-400/15',
                        };
                        $iconClass = match ($script['tone']) {
                            'emerald' => 'bg-emerald-400 text-emerald-950',
                            'rose' => 'bg-rose-500 text-white',
                            default => 'bg-amber-400 text-amber-950',
                        };
                        ?>
                        <button class="group flex min-h-44 flex-col items-start rounded-lg border p-5 text-left transition <?= $buttonClass ?>" type="button" data-cue-button="<?= htmlspecialchars($cue) ?>">
                            <span class="grid h-11 w-11 place-items-center rounded-lg <?= $iconClass ?>">
                                <i data-lucide="<?= htmlspecialchars($script['icon']) ?>" class="h-5 w-5"></i>
                            </span>
                            <span class="mt-auto pt-6">
                                <strong class="block text-lg font-black"><?= htmlspecialchars($script['button']) ?></strong>
                                <span class="mt-1 block text-xs leading-5 text-slate-400"><?= htmlspecialchars($script['short']) ?></span>
                            </span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </section>

            <aside class="overflow-hidden rounded-lg border border-white/10 bg-[#151820]">
                <div class="border-b border-white/10 p-5">
                    <p class="text-[10px] font-black uppercase text-slate-500">Current website state</p>
                    <div class="mt-4 flex items-center gap-4">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-lg bg-white/5 text-rose-300" data-current-icon>
                            <i data-lucide="<?= htmlspecialchars($cueScripts[$currentCue]['icon']) ?>" class="h-6 w-6"></i>
                        </span>
                        <div>
                            <p class="font-mono text-xs font-black text-rose-300" data-current-number><?= htmlspecialchars($cueScripts[$currentCue]['number']) ?></p>
                            <h2 class="mt-1 text-xl font-black" data-current-title><?= htmlspecialchars($cueScripts[$currentCue]['title']) ?></h2>
                        </div>
                    </div>
                </div>

                <div class="p-5">
                    <p class="text-[10px] font-black uppercase text-cyan-300">What the audience sees</p>
                    <p class="mt-2 text-sm leading-6 text-slate-300" data-current-summary><?= htmlspecialchars($cueScripts[$currentCue]['result']) ?></p>
                    <div class="mt-6 flex items-center gap-3 border-t border-white/10 pt-5">
                        <span class="relative flex h-3 w-3">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-50"></span>
                            <span class="relative inline-flex h-3 w-3 rounded-full bg-emerald-400"></span>
                        </span>
                        <div>
                            <p class="text-xs font-black">Remote connected</p>
                            <p class="mt-0.5 text-[10px] text-slate-500">Customer screens update automatically.</p>
                        </div>
                    </div>
                </div>

                <div class="border-t border-white/10 bg-black/20 p-5">
                    <div class="flex items-start gap-3 text-xs leading-5 text-slate-500">
                        <i data-lucide="shield-check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-300"></i>
                        <p>This is a filming control. It never stops GoogieHost and never generates traffic.</p>
                    </div>
                </div>
            </aside>
        </div>

        <details class="mt-5 overflow-hidden rounded-lg border border-white/10 bg-[#151820]" id="moderation" <?= isset($_GET['moderation']) || $directorNotice ? 'open' : '' ?>>
            <summary class="flex cursor-pointer list-none items-center gap-4 p-5 sm:p-6">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg bg-cyan-400/10 text-cyan-300"><i data-lucide="messages-square" class="h-5 w-5"></i></span>
                <span class="min-w-0 flex-1"><strong class="block font-black">Discussion moderation</strong><span class="mt-1 block text-xs text-slate-500">Search, review, open, and remove storefront comments.</span></span>
                <span class="rounded bg-white/5 px-2.5 py-1 text-xs font-black text-slate-300"><?= count($recentDiscussions) ?></span>
                <i data-lucide="chevron-down" class="h-5 w-5 text-slate-500"></i>
            </summary>

            <div class="border-t border-white/10 p-4 sm:p-5">
                <?php if ($directorNotice): ?><div class="mb-4 rounded-lg border p-3 text-sm font-bold <?= $directorNotice['tone'] === 'success' ? 'border-emerald-400/25 bg-emerald-400/10 text-emerald-200' : 'border-rose-400/25 bg-rose-400/10 text-rose-200' ?>" role="status"><?= htmlspecialchars((string) $directorNotice['message']) ?></div><?php endif; ?>

                <?php if ($recentDiscussions): ?>
                    <div class="grid gap-3 rounded-lg border border-white/10 bg-black/20 p-3 lg:grid-cols-[minmax(220px,1fr)_180px_220px_auto]">
                        <label class="relative"><i data-lucide="search" class="pointer-events-none absolute left-3 top-3 h-4 w-4 text-slate-500"></i><input class="input h-10 min-h-10 w-full rounded-lg border-white/10 bg-[#0d1016] pl-10 text-sm text-white placeholder:text-slate-600 focus:border-cyan-400 focus:outline-none" type="search" placeholder="Search customer, product, or comment" data-moderation-search></label>
                        <select class="select h-10 min-h-10 w-full rounded-lg border-white/10 bg-[#0d1016] text-sm text-white" data-moderation-rating aria-label="Filter by rating"><option value="all">All ratings</option><option value="low">1-2 stars</option><option value="3">3 stars</option><option value="high">4-5 stars</option></select>
                        <select class="select h-10 min-h-10 w-full rounded-lg border-white/10 bg-[#0d1016] text-sm text-white" data-moderation-product aria-label="Filter by product"><option value="all">All products</option><?php foreach ($moderationProducts as $moderationProductId => $moderationProductName): ?><option value="<?= $moderationProductId ?>"><?= htmlspecialchars($moderationProductName) ?></option><?php endforeach; ?></select>
                        <button class="btn btn-sm min-h-10 border-white/10 bg-white/5 text-slate-300 hover:border-cyan-400" type="button" data-moderation-clear><i data-lucide="list-restart" class="h-4 w-4"></i> Clear</button>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-3 rounded-lg border border-white/10 bg-black/20 px-3 py-2.5">
                        <button class="btn btn-sm border-white/10 bg-white/5 text-slate-300" type="button" data-moderation-select-visible><i data-lucide="list-checks" class="h-4 w-4"></i> Select visible</button>
                        <p class="text-xs font-bold text-slate-500"><span class="text-slate-200" data-moderation-visible-count><?= count($recentDiscussions) ?></span> shown &middot; <span class="text-cyan-300" data-moderation-selected-count>0</span> selected</p>
                        <form class="ml-auto" id="moderation-bulk-form" method="post" action="director-discussion-action.php" data-moderation-bulk-form>
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                            <button class="btn btn-sm border-rose-400/25 bg-rose-400/10 text-rose-200 disabled:text-slate-600" type="submit" disabled data-moderation-bulk-delete><i data-lucide="trash-2" class="h-4 w-4"></i> Remove selected</button>
                        </form>
                    </div>

                    <div class="mt-3 grid gap-2" data-moderation-list>
                        <?php foreach ($recentDiscussions as $comment): ?>
                            <?php $commentAvatar = profile_avatar_url($comment); $reactionTotal = (int) $comment['helpful_count'] + (int) $comment['love_count'] + (int) $comment['funny_count']; $searchText = strtolower((string) $comment['author_name'] . ' ' . (string) $comment['author_email'] . ' ' . (string) ($comment['product_name'] ?: '') . ' ' . (string) $comment['body']); ?>
                            <article class="rounded-lg border border-white/10 bg-black/20" data-moderation-item data-moderation-search-text="<?= htmlspecialchars($searchText) ?>" data-moderation-rating-value="<?= (int) $comment['rating'] ?>" data-moderation-product-value="<?= (int) $comment['product_id'] ?>">
                                <div class="flex items-start gap-3 p-4">
                                    <label class="mt-3 grid h-5 w-5 shrink-0 cursor-pointer place-items-center" title="Select comment"><input class="checkbox checkbox-sm border-slate-600" type="checkbox" name="discussion_ids[]" value="<?= (int) $comment['id'] ?>" form="moderation-bulk-form" data-moderation-checkbox aria-label="Select comment by <?= htmlspecialchars((string) $comment['author_name']) ?>"></label>
                                    <span class="h-11 w-11 shrink-0 overflow-hidden rounded-lg text-sm font-black <?= htmlspecialchars(avatar_class((string) $comment['avatar_style'])) ?>"><?php if ($commentAvatar): ?><img class="h-full w-full object-cover" src="<?= htmlspecialchars($commentAvatar) ?>" alt=""><?php else: ?><span class="grid h-full w-full place-items-center"><?= htmlspecialchars(profile_initial((string) $comment['author_name'])) ?></span><?php endif; ?></span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1"><p class="text-sm font-black"><?= htmlspecialchars((string) $comment['author_name']) ?></p><span class="rounded bg-emerald-400/10 px-2 py-0.5 text-[9px] font-black uppercase text-emerald-300">Active</span><a class="text-[10px] font-bold text-cyan-300 hover:underline" href="product.php?id=<?= (int) $comment['product_id'] ?>#comment-<?= (int) $comment['id'] ?>" target="_blank" rel="noopener"><?= htmlspecialchars((string) ($comment['product_name'] ?: 'Product #' . $comment['product_id'])) ?></a><span class="flex items-center text-amber-300"><?php for ($star = 1; $star <= 5; $star++): ?><i data-lucide="star" class="h-3 w-3 <?= (int) $comment['rating'] >= $star ? 'fill-current' : '' ?>"></i><?php endfor; ?></span></div>
                                        <p class="mt-2 text-sm leading-6 text-slate-300"><?= nl2br(htmlspecialchars((string) $comment['body'])) ?></p>
                                        <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-[10px] text-slate-600"><span><?= htmlspecialchars((string) $comment['author_email']) ?></span><span>&middot;</span><time><?= htmlspecialchars(date('M j, g:i A', strtotime((string) $comment['created_at']))) ?></time><span>&middot;</span><?php if ($reactionTotal > 0): ?><span class="flex items-center gap-2 text-slate-400"><span><i data-lucide="thumbs-up" class="mr-1 inline h-3 w-3"></i><?= (int) $comment['helpful_count'] ?></span><span><i data-lucide="heart" class="mr-1 inline h-3 w-3"></i><?= (int) $comment['love_count'] ?></span><span><i data-lucide="smile" class="mr-1 inline h-3 w-3"></i><?= (int) $comment['funny_count'] ?></span></span><?php else: ?><span>No reactions</span><?php endif; ?></div>
                                    </div>
                                    <div class="flex shrink-0 flex-col gap-2 sm:flex-row">
                                        <a class="btn btn-square btn-sm border-white/10 bg-white/5 text-slate-400 hover:border-cyan-400 hover:text-cyan-300" href="product.php?id=<?= (int) $comment['product_id'] ?>#comment-<?= (int) $comment['id'] ?>" target="_blank" rel="noopener" aria-label="Open comment on storefront" title="Open on storefront"><i data-lucide="external-link" class="h-4 w-4"></i></a>
                                        <a class="btn btn-square btn-sm border-white/10 bg-white/5 text-slate-400 hover:border-violet-400 hover:text-violet-300" href="director.php?customers=1#customer-<?= (int) $comment['user_id'] ?>" aria-label="Review customer account" title="Review customer"><i data-lucide="user-round-search" class="h-4 w-4"></i></a>
                                        <form method="post" action="director-discussion-action.php" onsubmit="return confirm('Remove this comment from the storefront?');"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="discussion_id" value="<?= (int) $comment['id'] ?>"><button class="btn btn-square btn-sm border-rose-400/25 bg-rose-400/10 text-rose-200 hover:border-rose-300 hover:bg-rose-400/20" type="submit" aria-label="Remove comment" title="Remove comment"><i data-lucide="trash-2" class="h-4 w-4"></i></button></form>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <div class="hidden py-8 text-center" data-moderation-empty><i data-lucide="search-x" class="mx-auto h-7 w-7 text-slate-600"></i><p class="mt-3 text-sm font-black">No comments match these filters.</p><p class="mt-1 text-xs text-slate-600">Clear the search or choose another rating or product.</p></div>
                <?php else: ?>
                    <div class="py-8 text-center"><i data-lucide="message-circle-off" class="mx-auto h-7 w-7 text-slate-600"></i><p class="mt-3 text-sm font-black">No comments to moderate.</p><p class="mt-1 text-xs text-slate-600">New product discussions will appear here.</p></div>
                <?php endif; ?>
            </div>
        </details>

        <details class="mt-5 overflow-hidden rounded-lg border border-white/10 bg-[#151820]" id="customers" <?= isset($_GET['customers']) || $customerNotice ? 'open' : '' ?>>
            <summary class="flex cursor-pointer list-none items-center gap-4 p-5 sm:p-6">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg bg-violet-400/10 text-violet-300"><i data-lucide="users-round" class="h-5 w-5"></i></span>
                <span class="min-w-0 flex-1"><strong class="block font-black">Customer safety</strong><span class="mt-1 block text-xs text-slate-500">Edit profiles, remove inappropriate photos, or suspend accounts.</span></span>
                <span class="rounded bg-white/5 px-2.5 py-1 text-xs font-black text-slate-300"><?= count($customerProfiles) ?></span>
                <i data-lucide="chevron-down" class="h-5 w-5 text-slate-500"></i>
            </summary>

            <div class="border-t border-white/10 p-4 sm:p-5">
                <?php if ($customerNotice): ?><div class="mb-4 rounded-lg border p-3 text-sm font-bold <?= $customerNotice['tone'] === 'success' ? 'border-emerald-400/25 bg-emerald-400/10 text-emerald-200' : 'border-rose-400/25 bg-rose-400/10 text-rose-200' ?>" role="status"><?= htmlspecialchars((string) $customerNotice['message']) ?></div><?php endif; ?>

                <div class="relative mb-4"><i data-lucide="search" class="pointer-events-none absolute left-3 top-3 h-4 w-4 text-slate-500"></i><input class="input h-10 min-h-10 w-full rounded-lg border-white/10 bg-black/20 pl-10 text-sm text-white placeholder:text-slate-600 focus:border-violet-400 focus:outline-none" type="search" placeholder="Search customer name or email" data-customer-search></div>

                <?php if ($customerProfiles): ?>
                    <div class="grid gap-2" data-customer-list>
                        <?php foreach ($customerProfiles as $customer): ?>
                            <?php $customerAvatar = profile_avatar_url($customer); $isBanned = !empty($customer['is_banned']); ?>
                            <article class="scroll-mt-5 rounded-lg border <?= $isBanned ? 'border-rose-400/25 bg-rose-400/5' : 'border-white/10 bg-black/20' ?>" id="customer-<?= (int) $customer['id'] ?>" data-customer-item data-customer-search-text="<?= htmlspecialchars(strtolower((string) $customer['name'] . ' ' . (string) $customer['email'])) ?>">
                                <div class="flex flex-wrap items-center gap-3 p-4">
                                    <div class="h-11 w-11 shrink-0 overflow-hidden rounded-lg <?= htmlspecialchars(avatar_class((string) $customer['avatar_style'])) ?>"><?php if ($customerAvatar): ?><img class="h-full w-full object-cover" src="<?= htmlspecialchars($customerAvatar) ?>" alt=""><?php else: ?><span class="grid h-full w-full place-items-center text-sm font-black"><?= htmlspecialchars(profile_initial((string) $customer['name'])) ?></span><?php endif; ?></div>
                                    <div class="min-w-0 flex-1"><div class="flex flex-wrap items-center gap-2"><p class="truncate text-sm font-black"><?= htmlspecialchars((string) $customer['name']) ?></p><span class="rounded px-2 py-0.5 text-[9px] font-black uppercase <?= $isBanned ? 'bg-rose-400/15 text-rose-300' : 'bg-emerald-400/10 text-emerald-300' ?>"><?= $isBanned ? 'Suspended' : 'Active' ?></span></div><p class="mt-0.5 truncate text-[11px] text-slate-500"><?= htmlspecialchars((string) $customer['email']) ?></p></div>
                                    <div class="hidden gap-5 text-center sm:flex"><div><p class="text-sm font-black"><?= (int) $customer['order_count'] ?></p><p class="text-[9px] font-bold uppercase text-slate-600">Orders</p></div><div><p class="text-sm font-black"><?= (int) $customer['comment_count'] ?></p><p class="text-[9px] font-bold uppercase text-slate-600">Comments</p></div></div>
                                    <button class="btn btn-sm border-white/10 bg-white/5 text-slate-300 hover:border-violet-400" type="button" data-customer-toggle><i data-lucide="user-round-cog" class="h-4 w-4"></i> Manage</button>
                                </div>

                                <div class="hidden border-t border-white/10 p-4" data-customer-editor>
                                    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_290px]">
                                        <form class="grid gap-3 sm:grid-cols-2" method="post" action="director-customer-action.php">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="customer_id" value="<?= (int) $customer['id'] ?>"><input type="hidden" name="action" value="save">
                                            <label class="block"><span class="mb-1 block text-[10px] font-black uppercase text-slate-500">Display name</span><input class="input h-10 min-h-10 w-full rounded-lg border-white/10 bg-black/20 text-sm text-white" name="name" maxlength="120" value="<?= htmlspecialchars((string) $customer['name']) ?>" required></label>
                                            <label class="block"><span class="mb-1 block text-[10px] font-black uppercase text-slate-500">Profile color</span><select class="select h-10 min-h-10 w-full rounded-lg border-white/10 bg-[#11141a] text-sm text-white" name="avatar_style"><?php foreach (avatar_styles() as $key => $style): ?><option value="<?= htmlspecialchars($key) ?>" <?= $customer['avatar_style'] === $key ? 'selected' : '' ?>><?= htmlspecialchars($style['label']) ?></option><?php endforeach; ?></select></label>
                                            <label class="block sm:col-span-2"><span class="mb-1 block text-[10px] font-black uppercase text-slate-500">Profile note</span><textarea class="textarea min-h-20 w-full resize-none rounded-lg border-white/10 bg-black/20 text-sm text-white" name="bio" maxlength="180"><?= htmlspecialchars((string) $customer['bio']) ?></textarea></label>
                                            <button class="btn btn-sm w-fit border-violet-400/30 bg-violet-400/10 text-violet-200 hover:bg-violet-400/20 sm:col-span-2" type="submit"><i data-lucide="save" class="h-4 w-4"></i> Save profile edits</button>
                                        </form>

                                        <div class="rounded-lg border border-white/10 bg-black/20 p-4">
                                            <p class="text-[10px] font-black uppercase text-slate-500">Safety actions</p>
                                            <?php if ($customerAvatar): ?><form class="mt-3" method="post" action="director-customer-action.php" onsubmit="return confirm('Remove this customer profile picture?');"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="customer_id" value="<?= (int) $customer['id'] ?>"><input type="hidden" name="action" value="remove_photo"><button class="btn btn-sm w-full border-amber-400/25 bg-amber-400/10 text-amber-200" type="submit"><i data-lucide="image-off" class="h-4 w-4"></i> Remove profile photo</button></form><?php endif; ?>
                                            <?php if ($isBanned): ?>
                                                <p class="mt-3 rounded-lg bg-rose-400/10 p-3 text-xs leading-5 text-rose-200"><?= htmlspecialchars((string) ($customer['ban_reason'] ?: 'No reason recorded.')) ?></p>
                                                <form class="mt-3" method="post" action="director-customer-action.php" onsubmit="return confirm('Restore this customer account?');"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="customer_id" value="<?= (int) $customer['id'] ?>"><input type="hidden" name="action" value="restore"><button class="btn btn-sm w-full border-emerald-400/25 bg-emerald-400/10 text-emerald-200" type="submit"><i data-lucide="user-check" class="h-4 w-4"></i> Restore account</button></form>
                                            <?php else: ?>
                                                <form class="mt-3" method="post" action="director-customer-action.php" onsubmit="return confirm('Suspend this customer account? They will be signed out and unable to comment.');"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="customer_id" value="<?= (int) $customer['id'] ?>"><input type="hidden" name="action" value="suspend"><input class="input h-10 min-h-10 w-full rounded-lg border-white/10 bg-black/20 text-sm text-white placeholder:text-slate-600" name="ban_reason" maxlength="180" placeholder="Reason for suspension" required><button class="btn btn-sm mt-2 w-full border-rose-400/25 bg-rose-400/10 text-rose-200" type="submit"><i data-lucide="user-x" class="h-4 w-4"></i> Suspend account</button></form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <div class="hidden py-8 text-center" data-customer-empty><i data-lucide="search-x" class="mx-auto h-6 w-6 text-slate-600"></i><p class="mt-2 text-sm font-black">No customer matches that search.</p></div>
                <?php else: ?>
                    <div class="py-8 text-center"><i data-lucide="users" class="mx-auto h-7 w-7 text-slate-600"></i><p class="mt-3 text-sm font-black">No customer accounts yet.</p></div>
                <?php endif; ?>
            </div>
        </details>
    </main>

    <div class="pointer-events-none fixed inset-x-0 bottom-4 z-50 flex justify-center px-4">
        <div class="hidden max-w-md rounded-lg border border-rose-400/30 bg-[#211117] px-4 py-3 text-sm font-bold text-rose-200 shadow-2xl" role="alert" data-director-error></div>
    </div>

    <script>
        window.ECOCART_DIRECTOR_CUES = <?= json_encode($cueScripts, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="assets/director.js?v=<?= htmlspecialchars((string) @filemtime(__DIR__ . '/assets/director.js')) ?>"></script>
    <script>
        const customerSearch = document.querySelector('[data-customer-search]');
        const customerItems = [...document.querySelectorAll('[data-customer-item]')];
        customerSearch?.addEventListener('input', () => {
            const term = customerSearch.value.trim().toLowerCase();
            let visible = 0;
            customerItems.forEach((item) => { const show = !term || item.dataset.customerSearchText.includes(term); item.classList.toggle('hidden', !show); visible += show ? 1 : 0; });
            document.querySelector('[data-customer-empty]')?.classList.toggle('hidden', visible > 0);
        });
        document.querySelectorAll('[data-customer-toggle]').forEach((button) => button.addEventListener('click', () => {
            const editor = button.closest('[data-customer-item]')?.querySelector('[data-customer-editor]');
            editor?.classList.toggle('hidden');
        }));
    </script>
</body>
</html>

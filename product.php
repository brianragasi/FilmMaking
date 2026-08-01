<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/discussions.php';
require_once __DIR__ . '/includes/scene.php';

$sceneState = read_scene_state();
if (scene_is_outage($sceneState)) {
    require __DIR__ . '/includes/customer-outage.php';
    exit;
}

$productId = max(0, (int) ($_GET['id'] ?? ($_SESSION['last_product_id'] ?? 0)));
$product = product_lookup()[$productId] ?? null;
if (!$product) {
    http_response_code(404);
    header('Location: index.php#products');
    exit;
}
$_SESSION['last_product_id'] = $productId;

$user = current_user();
if ($user) {
    $user = refresh_authenticated_user($user);
}
$isCustomer = $user && (string) ($user['role'] ?? '') === 'customer';
$requestedSort = (string) ($_GET['sort'] ?? 'newest');
$sort = in_array($requestedSort, ['newest', 'highest', 'helpful'], true)
    ? $requestedSort
    : 'newest';
$comments = product_discussions($productId, $isCustomer ? (int) $user['id'] : 0, $sort);
$summary = product_discussion_summary()[$productId] ?? ['count' => 0, 'rating' => 0.0];
$ratingBreakdown = product_discussion_rating_breakdown($productId);
$reactionTypes = discussion_reaction_types();
$viewerProfile = $isCustomer ? customer_profile($user) : null;
$notice = isset($_SESSION['discussion_notice']) && is_array($_SESSION['discussion_notice'])
    ? $_SESSION['discussion_notice']
    : null;
unset($_SESSION['discussion_notice']);

$cartProduct = [
    'id' => (int) $product['id'],
    'name' => (string) $product['name'],
    'price' => (float) $product['price'],
    'category' => (string) $product['category'],
    'image_url' => (string) $product['image_url'],
];
$productJson = htmlspecialchars((string) json_encode($cartProduct), ENT_QUOTES);
$reviewCount = (int) $summary['count'];
$averageRating = (float) $summary['rating'];
?>
<!doctype html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= htmlspecialchars((string) $product['description']) ?>">
    <title><?= htmlspecialchars((string) $product['name']) ?> | EcoCart</title>
    <link href="public/output-public.css?v=<?= htmlspecialchars((string) @filemtime(__DIR__ . '/public/output-public.css')) ?>" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="icon" href="data:,">
</head>
<body class="min-h-screen bg-[#f4f5f7] text-slate-950" data-scene-client data-scene-view="storefront" data-scene-cue="<?= htmlspecialchars((string) $sceneState['cue']) ?>" data-scene-revision="<?= (int) $sceneState['revision'] ?>" data-scene-updated="<?= htmlspecialchars((string) $sceneState['updated_at']) ?>">
    <div class="bg-rose-600 text-white"><div class="app-shell-narrow flex min-h-9 items-center justify-center text-center text-[10px] font-black uppercase sm:text-[11px]" data-sale-ribbon-text><?= $sceneState['cue'] === 'sale_live' ? 'SALE IS LIVE NOW - UP TO 70% OFF' : 'Big Blowout Sale: up to 70% off selected essentials' ?></div></div>
    <header class="border-b border-slate-200 bg-white">
        <div class="app-shell-narrow flex min-h-[72px] items-center gap-3">
            <a class="flex items-center gap-2" href="index.php"><span class="grid h-10 w-10 place-items-center rounded-lg bg-slate-950 text-white"><i data-lucide="leaf" class="h-5 w-5"></i></span><span class="text-xl font-black sm:text-2xl">Eco<span class="text-rose-600">Cart.</span></span></a>
            <a class="btn btn-sm ml-auto border-slate-200 bg-white" href="index.php#products"><i data-lucide="arrow-left" class="h-4 w-4"></i><span class="hidden sm:inline">Products</span></a>
            <?php if ($user): ?>
                <?php $headerAvatar = $viewerProfile ? profile_avatar_url($viewerProfile) : null; ?>
                <a class="flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white pr-2 text-xs font-bold text-slate-700 transition hover:border-rose-200 hover:text-rose-600" href="account.php" aria-label="Open my account"><span class="h-9 w-9 overflow-hidden rounded-lg <?= $viewerProfile ? htmlspecialchars(avatar_class((string) $viewerProfile['avatar_style'])) : 'bg-slate-100' ?>"><?php if ($headerAvatar): ?><img class="h-full w-full object-cover" src="<?= htmlspecialchars($headerAvatar) ?>" alt="<?= htmlspecialchars((string) $user['name']) ?> profile picture"><?php elseif ($viewerProfile): ?><span class="grid h-full w-full place-items-center font-black"><?= htmlspecialchars(profile_initial((string) $viewerProfile['name'])) ?></span><?php else: ?><span class="grid h-full w-full place-items-center"><i data-lucide="user-round" class="h-4 w-4"></i></span><?php endif; ?></span><span class="hidden sm:inline">Account</span></a>
            <?php else: ?>
                <a class="btn btn-sm border-0 bg-slate-950 text-white" href="login.php?mode=login&amp;next=product.php">Sign in</a>
            <?php endif; ?>
        </div>
    </header>

    <main>
        <section class="border-b border-slate-200 bg-white">
            <div class="app-shell-narrow py-5 sm:py-8">
                <nav class="mb-4 flex items-center gap-2 text-xs font-bold text-slate-400" aria-label="Breadcrumb"><a class="hover:text-rose-600" href="index.php">Store</a><i data-lucide="chevron-right" class="h-3.5 w-3.5"></i><a class="hover:text-rose-600" href="index.php#products"><?= htmlspecialchars((string) $product['category']) ?></a><i data-lucide="chevron-right" class="h-3.5 w-3.5"></i><span class="truncate text-slate-700"><?= htmlspecialchars((string) $product['name']) ?></span></nav>

                <div class="grid gap-7 lg:grid-cols-[minmax(0,1.05fr)_minmax(400px,0.95fr)] lg:gap-12">
                    <figure class="overflow-hidden rounded-lg bg-slate-100">
                        <img class="aspect-[4/3] h-full w-full object-cover" src="<?= htmlspecialchars((string) $product['image_url']) ?>" alt="<?= htmlspecialchars((string) $product['name']) ?>">
                    </figure>
                    <div class="flex min-w-0 flex-col justify-center py-1">
                        <div class="flex flex-wrap items-center gap-2"><span class="rounded bg-rose-50 px-2.5 py-1 text-[10px] font-black uppercase text-rose-700"><?= htmlspecialchars((string) $product['category']) ?></span><?php if ((int) $product['stock'] > 0): ?><span class="flex items-center gap-1 text-xs font-bold text-emerald-700"><i data-lucide="circle-check" class="h-3.5 w-3.5"></i> In stock</span><?php endif; ?></div>
                        <h1 class="mt-4 text-3xl font-black leading-tight sm:text-4xl"><?= htmlspecialchars((string) $product['name']) ?></h1>
                        <a class="mt-3 flex w-fit flex-wrap items-center gap-2" href="#discussion"><span class="flex items-center gap-0.5 text-amber-400"><?php for ($star = 1; $star <= 5; $star++): ?><i data-lucide="star" class="h-4 w-4 <?= $averageRating >= $star - 0.5 ? 'fill-current' : '' ?>"></i><?php endfor; ?></span><strong class="text-sm"><?= $reviewCount > 0 ? number_format($averageRating, 1) : 'New' ?></strong><span class="text-sm text-slate-500"><?= $reviewCount ?> review<?= $reviewCount === 1 ? '' : 's' ?></span></a>
                        <p class="mt-5 max-w-xl text-sm leading-6 text-slate-600 sm:text-base sm:leading-7"><?= htmlspecialchars((string) $product['description']) ?></p>

                        <div class="mt-6 flex flex-wrap items-end justify-between gap-3 border-y border-slate-100 py-5"><div><p class="text-[10px] font-black uppercase text-slate-400">Big Blowout price</p><p class="mt-1 text-3xl font-black text-rose-600"><?= peso((float) $product['price']) ?></p></div><p class="rounded bg-slate-100 px-3 py-2 text-xs font-bold text-slate-600"><?= (int) $product['stock'] ?> available</p></div>
                        <div class="mt-5 grid gap-2 sm:grid-cols-[1fr_auto]"><button class="btn min-h-12 border-0 bg-slate-950 px-8 text-white hover:bg-rose-600" type="button" data-add-product="<?= $productJson ?>"><i data-lucide="shopping-bag" class="h-5 w-5"></i><span>Add to cart</span></button><a class="btn min-h-12 border-slate-200 bg-white px-6" href="checkout.php"><i data-lucide="credit-card" class="h-5 w-5"></i> Checkout</a></div>
                        <div class="mt-5 grid grid-cols-3 divide-x divide-slate-100 border-t border-slate-100 pt-5 text-center"><div class="px-2"><i data-lucide="shield-check" class="mx-auto h-4 w-4 text-emerald-600"></i><p class="mt-1 text-[10px] font-bold text-slate-500">Secure account</p></div><div class="px-2"><i data-lucide="package-check" class="mx-auto h-4 w-4 text-cyan-600"></i><p class="mt-1 text-[10px] font-bold text-slate-500">Stock confirmed</p></div><div class="px-2"><i data-lucide="messages-square" class="mx-auto h-4 w-4 text-violet-600"></i><p class="mt-1 text-[10px] font-bold text-slate-500">Community reviewed</p></div></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="app-shell-narrow py-8 sm:py-10" id="discussion">
            <div class="flex flex-col gap-3 border-b border-slate-300 pb-5 sm:flex-row sm:items-end sm:justify-between">
                <div><p class="text-xs font-black uppercase text-rose-600">Product community</p><h2 class="mt-1 text-3xl font-black">Reviews and discussion</h2><p class="mt-1 text-sm text-slate-500">Questions, practical tips, and honest experiences from EcoCart customers.</p></div>
                <div class="flex items-center gap-2 text-xs font-bold text-slate-500"><span class="grid h-8 w-8 place-items-center rounded-lg bg-slate-950 text-white"><i data-lucide="users-round" class="h-4 w-4"></i></span><?= $reviewCount ?> contribution<?= $reviewCount === 1 ? '' : 's' ?></div>
            </div>

            <?php if ($notice): ?><div class="mt-5 flex items-center gap-3 rounded-lg border p-4 text-sm font-bold <?= $notice['tone'] === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800' ?>" role="status"><i data-lucide="<?= $notice['tone'] === 'success' ? 'circle-check' : 'circle-alert' ?>" class="h-5 w-5 shrink-0"></i><?= htmlspecialchars((string) $notice['message']) ?></div><?php endif; ?>

            <div class="mt-6 grid items-start gap-6 lg:grid-cols-[340px_minmax(0,1fr)]">
                <aside class="space-y-4">
                    <section class="rounded-lg border border-slate-200 bg-white p-5">
                        <div class="flex items-center gap-4"><p class="text-4xl font-black"><?= $reviewCount > 0 ? number_format($averageRating, 1) : '0.0' ?></p><div><span class="flex items-center gap-0.5 text-amber-400"><?php for ($star = 1; $star <= 5; $star++): ?><i data-lucide="star" class="h-4 w-4 <?= $averageRating >= $star - 0.5 ? 'fill-current' : '' ?>"></i><?php endfor; ?></span><p class="mt-1 text-xs font-semibold text-slate-500">Based on <?= $reviewCount ?> review<?= $reviewCount === 1 ? '' : 's' ?></p></div></div>
                        <div class="mt-5 space-y-2.5"><?php for ($rating = 5; $rating >= 1; $rating--): ?><?php $percentage = $reviewCount > 0 ? ((int) $ratingBreakdown[$rating] / $reviewCount) * 100 : 0; ?><div class="grid grid-cols-[12px_1fr_24px] items-center gap-2 text-[11px] font-bold text-slate-500"><span><?= $rating ?></span><div class="h-1.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-amber-400" style="width: <?= number_format($percentage, 2, '.', '') ?>%"></div></div><span class="text-right"><?= (int) $ratingBreakdown[$rating] ?></span></div><?php endfor; ?></div>
                    </section>

                    <?php if ($isCustomer): ?>
                        <?php $viewerAvatar = profile_avatar_url($viewerProfile ?? []); ?>
                        <section class="rounded-lg border border-slate-200 bg-white p-5">
                            <div class="flex items-center gap-3"><span class="h-10 w-10 shrink-0 overflow-hidden rounded-lg font-black <?= htmlspecialchars(avatar_class((string) ($viewerProfile['avatar_style'] ?? 'rose'))) ?>"><?php if ($viewerAvatar): ?><img class="h-full w-full object-cover" src="<?= htmlspecialchars($viewerAvatar) ?>" alt=""><?php else: ?><span class="grid h-full w-full place-items-center"><?= htmlspecialchars(profile_initial((string) ($viewerProfile['name'] ?? $user['name']))) ?></span><?php endif; ?></span><div class="min-w-0"><p class="text-[10px] font-black uppercase text-slate-400">Posting as</p><p class="truncate text-sm font-black"><?= htmlspecialchars((string) ($viewerProfile['name'] ?? $user['name'])) ?></p></div></div>
                            <form class="mt-5" method="post" action="discussion-action.php">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                <input type="hidden" name="action" value="create">
                                <input type="hidden" name="product_id" value="<?= $productId ?>">
                                <fieldset><legend class="text-xs font-black">Your rating</legend><div class="rating rating-md mt-2"><?php for ($rating = 1; $rating <= 5; $rating++): ?><input type="radio" name="rating" class="mask mask-star-2 bg-amber-400" value="<?= $rating ?>" aria-label="<?= $rating ?> star<?= $rating === 1 ? '' : 's' ?>" <?= $rating === 5 ? 'checked' : '' ?>><?php endfor; ?></div></fieldset>
                                <label class="mt-4 block"><span class="mb-1.5 block text-xs font-black">Your review or question</span><textarea class="textarea textarea-bordered min-h-24 w-full resize-none rounded-lg bg-white" name="body" minlength="3" maxlength="1000" placeholder="Share something useful about this product..." required></textarea></label>
                                <button class="btn mt-3 w-full border-0 bg-rose-600 text-white hover:bg-rose-700" type="submit"><i data-lucide="send" class="h-4 w-4"></i> Post to community</button>
                            </form>
                        </section>
                    <?php else: ?>
                        <section class="rounded-lg bg-slate-950 p-5 text-white"><i data-lucide="lock-keyhole" class="h-5 w-5 text-rose-400"></i><p class="mt-3 font-black">Join the conversation</p><p class="mt-1 text-xs leading-5 text-slate-400">Anyone can read reviews. A registered customer account is required to post or react.</p><div class="mt-4 grid grid-cols-2 gap-2"><a class="btn btn-sm border-0 bg-rose-600 text-white" href="login.php?mode=register&amp;next=product.php">Register</a><a class="btn btn-sm border-white/15 bg-white/5 text-white" href="login.php?mode=login&amp;next=product.php">Sign in</a></div></section>
                    <?php endif; ?>
                </aside>

                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-3 rounded-lg border border-slate-200 bg-white p-3 sm:px-4"><div class="min-w-0 flex-1"><p class="font-black">Community posts</p><p class="text-xs text-slate-500"><?= $reviewCount > count($comments) ? 'Showing the latest ' . count($comments) . ' posts' : 'Showing all active posts' ?></p></div><form method="get" action="product.php#discussion"><input type="hidden" name="id" value="<?= $productId ?>"><label class="flex items-center gap-2"><span class="text-xs font-bold text-slate-500">Sort</span><select class="select select-sm min-h-9 rounded-lg border-slate-200 bg-white" name="sort" onchange="this.form.submit()"><option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option><option value="helpful" <?= $sort === 'helpful' ? 'selected' : '' ?>>Most reacted</option><option value="highest" <?= $sort === 'highest' ? 'selected' : '' ?>>Highest rated</option></select></label></form></div>

                    <?php if ($comments): ?>
                        <ol class="mt-3 space-y-3">
                            <?php foreach ($comments as $comment): ?>
                                <?php $commentAvatar = profile_avatar_url($comment); $isOwner = $isCustomer && (int) $comment['user_id'] === (int) $user['id']; ?>
                                <li class="scroll-mt-5 rounded-lg border border-slate-200 bg-white p-4 sm:p-5" id="comment-<?= (int) $comment['id'] ?>">
                                    <article>
                                        <header class="flex items-start gap-3"><span class="h-11 w-11 shrink-0 overflow-hidden rounded-lg font-black <?= htmlspecialchars(avatar_class((string) $comment['avatar_style'])) ?>"><?php if ($commentAvatar): ?><img class="h-full w-full object-cover" src="<?= htmlspecialchars($commentAvatar) ?>" alt=""><?php else: ?><span class="grid h-full w-full place-items-center"><?= htmlspecialchars(profile_initial((string) $comment['author_name'])) ?></span><?php endif; ?></span><div class="min-w-0 flex-1"><div class="flex flex-wrap items-center gap-x-2 gap-y-1"><p class="font-black"><?= htmlspecialchars((string) $comment['author_name']) ?></p><?php if ($isOwner): ?><span class="rounded bg-rose-50 px-2 py-0.5 text-[9px] font-black uppercase text-rose-700">Your post</span><?php endif; ?></div><div class="mt-0.5 flex flex-wrap items-center gap-2"><span class="flex items-center gap-0.5 text-amber-400"><?php for ($star = 1; $star <= 5; $star++): ?><i data-lucide="star" class="h-3 w-3 <?= (int) $comment['rating'] >= $star ? 'fill-current' : '' ?>"></i><?php endfor; ?></span><span class="text-[11px] font-semibold text-slate-400">Community member</span><span class="text-slate-300">&middot;</span><time class="text-[11px] font-semibold text-slate-400" datetime="<?= htmlspecialchars((string) $comment['created_at']) ?>" title="<?= htmlspecialchars(date('M j, Y g:i A', strtotime((string) $comment['created_at']))) ?>"><?= htmlspecialchars(discussion_relative_time((string) $comment['created_at'])) ?></time></div></div><?php if ($isOwner): ?><form method="post" action="discussion-action.php" onsubmit="return confirm('Delete your comment? This cannot be undone.');"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="product_id" value="<?= $productId ?>"><input type="hidden" name="discussion_id" value="<?= (int) $comment['id'] ?>"><button class="btn btn-square btn-sm shrink-0 border-slate-200 bg-white text-slate-400 hover:border-rose-200 hover:bg-rose-50 hover:text-rose-600" type="submit" aria-label="Delete your comment" title="Delete your comment"><i data-lucide="trash-2" class="h-4 w-4"></i></button></form><?php endif; ?></header>
                                        <?php if (trim((string) $comment['author_bio']) !== ''): ?><p class="mt-3 border-l-2 border-slate-200 pl-3 text-xs text-slate-400"><?= htmlspecialchars((string) $comment['author_bio']) ?></p><?php endif; ?>
                                        <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700"><?= htmlspecialchars((string) $comment['body']) ?></p>
                                        <footer class="mt-4 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3"><span class="mr-1 text-[10px] font-black uppercase text-slate-400">React</span><?php foreach ($reactionTypes as $reactionKey => $reaction): ?><?php $reactionCount = (int) ($comment[$reactionKey . '_count'] ?? 0); $hasReacted = !empty($comment['viewer_reactions'][$reactionKey]); ?><?php if ($isCustomer): ?><form method="post" action="discussion-action.php" data-reaction-form><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="action" value="react"><input type="hidden" name="product_id" value="<?= $productId ?>"><input type="hidden" name="discussion_id" value="<?= (int) $comment['id'] ?>"><input type="hidden" name="reaction" value="<?= htmlspecialchars($reactionKey) ?>"><button class="discussion-reaction btn btn-sm min-h-8 gap-1.5 rounded-full border-slate-200 bg-white px-3 text-slate-600 hover:bg-slate-50 <?= $hasReacted ? 'is-active' : '' ?>" type="submit" aria-pressed="<?= $hasReacted ? 'true' : 'false' ?>" data-reaction-button data-reaction-kind="<?= htmlspecialchars($reactionKey) ?>"><i data-lucide="<?= htmlspecialchars($reaction['icon']) ?>" class="h-3.5 w-3.5"></i><?= htmlspecialchars($reaction['label']) ?><span class="font-black <?= $reactionCount > 0 ? '' : 'hidden' ?>" data-reaction-count><?= $reactionCount ?></span></button></form><?php else: ?><a class="btn btn-sm min-h-8 gap-1.5 rounded-full border-slate-200 bg-white px-3 text-slate-600" href="login.php?mode=login&amp;next=product.php" title="Sign in to react"><i data-lucide="<?= htmlspecialchars($reaction['icon']) ?>" class="h-3.5 w-3.5"></i><?= htmlspecialchars($reaction['label']) ?><?php if ($reactionCount > 0): ?><span class="font-black"><?= $reactionCount ?></span><?php endif; ?></a><?php endif; ?><?php endforeach; ?><span class="sr-only" aria-live="polite" data-reaction-status></span></footer>
                                    </article>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php else: ?>
                        <div class="mt-3 rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center"><span class="mx-auto grid h-12 w-12 place-items-center rounded-lg bg-rose-50 text-rose-600"><i data-lucide="messages-square" class="h-6 w-6"></i></span><p class="mt-3 font-black">Be the first to post.</p><p class="mt-1 text-sm text-slate-500">Share a question or an honest product experience.</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-slate-200 bg-white"><div class="app-shell-narrow flex flex-wrap items-center justify-between gap-3 py-5 text-xs text-slate-500"><p>&copy; <?= date('Y') ?> EcoCart</p><p>Secure account &middot; Saved cart &middot; Customer community</p></div></footer>
    <div class="toast toast-end toast-bottom z-50 hidden" data-store-toast><div class="alert rounded-lg bg-slate-950 text-white shadow-xl"><i data-lucide="check-circle-2" class="h-5 w-5 text-emerald-400"></i><span data-store-toast-message>Added to cart</span></div></div>
    <script src="assets/scene-client.js?v=<?= htmlspecialchars((string) @filemtime(__DIR__ . '/assets/scene-client.js')) ?>"></script>
    <script src="assets/app-public.js?v=<?= htmlspecialchars((string) @filemtime(__DIR__ . '/assets/app-public.js')) ?>"></script>
    <script src="assets/discussions.js?v=<?= htmlspecialchars((string) @filemtime(__DIR__ . '/assets/discussions.js')) ?>"></script>
</body>
</html>

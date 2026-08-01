<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/discussions.php';
require_once __DIR__ . '/includes/scene.php';
require_once __DIR__ . '/includes/promotions.php';

$sceneState = read_scene_state();
if (scene_is_outage($sceneState)) {
    require __DIR__ . '/includes/customer-outage.php';
    exit;
}

$errors = [];
$success = false;
$orderId = null;
$orderQueued = false;
$subtotal = 0.0;
$orderTotal = 0.0;
$discount = 0.0;
$shipping = 0.0;
$promoCode = '';
$promotion = null;
$publicPromotion = ecocart_promotions()['BIGBLOWOUT'];
$currentUser = current_user();
if ($currentUser) {
    $currentUser = refresh_authenticated_user($currentUser);
}
$headerProfile = $currentUser && (string) ($currentUser['role'] ?? '') === 'customer'
    ? customer_profile($currentUser)
    : null;
$headerAvatar = $headerProfile ? profile_avatar_url($headerProfile) : null;

function offline_order_number(string $email): string
{
    return 'ECV-' . strtoupper(substr(hash('sha256', $email . microtime(true)), 0, 6));
}

function queue_offline_order(string $orderId, string $reason, string $name, string $email, string $phone, string $address, array $cart, float $subtotal, float $total): void
{
    $payload = [
        'order_id' => $orderId,
        'reason' => $reason,
        'created_at' => date(DATE_ATOM),
        'customer_name' => $name,
        'email' => $email,
        'phone' => $phone,
        'address' => $address,
        'cart' => $cart,
        'subtotal' => round($subtotal, 2),
        'total' => round($total, 2),
    ];

    $encoded = json_encode($payload, JSON_UNESCAPED_SLASHES);
    if ($encoded === false || @file_put_contents(__DIR__ . '/includes/order-queue.jsonl', $encoded . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
        error_log("[EcoCart checkout] could not write offline order {$orderId}");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        $errors[] = 'Your checkout session expired. Refresh the page and try again.';
    }

    $name = trim((string) ($_POST['customer_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $address = trim((string) ($_POST['address'] ?? ''));
    $cartJson = (string) ($_POST['cart_json'] ?? '[]');
    $promoCode = normalize_promo_code((string) ($_POST['promo_code'] ?? ''));
    $promotion = promotion_for_code($promoCode);
    $submittedCart = json_decode($cartJson, true);

    if ($name === '') {
        $errors[] = 'Customer name is required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    if ($phone === '') {
        $errors[] = 'Phone number is required.';
    }
    if ($address === '') {
        $errors[] = 'Delivery address is required.';
    }
    if (!is_array($submittedCart) || count($submittedCart) === 0) {
        $errors[] = 'Your cart is empty.';
    }
    if ($promoCode !== '' && !$promotion) {
        $errors[] = 'That discount code is not valid. Check the spelling or remove it.';
    }

    $catalog = product_lookup();
    $cart = [];

    foreach (is_array($submittedCart) ? $submittedCart : [] as $item) {
        $productId = (int) ($item['id'] ?? 0);
        $quantity = (int) ($item['quantity'] ?? 0);
        $product = $catalog[$productId] ?? null;

        if (!$product || $quantity < 1 || $quantity > 99) {
            $errors[] = 'One or more cart items could not be verified.';
            break;
        }

        $price = (float) $product['price'];
        $subtotal += $price * $quantity;
        $cart[] = [
            'id' => $productId,
            'name' => (string) $product['name'],
            'price' => $price,
            'quantity' => $quantity,
            'category' => (string) $product['category'],
            'image_url' => (string) $product['image_url'],
        ];
    }

    if ($subtotal <= 0) {
        $errors[] = 'Your cart total must be greater than zero.';
    }

    $discount = promotion_discount($subtotal, $promotion);
    $shipping = $subtotal >= 1500 ? 0.0 : 49.0;
    $orderTotal = max(0.0, $subtotal - $discount + $shipping);

    if (!$errors) {
        $pdo = db();

        if (!$pdo) {
            $orderId = offline_order_number($email);
            $orderQueued = true;
            $success = true;
            queue_offline_order($orderId, 'database unavailable', $name, $email, $phone, $address, $cart, $subtotal, $orderTotal);
            error_log("[EcoCart checkout] database unavailable; queued offline order {$orderId}");
        } else {
            try {
                $statement = $pdo->prepare(
                    'INSERT INTO orders (customer_name, email, phone, address, cart_json, subtotal)
                     VALUES (:customer_name, :email, :phone, :address, :cart_json, :subtotal)'
                );
                $statement->execute([
                    'customer_name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'address' => $address,
                    'cart_json' => json_encode($cart),
                    'subtotal' => $subtotal,
                ]);
                $orderId = (int) $pdo->lastInsertId();
                $success = true;
            } catch (Throwable $error) {
                $orderId = offline_order_number($email);
                $orderQueued = true;
                $success = true;
                queue_offline_order($orderId, 'database insert failed', $name, $email, $phone, $address, $cart, $subtotal, $orderTotal);
                error_log("[EcoCart checkout] database order insert failed; queued offline order {$orderId}: " . $error->getMessage());
            }
        }
    }
}
?>
<!doctype html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Complete your EcoCart Big Blowout Sale order.">
    <title>Secure Checkout | EcoCart</title>
    <link href="public/output-public.css?v=<?= htmlspecialchars((string) @filemtime(__DIR__ . '/public/output-public.css')) ?>" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="icon" href="data:,">
</head>
<body class="flex min-h-screen flex-col bg-[#f4f5f7] text-slate-950" data-scene-client data-scene-view="checkout" data-scene-cue="<?= htmlspecialchars((string) $sceneState['cue']) ?>" data-scene-revision="<?= (int) $sceneState['revision'] ?>" data-scene-updated="<?= htmlspecialchars((string) $sceneState['updated_at']) ?>" data-promo-code="<?= htmlspecialchars((string) $publicPromotion['code']) ?>" data-promo-rate="<?= htmlspecialchars((string) $publicPromotion['rate']) ?>">
    <div class="fixed inset-0 z-[100] hidden bg-white" data-scene-loading>
        <div class="flex min-h-screen flex-col">
            <header class="border-b border-slate-200">
                <div class="app-shell flex min-h-[72px] items-center">
                    <span class="flex items-center gap-2">
                        <span class="grid h-10 w-10 place-items-center rounded-lg bg-slate-950 text-white"><i data-lucide="leaf" class="h-5 w-5"></i></span>
                        <span class="text-2xl font-black">Eco<span class="text-rose-600">Cart.</span></span>
                    </span>
                </div>
            </header>
            <main class="grid flex-1 place-items-center p-6 text-center">
                <div>
                    <span class="loading loading-spinner loading-lg text-rose-600"></span>
                    <h1 class="mt-6 text-2xl font-black sm:text-3xl">Connecting to checkout</h1>
                    <p class="mt-2 text-sm text-slate-500">Please keep this page open while we process your request.</p>
                </div>
            </main>
        </div>
    </div>
    <div class="bg-rose-600 text-white">
        <div class="app-shell flex min-h-8 items-center justify-center gap-2 text-center text-[10px] font-black uppercase">
            <i data-lucide="tag" class="h-3.5 w-3.5"></i>
            Big Blowout prices are reserved while items remain in stock
        </div>
    </div>

    <header class="border-b border-slate-200 bg-white">
        <div class="app-shell flex min-h-[72px] items-center gap-4">
            <a class="flex items-center gap-2" href="index.php" aria-label="EcoCart home">
                <span class="grid h-10 w-10 place-items-center rounded-lg bg-slate-950 text-white"><i data-lucide="leaf" class="h-5 w-5"></i></span>
                <span class="text-2xl font-black">Eco<span class="text-rose-600">Cart.</span></span>
            </a>
            <div class="ml-auto flex items-center gap-4">
                <span class="hidden items-center gap-2 text-xs font-bold text-emerald-700 sm:flex">
                    <i data-lucide="shield-check" class="h-4 w-4"></i>
                    Secure checkout
                </span>
                <a class="h-9 w-9 overflow-hidden rounded-lg border border-slate-200 <?= $headerProfile ? htmlspecialchars(avatar_class((string) $headerProfile['avatar_style'])) : 'bg-white text-slate-700' ?>" href="<?= $currentUser ? 'account.php' : 'login.php?next=checkout.php' ?>" aria-label="<?= $currentUser ? 'Open my account' : 'Sign in' ?>" title="<?= $currentUser ? 'My account' : 'Sign in' ?>"><?php if ($headerAvatar): ?><img class="h-full w-full object-cover" src="<?= htmlspecialchars($headerAvatar) ?>" alt="<?= htmlspecialchars((string) $currentUser['name']) ?> profile picture"><?php elseif ($headerProfile): ?><span class="grid h-full w-full place-items-center text-xs font-black"><?= htmlspecialchars(profile_initial((string) $headerProfile['name'])) ?></span><?php else: ?><span class="grid h-full w-full place-items-center"><i data-lucide="user-round" class="h-4 w-4"></i></span><?php endif; ?></a>
                <a class="btn btn-sm border-slate-200 bg-white text-slate-700 hover:border-slate-950 hover:bg-slate-50" href="index.php#products">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                    <span class="hidden sm:inline">Continue shopping</span>
                    <span class="sm:hidden">Shop</span>
                </a>
            </div>
        </div>
    </header>

    <main class="app-shell w-full flex-1 py-7 sm:py-10">
        <?php if ($success): ?>
            <?php
                $arriveStart = date('D, M j', strtotime('+2 days'));
                $arriveEnd = date('D, M j', strtotime('+5 days'));
                $itemCount = 0;
                foreach ($cart as $ci) { $itemCount += (int) $ci['quantity']; }
            ?>
            <section class="mx-auto max-w-5xl overflow-hidden rounded-lg border border-emerald-200 bg-white shadow-xl">
                <div class="bg-emerald-600 px-6 py-8 text-white sm:px-10">
                    <div class="flex flex-wrap items-center gap-5">
                        <span class="grid h-14 w-14 shrink-0 place-items-center rounded-lg bg-white text-emerald-600"><i data-lucide="check" class="h-8 w-8"></i></span>
                        <div class="min-w-0">
                            <p class="text-xs font-black uppercase text-emerald-100">Order confirmed</p>
                            <h1 class="mt-1 text-3xl font-black sm:text-4xl">Thanks, <?= htmlspecialchars($name) ?>. Your order is in.</h1>
                            <p class="mt-2 text-sm text-emerald-50">Order <span class="font-black">#<?= $orderId ?></span> &middot; <?= $itemCount ?> item<?= $itemCount === 1 ? '' : 's' ?> &middot; confirmation sent to <?= htmlspecialchars($email) ?>.</p>
                            <?php if ($orderQueued): ?>
                                <p class="mt-2 inline-flex rounded-full bg-white/15 px-3 py-1 text-xs font-black uppercase text-white">Queued for manual confirmation</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="grid gap-8 p-6 sm:p-10 lg:grid-cols-[minmax(0,1fr)_330px]">
                    <div class="space-y-8">
                        <div>
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-black">Items in this order</h2>
                                <span class="text-xs font-bold text-slate-400"><?= $itemCount ?> item<?= $itemCount === 1 ? '' : 's' ?></span>
                            </div>
                            <ul class="mt-4 divide-y divide-slate-100 border-y border-slate-100">
                                <?php foreach ($cart as $item): ?>
                                    <li class="flex items-center gap-4 py-4">
                                        <img class="h-16 w-16 shrink-0 rounded-lg border border-slate-200 object-cover" src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate font-bold"><?= htmlspecialchars($item['name']) ?></p>
                                            <p class="text-xs text-slate-500"><?= htmlspecialchars($item['category']) ?> &middot; Qty <?= (int) $item['quantity'] ?></p>
                                        </div>
                                        <p class="shrink-0 font-black"><?= peso((float) $item['price'] * (int) $item['quantity']) ?></p>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <div>
                            <h2 class="text-lg font-black">Delivery details</h2>
                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div class="rounded-lg border border-slate-200 p-4">
                                    <p class="flex items-center gap-2 text-xs font-black uppercase text-slate-400"><i data-lucide="map-pin" class="h-4 w-4 text-rose-600"></i> Deliver to</p>
                                    <p class="mt-2 font-bold"><?= htmlspecialchars($name) ?></p>
                                    <p class="mt-1 text-sm leading-6 text-slate-600"><?= nl2br(htmlspecialchars($address)) ?></p>
                                    <p class="mt-1 text-sm font-bold text-slate-600"><?= htmlspecialchars($phone) ?></p>
                                </div>
                                <div class="rounded-lg border border-slate-200 p-4">
                                    <p class="flex items-center gap-2 text-xs font-black uppercase text-slate-400"><i data-lucide="truck" class="h-4 w-4 text-cyan-600"></i> Standard delivery</p>
                                    <p class="mt-2 font-bold">Estimated arrival</p>
                                    <p class="mt-1 text-sm font-black text-emerald-700"><?= $arriveStart ?> &ndash; <?= $arriveEnd ?></p>
                                    <p class="mt-1 text-xs text-slate-500">You&apos;ll get an SMS when it&apos;s on the way.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <aside class="space-y-6">
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-5">
                            <h2 class="text-sm font-black uppercase text-slate-500">Order summary</h2>
                            <dl class="mt-4 space-y-2.5 text-sm">
                                <div class="flex justify-between"><dt class="text-slate-500">Order number</dt><dd class="font-bold">#<?= $orderId ?></dd></div>
                                <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd class="font-bold"><?= peso($subtotal) ?></dd></div>
                                <div class="flex justify-between"><dt class="text-slate-500"><?= $promotion ? htmlspecialchars((string) $promotion['label']) : 'Discount' ?></dt><dd class="font-bold text-emerald-700">-<?= peso($discount) ?></dd></div>
                                <div class="flex justify-between"><dt class="text-slate-500">Delivery</dt><dd class="font-bold"><?= $shipping > 0 ? peso($shipping) : 'FREE' ?></dd></div>
                                <div class="my-3 h-px bg-slate-200"></div>
                                <div class="flex items-end justify-between"><dt class="font-black">Total</dt><dd class="text-xl font-black text-rose-600"><?= peso($orderTotal) ?></dd></div>
                                <div class="flex justify-between"><dt class="text-slate-500">Payment</dt><dd class="font-bold">Cash on delivery</dd></div>
                            </dl>
                        </div>

                        <div class="rounded-lg border border-slate-200 p-5">
                            <h2 class="text-sm font-black uppercase text-slate-500">What happens next</h2>
                            <ol class="mt-4 space-y-4">
                                <li class="flex gap-3">
                                    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-emerald-600 text-white"><i data-lucide="check" class="h-4 w-4"></i></span>
                                    <div><p class="text-sm font-bold">Order placed</p><p class="text-xs text-slate-500">We&apos;ve received your order.</p></div>
                                </li>
                                <li class="flex gap-3">
                                    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-slate-200 text-slate-500"><i data-lucide="package" class="h-4 w-4"></i></span>
                                    <div><p class="text-sm font-bold">Packed</p><p class="text-xs text-slate-500">We&apos;re preparing your items.</p></div>
                                </li>
                                <li class="flex gap-3">
                                    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-slate-200 text-slate-500"><i data-lucide="truck" class="h-4 w-4"></i></span>
                                    <div><p class="text-sm font-bold">Out for delivery</p><p class="text-xs text-slate-500">Cash on delivery &middot; have payment ready.</p></div>
                                </li>
                            </ol>
                        </div>

                        <a class="btn w-full border-0 bg-slate-950 text-white hover:bg-rose-600" href="index.php">
                            Continue shopping <i data-lucide="arrow-right" class="h-4 w-4"></i>
                        </a>
                    </aside>
                </div>
            </section>
            <script>localStorage.removeItem('ecocart_cart');</script>
        <?php else: ?>
            <div class="mb-7 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-black uppercase text-rose-600">Complete your purchase</p>
                    <h1 class="mt-1 text-3xl font-black sm:text-4xl">Checkout</h1>
                    <p class="mt-2 text-sm text-slate-500">Review your items and tell us where to deliver them.</p>
                </div>
                <ol class="flex items-center gap-2 text-[10px] font-black uppercase text-slate-400" aria-label="Checkout progress">
                    <li class="flex items-center gap-2 text-emerald-700"><span class="grid h-6 w-6 place-items-center rounded-full bg-emerald-100">1</span> Cart</li>
                    <li class="h-px w-6 bg-slate-300"></li>
                    <li class="flex items-center gap-2 text-rose-600"><span class="grid h-6 w-6 place-items-center rounded-full bg-rose-100">2</span> Delivery</li>
                    <li class="h-px w-6 bg-slate-300"></li>
                    <li class="flex items-center gap-2"><span class="grid h-6 w-6 place-items-center rounded-full bg-slate-200">3</span> Confirm</li>
                </ol>
            </div>

            <div class="mb-5 hidden rounded-lg border border-rose-300 bg-rose-50 p-4 text-rose-900 shadow-sm" data-checkout-error>
                <div class="flex items-start gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-rose-600 text-white"><i data-lucide="server-crash" class="h-5 w-5"></i></span>
                    <div>
                        <p class="font-black">SERVER ERROR. PLEASE TRY AGAIN.</p>
                        <p class="mt-1 text-sm text-rose-700">We could not reach checkout. Your cart is still saved, so you can safely try again shortly.</p>
                    </div>
                </div>
            </div>

            <?php if ($errors): ?>
                <div class="mb-5 rounded-lg border border-rose-300 bg-rose-50 p-4 text-rose-900">
                    <div class="flex items-start gap-3">
                        <i data-lucide="circle-alert" class="mt-0.5 h-5 w-5 shrink-0 text-rose-600"></i>
                        <div>
                            <p class="font-black">Please check your order details.</p>
                            <?php foreach (array_unique($errors) as $error): ?>
                                <p class="mt-1 text-sm"><?= htmlspecialchars($error) ?></p>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1.12fr)_minmax(390px,0.88fr)]">
                <section class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 sm:px-6">
                        <div>
                            <h2 class="text-xl font-black">Your cart</h2>
                            <p class="mt-1 text-xs text-slate-500">Quantities can still be adjusted before ordering.</p>
                        </div>
                        <p class="text-lg font-black text-rose-600" data-cart-total>PHP 0.00</p>
                    </div>
                    <div class="hidden p-6" data-empty-cart>
                        <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center">
                            <span class="mx-auto grid h-14 w-14 place-items-center rounded-lg bg-rose-100 text-rose-600"><i data-lucide="shopping-bag" class="h-7 w-7"></i></span>
                            <h3 class="mt-4 text-xl font-black">Your cart is waiting for something good.</h3>
                            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">Browse the Big Blowout catalog and add at least one item before checking out.</p>
                            <a class="btn mt-5 border-0 bg-slate-950 text-white hover:bg-rose-600" href="index.php#products">Browse sale items</a>
                        </div>
                    </div>
                    <div class="divide-y divide-slate-100 px-5 sm:px-6" data-checkout-list></div>
                    <div class="grid gap-3 border-t border-slate-200 bg-slate-50 p-4 sm:grid-cols-3">
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-600"><i data-lucide="shield-check" class="h-4 w-4 text-emerald-600"></i> Protected checkout</div>
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-600"><i data-lucide="package-check" class="h-4 w-4 text-cyan-600"></i> Tracked delivery</div>
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-600"><i data-lucide="rotate-ccw" class="h-4 w-4 text-amber-600"></i> Easy returns</div>
                    </div>
                </section>

                <form class="overflow-hidden rounded-lg border border-slate-200 bg-white xl:sticky xl:top-5" method="post" data-checkout-form>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <input type="hidden" name="cart_json" data-cart-json>
                    <input type="hidden" name="promo_code" value="<?= htmlspecialchars($promoCode) ?>" data-promo-code-field>

                    <div class="border-b border-slate-200 p-5 sm:p-6">
                        <div class="flex items-center gap-3">
                            <span class="grid h-9 w-9 place-items-center rounded-lg bg-slate-950 text-white"><i data-lucide="map-pin" class="h-4 w-4"></i></span>
                            <div>
                                <h2 class="text-lg font-black">Delivery information</h2>
                                <p class="text-xs text-slate-500"><?= $currentUser ? 'Signed in as ' . htmlspecialchars((string) $currentUser['email']) : 'We&apos;ll use these details for updates.' ?></p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <label class="block sm:col-span-2">
                                <span class="mb-1.5 block text-xs font-black">Full name</span>
                                <input class="input input-bordered h-11 w-full rounded-lg border-slate-300 bg-white focus:border-slate-950 focus:outline-none" name="customer_name" autocomplete="name" value="<?= htmlspecialchars((string) ($_POST['customer_name'] ?? $currentUser['name'] ?? '')) ?>" placeholder="Juan Dela Cruz" required>
                            </label>
                            <label class="block">
                                <span class="mb-1.5 block text-xs font-black">Email</span>
                                <input class="input input-bordered h-11 w-full rounded-lg border-slate-300 bg-white focus:border-slate-950 focus:outline-none" type="email" name="email" autocomplete="email" value="<?= htmlspecialchars((string) ($_POST['email'] ?? $currentUser['email'] ?? '')) ?>" placeholder="juan@email.com" required>
                            </label>
                            <label class="block">
                                <span class="mb-1.5 block text-xs font-black">Mobile number</span>
                                <input class="input input-bordered h-11 w-full rounded-lg border-slate-300 bg-white focus:border-slate-950 focus:outline-none" name="phone" autocomplete="tel" value="<?= htmlspecialchars((string) ($_POST['phone'] ?? '')) ?>" placeholder="+63 9XX XXX XXXX" required>
                            </label>
                            <label class="block sm:col-span-2">
                                <span class="mb-1.5 block text-xs font-black">Complete delivery address</span>
                                <textarea class="textarea textarea-bordered min-h-24 w-full rounded-lg border-slate-300 bg-white focus:border-slate-950 focus:outline-none" name="address" autocomplete="street-address" placeholder="House number, street, barangay, city, province" required><?= htmlspecialchars((string) ($_POST['address'] ?? '')) ?></textarea>
                            </label>
                        </div>
                    </div>

                    <div class="border-b border-slate-200 p-5 sm:p-6">
                        <h2 class="text-sm font-black">Delivery and payment</h2>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border-2 border-rose-500 bg-rose-50 p-3">
                                <input class="radio radio-sm border-rose-500 bg-white text-rose-600" type="radio" checked>
                                <span><span class="block text-xs font-black">Standard delivery</span><span class="text-[10px] text-slate-500">2-5 business days</span></span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 p-3">
                                <input class="radio radio-sm" type="radio" checked>
                                <span><span class="block text-xs font-black">Cash on delivery</span><span class="text-[10px] text-slate-500">Pay when it arrives</span></span>
                            </label>
                        </div>
                    </div>

                    <div class="border-b border-slate-200 p-5 sm:p-6">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-sm font-black">Discount code</h2>
                                <p class="mt-1 text-xs text-slate-500">Enter your sale code before placing the order.</p>
                            </div>
                            <span class="rounded bg-amber-100 px-2 py-1 text-[10px] font-black uppercase text-amber-800">Extra 10% off</span>
                        </div>
                        <div class="mt-3 flex gap-2">
                            <div class="relative min-w-0 flex-1">
                                <i data-lucide="ticket-percent" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                                <input class="input input-bordered h-11 w-full rounded-lg border-slate-300 bg-white pl-10 font-mono text-sm uppercase focus:border-slate-950 focus:outline-none" value="<?= htmlspecialchars($promoCode) ?>" maxlength="24" autocomplete="off" placeholder="Enter code" data-promo-input>
                            </div>
                            <button class="btn h-11 min-h-11 border-0 bg-slate-950 px-5 text-white hover:bg-rose-600" type="button" data-promo-apply>Apply</button>
                            <button class="btn btn-square h-11 min-h-11 border-slate-200 bg-white text-slate-500 hover:bg-rose-50 hover:text-rose-700 <?= $promoCode === '' ? 'hidden' : '' ?>" type="button" aria-label="Remove discount code" title="Remove code" data-promo-remove><i data-lucide="x" class="h-4 w-4"></i></button>
                        </div>
                        <p class="mt-2 hidden text-xs font-bold" role="status" aria-live="polite" data-promo-feedback></p>
                    </div>

                    <div class="bg-slate-50 p-5 sm:p-6">
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span class="font-bold" data-order-subtotal>PHP 0.00</span></div>
                            <div class="flex justify-between"><span class="text-slate-500" data-order-discount-label>Discount</span><span class="font-bold text-emerald-700" data-order-discount>-PHP 0.00</span></div>
                            <div class="flex justify-between"><span class="text-slate-500">Delivery</span><span class="font-bold" data-order-shipping>PHP 0.00</span></div>
                        </div>
                        <div class="my-4 h-px bg-slate-200"></div>
                        <div class="flex items-end justify-between">
                            <div><p class="text-sm font-black">Order total</p><p class="text-[10px] text-slate-500">VAT included where applicable</p></div>
                            <span class="text-2xl font-black text-rose-600" data-order-total>PHP 0.00</span>
                        </div>
                        <button class="btn btn-lg mt-5 w-full border-0 bg-rose-600 text-white shadow-lg shadow-rose-600/20 hover:bg-rose-700" type="submit" data-place-order disabled>
                            <i data-lucide="lock-keyhole" class="h-4 w-4"></i>
                            <span>Add items to continue</span>
                        </button>
                        <p class="mt-3 text-center text-[10px] leading-4 text-slate-400">By placing the order, you agree to EcoCart&apos;s sale and delivery terms.</p>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </main>

    <footer class="mt-6 border-t border-slate-200 bg-white">
        <div class="app-shell flex flex-wrap items-center justify-between gap-3 py-5 text-xs text-slate-500">
            <p>&copy; <?= date('Y') ?> EcoCart</p>
            <div class="flex items-center gap-4"><span>Secure order handling</span><span>Customer support: +63 917 555 0142</span></div>
        </div>
    </footer>

    <div class="pointer-events-none fixed inset-x-0 top-24 z-50 flex justify-center px-4">
        <div class="hidden items-center gap-3 rounded-lg border border-emerald-200 bg-white px-4 py-3 text-sm font-bold text-emerald-800 shadow-2xl" data-scene-restored>
            <span class="grid h-8 w-8 place-items-center rounded-lg bg-emerald-600 text-white"><i data-lucide="circle-check-big" class="h-4 w-4"></i></span>
            EcoCart services have been restored. Thank you for your patience.
        </div>
    </div>

    <script src="assets/scene-client.js?v=<?= htmlspecialchars((string) @filemtime(__DIR__ . '/assets/scene-client.js')) ?>"></script>
    <script src="assets/app-public.js?v=<?= htmlspecialchars((string) @filemtime(__DIR__ . '/assets/app-public.js')) ?>"></script>
</body>
</html>

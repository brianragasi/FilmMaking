<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/products.php';
require_once __DIR__ . '/includes/auth.php';

$errors = [];
$success = false;
$orderId = null;
$subtotal = 0.0;
$orderTotal = 0.0;
$currentUser = current_user();

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

    $discount = $subtotal * 0.10;
    $shipping = $subtotal >= 1500 ? 0.0 : 49.0;
    $orderTotal = max(0.0, $subtotal - $discount + $shipping);

    if (!$errors) {
        $pdo = db();

        if (!$pdo) {
            $errors[] = 'Checkout is temporarily unavailable. Please try again in a few moments.';
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
                $errors[] = 'Checkout is temporarily unavailable. Please try again in a few moments.';
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
    <link href="public/output.css?v=<?= htmlspecialchars((string) @filemtime(__DIR__ . '/public/output.css')) ?>" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="icon" href="data:,">
</head>
<body class="min-h-screen bg-[#f4f5f7] text-slate-950">
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
                <a class="btn btn-square btn-sm border-slate-200 bg-white text-slate-700 hover:border-slate-950" href="<?= $currentUser ? 'account.php' : 'login.php?next=checkout.php' ?>" aria-label="<?= $currentUser ? 'Open my account' : 'Sign in' ?>" title="<?= $currentUser ? 'My account' : 'Sign in' ?>">
                    <i data-lucide="user-round" class="h-4 w-4"></i>
                </a>
                <a class="btn btn-sm border-slate-200 bg-white text-slate-700 hover:border-slate-950 hover:bg-slate-50" href="index.php#products">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                    <span class="hidden sm:inline">Continue shopping</span>
                    <span class="sm:hidden">Shop</span>
                </a>
            </div>
        </div>
    </header>

    <main class="app-shell py-7 sm:py-10">
        <?php if ($success): ?>
            <section class="mx-auto max-w-3xl overflow-hidden rounded-lg border border-emerald-200 bg-white shadow-xl">
                <div class="bg-emerald-600 px-6 py-8 text-white sm:px-10">
                    <span class="grid h-14 w-14 place-items-center rounded-lg bg-white text-emerald-600"><i data-lucide="check" class="h-8 w-8"></i></span>
                    <p class="mt-6 text-xs font-black uppercase text-emerald-100">Order confirmed</p>
                    <h1 class="mt-2 text-3xl font-black sm:text-4xl">Thanks, <?= htmlspecialchars($name) ?>. Your order is in.</h1>
                    <p class="mt-3 text-sm text-emerald-50">We sent the order details to <?= htmlspecialchars($email) ?>.</p>
                </div>
                <div class="grid gap-6 p-6 sm:grid-cols-3 sm:p-10">
                    <div><p class="text-[10px] font-black uppercase text-slate-400">Order number</p><p class="mt-1 text-lg font-black">#<?= $orderId ?></p></div>
                    <div><p class="text-[10px] font-black uppercase text-slate-400">Amount</p><p class="mt-1 text-lg font-black text-rose-600"><?= peso($orderTotal) ?></p></div>
                    <div><p class="text-[10px] font-black uppercase text-slate-400">Payment</p><p class="mt-1 text-lg font-black">Cash on delivery</p></div>
                    <a class="btn border-0 bg-slate-950 text-white hover:bg-rose-600 sm:col-span-3" href="index.php">
                        Continue shopping <i data-lucide="arrow-right" class="h-4 w-4"></i>
                    </a>
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

                    <div class="bg-slate-50 p-5 sm:p-6">
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span class="font-bold" data-order-subtotal>PHP 0.00</span></div>
                            <div class="flex justify-between"><span class="text-slate-500">Big Blowout discount</span><span class="font-bold text-emerald-700" data-order-discount>-PHP 0.00</span></div>
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

    <script src="assets/app.js"></script>
</body>
</html>

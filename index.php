<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/products.php';
require_once __DIR__ . '/includes/auth.php';

$products = products();
$currentUser = current_user();
$accountName = $currentUser ? trim(explode(' ', (string) $currentUser['name'])[0] ?? (string) $currentUser['name']) : '';
$departments = [
    ['name' => 'School', 'filter' => 'students', 'label' => 'Student picks', 'icon' => 'graduation-cap', 'tone' => 'bg-cyan-50 text-cyan-700'],
    ['name' => 'Worksite', 'filter' => 'construction', 'label' => 'Safety and tools', 'icon' => 'hard-hat', 'tone' => 'bg-amber-50 text-amber-700'],
    ['name' => 'Rider', 'filter' => 'rider', 'label' => 'Road ready', 'icon' => 'bike', 'tone' => 'bg-indigo-50 text-indigo-700'],
    ['name' => 'Home', 'filter' => 'barangay', 'label' => 'Practical finds', 'icon' => 'house', 'tone' => 'bg-emerald-50 text-emerald-700'],
    ['name' => 'Baby care', 'filter' => 'family', 'label' => 'Family essentials', 'icon' => 'baby', 'tone' => 'bg-rose-50 text-rose-700'],
];

$productCount = count($products);
$dealProduct = $products[1] ?? $products[0] ?? null;
$storyPicks = array_slice($products, 0, 6);
?>
<!doctype html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="EcoCart Big Blowout Sale - everyday essentials for students, workers, riders, and families.">
    <link rel="canonical" href="https://ecocart-mnl.site.je/">
    <meta property="og:title" content="EcoCart | Big Blowout Sale">
    <meta property="og:description" content="Shop school, worksite, rider, home, and family essentials at unusually good prices.">
    <meta property="og:image" content="https://ecocart-mnl.site.je/assets/images/ecocart-share.png">
    <meta property="og:image:secure_url" content="https://ecocart-mnl.site.je/assets/images/ecocart-share.png">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="1730">
    <meta property="og:image:height" content="909">
    <meta property="og:image:alt" content="EcoCart sale collection with school, worksite, rider, home, and family essentials">
    <meta property="og:url" content="https://ecocart-mnl.site.je/">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="EcoCart">
    <meta property="og:locale" content="en_PH">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="EcoCart | Big Blowout Sale">
    <meta name="twitter:description" content="Shop school, worksite, rider, home, and family essentials at unusually good prices.">
    <meta name="twitter:image" content="https://ecocart-mnl.site.je/assets/images/ecocart-share.png">
    <meta name="twitter:image:alt" content="EcoCart sale collection with school, worksite, rider, home, and family essentials">
    <title>EcoCart | Big Blowout Sale</title>
    <link href="public/output.css?v=<?= htmlspecialchars((string) @filemtime(__DIR__ . '/public/output.css')) ?>" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="icon" href="data:,">
</head>
<body class="bg-[#f4f5f7] text-slate-950 antialiased">
    <div class="bg-[#e11d48] text-white">
        <div class="app-shell flex min-h-9 items-center justify-between gap-4 text-[11px] font-bold">
            <div class="hidden items-center gap-5 sm:flex">
                <span class="flex items-center gap-1.5"><i data-lucide="phone" class="h-3.5 w-3.5"></i> +63 917 555 0142</span>
                <span class="flex items-center gap-1.5"><i data-lucide="mail" class="h-3.5 w-3.5"></i> hello@ecocart.ph</span>
            </div>
            <p class="flex flex-1 items-center justify-center gap-2 text-center uppercase">
                <i data-lucide="zap" class="h-3.5 w-3.5 fill-current"></i>
                Big Blowout Sale: up to 70% off selected essentials
            </p>
            <span class="hidden items-center gap-1.5 lg:flex"><i data-lucide="map-pin" class="h-3.5 w-3.5"></i> Nationwide delivery</span>
        </div>
    </div>

    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white">
        <div class="app-shell flex h-[76px] items-center gap-5">
            <a class="flex shrink-0 items-center gap-2" href="index.php" aria-label="EcoCart home">
                <span class="grid h-10 w-10 place-items-center rounded-lg bg-slate-950 text-white">
                    <i data-lucide="leaf" class="h-5 w-5"></i>
                </span>
                <span class="text-2xl font-black">Eco<span class="text-rose-600">Cart</span><span class="text-rose-600">.</span></span>
            </a>

            <form class="hidden min-w-0 flex-1 lg:block" role="search" data-product-search-form>
                <div class="join flex w-full overflow-hidden rounded-lg border-2 border-slate-950">
                    <select class="join-item select h-11 w-40 border-0 bg-slate-100 text-xs font-extrabold focus:outline-none" data-category-select aria-label="Search department">
                        <option value="all">All departments</option>
                        <option value="students">School</option>
                        <option value="construction">Worksite</option>
                        <option value="rider">Rider</option>
                        <option value="barangay">Home</option>
                        <option value="family">Baby care</option>
                    </select>
                    <input class="join-item input h-11 min-w-0 flex-1 border-0 bg-white focus:outline-none" type="search" placeholder="What are you looking for today?" data-product-search>
                    <button class="join-item btn h-11 min-h-11 rounded-none border-0 bg-slate-950 px-5 text-white hover:bg-rose-600" type="submit" aria-label="Search products">
                        <i data-lucide="search" class="h-4 w-4"></i>
                        <span class="hidden xl:inline">Search</span>
                    </button>
                </div>
            </form>

            <nav class="ml-auto flex items-center gap-2 sm:gap-4" aria-label="Customer actions">
                <a class="flex items-center gap-2 text-left text-xs font-bold text-slate-700 transition hover:text-rose-600" href="<?= $currentUser ? 'account.php' : 'login.php' ?>" aria-label="<?= $currentUser ? 'Open my account' : 'Sign in' ?>">
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-slate-100"><i data-lucide="user-round" class="h-5 w-5"></i></span>
                    <span class="hidden xl:block">
                        <span class="block text-[10px] font-semibold text-slate-400"><?= $currentUser ? 'Welcome, ' . htmlspecialchars($accountName) : 'Welcome' ?></span>
                        <?= $currentUser ? 'My account' : 'Sign in' ?>
                    </span>
                </a>
                <button class="relative grid h-10 w-10 place-items-center rounded-lg text-slate-700 transition hover:bg-rose-50 hover:text-rose-600" type="button" aria-label="Wishlist">
                    <i data-lucide="heart" class="h-5 w-5"></i>
                    <span class="absolute right-0 top-0 grid h-4 min-w-4 place-items-center rounded-full bg-rose-600 px-1 text-[9px] font-black text-white" data-wishlist-count>0</span>
                </button>
                <a href="checkout.php" class="flex items-center gap-2 border-l border-slate-200 pl-3 text-slate-800 transition hover:text-rose-600 sm:pl-4">
                    <span class="relative grid h-10 w-10 place-items-center rounded-lg bg-slate-950 text-white">
                        <i data-lucide="shopping-bag" class="h-5 w-5"></i>
                        <span class="absolute -right-1 -top-1 grid h-4 min-w-4 place-items-center rounded-full bg-rose-600 px-1 text-[9px] font-black" data-cart-count>0</span>
                    </span>
                    <span class="hidden text-left sm:block">
                        <span class="block text-[10px] font-semibold text-slate-400">Your cart</span>
                        <span class="block text-xs font-black" data-cart-total>PHP 0.00</span>
                    </span>
                </a>
            </nav>
        </div>

        <div class="app-shell pb-3 lg:hidden">
            <form class="flex overflow-hidden rounded-lg border border-slate-300 bg-white" role="search" data-product-search-form>
                <input class="input h-10 min-w-0 flex-1 border-0 focus:outline-none" type="search" placeholder="Search products" data-product-search>
                <button class="btn h-10 min-h-10 rounded-none border-0 bg-slate-950 text-white" type="submit" aria-label="Search products">
                    <i data-lucide="search" class="h-4 w-4"></i>
                </button>
            </form>
        </div>

        <div class="border-t border-slate-100">
            <nav class="app-shell flex h-11 items-center gap-1 overflow-x-auto text-xs font-extrabold uppercase" aria-label="Shop departments">
                <button class="flex shrink-0 items-center gap-2 self-stretch bg-slate-950 px-4 text-white" type="button" data-product-filter="all">
                    <i data-lucide="menu" class="h-4 w-4"></i> All departments
                </button>
                <a href="#products" class="shrink-0 px-4 py-3 text-rose-600">Hot deals</a>
                <button class="shrink-0 px-4 py-3 transition hover:text-rose-600" type="button" data-product-filter="students">School</button>
                <button class="shrink-0 px-4 py-3 transition hover:text-rose-600" type="button" data-product-filter="construction">Worksite</button>
                <button class="shrink-0 px-4 py-3 transition hover:text-rose-600" type="button" data-product-filter="rider">Rider</button>
                <button class="shrink-0 px-4 py-3 transition hover:text-rose-600" type="button" data-product-filter="barangay">Home</button>
                <button class="shrink-0 px-4 py-3 transition hover:text-rose-600" type="button" data-product-filter="family">Baby care</button>
                <span class="ml-auto hidden shrink-0 items-center gap-2 text-emerald-700 xl:flex">
                    <i data-lucide="truck" class="h-4 w-4"></i> Free shipping over PHP 1,500
                </span>
            </nav>
        </div>
    </header>

    <main>
        <div class="app-shell py-5">
            <section class="grid gap-4 lg:grid-cols-[minmax(0,2fr)_minmax(280px,0.8fr)]" aria-label="Big Blowout Sale">
                <article class="hero-market relative min-h-[480px] overflow-hidden rounded-lg">
                    <div class="absolute inset-0 bg-slate-950/55"></div>
                    <div class="relative flex min-h-[480px] max-w-2xl flex-col justify-center px-6 py-12 text-white sm:px-12">
                        <span class="mb-5 w-fit border-l-4 border-rose-500 bg-white px-3 py-2 text-[11px] font-black uppercase text-slate-950">Midyear price drop</span>
                        <p class="mb-2 text-sm font-black uppercase text-rose-300">EcoCart Big Blowout Sale</p>
                        <h1 class="max-w-xl text-4xl font-black leading-tight sm:text-6xl">Everyday essentials. Unusually good prices.</h1>
                        <p class="mt-5 max-w-lg text-sm leading-6 text-slate-200 sm:text-base">School supplies, work gear, rider equipment, home finds, and baby care deals in one practical sale.</p>
                        <div class="mt-7 flex flex-wrap items-center gap-3">
                            <a class="btn border-0 bg-rose-600 px-7 text-white hover:bg-rose-700" href="#products">
                                Shop the sale <i data-lucide="arrow-right" class="h-4 w-4"></i>
                            </a>
                            <div class="flex h-12 items-center gap-3 border border-white/30 bg-slate-950/70 px-4">
                                <span class="text-[10px] font-bold uppercase text-slate-300">Deals refresh in</span>
                                <span class="font-mono text-xl font-black" data-countdown>05:00</span>
                            </div>
                        </div>
                        <div class="mt-9 flex flex-wrap gap-x-6 gap-y-2 text-xs font-bold text-slate-200">
                            <span class="flex items-center gap-2"><i data-lucide="badge-check" class="h-4 w-4 text-emerald-400"></i> Secure payments</span>
                            <span class="flex items-center gap-2"><i data-lucide="rotate-ccw" class="h-4 w-4 text-cyan-300"></i> Easy returns</span>
                            <span class="flex items-center gap-2"><i data-lucide="package-check" class="h-4 w-4 text-amber-300"></i> Limited stocks</span>
                        </div>
                    </div>
                </article>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                    <a class="side-promo side-promo-work relative min-h-[232px] overflow-hidden rounded-lg text-white" href="#products">
                        <div class="absolute inset-0 bg-slate-950/60"></div>
                        <div class="relative flex h-full min-h-[232px] flex-col items-start justify-end p-6">
                            <span class="badge border-0 bg-amber-400 font-black text-slate-950">WORKSITE WEEK</span>
                            <h2 class="mt-3 text-2xl font-black">Built for the job.</h2>
                            <p class="mt-1 text-sm text-slate-200">Safety boots, helmets, and tool sets from PHP 499.</p>
                            <span class="mt-4 flex items-center gap-2 text-xs font-black uppercase">Shop work gear <i data-lucide="arrow-up-right" class="h-4 w-4"></i></span>
                        </div>
                    </a>
                    <a class="side-promo side-promo-family relative min-h-[232px] overflow-hidden rounded-lg text-white" href="#products">
                        <div class="absolute inset-0 bg-rose-950/55"></div>
                        <div class="relative flex h-full min-h-[232px] flex-col items-start justify-end p-6">
                            <span class="badge border-0 bg-white font-black text-rose-700">FAMILY SAVINGS</span>
                            <h2 class="mt-3 text-2xl font-black">Care costs less today.</h2>
                            <p class="mt-1 text-sm text-rose-50">Baby essentials and practical home bundles on sale.</p>
                            <span class="mt-4 flex items-center gap-2 text-xs font-black uppercase">See family deals <i data-lucide="arrow-up-right" class="h-4 w-4"></i></span>
                        </div>
                    </a>
                </div>
            </section>

            <section class="mt-4 grid border border-slate-200 bg-white sm:grid-cols-2 xl:grid-cols-4" aria-label="Shopping benefits">
                <?php
                $benefits = [
                    ['truck', 'Nationwide delivery', 'Track every parcel'],
                    ['shield-check', 'Protected checkout', 'Secure order handling'],
                    ['badge-percent', 'Price-drop deals', 'Save up to 70%'],
                    ['messages-square', 'Customer support', 'Help when you need it'],
                ];
                ?>
                <?php foreach ($benefits as $benefit): ?>
                    <div class="flex min-h-20 items-center gap-3 border-b border-slate-200 px-5 last:border-b-0 sm:[&:nth-child(odd)]:border-r xl:border-b-0 xl:border-r xl:last:border-r-0">
                        <i data-lucide="<?= $benefit[0] ?>" class="h-6 w-6 shrink-0 text-rose-600"></i>
                        <div>
                            <p class="text-sm font-black"><?= $benefit[1] ?></p>
                            <p class="text-xs text-slate-500"><?= $benefit[2] ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </section>
        </div>

        <section id="departments" class="border-y border-slate-200 bg-white">
            <div class="app-shell py-8">
                <div class="mb-5 flex items-end justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase text-rose-600">Find your aisle</p>
                        <h2 class="mt-1 text-2xl font-black">Shop by department</h2>
                    </div>
                    <p class="hidden text-sm text-slate-500 md:block"><?= $productCount ?> sale items available today</p>
                </div>
                <div class="grid grid-cols-2 gap-3 md:grid-cols-5">
                    <?php foreach ($departments as $department): ?>
                        <button class="group flex min-h-28 items-center gap-4 rounded-lg border border-slate-200 p-4 text-left transition hover:border-slate-950 hover:shadow-lg" type="button" data-product-filter="<?= htmlspecialchars($department['filter']) ?>">
                            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-lg <?= $department['tone'] ?>">
                                <i data-lucide="<?= $department['icon'] ?>" class="h-6 w-6"></i>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-black"><?= htmlspecialchars($department['name']) ?></span>
                                <span class="mt-1 block text-xs text-slate-500"><?= htmlspecialchars($department['label']) ?></span>
                                <span class="mt-2 flex items-center gap-1 text-[10px] font-black uppercase text-rose-600">Explore <i data-lucide="arrow-right" class="h-3 w-3 transition group-hover:translate-x-1"></i></span>
                            </span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="app-shell py-10">
            <div class="mb-5 flex items-end justify-between">
                <div>
                    <p class="text-xs font-black uppercase text-rose-600">Popular right now</p>
                    <h2 class="mt-1 text-2xl font-black">What shoppers are watching</h2>
                </div>
                <a class="hidden items-center gap-2 text-xs font-black uppercase text-slate-700 sm:flex" href="#products">View all products <i data-lucide="arrow-right" class="h-4 w-4"></i></a>
            </div>

            <div class="grid gap-4 lg:grid-cols-[1.1fr_1.9fr]">
                <?php if ($dealProduct): ?>
                    <article class="grid overflow-hidden rounded-lg border border-slate-200 bg-[#fff7ed] sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                        <figure class="relative min-h-72 overflow-hidden bg-white">
                            <img class="absolute inset-0 h-full w-full object-cover" src="<?= htmlspecialchars((string) $dealProduct['image_url']) ?>" alt="<?= htmlspecialchars((string) $dealProduct['name']) ?>">
                            <span class="absolute left-4 top-4 bg-rose-600 px-3 py-2 text-[10px] font-black uppercase text-white">Deal of the day</span>
                        </figure>
                        <div class="flex flex-col justify-center p-6">
                            <p class="text-xs font-black uppercase text-amber-700"><?= htmlspecialchars((string) $dealProduct['category']) ?></p>
                            <h3 class="mt-2 text-2xl font-black"><?= htmlspecialchars((string) $dealProduct['name']) ?></h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600"><?= htmlspecialchars((string) $dealProduct['description']) ?></p>
                            <div class="mt-4 flex items-end gap-3">
                                <span class="text-2xl font-black text-rose-600"><?= peso((float) $dealProduct['price']) ?></span>
                                <span class="pb-1 text-xs text-slate-400 line-through"><?= peso((float) $dealProduct['price'] * 1.4) ?></span>
                            </div>
                            <div class="mt-5 h-1.5 overflow-hidden bg-white"><div class="h-full w-3/4 bg-rose-600"></div></div>
                            <p class="mt-2 text-[11px] font-bold text-slate-500">Only <?= (int) $dealProduct['stock'] ?> left at this price</p>
                        </div>
                    </article>
                <?php endif; ?>

                <div class="grid gap-4 rounded-lg border border-slate-200 bg-white p-4 sm:grid-cols-2 xl:grid-cols-3">
                    <?php foreach ($storyPicks as $index => $product): ?>
                        <article class="flex min-w-0 gap-3 border-b border-slate-100 pb-4 sm:[&:nth-last-child(-n+2)]:border-b-0 xl:[&:nth-last-child(-n+3)]:border-b-0">
                            <img class="h-20 w-20 shrink-0 rounded-lg object-cover" src="<?= htmlspecialchars((string) $product['image_url']) ?>" alt="<?= htmlspecialchars((string) $product['name']) ?>">
                            <div class="min-w-0">
                                <p class="text-[10px] font-black uppercase text-slate-400"><?= htmlspecialchars((string) $product['category']) ?></p>
                                <h3 class="mt-1 line-clamp-2 text-sm font-black"><?= htmlspecialchars((string) $product['name']) ?></h3>
                                <div class="mt-1 flex text-amber-400" aria-label="5 out of 5 stars">
                                    <?php for ($star = 0; $star < 5; $star++): ?><i data-lucide="star" class="h-3 w-3 fill-current"></i><?php endfor; ?>
                                </div>
                                <p class="mt-1 text-sm font-black text-rose-600"><?= peso((float) $product['price']) ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section id="products" class="border-y border-slate-200 bg-white">
            <div class="app-shell py-10">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase text-rose-600">Big Blowout catalog</p>
                        <h2 class="mt-1 text-3xl font-black">Today&apos;s sale picks</h2>
                        <p class="mt-2 text-sm text-slate-500"><span data-product-result-count><?= $productCount ?></span> products ready to shop.</p>
                    </div>
                    <div class="flex max-w-full gap-1 overflow-x-auto rounded-lg bg-slate-100 p-1" role="group" aria-label="Filter products">
                        <button class="product-filter-active btn btn-sm shrink-0 border-0" type="button" data-product-filter="all">All</button>
                        <button class="btn btn-sm shrink-0 border-0 bg-transparent" type="button" data-product-filter="students">School</button>
                        <button class="btn btn-sm shrink-0 border-0 bg-transparent" type="button" data-product-filter="construction">Worksite</button>
                        <button class="btn btn-sm shrink-0 border-0 bg-transparent" type="button" data-product-filter="rider">Rider</button>
                        <button class="btn btn-sm shrink-0 border-0 bg-transparent" type="button" data-product-filter="barangay">Home</button>
                        <button class="btn btn-sm shrink-0 border-0 bg-transparent" type="button" data-product-filter="family">Baby care</button>
                    </div>
                </div>

                <div class="mt-6 hidden rounded-lg border border-dashed border-slate-300 bg-slate-50 p-10 text-center" data-product-empty>
                    <i data-lucide="search-x" class="mx-auto h-8 w-8 text-slate-400"></i>
                    <h3 class="mt-3 font-black">No products match that search.</h3>
                    <p class="mt-1 text-sm text-slate-500">Try another keyword or browse all departments.</p>
                </div>

                <div class="mt-6 grid gap-px overflow-hidden rounded-lg border border-slate-200 bg-slate-200 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4" data-product-grid>
                    <?php foreach ($products as $index => $product): ?>
                        <?php
                        $category = strtolower((string) $product['category']);
                        $cartProduct = [
                            'id' => (int) $product['id'],
                            'name' => (string) $product['name'],
                            'price' => (float) $product['price'],
                            'category' => (string) $product['category'],
                            'image_url' => (string) $product['image_url'],
                        ];
                        $productJson = htmlspecialchars((string) json_encode($cartProduct), ENT_QUOTES);
                        ?>
                        <article class="product-card group relative flex min-w-0 flex-col bg-white p-4" data-product-card data-product-name="<?= htmlspecialchars(strtolower((string) $product['name'])) ?>" data-product-category="<?= htmlspecialchars($category) ?>">
                            <figure class="relative aspect-[5/4] overflow-hidden bg-slate-100">
                                <img class="h-full w-full object-cover transition duration-300 group-hover:scale-105" src="<?= htmlspecialchars((string) $product['image_url']) ?>" alt="<?= htmlspecialchars((string) $product['name']) ?>" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?auto=format&fit=crop&w=900&q=80';">
                                <span class="absolute left-3 top-3 bg-rose-600 px-2 py-1 text-[10px] font-black uppercase text-white"><?= $index % 3 === 0 ? 'Best seller' : 'Sale' ?></span>
                                <div class="absolute right-3 top-3 flex flex-col gap-2">
                                    <button class="grid h-9 w-9 place-items-center rounded-lg bg-white text-slate-700 shadow transition hover:bg-rose-600 hover:text-white" type="button" aria-label="Add <?= htmlspecialchars((string) $product['name']) ?> to wishlist" data-wishlist-product="<?= (int) $product['id'] ?>">
                                        <i data-lucide="heart" class="h-4 w-4"></i>
                                    </button>
                                    <button class="grid h-9 w-9 place-items-center rounded-lg bg-white text-slate-700 shadow transition hover:bg-slate-950 hover:text-white" type="button" aria-label="Quick view <?= htmlspecialchars((string) $product['name']) ?>" data-quick-view="<?= $productJson ?>">
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                    </button>
                                </div>
                            </figure>
                            <div class="flex flex-1 flex-col pt-4">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-[10px] font-black uppercase text-slate-400"><?= htmlspecialchars((string) $product['category']) ?></p>
                                    <p class="flex items-center gap-1 text-[11px] font-bold text-slate-500"><i data-lucide="star" class="h-3 w-3 fill-amber-400 text-amber-400"></i> 4.<?= 6 + ($index % 4) ?></p>
                                </div>
                                <h3 class="mt-2 min-h-12 text-base font-black leading-6"><?= htmlspecialchars((string) $product['name']) ?></h3>
                                <p class="mt-1 line-clamp-2 text-xs leading-5 text-slate-500"><?= htmlspecialchars((string) $product['description']) ?></p>
                                <div class="mt-4 flex items-end gap-2">
                                    <p class="text-xl font-black text-rose-600"><?= peso((float) $product['price']) ?></p>
                                    <p class="pb-0.5 text-[11px] font-semibold text-slate-400 line-through"><?= peso((float) $product['price'] * 1.4) ?></p>
                                </div>
                                <div class="mt-4 flex items-center gap-2">
                                    <button class="btn min-w-0 flex-1 border-0 bg-slate-950 text-white hover:bg-rose-600" type="button" data-add-product="<?= $productJson ?>">
                                        <i data-lucide="shopping-bag" class="h-4 w-4"></i><span>Add to cart</span>
                                    </button>
                                    <span class="text-[10px] font-bold text-slate-400"><?= (int) $product['stock'] ?> left</span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="app-shell py-10">
            <div class="newsletter-market grid overflow-hidden rounded-lg bg-slate-950 text-white md:grid-cols-[1fr_0.8fr]">
                <div class="p-7 sm:p-10">
                    <p class="text-xs font-black uppercase text-rose-400">EcoCart insiders</p>
                    <h2 class="mt-2 max-w-xl text-3xl font-black">Get the next price drop before everyone else.</h2>
                    <p class="mt-3 max-w-lg text-sm leading-6 text-slate-300">Sale alerts, restocks, and useful deals. No daily noise.</p>
                </div>
                <form class="flex items-center bg-rose-600 p-7 sm:p-10" onsubmit="event.preventDefault();">
                    <div class="join w-full overflow-hidden rounded-lg bg-white">
                        <input class="join-item input min-w-0 flex-1 border-0 bg-white text-slate-950 focus:outline-none" type="email" required placeholder="Email address">
                        <button class="join-item btn rounded-none border-0 bg-slate-950 text-white hover:bg-slate-800" type="submit">Subscribe</button>
                    </div>
                </form>
            </div>
        </section>
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="app-shell grid gap-8 py-10 md:grid-cols-[1.4fr_1fr_1fr_1fr]">
            <div>
                <a class="text-2xl font-black" href="index.php">Eco<span class="text-rose-600">Cart.</span></a>
                <p class="mt-3 max-w-sm text-sm leading-6 text-slate-500">Practical goods at honest sale prices for students, workers, riders, homes, and growing families.</p>
                <div class="mt-5 flex gap-2">
                    <?php foreach (['facebook', 'instagram', 'youtube'] as $social): ?>
                        <a class="grid h-9 w-9 place-items-center rounded-lg bg-slate-100 transition hover:bg-rose-600 hover:text-white" href="#" aria-label="<?= ucfirst($social) ?>"><i data-lucide="<?= $social ?>" class="h-4 w-4"></i></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div>
                <h2 class="text-xs font-black uppercase">Shop</h2>
                <ul class="mt-4 space-y-3 text-sm text-slate-500">
                    <li><a class="hover:text-rose-600" href="#products">All products</a></li>
                    <li><a class="hover:text-rose-600" href="#departments">Departments</a></li>
                    <li><a class="hover:text-rose-600" href="#products">Hot deals</a></li>
                </ul>
            </div>
            <div>
                <h2 class="text-xs font-black uppercase">Customer care</h2>
                <ul class="mt-4 space-y-3 text-sm text-slate-500">
                    <li><a class="hover:text-rose-600" href="#">Track order</a></li>
                    <li><a class="hover:text-rose-600" href="#">Shipping</a></li>
                    <li><a class="hover:text-rose-600" href="#">Returns</a></li>
                </ul>
            </div>
            <div>
                <h2 class="text-xs font-black uppercase">Contact</h2>
                <ul class="mt-4 space-y-3 text-sm text-slate-500">
                    <li>+63 917 555 0142</li>
                    <li>hello@ecocart.ph</li>
                    <li>Mon-Sat, 8AM-6PM</li>
                </ul>
            </div>
        </div>
        <div class="border-t border-slate-100">
            <div class="app-shell flex flex-wrap items-center justify-between gap-3 py-4 text-xs text-slate-500">
                <p>&copy; <?= date('Y') ?> EcoCart. All rights reserved.</p>
                <p>Cash on delivery &middot; GCash &middot; Card</p>
            </div>
        </div>
    </footer>

    <dialog id="quick_view_modal" class="modal">
        <div class="modal-box max-w-3xl rounded-lg p-0">
            <form method="dialog"><button class="btn btn-circle btn-ghost btn-sm absolute right-3 top-3 z-10 bg-white" aria-label="Close quick view"><i data-lucide="x" class="h-4 w-4"></i></button></form>
            <div class="grid md:grid-cols-2">
                <img class="h-72 w-full object-cover md:h-full" src="https://images.unsplash.com/photo-1607082350899-7e105aa886ae?auto=format&fit=crop&w=900&q=80" alt="EcoCart sale item" data-quick-image>
                <div class="flex flex-col justify-center p-7">
                    <p class="text-xs font-black uppercase text-rose-600" data-quick-category></p>
                    <h2 class="mt-2 text-3xl font-black" data-quick-name></h2>
                    <div class="mt-3 flex items-center gap-2 text-amber-400">
                        <?php for ($star = 0; $star < 5; $star++): ?><i data-lucide="star" class="h-4 w-4 fill-current"></i><?php endfor; ?>
                        <span class="text-xs font-bold text-slate-500">Verified shopper favorite</span>
                    </div>
                    <p class="mt-5 text-2xl font-black text-rose-600" data-quick-price></p>
                    <button class="btn mt-6 border-0 bg-slate-950 text-white hover:bg-rose-600" type="button" data-quick-add>
                        <i data-lucide="shopping-bag" class="h-4 w-4"></i><span>Add to cart</span>
                    </button>
                </div>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>Close</button></form>
    </dialog>

    <div class="toast toast-end toast-bottom z-50 hidden" data-store-toast>
        <div class="alert rounded-lg bg-slate-950 text-white shadow-xl">
            <i data-lucide="check-circle-2" class="h-5 w-5 text-emerald-400"></i>
            <span data-store-toast-message>Added to cart</span>
        </div>
    </div>

    <script src="assets/app.js?v=<?= htmlspecialchars((string) @filemtime(__DIR__ . '/assets/app.js')) ?>"></script>
</body>
</html>

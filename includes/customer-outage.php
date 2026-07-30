<?php
declare(strict_types=1);

$sceneState = isset($sceneState) && is_array($sceneState) ? $sceneState : read_scene_state();
$scenePayload = scene_public_payload($sceneState);

http_response_code(503);
header('Retry-After: 20');
header('Cache-Control: no-store, max-age=0');
header('X-Robots-Tag: noindex, nofollow');
?>
<!doctype html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Server Error | EcoCart</title>
    <link href="public/output-public.css?v=<?= htmlspecialchars((string) @filemtime(dirname(__DIR__) . '/public/output-public.css')) ?>" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="icon" href="data:,">
</head>
<body class="min-h-screen bg-[#f4f5f7] text-slate-950" data-scene-client data-scene-view="outage" data-scene-cue="<?= htmlspecialchars((string) $scenePayload['cue']) ?>" data-scene-revision="<?= (int) $scenePayload['revision'] ?>" data-scene-updated="<?= htmlspecialchars((string) $scenePayload['updated_at']) ?>">
    <div class="flex min-h-screen flex-col">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex min-h-[72px] w-[min(1180px,calc(100%_-_32px))] items-center justify-between">
                <a class="flex items-center gap-2" href="index.php" aria-label="EcoCart home">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-slate-950 text-white"><i data-lucide="leaf" class="h-5 w-5"></i></span>
                    <span class="text-2xl font-black">Eco<span class="text-rose-600">Cart.</span></span>
                </a>
                <span class="flex items-center gap-2 text-xs font-bold text-slate-500">
                    <span class="h-2 w-2 rounded-full bg-rose-600"></span>
                    Service unavailable
                </span>
            </div>
        </header>

        <main class="mx-auto grid w-[min(1180px,calc(100%_-_32px))] flex-1 items-center py-10">
            <section class="grid items-center gap-10 lg:grid-cols-[minmax(0,0.82fr)_minmax(420px,1.18fr)]">
                <div>
                    <div class="flex items-center gap-3">
                        <span class="grid h-12 w-12 place-items-center rounded-lg bg-rose-600 text-white shadow-lg shadow-rose-600/20">
                            <i data-lucide="server-crash" class="h-6 w-6"></i>
                        </span>
                        <span class="font-mono text-sm font-black text-rose-600">ERROR 503</span>
                    </div>
                    <p class="mt-8 text-xs font-black uppercase text-rose-600">Server error</p>
                    <h1 class="mt-2 max-w-2xl text-4xl font-black leading-tight sm:text-6xl">Please try again.</h1>
                    <p class="mt-5 max-w-xl text-base leading-7 text-slate-600 sm:text-lg">EcoCart could not respond to your request. Your cart is still saved.</p>

                    <div class="mt-8 inline-flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-600">
                        <span class="loading loading-dots loading-sm text-rose-600"></span>
                        Waiting for EcoCart to return
                    </div>
                </div>

                <div class="relative min-h-[390px] overflow-hidden rounded-lg border border-slate-200 bg-slate-950 p-7 text-white shadow-2xl sm:p-10">
                    <div class="absolute inset-x-0 top-0 h-1 bg-rose-600"></div>
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-black uppercase text-slate-500">Request status</p>
                        <span class="rounded bg-rose-500/15 px-2 py-1 text-[10px] font-black uppercase text-rose-300">Unavailable</span>
                    </div>
                    <div class="mt-12 flex items-center gap-4">
                        <span class="grid h-14 w-14 place-items-center rounded-lg border border-rose-400/30 bg-rose-500/10 text-rose-300"><i data-lucide="cloud-off" class="h-7 w-7"></i></span>
                        <div>
                            <p class="text-lg font-black">Checkout did not respond</p>
                            <p class="mt-1 text-sm text-slate-400">The request ended before an order was submitted.</p>
                        </div>
                    </div>
                    <div class="mt-10 grid gap-px overflow-hidden rounded-lg bg-slate-700 sm:grid-cols-3">
                        <div class="bg-[#111827] p-4"><p class="text-[10px] font-bold uppercase text-slate-500">Cart</p><p class="mt-2 text-sm font-black text-emerald-300">Saved</p></div>
                        <div class="bg-[#111827] p-4"><p class="text-[10px] font-bold uppercase text-slate-500">Order</p><p class="mt-2 text-sm font-black text-slate-300">Not placed</p></div>
                        <div class="bg-[#111827] p-4"><p class="text-[10px] font-bold uppercase text-slate-500">Reference</p><p class="mt-2 font-mono text-sm font-black text-rose-300">ECT-503</p></div>
                    </div>
                    <p class="mt-8 border-t border-slate-800 pt-5 text-xs leading-5 text-slate-500">This screen will return automatically when EcoCart is available.</p>
                </div>
            </section>
        </main>
    </div>

    <script src="assets/scene-client.js?v=<?= htmlspecialchars((string) @filemtime(dirname(__DIR__) . '/assets/scene-client.js')) ?>"></script>
    <script>if (window.lucide) window.lucide.createIcons();</script>
</body>
</html>

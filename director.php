<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/scene.php';

$director = require_director();
auth_no_store();

$sceneState = read_scene_state();
$requestHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
$attackerAvailable = str_contains($requestHost, 'localhost') || str_starts_with($requestHost, '127.0.0.1');
$currentCue = (string) $sceneState['cue'];
$cueOrder = ['standby', 'sale_live', 'traffic_rising', 'checkout_loading', 'outage', 'recovery', 'restored'];
$cueScripts = [
    'standby' => [
        'number' => '00',
        'icon' => 'rotate-ccw',
        'button' => 'Reset take',
        'title' => 'Clear every screen',
        'chapter' => 'Pre-take',
        'location' => 'All filming locations',
        'timing' => 'Press before the cameras roll or whenever the take needs to restart.',
        'call' => 'Screens to standby. Everyone hold for action.',
        'speaker' => 'DIRECTOR',
        'line' => 'Confirm Sarah has items in her cart, then check the admin and attacker screens.',
        'camera' => 'Keep this control screen off camera. Confirm all three screen checks below show ready.',
        'customer' => 'Store ready',
        'admin' => 'Normal traffic',
        'attacker' => 'Standby',
    ],
    'sale_live' => [
        'number' => '01',
        'icon' => 'shopping-bag',
        'button' => 'Start the sale',
        'title' => 'The sale goes live',
        'chapter' => 'Chapter 3',
        'location' => "Sarah's bedroom",
        'timing' => 'Press as the countdown reaches zero and just before Sarah announces the sale.',
        'call' => 'Action. Countdown finishes. Customers begin clicking.',
        'speaker' => 'SARAH',
        'line' => 'The sale is live!',
        'camera' => 'Frame Sarah and the checkout screen. Cut quickly between classmates adding products.',
        'customer' => 'Shopping live',
        'admin' => 'Normal traffic',
        'attacker' => 'Waiting',
    ],
    'traffic_rising' => [
        'number' => '02',
        'icon' => 'activity',
        'button' => 'Raise the traffic',
        'title' => 'The attack begins',
        'chapter' => 'Chapter 3',
        'location' => 'Unknown room / IT office',
        'timing' => 'Press immediately after the attacker enters the request command.',
        'call' => 'Cut to the attacker. Device groups begin slowly, then move faster.',
        'speaker' => 'ATTACKER',
        'line' => "Let's see how their server handles this.",
        'camera' => 'Show the attacker pressing Enter, then cut to the admin graph climbing past normal.',
        'customer' => 'Still shopping',
        'admin' => 'Traffic rising',
        'attacker' => 'Requests active',
    ],
    'checkout_loading' => [
        'number' => '03',
        'icon' => 'loader-circle',
        'button' => 'Hold checkout',
        'title' => 'Sarah gets stuck loading',
        'chapter' => 'Chapter 3',
        'location' => "Sarah's bedroom",
        'timing' => 'Press before Sarah opens or submits checkout. Her next checkout screen will keep loading.',
        'call' => 'Hold on Sarah. Let the loading icon remain on screen.',
        'speaker' => 'SARAH',
        'line' => 'Hala! di lagi?',
        'camera' => 'Get a close shot of the spinner. Let it hold long enough for Sarah to react.',
        'customer' => 'Checkout loading',
        'admin' => 'Checkout degraded',
        'attacker' => 'Pressure active',
    ],
    'outage' => [
        'number' => '04',
        'icon' => 'server-crash',
        'button' => 'Cue server error',
        'title' => 'The website goes down',
        'chapter' => 'Chapter 3',
        'location' => "Sarah's bedroom / customer montage",
        'timing' => 'Press after Sarah says her line. Then tell Sarah and the customer actors to refresh.',
        'call' => 'Cue refresh. All customer screens refresh now.',
        'speaker' => 'CLASSMATE 4',
        'line' => 'Down ang website do!',
        'camera' => 'The refresh must reveal the full-screen SERVER ERROR page. Capture several customer reactions.',
        'customer' => '503 server error',
        'admin' => 'Critical incident',
        'attacker' => 'Target timing out',
    ],
    'recovery' => [
        'number' => '05',
        'icon' => 'shield-check',
        'button' => 'Begin recovery',
        'title' => 'The admins turn the tide',
        'chapter' => 'Chapter 4',
        'location' => 'EcoCart IT office',
        'timing' => 'Press after rate limiting and filtering have been entered on the admin console.',
        'call' => 'Filters are active. Keep customer screens on the error while the team verifies recovery.',
        'speaker' => 'SERVER ADMIN 2',
        'line' => 'Malicious traffic is being filtered and blocked.',
        'camera' => 'Show blocked traffic increasing and the checkout queue falling. Customers still wait.',
        'customer' => 'Still unavailable',
        'admin' => 'Filtering traffic',
        'attacker' => 'Requests rejected',
    ],
    'restored' => [
        'number' => '06',
        'icon' => 'circle-check-big',
        'button' => 'Restore EcoCart',
        'title' => 'Bring the store back',
        'chapter' => 'Chapter 4',
        'location' => "Sarah's bedroom",
        'timing' => 'Press before Sarah performs her final refresh. Wait for the Director status to turn green.',
        'call' => 'Services restored. Sarah refreshes, checks her saved cart, and completes the order.',
        'speaker' => 'SARAH',
        'line' => 'Yes naa na!',
        'camera' => 'Show the homepage loading normally, the cart still populated, and the order confirmation.',
        'customer' => 'Store restored',
        'admin' => 'Incident resolved',
        'attacker' => 'Stopped',
    ],
];
foreach ($cueScripts as $cue => &$script) {
    $script['short'] = scene_cues()[$cue]['short'];
}
unset($script);
?>
<!doctype html>
<html lang="en" data-theme="business">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="EcoCart production cue control.">
    <title>Director Console | EcoCart</title>
    <link href="public/output-public.css?v=<?= htmlspecialchars((string) @filemtime(__DIR__ . '/public/output-public.css')) ?>" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="icon" href="data:,">
</head>
<body class="min-h-screen bg-[#0b0d12] text-slate-100" data-director-console data-scene-endpoint="scene-state.php" data-csrf-token="<?= htmlspecialchars(csrf_token()) ?>" data-initial-state="<?= htmlspecialchars(json_encode(scene_public_payload($sceneState)), ENT_QUOTES, 'UTF-8') ?>">
    <header class="border-b border-white/10 bg-[#11141a]">
        <div class="mx-auto flex min-h-[68px] w-[min(1540px,calc(100%_-_32px))] items-center gap-4">
            <div class="flex items-center gap-3">
                <span class="grid h-9 w-9 place-items-center rounded-lg bg-rose-600 text-white">
                    <i data-lucide="clapperboard" class="h-5 w-5"></i>
                </span>
                <div>
                    <p class="text-sm font-black">OVERLOAD</p>
                    <p class="text-[10px] font-bold uppercase text-slate-500">Director cue console</p>
                </div>
            </div>
            <span class="hidden h-7 w-px bg-white/10 sm:block"></span>
            <div class="hidden items-center gap-2 sm:flex">
                <span class="flex items-center gap-1.5 rounded bg-rose-500/10 px-2 py-1 text-[10px] font-black uppercase text-rose-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500" data-live-dot></span>
                    Live take
                </span>
                <span class="rounded bg-white/5 px-2 py-1 font-mono text-[10px] text-slate-400">Chapter 3-4</span>
            </div>
            <div class="ml-auto flex items-center gap-2">
                <div class="hidden text-right md:block">
                    <p class="font-mono text-xs font-black" data-take-clock>00:00</p>
                    <p class="text-[9px] font-bold uppercase text-slate-600">Current cue time</p>
                </div>
                <a class="btn btn-square btn-sm border-white/10 bg-white/5 text-slate-300 hover:border-cyan-400" href="admin.php" target="_blank" aria-label="Open operations screen" title="Operations screen">
                    <i data-lucide="monitor-dot" class="h-4 w-4"></i>
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

    <main class="mx-auto w-[min(1540px,calc(100%_-_32px))] py-5">
        <section class="mb-4 grid overflow-hidden rounded-lg border border-white/10 bg-[#151820] lg:grid-cols-[1fr_auto]">
            <div class="flex min-w-0 items-center gap-4 p-4 sm:p-5">
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-lg bg-rose-500/10 text-rose-300" data-current-icon>
                    <i data-lucide="<?= htmlspecialchars($cueScripts[$currentCue]['icon'] ?? 'clapperboard') ?>" class="h-6 w-6"></i>
                </span>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-[10px] font-black uppercase text-slate-500">Now controlling</p>
                        <span class="rounded bg-emerald-400/10 px-2 py-0.5 text-[9px] font-black uppercase text-emerald-300" data-save-state>Synced</span>
                    </div>
                    <h1 class="mt-1 truncate text-2xl font-black sm:text-3xl" data-current-title><?= htmlspecialchars($cueScripts[$currentCue]['title']) ?></h1>
                    <p class="mt-1 text-sm text-slate-400" data-current-summary><?= htmlspecialchars(scene_cues()[$currentCue]['short']) ?></p>
                </div>
            </div>
            <div class="flex items-center gap-5 border-t border-white/10 bg-black/20 px-5 py-4 lg:border-l lg:border-t-0">
                <div>
                    <p class="text-[9px] font-black uppercase text-slate-600">Cue</p>
                    <p class="mt-1 font-mono text-xl font-black text-rose-300" data-current-number><?= htmlspecialchars($cueScripts[$currentCue]['number']) ?></p>
                </div>
                <div class="h-9 w-px bg-white/10"></div>
                <div>
                    <p class="text-[9px] font-black uppercase text-slate-600">Safety reset</p>
                    <button class="mt-1 flex items-center gap-2 text-xs font-black text-emerald-300 hover:text-emerald-200" type="button" data-emergency-reset>
                        <i data-lucide="power" class="h-4 w-4"></i> Restore now
                    </button>
                </div>
            </div>
        </section>

        <div class="grid gap-4 xl:grid-cols-[340px_minmax(0,1fr)_320px]">
            <section class="overflow-hidden rounded-lg border border-white/10 bg-[#151820]">
                <div class="border-b border-white/10 px-4 py-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-black">Cue stack</h2>
                        <span class="font-mono text-[10px] text-slate-500" data-cue-progress>1 / 7</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Press only when the matching action happens on camera.</p>
                </div>
                <div class="space-y-1 p-2">
                    <?php foreach ($cueOrder as $index => $cue): ?>
                        <?php $script = $cueScripts[$cue]; ?>
                        <button class="group flex w-full items-center gap-3 rounded-lg border border-transparent px-3 py-3 text-left transition hover:border-white/10 hover:bg-white/5" type="button" data-cue-button="<?= htmlspecialchars($cue) ?>" data-cue-index="<?= $index ?>">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-white/5 font-mono text-xs font-black text-slate-500 group-hover:text-white"><?= htmlspecialchars($script['number']) ?></span>
                            <span class="min-w-0 flex-1">
                                <strong class="block truncate text-sm"><?= htmlspecialchars($script['button']) ?></strong>
                                <span class="mt-0.5 block truncate text-[10px] font-bold uppercase text-slate-600"><?= htmlspecialchars($script['chapter']) ?></span>
                            </span>
                            <i data-lucide="<?= htmlspecialchars($script['icon']) ?>" class="h-4 w-4 shrink-0 text-slate-600"></i>
                        </button>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="min-w-0 overflow-hidden rounded-lg border border-white/10 bg-[#151820]">
                <?php foreach ($cueOrder as $cue): ?>
                    <?php $script = $cueScripts[$cue]; ?>
                    <article class="<?= $cue === $currentCue ? '' : 'hidden' ?>" data-cue-script="<?= htmlspecialchars($cue) ?>">
                        <div class="border-b border-white/10 bg-[linear-gradient(135deg,rgba(244,63,94,0.12),transparent_58%)] p-5 sm:p-6">
                            <div class="flex flex-wrap items-center gap-2 text-[10px] font-black uppercase">
                                <span class="rounded bg-rose-500/15 px-2 py-1 text-rose-300"><?= htmlspecialchars($script['chapter']) ?></span>
                                <span class="text-slate-600"><?= htmlspecialchars($script['location']) ?></span>
                            </div>
                            <h2 class="mt-3 text-2xl font-black sm:text-3xl"><?= htmlspecialchars($script['title']) ?></h2>
                            <div class="mt-5 flex items-start gap-3 rounded-lg border border-amber-400/20 bg-amber-400/5 p-4">
                                <i data-lucide="timer" class="mt-0.5 h-5 w-5 shrink-0 text-amber-300"></i>
                                <div>
                                    <p class="text-[10px] font-black uppercase text-amber-300">When to press</p>
                                    <p class="mt-1 text-sm leading-6 text-slate-200"><?= htmlspecialchars($script['timing']) ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-px bg-white/10 md:grid-cols-2">
                            <div class="bg-[#151820] p-5 sm:p-6">
                                <p class="text-[10px] font-black uppercase text-cyan-300">Director calls</p>
                                <p class="mt-3 text-lg font-bold leading-7 text-white">&ldquo;<?= htmlspecialchars($script['call']) ?>&rdquo;</p>
                            </div>
                            <div class="bg-[#151820] p-5 sm:p-6">
                                <p class="text-[10px] font-black uppercase text-rose-300"><?= htmlspecialchars($script['speaker']) ?></p>
                                <p class="mt-3 text-lg font-bold leading-7 text-white">&ldquo;<?= htmlspecialchars($script['line']) ?>&rdquo;</p>
                            </div>
                        </div>

                        <div class="border-t border-white/10 p-5 sm:p-6">
                            <div class="flex items-start gap-3">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-violet-500/10 text-violet-300"><i data-lucide="camera" class="h-5 w-5"></i></span>
                                <div>
                                    <p class="text-[10px] font-black uppercase text-violet-300">Camera direction</p>
                                    <p class="mt-1 text-sm leading-6 text-slate-300"><?= htmlspecialchars($script['camera']) ?></p>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>

            <aside class="space-y-4">
                <section class="overflow-hidden rounded-lg border border-white/10 bg-[#151820]">
                    <div class="border-b border-white/10 px-4 py-3">
                        <h2 class="text-sm font-black">Screen check</h2>
                        <p class="mt-1 text-xs text-slate-500">Expected result after the current cue.</p>
                    </div>
                    <div class="divide-y divide-white/10">
                        <div class="flex items-center gap-3 p-4">
                            <span class="grid h-9 w-9 place-items-center rounded-lg bg-cyan-500/10 text-cyan-300"><i data-lucide="shopping-cart" class="h-4 w-4"></i></span>
                            <div class="min-w-0 flex-1"><p class="text-xs font-black">Customer</p><p class="mt-0.5 truncate text-[10px] text-slate-500" data-screen-customer><?= htmlspecialchars($cueScripts[$currentCue]['customer']) ?></p></div>
                            <span class="h-2 w-2 rounded-full bg-cyan-400"></span>
                        </div>
                        <div class="flex items-center gap-3 p-4">
                            <span class="grid h-9 w-9 place-items-center rounded-lg bg-emerald-500/10 text-emerald-300"><i data-lucide="monitor-dot" class="h-4 w-4"></i></span>
                            <div class="min-w-0 flex-1"><p class="text-xs font-black">Server admins</p><p class="mt-0.5 truncate text-[10px] text-slate-500" data-screen-admin><?= htmlspecialchars($cueScripts[$currentCue]['admin']) ?></p></div>
                            <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                        </div>
                        <div class="flex items-center gap-3 p-4">
                            <span class="grid h-9 w-9 place-items-center rounded-lg bg-rose-500/10 text-rose-300"><i data-lucide="terminal" class="h-4 w-4"></i></span>
                            <div class="min-w-0 flex-1"><p class="text-xs font-black">Attacker</p><p class="mt-0.5 truncate text-[10px] text-slate-500" data-screen-attacker><?= htmlspecialchars($cueScripts[$currentCue]['attacker']) ?></p></div>
                            <span class="h-2 w-2 rounded-full bg-rose-400"></span>
                        </div>
                    </div>
                </section>

                <section class="rounded-lg border border-white/10 bg-[#151820] p-4">
                    <h2 class="text-sm font-black">Open filming screens</h2>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <a class="btn btn-sm justify-start border-white/10 bg-white/5 text-xs text-slate-300 hover:border-cyan-400" href="index.php" target="_blank"><i data-lucide="store" class="h-4 w-4"></i> Store</a>
                        <a class="btn btn-sm justify-start border-white/10 bg-white/5 text-xs text-slate-300 hover:border-cyan-400" href="checkout.php" target="_blank"><i data-lucide="credit-card" class="h-4 w-4"></i> Checkout</a>
                        <a class="btn btn-sm justify-start border-white/10 bg-white/5 text-xs text-slate-300 hover:border-emerald-400" href="admin.php" target="_blank"><i data-lucide="activity" class="h-4 w-4"></i> Admin</a>
                        <?php if ($attackerAvailable): ?>
                            <a class="btn btn-sm justify-start border-white/10 bg-white/5 text-xs text-slate-300 hover:border-rose-400" href="attacker-terminal.php" target="_blank"><i data-lucide="terminal" class="h-4 w-4"></i> Attacker</a>
                        <?php else: ?>
                            <span class="flex min-h-8 items-center gap-2 rounded-lg border border-white/10 bg-white/[0.03] px-3 text-xs text-slate-500"><i data-lucide="laptop" class="h-4 w-4"></i> Local actor app</span>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="rounded-lg border border-emerald-400/25 bg-[#10211b] p-4">
                    <div class="flex items-start gap-3">
                        <i data-lucide="shield-check" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-300"></i>
                        <div>
                            <p class="text-xs font-black text-emerald-300">Hosting remains online</p>
                            <p class="mt-1 text-[11px] leading-5 text-slate-400">Cues only change EcoCart&apos;s filming screens. No traffic is generated and GoogieHost is never stopped.</p>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </main>

    <div class="pointer-events-none fixed inset-x-0 bottom-4 z-50 flex justify-center px-4">
        <div class="hidden max-w-md rounded-lg border border-rose-400/30 bg-[#211117] px-4 py-3 text-sm font-bold text-rose-200 shadow-2xl" role="alert" data-director-error></div>
    </div>

    <script>
        window.ECOCART_DIRECTOR_CUES = <?= json_encode($cueScripts, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="assets/director.js?v=<?= htmlspecialchars((string) @filemtime(__DIR__ . '/assets/director.js')) ?>"></script>
</body>
</html>

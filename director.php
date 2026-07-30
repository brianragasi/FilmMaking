<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/scene.php';

$director = require_director();
auth_no_store();

$sceneState = read_scene_state();
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

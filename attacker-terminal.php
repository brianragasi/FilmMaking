<?php
$requestHost = $_SERVER['HTTP_HOST'] ?? 'ecocart.local';
$targetHost = preg_replace('/[^a-zA-Z0-9.\-:\[\]]/', '', $requestHost);
if ($targetHost === '' || $targetHost === null) {
    $targetHost = 'ecocart.local';
}
?>
<!doctype html>
<html lang="en" data-theme="business">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="EcoCart traffic control console.">
    <meta name="theme-color" content="#03070c">
    <title>EcoCart Traffic Control</title>
    <link href="public/output.css?v=<?= htmlspecialchars((string) @filemtime(__DIR__ . '/public/output.css')) ?>" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="assets/traffic-control-icon.svg">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="attacker-body min-h-screen overflow-x-hidden text-slate-100" data-attacker-target="<?= htmlspecialchars($targetHost, ENT_QUOTES, 'UTF-8') ?>">
    <header class="border-b border-slate-800 bg-[#05090f]">
        <div class="flex min-h-12 items-center gap-3 px-4">
            <div class="flex gap-1.5" aria-hidden="true">
                <span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span>
                <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
            </div>
            <span class="h-5 w-px bg-slate-800"></span>
            <div class="flex items-center gap-2">
                <i data-lucide="square-terminal" class="h-4 w-4 text-rose-400"></i>
                <span class="font-mono text-xs font-black text-slate-300">EcoCart Traffic Control</span>
            </div>
            <span class="ml-auto hidden font-mono text-[9px] uppercase text-slate-600 sm:inline">session R-0428</span>
            <button class="btn btn-square btn-xs border-slate-700 bg-slate-900 text-slate-400 hover:border-cyan-400 hover:text-cyan-300" type="button" data-terminal-fullscreen aria-label="Open full screen" title="Full screen">
                <i data-lucide="maximize" class="h-3.5 w-3.5"></i>
            </button>
            <button class="btn btn-square btn-xs border-slate-700 bg-slate-900 text-slate-400 hover:border-rose-500 hover:text-rose-300" type="button" data-attacker-reset aria-label="Reset terminal" title="Reset terminal">
                <i data-lucide="rotate-ccw" class="h-3.5 w-3.5"></i>
            </button>
        </div>
    </header>

    <main class="grid min-h-[calc(100vh-48px)] lg:grid-cols-[minmax(0,1fr)_280px]">
        <section class="flex min-h-[620px] flex-col border-r border-slate-800 bg-[#03070c] p-4">
            <div class="mb-3 flex items-center justify-between gap-3">
                <div>
                <p class="font-mono text-[10px] uppercase text-slate-600">Command terminal</p>
                <p class="mt-1 font-mono text-xs font-black text-slate-300">Traffic controller / ready for input</p>
                </div>
                <span class="rounded bg-emerald-400/10 px-2 py-1 font-mono text-[9px] font-black text-emerald-400" data-attacker-status>STANDBY</span>
            </div>

            <div class="attacker-terminal flex-1" data-attacker-terminal aria-live="polite"></div>

            <form class="mt-3 flex items-center gap-2 rounded-lg border border-slate-800 bg-black px-3" data-attacker-command-form>
                <span class="hidden font-mono text-xs text-rose-400 sm:inline">operator@control:~$</span>
                <input class="h-11 min-w-0 flex-1 bg-transparent font-mono text-xs text-slate-200 outline-none" data-attacker-command-input autocomplete="off" spellcheck="false" placeholder="ready" autofocus>
                <span class="font-mono text-[9px] uppercase text-slate-700">Enter</span>
            </form>
        </section>

        <aside class="bg-[#07101a] p-4">
            <div class="mb-4 border-l-2 border-rose-500 bg-rose-500/5 px-3 py-3">
                <p class="font-mono text-[9px] uppercase text-slate-600">Selected target</p>
                <p class="mt-2 break-all font-mono text-xs font-black text-rose-300">EcoCart Ecommerce</p>
                <p class="mt-1 break-all font-mono text-[9px] text-slate-500" data-attacker-target-label><?= htmlspecialchars($targetHost, ENT_QUOTES, 'UTF-8') ?></p>
                <div class="mt-3 flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-slate-600" data-attacker-target-dot></span>
                    <span class="font-mono text-[9px] font-black uppercase text-slate-500" data-attacker-target-state>Not selected</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2 lg:grid-cols-1">
                <div class="rounded-lg border border-slate-800 bg-black/40 p-3">
                    <p class="font-mono text-[9px] uppercase text-slate-600">Connected devices</p>
                    <p class="mt-2 font-mono text-xl font-black text-cyan-300" data-attacker-devices>0</p>
                </div>
                <div class="rounded-lg border border-slate-800 bg-black/40 p-3">
                    <p class="font-mono text-[9px] uppercase text-slate-600">Repeated requests</p>
                    <p class="mt-2 font-mono text-xl font-black text-rose-400" data-attacker-rate>0 / min</p>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-between">
                <p class="font-mono text-[10px] font-black uppercase text-slate-400">Device groups</p>
                <span class="h-2.5 w-2.5 rounded-full bg-slate-700" data-attacker-live-dot></span>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-2">
                <div class="attacker-node" data-attacker-node>
                    <div class="flex items-center justify-between"><i data-lucide="mouse-pointer-click" class="h-4 w-4"></i><span class="font-mono text-[9px]" data-attacker-node-count>0</span></div>
                    <p class="mt-5 font-mono text-[10px] font-black">REQUEST</p><p class="mt-1 font-mono text-[8px] uppercase text-slate-600" data-attacker-node-state>Offline</p>
                </div>
                <div class="attacker-node" data-attacker-node>
                    <div class="flex items-center justify-between"><i data-lucide="refresh-cw" class="h-4 w-4"></i><span class="font-mono text-[9px]" data-attacker-node-count>0</span></div>
                    <p class="mt-5 font-mono text-[10px] font-black">REFRESH</p><p class="mt-1 font-mono text-[8px] uppercase text-slate-600" data-attacker-node-state>Offline</p>
                </div>
                <div class="attacker-node" data-attacker-node>
                    <div class="flex items-center justify-between"><i data-lucide="plug-zap" class="h-4 w-4"></i><span class="font-mono text-[9px]" data-attacker-node-count>0</span></div>
                    <p class="mt-5 font-mono text-[10px] font-black">CONNECT</p><p class="mt-1 font-mono text-[8px] uppercase text-slate-600" data-attacker-node-state>Offline</p>
                </div>
                <div class="attacker-node" data-attacker-node>
                    <div class="flex items-center justify-between"><i data-lucide="panel-top-open" class="h-4 w-4"></i><span class="font-mono text-[9px]" data-attacker-node-count>0</span></div>
                    <p class="mt-5 font-mono text-[10px] font-black">LOAD PAGE</p><p class="mt-1 font-mono text-[8px] uppercase text-slate-600" data-attacker-node-state>Offline</p>
                </div>
            </div>

            <div class="mt-4 rounded-lg border border-slate-800 bg-black/40 p-3">
                <div class="flex items-center justify-between">
                    <p class="font-mono text-[9px] uppercase text-slate-600">Request pressure</p>
                    <span class="font-mono text-[9px] text-slate-500" data-attacker-pressure>0%</span>
                </div>
                <div class="attacker-bars mt-3" data-attacker-bars>
                    <span class="attacker-bar"></span><span class="attacker-bar"></span><span class="attacker-bar"></span><span class="attacker-bar"></span>
                    <span class="attacker-bar"></span><span class="attacker-bar"></span><span class="attacker-bar"></span><span class="attacker-bar"></span>
                    <span class="attacker-bar"></span><span class="attacker-bar"></span><span class="attacker-bar"></span><span class="attacker-bar"></span>
                    <span class="attacker-bar"></span><span class="attacker-bar"></span><span class="attacker-bar"></span><span class="attacker-bar"></span>
                    <span class="attacker-bar"></span><span class="attacker-bar"></span><span class="attacker-bar"></span><span class="attacker-bar"></span>
                    <span class="attacker-bar"></span><span class="attacker-bar"></span><span class="attacker-bar"></span><span class="attacker-bar"></span>
                    <span class="attacker-bar"></span><span class="attacker-bar"></span><span class="attacker-bar"></span><span class="attacker-bar"></span>
                </div>
            </div>

            <div class="mt-4 space-y-2 rounded-lg border border-slate-800 bg-black/40 p-3 font-mono text-[9px]">
                <div class="flex justify-between"><span class="text-slate-600">Sent</span><strong class="text-slate-300" data-attacker-sent>0</strong></div>
                <div class="flex justify-between"><span class="text-slate-600">Accepted</span><strong class="text-amber-300" data-attacker-accepted>0</strong></div>
                <div class="flex justify-between"><span class="text-slate-600">Rejected</span><strong class="text-rose-400" data-attacker-rejected>0</strong></div>
            </div>
        </aside>
    </main>

    <script src="assets/app.js?v=<?= htmlspecialchars((string) @filemtime(__DIR__ . '/assets/app.js')) ?>"></script>
</body>
</html>

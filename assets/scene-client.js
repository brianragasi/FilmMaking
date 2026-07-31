(() => {
  const root = document.querySelector('[data-scene-client]');
  if (!root) {
    return;
  }

  const endpoint = root.dataset.sceneEndpoint || 'scene-state.php';
  const view = root.dataset.sceneView || 'screen';
  const customerView = ['storefront', 'checkout', 'outage'].includes(view);
  let state = {
    cue: root.dataset.sceneCue || 'restored',
    revision: Number(root.dataset.sceneRevision || 0),
    updated_at: root.dataset.sceneUpdated || '',
  };
  let outageTakeoverActive = view === 'outage';

  function renderOutageTakeover() {
    if (outageTakeoverActive) {
      return;
    }

    outageTakeoverActive = true;
    document.title = 'Server Error | EcoCart';
    document.body.className = 'min-h-screen bg-[#f4f5f7] text-slate-950';
    document.body.innerHTML = `
      <div class="flex min-h-screen flex-col" data-live-outage>
        <header class="border-b border-slate-200 bg-white">
          <div class="mx-auto flex min-h-[72px] w-[min(1180px,calc(100%_-_32px))] items-center justify-between">
            <span class="flex items-center gap-2">
              <span class="grid h-10 w-10 place-items-center rounded-lg bg-slate-950 text-white"><i data-lucide="leaf" class="h-5 w-5"></i></span>
              <span class="text-2xl font-black">Eco<span class="text-rose-600">Cart.</span></span>
            </span>
            <span class="flex items-center gap-2 text-xs font-bold text-slate-500"><span class="h-2 w-2 rounded-full bg-rose-600"></span>Service unavailable</span>
          </div>
        </header>
        <main class="mx-auto grid w-[min(1180px,calc(100%_-_32px))] flex-1 place-items-center py-10">
          <section class="w-full max-w-4xl overflow-hidden rounded-lg border border-slate-200 bg-white shadow-2xl">
            <div class="h-2 bg-rose-600"></div>
            <div class="grid gap-8 p-7 sm:p-10 lg:grid-cols-[1fr_280px] lg:items-center">
              <div>
                <div class="flex items-center gap-3 text-rose-600">
                  <span class="grid h-12 w-12 place-items-center rounded-lg bg-rose-600 text-white"><i data-lucide="server-crash" class="h-6 w-6"></i></span>
                  <span class="font-mono text-sm font-black">ERROR 503</span>
                </div>
                <p class="mt-8 text-xs font-black uppercase text-rose-600">Server error</p>
                <h1 class="mt-2 text-4xl font-black leading-tight sm:text-6xl">PLEASE TRY AGAIN.</h1>
                <p class="mt-5 max-w-xl text-base leading-7 text-slate-600">EcoCart could not respond to your request. Your cart is still saved.</p>
              </div>
              <div class="rounded-lg bg-slate-950 p-6 text-white">
                <p class="text-[10px] font-black uppercase text-slate-500">Request status</p>
                <p class="mt-5 text-xl font-black">Website unavailable</p>
                <p class="mt-2 text-sm leading-6 text-slate-400">Waiting for EcoCart to return.</p>
                <div class="mt-6 flex items-center gap-3 border-t border-slate-800 pt-5 text-sm font-bold text-rose-300">
                  <span class="loading loading-dots loading-sm"></span> Reconnecting
                </div>
              </div>
            </div>
          </section>
        </main>
      </div>`;

    if (window.lucide) {
      window.lucide.createIcons();
    }
  }

  function showSaleTakeover(revision) {
    const takeover = document.querySelector('[data-sale-takeover]');
    if (!takeover) {
      return;
    }

    const storageKey = `ecocart_sale_takeover_${revision}`;
    if (sessionStorage.getItem(storageKey)) {
      return;
    }
    sessionStorage.setItem(storageKey, 'shown');
    takeover.classList.remove('hidden');
    requestAnimationFrame(() => takeover.classList.add('is-visible'));
    window.setTimeout(() => {
      takeover.classList.remove('is-visible');
      window.setTimeout(() => takeover.classList.add('hidden'), 450);
    }, 2800);
  }

  function applyState(nextState) {
    const previousCue = state.cue;
    state = { ...state, ...nextState };
    root.dataset.sceneCue = state.cue;
    root.dataset.sceneRevision = String(state.revision || 0);
    localStorage.setItem('ecocart_system_state', state.cue === 'outage' ? 'attack' : 'healthy');

    if (customerView) {
      if (view !== 'outage' && state.cue === 'outage') {
        renderOutageTakeover();
        return;
      }
      if (outageTakeoverActive && state.cue !== 'outage') {
        window.location.reload();
        return;
      }
    }

    const ribbonText = document.querySelector('[data-sale-ribbon-text]');
    if (ribbonText) {
      ribbonText.textContent = state.cue === 'sale_live'
        ? 'SALE IS LIVE NOW - UP TO 70% OFF'
        : 'Big Blowout Sale: up to 70% off selected essentials';
    }

    if (state.cue === 'sale_live' && (previousCue !== 'sale_live' || Number(nextState.revision) > 0)) {
      showSaleTakeover(state.revision);
    }

    window.dispatchEvent(new CustomEvent('ecocart:scenechange', {
      detail: state,
    }));
  }

  async function pollState() {
    try {
      const response = await fetch(`${endpoint}?v=${Date.now()}`, {
        credentials: 'same-origin',
        cache: 'no-store',
      });
      if (!response.ok) {
        return;
      }
      const nextState = await response.json();
      if (Number(nextState.revision) !== Number(state.revision)) {
        applyState(nextState);
      }
    } catch {
      // Keep the last confirmed filming state during a brief connection loss.
    }
  }

  document.addEventListener('DOMContentLoaded', () => applyState(state));
  if (root.dataset.scenePoll !== 'false') {
    const pollDelay = customerView ? 2500 : 4000;
    window.setInterval(() => {
      if (!document.hidden) {
        pollState();
      }
    }, pollDelay);
  }
})();

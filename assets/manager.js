(() => {
  const root = document.querySelector('[data-manager-console]');
  if (!root) return;

  const endpoint = root.dataset.sceneEndpoint || 'scene-state.php';
  const visitors = document.querySelector('[data-manager-visitors]');
  const carts = document.querySelector('[data-manager-carts]');
  const status = document.querySelector('[data-manager-status]');
  const headline = document.querySelector('[data-manager-headline]');
  const summary = document.querySelector('[data-manager-summary]');
  const countdown = document.querySelector('[data-manager-countdown]');
  const countdownLabel = document.querySelector('[data-manager-countdown-label]');
  const readiness = document.querySelector('[data-manager-readiness]');
  const progress = document.querySelector('[data-manager-progress]');
  const clock = document.querySelector('[data-manager-clock]');
  const visitorChange = document.querySelector('[data-manager-visitor-change]');
  const bars = [...document.querySelectorAll('[data-manager-chart-bar]')];
  let cue = root.dataset.sceneCue || 'restored';
  let revision = Number(root.dataset.sceneRevision || 0);
  let visitorCount = cue === 'sale_live' ? 7840 : 2340;
  let cartCount = cue === 'sale_live' ? 3280 : 1286;
  let secondsToLaunch = 299;

  function applyCue(nextCue) {
    cue = nextCue;
    root.dataset.sceneCue = cue;
    status.className = 'rounded px-3 py-1 text-[10px] font-black uppercase';
    if (cue === 'sale_live') {
      status.textContent = 'Sale live';
      status.classList.add('bg-rose-500', 'text-white');
      headline.textContent = 'The sale is open.';
      summary.textContent = 'Watch orders and stock as shoppers move through checkout.';
      countdownLabel.textContent = 'Current sale status';
      countdown.textContent = 'LIVE';
      readiness.textContent = 'Orders open';
      visitorChange.textContent = 'Shoppers entering the sale';
      progress.style.width = '100%';
      visitorCount = Math.max(visitorCount, 7840);
      cartCount = Math.max(cartCount, 3280);
    } else if (cue === 'outage') {
      status.textContent = 'Storefront unavailable';
      status.classList.add('bg-amber-400', 'text-slate-950');
      headline.textContent = 'Customer ordering is paused.';
      summary.textContent = 'Hold sale-floor announcements until the website returns.';
      countdownLabel.textContent = 'Storefront status';
      countdown.textContent = 'HOLD';
      readiness.textContent = 'Orders paused';
      visitorChange.textContent = 'Last confirmed audience';
      progress.style.width = '100%';
    } else {
      status.textContent = 'Ready for launch';
      status.classList.add('bg-emerald-400', 'text-slate-950');
      headline.textContent = 'Customers are lining up.';
      summary.textContent = 'Visitor interest is rising. Pricing, stock, and checkout are prepared for launch.';
      countdownLabel.textContent = 'Sale launch window';
      readiness.textContent = 'All checks passed';
      visitorChange.textContent = 'Rising before launch';
    }
  }

  function tickMetrics() {
    if (cue !== 'outage') {
      const visitorStep = cue === 'sale_live' ? 87 : 31;
      const cartStep = cue === 'sale_live' ? 21 : 9;
      visitorCount += visitorStep + Math.floor(Math.random() * visitorStep);
      cartCount += cartStep + Math.floor(Math.random() * cartStep);
    }
    visitors.textContent = visitorCount.toLocaleString('en-PH');
    carts.textContent = cartCount.toLocaleString('en-PH');
    bars.forEach((bar, index) => {
      const base = 25 + index * 3.6;
      const lift = cue === 'sale_live' ? 15 : 0;
      bar.style.height = `${Math.min(96, base + lift + Math.random() * 14)}%`;
      bar.classList.toggle('bg-rose-400/80', cue === 'sale_live');
      bar.classList.toggle('bg-cyan-400/70', cue !== 'sale_live');
    });
  }

  function tickClock() {
    const now = new Date();
    if (clock) {
      clock.textContent = new Intl.DateTimeFormat('en-PH', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false, timeZone: 'Asia/Manila' }).format(now);
    }
    if (cue === 'restored') {
      secondsToLaunch = Math.max(0, secondsToLaunch - 1);
      const minutes = String(Math.floor(secondsToLaunch / 60)).padStart(2, '0');
      const seconds = String(secondsToLaunch % 60).padStart(2, '0');
      countdown.textContent = `${minutes}:${seconds}`;
      progress.style.width = `${Math.min(100, 72 + ((299 - secondsToLaunch) / 299) * 28)}%`;
    }
  }

  async function pollScene() {
    try {
      const response = await fetch(`${endpoint}?v=${Date.now()}`, { cache: 'no-store', credentials: 'same-origin' });
      if (!response.ok) return;
      const state = await response.json();
      if (Number(state.revision) !== revision) {
        revision = Number(state.revision);
        applyCue(state.cue || 'restored');
      }
    } catch {
      // Keep the last confirmed business view during a brief connection loss.
    }
  }

  applyCue(cue);
  tickMetrics();
  tickClock();
  window.setInterval(tickMetrics, 1800);
  window.setInterval(tickClock, 1000);
  window.setInterval(pollScene, 4000);
})();

(() => {
  const root = document.querySelector('[data-director-console]');
  if (!root) {
    return;
  }

  const cueScripts = window.ECOCART_DIRECTOR_CUES || {};
  const endpoint = root.dataset.sceneEndpoint || 'scene-state.php';
  const csrfToken = root.dataset.csrfToken || '';
  const cueOrder = Object.keys(cueScripts);
  const cueButtons = [...document.querySelectorAll('[data-cue-button]')];
  const scriptPanels = [...document.querySelectorAll('[data-cue-script]')];
  const errorNode = document.querySelector('[data-director-error]');
  const saveState = document.querySelector('[data-save-state]');
  const takeClock = document.querySelector('[data-take-clock]');
  let currentState = JSON.parse(root.dataset.initialState || '{"cue":"standby","revision":0}');
  let cueChangedAt = Date.parse(currentState.updated_at || new Date().toISOString());
  let requestPending = false;

  const nodes = {
    title: document.querySelector('[data-current-title]'),
    summary: document.querySelector('[data-current-summary]'),
    number: document.querySelector('[data-current-number]'),
    progress: document.querySelector('[data-cue-progress]'),
    customer: document.querySelector('[data-screen-customer]'),
    admin: document.querySelector('[data-screen-admin]'),
    attacker: document.querySelector('[data-screen-attacker]'),
    icon: document.querySelector('[data-current-icon]'),
  };

  function showError(message) {
    if (!errorNode) {
      return;
    }
    errorNode.textContent = message;
    errorNode.classList.remove('hidden');
    window.setTimeout(() => errorNode.classList.add('hidden'), 4500);
  }

  function setSaveState(label, tone) {
    if (!saveState) {
      return;
    }
    saveState.textContent = label;
    saveState.className = `rounded px-2 py-0.5 text-[9px] font-black uppercase ${
      tone === 'saving'
        ? 'bg-amber-400/10 text-amber-300'
        : tone === 'error'
          ? 'bg-rose-400/10 text-rose-300'
          : 'bg-emerald-400/10 text-emerald-300'
    }`;
  }

  function applyState(state) {
    const cue = cueScripts[state.cue] ? state.cue : 'standby';
    const script = cueScripts[cue];
    const index = Math.max(0, cueOrder.indexOf(cue));
    currentState = { ...state, cue };
    cueChangedAt = Date.parse(state.updated_at || new Date().toISOString());

    nodes.title.textContent = script.title;
    nodes.summary.textContent = script.short || script.timing;
    nodes.number.textContent = script.number;
    nodes.progress.textContent = `${index + 1} / ${cueOrder.length}`;
    nodes.customer.textContent = script.customer;
    nodes.admin.textContent = script.admin;
    nodes.attacker.textContent = script.attacker;
    nodes.icon.innerHTML = `<i data-lucide="${script.icon}" class="h-6 w-6"></i>`;

    cueButtons.forEach((button) => {
      const active = button.dataset.cueButton === cue;
      button.classList.toggle('border-rose-400/40', active);
      button.classList.toggle('bg-rose-500/10', active);
      button.classList.toggle('text-white', active);
      button.setAttribute('aria-current', active ? 'step' : 'false');
    });

    scriptPanels.forEach((panel) => {
      panel.classList.toggle('hidden', panel.dataset.cueScript !== cue);
    });

    setSaveState('Synced', 'ok');
    if (window.lucide) {
      window.lucide.createIcons();
    }
  }

  async function setCue(cue) {
    if (requestPending || !cueScripts[cue]) {
      return;
    }

    requestPending = true;
    setSaveState('Sending cue', 'saving');
    cueButtons.forEach((button) => {
      button.disabled = true;
    });

    try {
      const body = new URLSearchParams({ cue, csrf_token: csrfToken });
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
        body,
        credentials: 'same-origin',
        cache: 'no-store',
      });
      const payload = await response.json();
      if (!response.ok || !payload.ok) {
        throw new Error(payload.message || 'The cue could not be sent.');
      }
      applyState(payload.state);
    } catch (error) {
      setSaveState('Not synced', 'error');
      showError(error instanceof Error ? error.message : 'The cue could not be sent.');
    } finally {
      requestPending = false;
      cueButtons.forEach((button) => {
        button.disabled = false;
      });
    }
  }

  async function pollState() {
    if (requestPending) {
      return;
    }
    try {
      const response = await fetch(`${endpoint}?v=${Date.now()}`, {
        credentials: 'same-origin',
        cache: 'no-store',
      });
      if (!response.ok) {
        return;
      }
      const state = await response.json();
      if (Number(state.revision) !== Number(currentState.revision)) {
        applyState(state);
      }
    } catch {
      // The last confirmed cue remains visible during brief hosting interruptions.
    }
  }

  cueButtons.forEach((button) => {
    button.addEventListener('click', () => setCue(button.dataset.cueButton));
  });

  document.querySelector('[data-emergency-reset]')?.addEventListener('click', () => setCue('restored'));

  window.setInterval(() => {
    const elapsed = Math.max(0, Math.floor((Date.now() - cueChangedAt) / 1000));
    const minutes = String(Math.floor(elapsed / 60)).padStart(2, '0');
    const seconds = String(elapsed % 60).padStart(2, '0');
    takeClock.textContent = `${minutes}:${seconds}`;
  }, 1000);

  applyState(currentState);
  window.setInterval(pollState, 4000);
})();

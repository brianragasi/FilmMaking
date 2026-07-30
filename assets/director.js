(() => {
  const root = document.querySelector('[data-director-console]');
  if (!root) {
    return;
  }

  const cueScripts = window.ECOCART_DIRECTOR_CUES || {};
  const endpoint = root.dataset.sceneEndpoint || 'scene-state.php';
  const csrfToken = root.dataset.csrfToken || '';
  const cueButtons = [...document.querySelectorAll('[data-cue-button]')];
  const errorNode = document.querySelector('[data-director-error]');
  const saveState = document.querySelector('[data-save-state]');
  let currentState = JSON.parse(root.dataset.initialState || '{"cue":"restored","revision":0}');
  let requestPending = false;

  const nodes = {
    title: document.querySelector('[data-current-title]'),
    summary: document.querySelector('[data-current-summary]'),
    number: document.querySelector('[data-current-number]'),
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
    const cue = cueScripts[state.cue] ? state.cue : 'restored';
    const script = cueScripts[cue];
    currentState = { ...state, cue };

    nodes.title.textContent = script.title;
    nodes.summary.textContent = script.result;
    nodes.number.textContent = script.number;
    nodes.icon.innerHTML = `<i data-lucide="${script.icon}" class="h-6 w-6"></i>`;

    cueButtons.forEach((button) => {
      const active = button.dataset.cueButton === cue;
      button.classList.toggle('ring-2', active);
      button.classList.toggle('ring-white/70', active);
      button.classList.toggle('ring-offset-2', active);
      button.classList.toggle('ring-offset-[#151820]', active);
      button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });

    setSaveState('Synced', 'ok');
    if (window.lucide) {
      window.lucide.createIcons();
    }
  }

  async function setCue(cue) {
    if (requestPending || !cueScripts[cue] || cue === currentState.cue) {
      return;
    }

    requestPending = true;
    setSaveState('Sending', 'saving');
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
        throw new Error(payload.message || 'The website control could not be sent.');
      }
      applyState(payload.state);
    } catch (error) {
      setSaveState('Not synced', 'error');
      showError(error instanceof Error ? error.message : 'The website control could not be sent.');
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
      // Keep the last confirmed website state during a brief connection loss.
    }
  }

  cueButtons.forEach((button) => {
    button.addEventListener('click', () => setCue(button.dataset.cueButton));
  });

  applyState(currentState);
  window.setInterval(pollState, 5000);
})();

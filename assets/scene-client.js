(() => {
  const root = document.querySelector('[data-scene-client]');
  if (!root) {
    return;
  }

  const endpoint = root.dataset.sceneEndpoint || 'scene-state.php';
  let state = {
    cue: root.dataset.sceneCue || 'standby',
    revision: Number(root.dataset.sceneRevision || 0),
    updated_at: root.dataset.sceneUpdated || '',
  };

  function systemStateForCue(cue) {
    if (cue === 'recovery') {
      return 'filtering';
    }
    if (['traffic_rising', 'checkout_loading', 'outage'].includes(cue)) {
      return 'attack';
    }
    return 'healthy';
  }

  function applyState(nextState) {
    state = { ...state, ...nextState };
    root.dataset.sceneCue = state.cue;
    root.dataset.sceneRevision = String(state.revision || 0);
    localStorage.setItem('ecocart_system_state', systemStateForCue(state.cue));

    const loadingOverlay = document.querySelector('[data-scene-loading]');
    const holdCheckout = ['checkout_loading', 'outage'].includes(state.cue);
    loadingOverlay?.classList.toggle('hidden', !holdCheckout);

    const restoredNotice = document.querySelector('[data-scene-restored]');
    restoredNotice?.classList.toggle('hidden', state.cue !== 'restored');

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
      applyState(nextState);
    } catch {
      // A filming screen keeps its last confirmed cue during a brief connection loss.
    }
  }

  document.addEventListener('submit', (event) => {
    if (
      event.target.matches('[data-checkout-form]')
      && ['checkout_loading', 'outage', 'recovery'].includes(state.cue)
    ) {
      event.preventDefault();
      document.querySelector('[data-scene-loading]')?.classList.remove('hidden');
    }
  }, true);

  document.addEventListener('DOMContentLoaded', () => applyState(state));
  if (root.dataset.scenePoll !== 'false') {
    window.setInterval(pollState, 3500);
  }
})();

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
        window.location.reload();
        return;
      }
      if (view === 'outage' && state.cue !== 'outage') {
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
    window.setInterval(pollState, 4000);
  }
})();

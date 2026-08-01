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

(() => {
  const items = [...document.querySelectorAll('[data-moderation-item]')];
  if (items.length === 0) {
    return;
  }

  const searchInput = document.querySelector('[data-moderation-search]');
  const ratingSelect = document.querySelector('[data-moderation-rating]');
  const productSelect = document.querySelector('[data-moderation-product]');
  const clearButton = document.querySelector('[data-moderation-clear]');
  const selectVisibleButton = document.querySelector('[data-moderation-select-visible]');
  const visibleCount = document.querySelector('[data-moderation-visible-count]');
  const selectedCount = document.querySelector('[data-moderation-selected-count]');
  const emptyState = document.querySelector('[data-moderation-empty]');
  const bulkForm = document.querySelector('[data-moderation-bulk-form]');
  const bulkButton = document.querySelector('[data-moderation-bulk-delete]');
  const checkboxes = items
    .map((item) => item.querySelector('[data-moderation-checkbox]'))
    .filter(Boolean);

  function visibleItems() {
    return items.filter((item) => !item.hidden);
  }

  function selectedCheckboxes() {
    return checkboxes.filter((checkbox) => checkbox.checked);
  }

  function updateSelectionState() {
    const visible = visibleItems();
    const selected = selectedCheckboxes();
    const selectedVisible = visible.filter((item) => {
      const checkbox = item.querySelector('[data-moderation-checkbox]');
      return checkbox && checkbox.checked;
    });

    if (selectedCount) {
      selectedCount.textContent = String(selected.length);
    }
    if (bulkButton) {
      bulkButton.disabled = selected.length === 0;
    }
    if (selectVisibleButton) {
      const allVisibleSelected = visible.length > 0 && selectedVisible.length === visible.length;
      selectVisibleButton.textContent = allVisibleSelected ? 'Clear visible' : 'Select visible';
      selectVisibleButton.disabled = visible.length === 0;
    }
  }

  function ratingMatches(itemRating, filter) {
    if (filter === 'low') {
      return itemRating <= 2;
    }
    if (filter === '3') {
      return itemRating === 3;
    }
    if (filter === 'high') {
      return itemRating >= 4;
    }
    return true;
  }

  function applyFilters() {
    const query = (searchInput?.value || '').trim().toLowerCase();
    const rating = ratingSelect?.value || 'all';
    const product = productSelect?.value || 'all';
    let count = 0;

    items.forEach((item) => {
      const matchesSearch = query === '' || (item.dataset.moderationSearchText || '').includes(query);
      const matchesRating = ratingMatches(Number(item.dataset.moderationRatingValue || 0), rating);
      const matchesProduct = product === 'all' || item.dataset.moderationProductValue === product;
      const show = matchesSearch && matchesRating && matchesProduct;
      const checkbox = item.querySelector('[data-moderation-checkbox]');

      item.hidden = !show;
      if (!show && checkbox) {
        checkbox.checked = false;
      }
      if (show) {
        count += 1;
      }
    });

    if (visibleCount) {
      visibleCount.textContent = String(count);
    }
    if (emptyState) {
      emptyState.classList.toggle('hidden', count !== 0);
    }
    updateSelectionState();
  }

  searchInput?.addEventListener('input', applyFilters);
  ratingSelect?.addEventListener('change', applyFilters);
  productSelect?.addEventListener('change', applyFilters);

  clearButton?.addEventListener('click', () => {
    if (searchInput) {
      searchInput.value = '';
    }
    if (ratingSelect) {
      ratingSelect.value = 'all';
    }
    if (productSelect) {
      productSelect.value = 'all';
    }
    applyFilters();
    searchInput?.focus();
  });

  selectVisibleButton?.addEventListener('click', () => {
    const visible = visibleItems();
    const shouldSelect = visible.some((item) => {
      const checkbox = item.querySelector('[data-moderation-checkbox]');
      return checkbox && !checkbox.checked;
    });

    visible.forEach((item) => {
      const checkbox = item.querySelector('[data-moderation-checkbox]');
      if (checkbox) {
        checkbox.checked = shouldSelect;
      }
    });
    updateSelectionState();
  });

  checkboxes.forEach((checkbox) => {
    checkbox.addEventListener('change', updateSelectionState);
  });

  bulkForm?.addEventListener('submit', (event) => {
    const count = selectedCheckboxes().length;
    if (count === 0) {
      event.preventDefault();
      return;
    }
    if (!window.confirm(`Remove ${count} selected ${count === 1 ? 'comment' : 'comments'}?`)) {
      event.preventDefault();
    }
  });

  applyFilters();
})();

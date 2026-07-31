const money = new Intl.NumberFormat('en-PH', {
  style: 'currency',
  currency: 'PHP',
});

function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>"']/g, (character) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
  })[character]);
}

function safeImageUrl(value) {
  const url = String(value ?? '');
  if (/^https?:\/\//i.test(url)) return url;
  return /^image\/[a-z0-9._% -]+$/i.test(url) ? url : '';
}

const systemStates = {
  healthy: {
    label: 'Services restored',
    badge: 'badge-success',
    message: 'Homepage, product pages, and checkout are online.',
    checkout: 'Online',
    dot: '',
  },
  attack: {
    label: 'Server error',
    badge: 'badge-error',
    message: 'SERVER ERROR. PLEASE TRY AGAIN. Checkout requests are timing out.',
    checkout: 'Timing out',
    dot: 'danger',
  },
  filtering: {
    label: 'Filtering traffic',
    badge: 'badge-warning',
    message: 'Rate limiting and traffic filtering are separating suspicious requests from real customers.',
    checkout: 'Recovering',
    dot: 'warning',
  },
};

function getSystemState() {
  const state = localStorage.getItem('ecocart_system_state') || 'healthy';
  return systemStates[state] ? state : 'healthy';
}

function setSystemState(state) {
  localStorage.setItem('ecocart_system_state', state);
  applySystemState();
}

function checkoutIncidentModeEnabled() {
  const params = new URLSearchParams(window.location.search);
  return params.has('scene') || document.body.dataset.checkoutIncident === 'true';
}

function applySystemState() {
  const stateKey = getSystemState();
  const state = systemStates[stateKey];
  document.body.dataset.systemState = stateKey;

  document.querySelectorAll('[data-system-label]').forEach((node) => {
    node.textContent = state.label;
    node.classList.remove('badge-success', 'badge-warning', 'badge-error');
    node.classList.add(state.badge);
  });

  document.querySelectorAll('[data-system-message]').forEach((node) => {
    node.textContent = state.message;
  });

  document.querySelectorAll('[data-system-checkout]').forEach((node) => {
    node.textContent = state.checkout;
  });

  document.querySelectorAll('[data-system-dot]').forEach((node) => {
    node.classList.remove('warning', 'danger');
    if (state.dot) {
      node.classList.add(state.dot);
    }
  });

  document.querySelectorAll('[data-system-service]').forEach((node) => {
    const service = node.dataset.systemService;
    const degraded = stateKey === 'attack' || (stateKey === 'filtering' && service === 'Checkout');
    node.classList.toggle('badge-success', !degraded);
    node.classList.toggle('badge-error', stateKey === 'attack');
    node.classList.toggle('badge-warning', stateKey === 'filtering' && service === 'Checkout');
    node.textContent = `${service} ${degraded ? state.checkout.toLowerCase() : 'online'}`;
  });

  const checkoutError = document.querySelector('[data-checkout-error]');
  const checkoutBlocked = stateKey === 'attack' && checkoutIncidentModeEnabled();
  if (checkoutError) {
    checkoutError.classList.toggle('hidden', !checkoutBlocked);
  }

  const placeOrder = document.querySelector('[data-place-order]');
  if (placeOrder) {
    const hasItems = getCart().length > 0;
    placeOrder.disabled = checkoutBlocked || !hasItems;
    placeOrder.classList.toggle('btn-disabled', checkoutBlocked || !hasItems);
    if (checkoutBlocked) {
      placeOrder.querySelector('span').textContent = 'Checkout unavailable';
    } else {
      placeOrder.querySelector('span').textContent = hasItems ? 'Place order' : 'Add items to continue';
    }
  }
}

function getCart() {
  try {
    return JSON.parse(localStorage.getItem('ecocart_cart') || '[]');
  } catch {
    return [];
  }
}

function saveCart(cart) {
  localStorage.setItem('ecocart_cart', JSON.stringify(cart));
  refreshCartUi();
}

function addToCart(product) {
  const cart = getCart();
  const existing = cart.find((item) => Number(item.id) === Number(product.id));

  if (existing) {
    const quantity = existing.quantity + 1;
    Object.assign(existing, product, { quantity });
  } else {
    cart.push({ ...product, quantity: 1 });
  }

  saveCart(cart);
}

function removeFromCart(productId) {
  saveCart(getCart().filter((item) => Number(item.id) !== Number(productId)));
}

function changeQty(productId, delta) {
  const cart = getCart()
    .map((item) => {
      if (Number(item.id) === Number(productId)) {
        return { ...item, quantity: Math.max(1, item.quantity + delta) };
      }

      return item;
    });

  saveCart(cart);
}

function cartTotals() {
  const cart = getCart();
  const count = cart.reduce((sum, item) => sum + item.quantity, 0);
  const subtotal = cart.reduce((sum, item) => sum + Number(item.price) * item.quantity, 0);

  return { cart, count, subtotal };
}

function refreshCartUi() {
  const { cart, count, subtotal } = cartTotals();
  const discount = subtotal > 0 ? subtotal * 0.1 : 0;
  const shipping = subtotal > 0 && subtotal < 1500 ? 49 : 0;
  const total = Math.max(0, subtotal - discount + shipping);

  document.querySelectorAll('[data-cart-count]').forEach((node) => {
    node.textContent = count;
  });

  document.querySelectorAll('[data-cart-total]').forEach((node) => {
    node.textContent = money.format(subtotal);
  });

  document.querySelectorAll('[data-order-subtotal]').forEach((node) => {
    node.textContent = money.format(subtotal);
  });

  document.querySelectorAll('[data-order-discount]').forEach((node) => {
    node.textContent = `-${money.format(discount)}`;
  });

  document.querySelectorAll('[data-order-shipping]').forEach((node) => {
    node.textContent = shipping ? money.format(shipping) : 'Free';
  });

  document.querySelectorAll('[data-order-total]').forEach((node) => {
    node.textContent = money.format(total);
  });

  document.querySelectorAll('[data-cart-json]').forEach((node) => {
    node.value = JSON.stringify(cart);
  });

  const checkoutList = document.querySelector('[data-checkout-list]');
  if (!checkoutList) {
    applySystemState();
    return;
  }

  const emptyCart = document.querySelector('[data-empty-cart]');
  if (emptyCart) {
    emptyCart.classList.toggle('hidden', cart.length > 0);
  }

  if (!cart.length) {
    checkoutList.innerHTML = '';
    applySystemState();
    return;
  }

  checkoutList.innerHTML = cart.map((item) => {
    const imageUrl = safeImageUrl(item.image_url);
    const media = imageUrl
      ? `<img class="h-20 w-20 shrink-0 rounded-lg object-cover" src="${escapeHtml(imageUrl)}" alt="${escapeHtml(item.name)}">`
      : `<div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
          <i data-lucide="package" class="h-6 w-6"></i>
        </div>`;

    return `
    <div class="grid gap-4 py-5 sm:grid-cols-[1fr_auto] sm:items-center">
      <div class="flex min-w-0 items-start gap-4">
        ${media}
        <div class="min-w-0">
          <p class="text-[10px] font-black uppercase text-slate-400">${escapeHtml(item.category || 'EcoCart pick')}</p>
          <p class="mt-1 font-black text-slate-900">${escapeHtml(item.name)}</p>
          <p class="mt-1 text-xs text-slate-500">${money.format(Number(item.price))} each</p>
          <p class="mt-2 text-base font-black text-rose-600">${money.format(Number(item.price) * item.quantity)}</p>
        </div>
      </div>
      <div class="flex items-center justify-between gap-3 sm:justify-end">
        <div class="join overflow-hidden rounded-lg border border-slate-200 bg-white">
          <button type="button" class="btn btn-sm join-item rounded-none border-0 bg-white px-3 hover:bg-slate-100" data-qty-minus="${item.id}" aria-label="Decrease quantity">-</button>
          <span class="join-item flex w-10 items-center justify-center border-x border-slate-200 text-sm font-black">${item.quantity}</span>
          <button type="button" class="btn btn-sm join-item rounded-none border-0 bg-white px-3 hover:bg-slate-100" data-qty-plus="${item.id}" aria-label="Increase quantity">+</button>
        </div>
        <button type="button" class="btn btn-square btn-sm btn-ghost text-slate-400 hover:bg-rose-50 hover:text-rose-600" data-remove="${item.id}" aria-label="Remove item">
          <i data-lucide="trash-2" class="h-4 w-4"></i>
        </button>
      </div>
    </div>
  `;
  }).join('');

  checkoutList.querySelectorAll('[data-qty-minus]').forEach((button) => {
    button.addEventListener('click', () => changeQty(button.dataset.qtyMinus, -1));
  });

  checkoutList.querySelectorAll('[data-qty-plus]').forEach((button) => {
    button.addEventListener('click', () => changeQty(button.dataset.qtyPlus, 1));
  });

  checkoutList.querySelectorAll('[data-remove]').forEach((button) => {
    button.addEventListener('click', () => removeFromCart(button.dataset.remove));
  });

  if (window.lucide) {
    window.lucide.createIcons();
  }

  applySystemState();
}

function bootStorefront() {
  const productGrid = document.querySelector('[data-product-grid]');
  if (!productGrid) {
    return;
  }

  const toast = document.querySelector('[data-store-toast]');
  const toastMessage = document.querySelector('[data-store-toast-message]');
  const productCards = [...document.querySelectorAll('[data-product-card]')];
  const resultCount = document.querySelector('[data-product-result-count]');
  const emptyState = document.querySelector('[data-product-empty]');
  const searchInputs = [...document.querySelectorAll('[data-product-search]')];
  const categorySelect = document.querySelector('[data-category-select]');
  const quickModal = document.querySelector('#quick_view_modal');
  let selectedCategory = 'all';
  let searchTerm = '';
  let toastTimer = null;

  function showToast(message) {
    if (!toast || !toastMessage) {
      return;
    }

    toastMessage.textContent = message;
    toast.classList.remove('hidden');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.add('hidden'), 1800);
  }

  function applyProductFilter() {
    let visibleCount = 0;

    productCards.forEach((card) => {
      const matchesCategory = selectedCategory === 'all' || card.dataset.productCategory === selectedCategory;
      const matchesSearch = !searchTerm || card.dataset.productName.includes(searchTerm);
      const visible = matchesCategory && matchesSearch;
      card.classList.toggle('hidden', !visible);
      visibleCount += visible ? 1 : 0;
    });

    document.querySelectorAll('#products [data-product-filter]').forEach((button) => {
      const active = button.dataset.productFilter === selectedCategory;
      button.classList.toggle('product-filter-active', active);
      button.classList.toggle('bg-transparent', !active);
    });

    if (resultCount) {
      resultCount.textContent = visibleCount;
    }

    if (emptyState) {
      emptyState.classList.toggle('hidden', visibleCount > 0);
    }
  }

  function addProductFromButton(button) {
    try {
      const product = JSON.parse(button.dataset.addProduct || '{}');
      addToCart(product);
      const label = button.querySelector('span');
      const original = label?.textContent || 'Add to cart';
      button.classList.add('btn-success');
      if (label) {
        label.textContent = 'Added';
      }
      showToast(`${product.name} added to your cart`);
      setTimeout(() => {
        button.classList.remove('btn-success');
        if (label) {
          label.textContent = original;
        }
      }, 900);
    } catch {
      showToast('This product could not be added');
    }
  }

  document.querySelectorAll('[data-add-product]').forEach((button) => {
    button.addEventListener('click', () => addProductFromButton(button));
  });

  document.querySelectorAll('[data-product-filter]').forEach((button) => {
    button.addEventListener('click', () => {
      selectedCategory = button.dataset.productFilter || 'all';
      searchTerm = '';
      searchInputs.forEach((input) => {
        input.value = '';
      });
      if (categorySelect) {
        categorySelect.value = selectedCategory;
      }
      applyProductFilter();
      document.querySelector('#products')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });

  document.querySelectorAll('[data-product-search-form]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      applyProductFilter();
      document.querySelector('#products')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });

  searchInputs.forEach((input) => {
    input.addEventListener('input', () => {
      searchTerm = input.value.trim().toLowerCase();
      searchInputs.forEach((otherInput) => {
        if (otherInput !== input) {
          otherInput.value = input.value;
        }
      });
      applyProductFilter();
    });
  });

  if (categorySelect) {
    categorySelect.addEventListener('change', () => {
      selectedCategory = categorySelect.value;
      applyProductFilter();
    });
  }

  let wishlist = [];
  try {
    wishlist = JSON.parse(localStorage.getItem('ecocart_wishlist') || '[]').map(Number);
  } catch {
    wishlist = [];
  }

  function refreshWishlist() {
    document.querySelectorAll('[data-wishlist-count]').forEach((node) => {
      node.textContent = wishlist.length;
    });

    document.querySelectorAll('[data-wishlist-product]').forEach((button) => {
      const active = wishlist.includes(Number(button.dataset.wishlistProduct));
      button.classList.toggle('wishlist-active', active);
      button.setAttribute('aria-pressed', String(active));
    });
  }

  document.querySelectorAll('[data-wishlist-product]').forEach((button) => {
    button.addEventListener('click', () => {
      const id = Number(button.dataset.wishlistProduct);
      wishlist = wishlist.includes(id)
        ? wishlist.filter((productId) => productId !== id)
        : [...wishlist, id];
      localStorage.setItem('ecocart_wishlist', JSON.stringify(wishlist));
      refreshWishlist();
      showToast(wishlist.includes(id) ? 'Saved to your wishlist' : 'Removed from your wishlist');
    });
  });

  document.querySelectorAll('[data-quick-view]').forEach((button) => {
    button.addEventListener('click', () => {
      if (!quickModal) {
        return;
      }

      try {
        const product = JSON.parse(button.dataset.quickView);
        quickModal.querySelector('[data-quick-image]').src = safeImageUrl(product.image_url);
        quickModal.querySelector('[data-quick-image]').alt = product.name;
        quickModal.querySelector('[data-quick-category]').textContent = product.category;
        quickModal.querySelector('[data-quick-name]').textContent = product.name;
        quickModal.querySelector('[data-quick-price]').textContent = money.format(Number(product.price));
        const quickAdd = quickModal.querySelector('[data-quick-add]');
        quickAdd.dataset.addProduct = JSON.stringify(product);
        quickModal.showModal();
      } catch {
        showToast('Quick view is unavailable');
      }
    });
  });

  const quickAdd = quickModal?.querySelector('[data-quick-add]');
  if (quickAdd) {
    quickAdd.addEventListener('click', () => addProductFromButton(quickAdd));
  }

  refreshWishlist();
  applyProductFilter();
}

function bootCountdown() {
  const node = document.querySelector('[data-countdown]');
  if (!node) {
    return;
  }

  let seconds = 5 * 60;

  setInterval(() => {
    seconds = Math.max(0, seconds - 1);
    const minutes = String(Math.floor(seconds / 60)).padStart(2, '0');
    const rest = String(seconds % 60).padStart(2, '0');
    node.textContent = `${minutes}:${rest}`;
  }, 1000);
}

function bootTerminal() {
  const terminal = document.querySelector('[data-terminal]');
  const startButton = document.querySelector('[data-ops-start]');
  const resetButton = document.querySelector('[data-ops-reset]');
  const traffic = document.querySelector('[data-traffic]');

  if (!terminal || !startButton || !resetButton || !traffic) {
    return;
  }

  const metrics = {
    requests: document.querySelector('[data-metric="requests"]'),
    errors: document.querySelector('[data-metric="errors"]'),
    latency: document.querySelector('[data-metric="latency"]'),
    blocked: document.querySelector('[data-metric="blocked"]'),
    checkout: document.querySelector('[data-metric="checkout"]'),
  };
  const metricNotes = {
    requests: document.querySelector('[data-metric-note="requests"]'),
    errors: document.querySelector('[data-metric-note="errors"]'),
    latency: document.querySelector('[data-metric-note="latency"]'),
    blocked: document.querySelector('[data-metric-note="blocked"]'),
    checkout: document.querySelector('[data-metric-note="checkout"]'),
  };
  const incident = {
    banner: document.querySelector('[data-incident-banner]'),
    title: document.querySelector('[data-incident-title]'),
    severity: document.querySelector('[data-incident-severity]'),
    copy: document.querySelector('[data-incident-copy]'),
    id: document.querySelector('[data-incident-id]'),
  };
  const routes = {
    products: document.querySelector('[data-route="products"]'),
    cart: document.querySelector('[data-route="cart"]'),
    checkout: document.querySelector('[data-route="checkout"]'),
    saturation: document.querySelector('[data-route="saturation"]'),
  };
  const actionButtons = [...document.querySelectorAll('[data-ops-action]')];
  const steps = [...document.querySelectorAll('[data-runbook-step]')];
  const bars = [...document.querySelectorAll('.ops-traffic-bar')];
  const sourceRows = [...document.querySelectorAll('[data-source-row]')];
  const sourceVerdicts = [...document.querySelectorAll('[data-source-verdict]')];
  const impactRows = [...document.querySelectorAll('[data-impact-row]')];
  const serviceNodes = [...document.querySelectorAll('[data-service-node]')];
  const serviceLinks = [...document.querySelectorAll('[data-service-link]')];
  const commandForm = document.querySelector('[data-command-form]');
  const commandInput = document.querySelector('[data-command-input]');
  const progress = document.querySelector('[data-runbook-progress]');
  const sourceCount = document.querySelector('[data-source-count]');
  const impactSummary = document.querySelector('[data-impact-summary]');
  const serviceSummary = document.querySelector('[data-service-summary]');
  const breachStatus = document.querySelector('[data-breach-status]');
  const securityFinding = document.querySelector('[data-security-finding]');
  const checkoutDot = document.querySelector('[data-checkout-dot]');
  const clock = document.querySelector('[data-ops-clock]');

  let stage = 'idle';
  let requestCount = 2340;
  let blockedCount = 0;
  let completedSteps = 0;
  let telemetryTimer = null;
  let incidentTimer = null;
  let idleTimer = null;
  let actionLocked = false;

  function line(text, tone = 'info', prompt = '') {
    const item = document.createElement('div');
    item.className = 'terminal-line';
    if (prompt) {
      const promptNode = document.createElement('span');
      promptNode.className = 'prompt';
      promptNode.textContent = `${prompt} `;
      item.appendChild(promptNode);
    }
    const textNode = document.createElement('span');
    textNode.className = `terminal-${tone}`;
    textNode.textContent = text;
    item.appendChild(textNode);
    terminal.appendChild(item);
    terminal.scrollTop = terminal.scrollHeight;
  }

  function command(text) {
    line(text, 'info', 'admin@ecocart:~$');
  }

  function setMetrics({
    errors = '0.18%',
    latency = '184 ms',
    checkout = 'Online',
    requestNote = 'normal sale level',
    errorNote = 'normal',
    latencyNote = 'normal response',
    blockedNote = 'requests filtered',
    checkoutNote = 'available',
  } = {}) {
    metrics.requests.textContent = requestCount.toLocaleString();
    metrics.errors.textContent = errors;
    metrics.latency.textContent = latency;
    metrics.blocked.textContent = blockedCount.toLocaleString();
    metrics.checkout.textContent = checkout;
    metricNotes.requests.textContent = requestNote;
    metricNotes.errors.textContent = errorNote;
    metricNotes.latency.textContent = latencyNote;
    metricNotes.blocked.textContent = blockedNote;
    metricNotes.checkout.textContent = checkoutNote;
  }

  function drawBars(mode = 'healthy') {
    traffic.classList.toggle('attack-mode', mode === 'attack');
    traffic.classList.toggle('filtering-mode', mode === 'filtering');
    traffic.classList.toggle('rising-mode', mode === 'rising');
    bars.forEach((bar, index) => {
      let base = 14 + ((index * 9) % 15);
      if (mode === 'rising') {
        base = index < 16 ? 20 + ((index * 7) % 16) : 36 + ((index * 9) % 38);
      }
      if (mode === 'attack') {
        base = index < 12 ? 26 + ((index * 7) % 20) : 60 + ((index * 13) % 36);
      }
      if (mode === 'filtering') {
        base = 34 + ((index * 11) % 38);
      }
      const height = Math.min(98, base + Math.round(Math.random() * 8));
      bar.style.height = `${height}%`;
    });
  }

  function setIncidentVisual(mode, title, severity, copy) {
    incident.title.textContent = title;
    incident.severity.textContent = severity;
    incident.copy.textContent = copy;
    incident.banner.classList.remove(
      'border-emerald-500/30',
      'bg-emerald-500/10',
      'border-amber-500/40',
      'bg-amber-500/10',
      'border-rose-500/40',
      'bg-rose-500/10',
    );
    incident.severity.classList.remove(
      'bg-emerald-400/15',
      'text-emerald-300',
      'bg-amber-400/15',
      'text-amber-300',
      'bg-rose-400/15',
      'text-rose-300',
    );

    const styles = {
      healthy: ['border-emerald-500/30', 'bg-emerald-500/10', 'bg-emerald-400/15', 'text-emerald-300'],
      warning: ['border-amber-500/40', 'bg-amber-500/10', 'bg-amber-400/15', 'text-amber-300'],
      danger: ['border-rose-500/40', 'bg-rose-500/10', 'bg-rose-400/15', 'text-rose-300'],
    };
    incident.banner.classList.add(styles[mode][0], styles[mode][1]);
    incident.severity.classList.add(styles[mode][2], styles[mode][3]);
  }

  function setServiceState(names, className) {
    serviceNodes.forEach((node) => {
      if (names.includes(node.dataset.serviceNode)) {
        node.classList.add(className);
      }
    });
  }

  function clearServiceStates() {
    serviceNodes.forEach((node) => {
      node.classList.remove('service-danger', 'service-warning', 'service-filtering');
    });
    serviceLinks.forEach((link) => {
      link.className = 'h-px w-4 shrink-0 bg-slate-700 sm:w-8';
    });
  }

  function setImpact(stateName, toneClass, summary) {
    impactRows.forEach((row) => {
      const stateNode = row.querySelector('[data-impact-state]');
      stateNode.textContent = stateName;
      stateNode.className = `text-[9px] font-black uppercase ${toneClass}`;
    });
    impactSummary.textContent = summary;
    impactSummary.className = `text-xs font-black ${toneClass}`;
  }

  function setStepState(name, stateName) {
    const step = document.querySelector(`[data-runbook-step="${name}"]`);
    if (!step) {
      return;
    }
    step.classList.remove('step-ready', 'step-running', 'step-complete');
    if (stateName !== 'waiting') {
      step.classList.add(`step-${stateName}`);
    }
    const status = step.querySelector('[data-step-status]');
    status.textContent = stateName === 'complete' ? 'Complete' : stateName === 'running' ? 'Running' : stateName === 'ready' ? 'Ready' : 'Waiting';
    status.className = `text-[10px] font-black uppercase ${
      stateName === 'complete' ? 'text-emerald-400' : stateName === 'running' ? 'text-amber-400' : stateName === 'ready' ? 'text-cyan-400' : 'text-slate-500'
    }`;
    const button = step.querySelector('[data-ops-action]');
    button.disabled = stateName !== 'ready';
    button.textContent = stateName === 'complete' ? 'Done' : stateName === 'running' ? 'Running' : 'Run';
  }

  function markComplete(name, nextName = '') {
    setStepState(name, 'complete');
    completedSteps += 1;
    progress.textContent = `${completedSteps} / 5 complete`;
    if (nextName) {
      setStepState(nextName, 'ready');
    }
  }

  function startAttackTelemetry() {
    stage = 'detected';
    setSystemState('attack');
    incident.id.textContent = 'EC-2026-0728-01';
    setIncidentVisual('danger', 'Abnormal traffic detected', 'Critical', 'The website is receiving far more requests than normal and checkout is timing out.');
    requestCount = 68420;
    blockedCount = 0;
    setMetrics({
      errors: '31.7%',
      latency: '8.9 s',
      checkout: 'Timing out',
      requestNote: 'far above normal traffic',
      errorNote: 'customers seeing errors',
      latencyNote: 'checkout overloaded',
      blockedNote: 'controls not active',
      checkoutNote: 'customer errors observed',
    });
    routes.products.textContent = '18,420 rpm';
    routes.cart.textContent = '14,880 rpm';
    routes.checkout.textContent = '29,640 rpm';
    routes.saturation.textContent = '96%';
    routes.saturation.className = 'mt-1 block text-sm text-rose-400';
    checkoutDot.classList.add('danger');
    clearServiceStates();
    setServiceState(['edge'], 'service-warning');
    setServiceState(['app', 'checkout'], 'service-danger');
    serviceSummary.textContent = 'Degraded';
    serviceSummary.className = 'rounded bg-rose-400/15 px-2 py-1 text-[10px] font-black uppercase text-rose-300';
    setImpact('Waiting', 'text-rose-400', '30 affected');
    sourceCount.textContent = 'Repeated activity detected';
    line('[x] request volume 68,420/min | 29x over baseline | anomaly score 0.98', 'error');
    line('[!] checkout queue depth 1,842 | p95 8.9s | worker starvation', 'warn');
    line('[!] signature match: REQUEST/REFRESH/CONNECT/LOAD_PAGE x4 clusters | bot heuristics tripped', 'warn');
    setStepState('inspect', 'ready');
    drawBars('attack');
    primeCinematicInput();

    clearInterval(telemetryTimer);
    telemetryTimer = setInterval(() => {
      requestCount = Math.min(76000, requestCount + Math.floor(200 + Math.random() * 900));
      setMetrics({
        errors: `${(29 + Math.random() * 8).toFixed(1)}%`,
        latency: `${(8.1 + Math.random() * 2.4).toFixed(1)} s`,
        checkout: 'Timing out',
        requestNote: 'traffic remains unusually high',
        errorNote: 'customers seeing errors',
        latencyNote: 'checkout overloaded',
        blockedNote: 'controls not active',
        checkoutNote: 'customer errors observed',
      });
      drawBars('attack');
    }, 900);
  }

  function startIdleTelemetry() {
    clearInterval(idleTimer);
    idleTimer = setInterval(() => {
      if (stage !== 'idle') {
        return;
      }
      requestCount = 2340 + Math.floor(Math.random() * 150) - 60;
      setMetrics({
        errors: `${(0.14 + Math.random() * 0.12).toFixed(2)}%`,
        latency: `${Math.round(176 + Math.random() * 26)} ms`,
        checkout: 'Online',
      });
      routes.products.textContent = `${Math.round(700 + Math.random() * 96).toLocaleString()} rpm`;
      routes.cart.textContent = `${Math.round(300 + Math.random() * 52).toLocaleString()} rpm`;
      routes.checkout.textContent = `${Math.round(118 + Math.random() * 30).toLocaleString()} rpm`;
      routes.saturation.textContent = `${Math.round(16 + Math.random() * 6)}%`;
      drawBars('healthy');
    }, 1500);
  }

  function startTrace() {
    if (stage !== 'idle') {
      return;
    }
    stage = 'monitoring';
    clearInterval(idleTimer);
    startButton.disabled = true;
    startButton.innerHTML = '<span class="loading loading-spinner loading-xs"></span> Trace active';
    command('edgectl monitor --live --routes /products,/cart,/checkout');
    line('[+] edge telemetry stream attached â†’ /products /cart /checkout', 'ok');
    line('[*] baseline locked: 2,340 req/min | p95 184ms | err 0.18%', 'info');
    setIncidentVisual('warning', 'Watching live traffic', 'Monitoring', 'Traffic will be compared with the normal sale-event level.');
    drawBars('healthy');
    primeCinematicInput();
    const trafficRamp = [
      { requests: 3200, errors: '0.24%', latency: '202 ms', checkout: 'Online', saturation: '23%', note: '>> ingress 3,200 req/min | conn/s climbing' },
      { requests: 4900, errors: '0.48%', latency: '248 ms', checkout: 'Online', saturation: '31%', note: '>> ingress 4,900 req/min | keep-alive pool filling' },
      { requests: 7400, errors: '1.2%', latency: '390 ms', checkout: 'Slowing', saturation: '42%', note: '>> ingress 7,400 req/min | above sale envelope | SYN backlog rising' },
      { requests: 12800, errors: '3.8%', latency: '820 ms', checkout: 'Slowing', saturation: '56%', note: '[!] p95 latency breach | checkout worker queue saturating' },
      { requests: 24100, errors: '9.6%', latency: '2.1 s', checkout: 'Degraded', saturation: '71%', note: '[!] ingress 24,100 req/min | anomalous request signature repeating' },
      { requests: 43800, errors: '19.4%', latency: '4.8 s', checkout: 'Degraded', saturation: '84%', note: '[!] checkout thread pool exhausted | 503s emitting' },
      { requests: 68420, errors: '31.7%', latency: '8.9 s', checkout: 'Timing out', saturation: '96%', note: '[x] ingress 68,420 req/min | origin saturation 96% | availability incident confirmed' },
    ];
    let rampPosition = 0;
    clearInterval(incidentTimer);
    incidentTimer = setInterval(() => {
      const point = trafficRamp[rampPosition];
      requestCount = point.requests;
      setMetrics({
        errors: point.errors,
        latency: point.latency,
        checkout: point.checkout,
        requestNote: point.requests < 7400 ? 'rising from normal level' : 'unusual traffic growth',
        errorNote: point.requests < 12800 ? 'being monitored' : 'customer errors increasing',
        latencyNote: point.requests < 12800 ? 'response getting slower' : 'checkout under pressure',
        blockedNote: 'controls not active',
        checkoutNote: point.checkout === 'Online' ? 'available' : 'customer delays observed',
      });
      routes.saturation.textContent = point.saturation;
      routes.saturation.className = `mt-1 block text-sm ${point.requests < 12800 ? 'text-amber-400' : 'text-rose-400'}`;
      routes.products.textContent = `${Math.round(point.requests * 0.27).toLocaleString()} rpm`;
      routes.cart.textContent = `${Math.round(point.requests * 0.22).toLocaleString()} rpm`;
      routes.checkout.textContent = `${Math.round(point.requests * 0.43).toLocaleString()} rpm`;
      line(point.note, point.requests < 7400 ? 'info' : point.requests < 24000 ? 'warn' : 'error');
      drawBars(point.requests < 7400 ? 'healthy' : point.requests < 24000 ? 'rising' : 'attack');
      if (point.requests === 7400) {
        setIncidentVisual('warning', 'Traffic above expected level', 'Watching', 'The request rate is rising faster than normal sale traffic.');
        checkoutDot.classList.add('warning');
      }
      if (point.requests === 24100) {
        setSystemState('attack');
        setIncidentVisual('danger', 'Customer delays detected', 'High', 'Repeated requests are beginning to interfere with checkout.');
        checkoutDot.classList.remove('warning');
        checkoutDot.classList.add('danger');
        setImpact('Waiting', 'text-rose-400', '30 affected');
      }
      rampPosition += 1;
      if (rampPosition >= trafficRamp.length) {
        clearInterval(incidentTimer);
        startAttackTelemetry();
      }
    }, 1300);
  }

  async function runAction(name) {
    if (actionLocked) {
      return;
    }
    const expected = {
      inspect: 'detected',
      classify: 'inspected',
      limit: 'classified',
      scrub: 'limited',
      verify: 'filtered',
    };
    if (stage !== expected[name]) {
      line('[!] pipeline busy â†’ await current stage completion', 'warn');
      return;
    }

    actionLocked = true;
    setStepState(name, 'running');

    if (name === 'inspect') {
      command('edgectl inspect --repeats --top 4');
      await new Promise((resolve) => setTimeout(resolve, 850));
      sourceRows.forEach((row) => row.classList.remove('opacity-45'));
      sourceVerdicts.forEach((node) => {
        node.textContent = 'Repeating';
        node.className = 'rounded bg-amber-400/15 px-2 py-1 text-[9px] font-black uppercase text-amber-300';
      });
      sourceCount.textContent = '4 repeating clusters';
      line('[!] repetition signature isolated across 4 source ASNs', 'warn');
      line('[*] /checkout x18,422 | /cart x16,108 | /products x14,977 hits', 'warn');
      line('[*] automation ratio 82.4% | residual human traffic in stream', 'info');
      stage = 'inspected';
      markComplete('inspect', 'classify');
      primeCinematicInput();
    }

    if (name === 'classify') {
      command('auditctl verify --scope accounts,orders');
      await new Promise((resolve) => setTimeout(resolve, 900));
      breachStatus.textContent = '0 auth anomalies';
      breachStatus.className = 'text-[10px] font-bold text-emerald-400';
      securityFinding.textContent = 'Availability-only traffic surge. Auth store and order ledger show no unauthorized writes or session-hijack indicators.';
      setIncidentVisual('danger', 'Availability incident confirmed', 'Critical', 'The website is being overwhelmed, but customer accounts and orders remain safe.');
      line('[+] auth store audit -> 0 anomalous sessions | tokens intact', 'ok');
      line('[+] order ledger checksum verified -> no unauthorized writes', 'ok');
      line('[x] classification: availability traffic surge | customer-data risk low', 'error');
      stage = 'classified';
      markComplete('classify', 'limit');
      primeCinematicInput();
    }

    if (name === 'limit') {
      command('ratectl limit --sources repeated --rate 40/10s');
      await new Promise((resolve) => setTimeout(resolve, 1000));
      stage = 'limited';
      setSystemState('filtering');
      blockedCount = 18420;
      setMetrics({
        errors: '18.2%',
        latency: '4.6 s',
        checkout: 'Recovering',
        requestNote: 'rate limit engaged',
        errorNote: 'declining',
        latencyNote: 'improving',
        blockedNote: 'repeating requests',
        checkoutNote: 'partial availability',
      });
      clearServiceStates();
      setServiceState(['edge', 'waf'], 'service-filtering');
      setServiceState(['app', 'checkout'], 'service-warning');
      serviceSummary.textContent = 'Filtering';
      serviceSummary.className = 'rounded bg-cyan-400/15 px-2 py-1 text-[10px] font-black uppercase text-cyan-300';
      line('[+] token-bucket limiter armed on all ingress edges', 'ok');
      line('[*] repeated sources throttled -> 40 req / 10s | 429 issuing', 'info');
      markComplete('limit', 'scrub');
      drawBars('filtering');
      primeCinematicInput();
    }

    if (name === 'scrub') {
      command('edge-control apply sale-protection --mode filter');
      await new Promise((resolve) => setTimeout(resolve, 1100));
      stage = 'filtered';
      sourceVerdicts.forEach((node, index) => {
        node.textContent = index % 2 === 0 ? 'Dropped' : 'Challenged';
        node.className = 'rounded bg-rose-400/15 px-2 py-1 text-[9px] font-black uppercase text-rose-300';
      });
      blockedCount = 62380;
      requestCount = 18960;
      setMetrics({
        errors: '3.4%',
        latency: '920 ms',
        checkout: 'Recovering',
        requestNote: 'clean traffic only',
        errorNote: 'almost normal',
        latencyNote: 'stabilizing',
        blockedNote: 'repeated requests',
        checkoutNote: 'verification required',
      });
      clearServiceStates();
      setServiceState(['waf'], 'service-filtering');
      setServiceState(['checkout'], 'service-warning');
      line('[+] edge filtering engaged | customer sessions preserved', 'ok');
      line('[*] repeated requests reduced 57,600/min | clean forwarded 4,200/min', 'ok');
      line('[*] checkout queue depth 1,842 -> 126 | worker pool recovering', 'info');
      setImpact('Retrying', 'text-amber-400', '12 retrying');
      markComplete('scrub', 'verify');
      clearInterval(telemetryTimer);
      telemetryTimer = setInterval(() => {
        requestCount = Math.max(3100, requestCount - Math.floor(1800 + Math.random() * 2400));
        blockedCount += Math.floor(900 + Math.random() * 1600);
        setMetrics({
          errors: requestCount > 7000 ? '2.1%' : '0.42%',
          latency: requestCount > 7000 ? '610 ms' : '228 ms',
          checkout: 'Recovering',
          requestNote: 'clean traffic only',
          errorNote: 'almost normal',
          latencyNote: 'stabilizing',
          blockedNote: 'repeated requests',
          checkoutNote: 'verification required',
        });
        drawBars('filtering');
      }, 850);
      primeCinematicInput();
    }

    if (name === 'verify') {
      command('healthctl probe --routes storefront,cart,checkout');
      await new Promise((resolve) => setTimeout(resolve, 1200));
      clearInterval(telemetryTimer);
      stage = 'recovered';
      requestCount = 3280;
      blockedCount += 8460;
      setSystemState('healthy');
      setMetrics({
        errors: '0.21%',
        latency: '196 ms',
        checkout: 'Online',
        requestNote: 'sale baseline restored',
        errorNote: 'normal',
        latencyNote: 'normal response',
        blockedNote: 'incident total',
        checkoutNote: '99.97% available',
      });
      routes.products.textContent = '918 rpm';
      routes.cart.textContent = '402 rpm';
      routes.checkout.textContent = '176 rpm';
      routes.saturation.textContent = '22%';
      routes.saturation.className = 'mt-1 block text-sm text-emerald-400';
      checkoutDot.classList.remove('danger', 'warning');
      clearServiceStates();
      serviceSummary.textContent = 'Healthy';
      serviceSummary.className = 'rounded bg-emerald-400/15 px-2 py-1 text-[10px] font-black uppercase text-emerald-300';
      setImpact('Restored', 'text-emerald-400', '0 affected');
      setIncidentVisual('healthy', 'Production services restored', 'Resolved', 'Website, saved carts, and checkout tests have passed.');
      line('[+] GET /products â†’ HTTP 200 | 142ms | synthetic probe passed', 'ok');
      line('[+] session store intact | cart payloads persisted', 'ok');
      line('[+] POST /checkout â†’ HTTP 201 | 196ms | order committed', 'ok');
      line('[+] incident closed | mitigation persistent | watch window 30m', 'ok');
      markComplete('verify');
      drawBars('healthy');
      startButton.innerHTML = '<i data-lucide="check-circle-2" class="h-4 w-4"></i> Incident resolved';
      if (window.lucide) {
        window.lucide.createIcons();
      }
      primeCinematicInput();
    }

    actionLocked = false;
  }

  function resetOperations() {
    clearInterval(telemetryTimer);
    clearInterval(incidentTimer);
    clearInterval(idleTimer);
    stage = 'idle';
    actionLocked = false;
    requestCount = 2340;
    blockedCount = 0;
    completedSteps = 0;
    setSystemState('healthy');
    terminal.innerHTML = '';
    command('systemctl status ecocart.target');
    line('[+] ecocart.web.service â†’ active (running)', 'ok');
    line('[+] ecocart.cart.service â†’ active (running)', 'ok');
    line('[+] ecocart.checkout.service â†’ active (running)', 'ok');
    line('[*] ingress 2,340 req/min | p95 184ms | err 0.18% | nominal', 'info');
    setMetrics();
    routes.products.textContent = '742 rpm';
    routes.cart.textContent = '316 rpm';
    routes.checkout.textContent = '128 rpm';
    routes.saturation.textContent = '18%';
    routes.saturation.className = 'mt-1 block text-sm text-emerald-400';
    incident.id.textContent = 'None';
    setIncidentVisual('healthy', 'All production services operational', 'Normal', 'Edge telemetry is within the expected Big Blowout Sale baseline.');
    startButton.disabled = false;
    startButton.innerHTML = '<i data-lucide="radio" class="h-4 w-4"></i> Start live trace';
    progress.textContent = '0 / 5 complete';
    steps.forEach((step) => setStepState(step.dataset.runbookStep, 'waiting'));
    sourceRows.forEach((row) => row.classList.add('opacity-45'));
    sourceVerdicts.forEach((node) => {
      node.textContent = 'Unreviewed';
      node.className = 'rounded bg-slate-800 px-2 py-1 text-[9px] font-black uppercase text-slate-500';
    });
    sourceCount.textContent = 'Awaiting trace';
    breachStatus.textContent = 'Standby';
    breachStatus.className = 'text-[10px] font-bold text-slate-500';
    securityFinding.textContent = 'No packet capture staged for the current trace window.';
    checkoutDot.classList.remove('danger', 'warning');
    clearServiceStates();
    serviceSummary.textContent = 'Healthy';
    serviceSummary.className = 'rounded bg-emerald-400/15 px-2 py-1 text-[10px] font-black uppercase text-emerald-300';
    setImpact('Normal', 'text-emerald-400', '0 affected');
    drawBars('healthy');
    if (window.lucide) {
      window.lucide.createIcons();
    }
    startIdleTelemetry();
    primeCinematicInput();
  }

  startButton.addEventListener('click', startTrace);
  resetButton.addEventListener('click', resetOperations);
  actionButtons.forEach((button) => {
    button.addEventListener('click', () => runAction(button.dataset.opsAction));
  });

  let cinematicIndex = 0;
  let cinematicExecuting = false;
  let cinematicTimer = null;

  function cinematicCommandForStage() {
    const commands = {
      idle: {
        text: 'edgectl monitor --live --routes /products,/cart,/checkout',
        action: () => startTrace(),
      },
      detected: {
        text: 'edgectl inspect --repeats --top 4',
        action: () => runAction('inspect'),
      },
      inspected: {
        text: 'auditctl verify --scope accounts,orders',
        action: () => runAction('classify'),
      },
      classified: {
        text: 'ratectl limit --sources repeated --rate 40/10s',
        action: () => runAction('limit'),
      },
      limited: {
        text: 'edge-control apply sale-protection --mode filter',
        action: () => runAction('scrub'),
      },
      filtered: {
        text: 'healthctl probe --routes storefront,cart,checkout',
        action: () => runAction('verify'),
      },
    };
    return commands[stage] || null;
  }

  function primeCinematicInput() {
    if (!commandInput) {
      return;
    }
    clearTimeout(cinematicTimer);
    cinematicIndex = 0;
    cinematicExecuting = false;
    commandInput.value = '';
    const nextCommand = cinematicCommandForStage();
    commandInput.disabled = !nextCommand;
    commandInput.placeholder = nextCommand
      ? 'shell ready'
      : stage === 'monitoring'
        ? 'waiting for telemetry'
        : 'runbook complete';
    if (nextCommand) {
      setTimeout(() => commandInput.focus(), 0);
    }
  }

  async function executeCinematicCommand() {
    const scripted = cinematicCommandForStage();
    if (!scripted || cinematicExecuting) {
      return;
    }
    cinematicExecuting = true;
    clearTimeout(cinematicTimer);
    commandInput.value = scripted.text;
    await new Promise((resolve) => setTimeout(resolve, 120));
    commandInput.value = '';
    cinematicIndex = 0;
    await Promise.resolve(scripted.action());
    cinematicExecuting = false;
  }

  if (commandForm && commandInput) {
    commandForm.addEventListener('submit', (event) => {
      event.preventDefault();
      executeCinematicCommand();
    });

    commandInput.addEventListener('keydown', (event) => {
      const scripted = cinematicCommandForStage();
      if (!scripted || cinematicExecuting) {
        event.preventDefault();
        return;
      }

      if (event.key === 'Enter') {
        event.preventDefault();
        executeCinematicCommand();
        return;
      }

      if (event.ctrlKey || event.metaKey || event.altKey) {
        event.preventDefault();
        return;
      }

      if (event.key === 'Backspace') {
        event.preventDefault();
        cinematicIndex = Math.max(0, cinematicIndex - 2);
        commandInput.value = scripted.text.slice(0, cinematicIndex);
        return;
      }

      if (event.key.length !== 1 && event.key !== 'Tab') {
        event.preventDefault();
        return;
      }

      event.preventDefault();
      const reveal = event.key === 'Tab' ? 8 : 1 + Math.floor(Math.random() * 3);
      cinematicIndex = Math.min(scripted.text.length, cinematicIndex + reveal);
      commandInput.value = scripted.text.slice(0, cinematicIndex);
      commandInput.setSelectionRange(commandInput.value.length, commandInput.value.length);

      if (cinematicIndex === scripted.text.length) {
        cinematicTimer = setTimeout(executeCinematicCommand, 260);
      }
    });

    commandInput.addEventListener('paste', (event) => {
      event.preventDefault();
      const scripted = cinematicCommandForStage();
      if (!scripted || cinematicExecuting) {
        return;
      }
      cinematicIndex = scripted.text.length;
      commandInput.value = scripted.text;
      cinematicTimer = setTimeout(executeCinematicCommand, 260);
    });

    document.addEventListener('keydown', (event) => {
      if (event.target === commandInput || event.target instanceof HTMLInputElement || event.target instanceof HTMLTextAreaElement) {
        return;
      }
      if (event.ctrlKey || event.metaKey || event.altKey) {
        return;
      }
      if (event.key.length !== 1 && event.key !== 'Enter' && event.key !== 'Backspace' && event.key !== 'Tab') {
        return;
      }
      event.preventDefault();
      commandInput.focus();
      commandInput.dispatchEvent(new KeyboardEvent('keydown', {
        key: event.key,
        bubbles: false,
      }));
    });
  }

  if (clock) {
    const updateClock = () => {
      clock.textContent = new Intl.DateTimeFormat('en-PH', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
        timeZone: 'Asia/Manila',
      }).format(new Date());
    };
    updateClock();
    setInterval(updateClock, 1000);
  }

  window.addEventListener('ecocart:scenechange', (event) => {
    const cue = event.detail?.cue;
    if (cue === 'outage' && ['idle', 'monitoring'].includes(stage)) {
      clearInterval(incidentTimer);
      clearInterval(idleTimer);
      startAttackTelemetry();
      return;
    }
    if (cue === 'recovery') {
      setSystemState('filtering');
      return;
    }
    if (cue === 'restored') {
      setSystemState('healthy');
    }
  });

  resetOperations();
}

function bootCheckoutGuard() {
  const form = document.querySelector('[data-checkout-form]');
  if (!form) {
    return;
  }

  form.addEventListener('submit', (event) => {
    if (getCart().length === 0) {
      event.preventDefault();
      refreshCartUi();
      return;
    }

    if (document.body.dataset.sceneCue === 'sale_live') {
      event.preventDefault();
      const loadingScreen = document.querySelector('[data-scene-loading]');
      const submitButton = form.querySelector('[data-place-order]');
      loadingScreen?.classList.remove('hidden');
      submitButton?.setAttribute('disabled', 'disabled');
      if (window.lucide) {
        window.lucide.createIcons();
      }
      return;
    }

    if (getSystemState() !== 'attack' || !checkoutIncidentModeEnabled()) {
      return;
    }

    event.preventDefault();
    applySystemState();
  });
}

document.addEventListener('DOMContentLoaded', () => {
  bootStorefront();
  bootCountdown();
  bootTerminal();
  bootCheckoutGuard();
  refreshCartUi();
  applySystemState();

  if (window.lucide) {
    window.lucide.createIcons();
  }
});

window.addEventListener('storage', (event) => {
  if (event.key === 'ecocart_system_state') {
    applySystemState();
  }
});


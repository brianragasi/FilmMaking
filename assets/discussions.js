(() => {
  const forms = [...document.querySelectorAll('[data-reaction-form]')];
  if (!forms.length || !window.fetch) {
    return;
  }

  function showMessage(message, isError = false) {
    const toast = document.querySelector('[data-store-toast]');
    const label = document.querySelector('[data-store-toast-message]');
    if (!toast || !label) {
      return;
    }
    label.textContent = message;
    const alert = toast.querySelector('.alert');
    alert?.classList.toggle('bg-rose-700', isError);
    alert?.classList.toggle('bg-slate-950', !isError);
    toast.classList.remove('hidden');
    window.clearTimeout(showMessage.timeoutId);
    showMessage.timeoutId = window.setTimeout(() => toast.classList.add('hidden'), 2600);
  }

  forms.forEach((form) => {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const button = form.querySelector('[data-reaction-button]');
      const count = form.querySelector('[data-reaction-count]');
      const status = form.closest('footer')?.querySelector('[data-reaction-status]');
      if (!button || button.disabled) {
        return;
      }

      button.disabled = true;
      button.classList.add('is-pending');
      try {
        const endpoint = form.getAttribute('action') || window.location.href;
        const response = await fetch(endpoint, {
          method: 'POST',
          body: new FormData(form),
          headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          credentials: 'same-origin',
          cache: 'no-store',
        });
        const payload = await response.json();
        if (response.status === 401 && payload.login_url) {
          window.location.assign(payload.login_url);
          return;
        }
        if (!response.ok || !payload.ok) {
          throw new Error(payload.message || 'That reaction could not be saved.');
        }

        button.classList.toggle('is-active', Boolean(payload.active));
        button.setAttribute('aria-pressed', payload.active ? 'true' : 'false');
        if (count) {
          count.textContent = String(payload.count || 0);
          count.classList.toggle('hidden', Number(payload.count || 0) === 0);
        }
        if (status) {
          status.textContent = payload.message || 'Reaction updated.';
        }
      } catch (error) {
        showMessage(error instanceof Error ? error.message : 'That reaction could not be saved.', true);
      } finally {
        button.disabled = false;
        button.classList.remove('is-pending');
      }
    });
  });
})();

/**
 * Helpers de toda la app (sin build: se carga directo con <script>).
 *
 *  - Formularios con  data-ajax          → se envían por fetch, muestran errores bajo cada campo y un toast.
 *      data-success="refresh"  → recarga la lista [data-list] de la página sin recargar todo
 *      data-success="redirect" → va a data.redirect que devuelva el servidor
 *  - Botones con      data-open-form="#modal"  data-action="url"  data-method="PUT"  data-values='{json}'
 *      → abren el modal con el formulario listo para crear/editar.
 *  - Botones con      data-patch="url"  data-confirm="¿Seguro?"  → PATCH + refresca la lista.
 */
(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

  // ---------- Toast ----------
  function toast(message, type = 'success') {
    const box = document.getElementById('toasts');
    if (!box || !message) return;
    const el = document.createElement('div');
    el.className = `app-toast app-toast-${type}`;
    el.setAttribute('role', 'status');
    el.textContent = message;
    box.appendChild(el);
    requestAnimationFrame(() => el.classList.add('show'));
    setTimeout(() => {
      el.classList.remove('show');
      setTimeout(() => el.remove(), 300);
    }, 3200);
  }

  // ---------- Petición JSON ----------
  async function request(url, { method = 'POST', body = null } = {}) {
    const res = await fetch(url, {
      method,
      headers: {
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body,
      credentials: 'same-origin',
    });

    let json = {};
    try { json = await res.json(); } catch { /* respuesta sin JSON */ }

    if (res.status === 401) {
      toast(json.message || 'Tu sesión expiró.', 'error');
      setTimeout(() => (window.location.href = '/login'), 1200);
    }
    if (res.status === 419) {
      json.message = 'La página expiró. Recárgala e intenta de nuevo.';
    }
    if (res.status === 403 && !json.message) {
      json.message = 'No tienes permiso para esta acción.';
    }
    return { ok: res.ok, status: res.status, json };
  }

  // ---------- Errores de validación ----------
  function clearErrors(form) {
    form.querySelectorAll('.is-invalid').forEach((i) => i.classList.remove('is-invalid'));
    form.querySelectorAll('.invalid-feedback[data-js]').forEach((d) => d.remove());
  }

  function showErrors(form, errors = {}) {
    let first = null;
    for (const [field, msgs] of Object.entries(errors)) {
      const input = form.querySelector(`[name="${field}"]`);
      if (!input) continue;
      input.classList.add('is-invalid');
      const div = document.createElement('div');
      div.className = 'invalid-feedback';
      div.dataset.js = '1';
      div.textContent = msgs[0];
      (input.closest('.input-group') || input).insertAdjacentElement('afterend', div);
      first ??= input;
    }
    first?.focus();
  }

  // ---------- Refrescar la lista sin recargar la página ----------
  async function refreshList() {
    const current = document.querySelector('[data-list]');
    if (!current) return;
    const res = await fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const html = await res.text();
    const fresh = new DOMParser().parseFromString(html, 'text/html').querySelector('[data-list]');
    if (fresh) current.innerHTML = fresh.innerHTML;
  }

  // ---------- Envío de formularios data-ajax ----------
  document.addEventListener('submit', async (e) => {
    const form = e.target.closest('form[data-ajax]');
    if (!form) return;
    e.preventDefault();

    clearErrors(form);
    const btn = form.querySelector('[type=submit]');
    btn?.setAttribute('disabled', 'disabled');
    btn?.classList.add('is-loading');

    try {
      const { ok, status, json } = await request(form.action, { method: 'POST', body: new FormData(form) });

      if (status === 422 && json.errors) {
        showErrors(form, json.errors);
        toast('Revisa los campos marcados', 'error');
        return;
      }
      if (!ok) {
        toast(json.message || 'Ocurrió un error inesperado', 'error');
        return;
      }

      toast(json.message, 'success');
      const modal = form.closest('.modal');
      if (modal) bootstrap.Modal.getOrCreateInstance(modal).hide();

      const after = form.dataset.success;
      if (after === 'refresh') await refreshList();
      if (after === 'redirect' && json.data?.redirect) window.location.href = json.data.redirect;
      form.dispatchEvent(new CustomEvent('ajax:success', { detail: json }));
    } catch {
      toast('Sin conexión con el servidor', 'error');
    } finally {
      btn?.removeAttribute('disabled');
      btn?.classList.remove('is-loading');
    }
  });

  // ---------- Abrir modal para crear / editar ----------
  document.addEventListener('click', (e) => {
    const trigger = e.target.closest('[data-open-form]');
    if (!trigger) return;

    const modalEl = document.querySelector(trigger.dataset.openForm);
    const form = modalEl.querySelector('form');
    form.reset();
    clearErrors(form);

    form.action = trigger.dataset.action;
    form.querySelector('[name=_method]').value = trigger.dataset.method || 'POST';
    modalEl.querySelector('.modal-title').textContent = trigger.dataset.title || '';

    const values = trigger.dataset.values ? JSON.parse(trigger.dataset.values) : {};
    for (const [name, value] of Object.entries(values)) {
      const input = form.querySelector(`[name="${name}"]`);
      if (input) input.value = value ?? '';
    }

    // campos que solo se muestran al crear o al editar
    const editing = (trigger.dataset.method || 'POST') !== 'POST';
    form.querySelectorAll('[data-only-create]').forEach((el) => el.classList.toggle('d-none', editing));
    form.querySelectorAll('[data-only-edit]').forEach((el) => el.classList.toggle('d-none', !editing));
    form.dispatchEvent(new Event('form:filled'));

    bootstrap.Modal.getOrCreateInstance(modalEl).show();
  });

  // ---------- Acciones rápidas (activar / desactivar) ----------
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-patch]');
    if (!btn) return;
    if (btn.dataset.confirm && !window.confirm(btn.dataset.confirm)) return;

    btn.setAttribute('disabled', 'disabled');
    try {
      const { ok, json } = await request(btn.dataset.patch, { method: 'PATCH' });
      toast(json.message || (ok ? 'Listo' : 'Ocurrió un error'), ok ? 'success' : 'error');
      if (ok) await refreshList();
    } catch {
      toast('Sin conexión con el servidor', 'error');
    } finally {
      btn.removeAttribute('disabled');
    }
  });

  // ---------- PWA ----------
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
  }

  let installEvent = null;
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    installEvent = e;
    document.querySelectorAll('[data-install]').forEach((b) => b.classList.remove('d-none'));
  });
  document.addEventListener('click', async (e) => {
    if (!e.target.closest('[data-install]') || !installEvent) return;
    installEvent.prompt();
    await installEvent.userChoice;
    installEvent = null;
    document.querySelectorAll('[data-install]').forEach((b) => b.classList.add('d-none'));
  });

  window.App = { toast, request, refreshList };
})();

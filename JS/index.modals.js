/**
 * Gestión de modal dinámico para servicios / tarjetas
 * Sección: Servicios + Cards inferiores
 */
(() => {
  const JSON_URL = 'data/services.json';
  const BTN_SELECTOR = '.btn-service';
  const MODAL_ID = 'serviceModal';

  const state = {
    data: {},
    loaded: false,
    loadingPromise: null,
    modal: null,
    currentId: null
  };

  // Cache perezoso de elementos
  const $ = id => document.getElementById(id);
  const els = {
    title: () => $('serviceModalLabel'),
    subtitle: () => $('serviceModalSubtitle'),
    resumen: () => $('serviceModalResumen'),
    features: () => $('serviceModalFeatures'),
    extra: () => $('serviceModalExtra'),
    image: () => $('serviceModalImage'),
    loading: () => $('serviceModalLoading'),
    content: () => $('serviceModalContent'),
    foot: () => $('serviceModalFootNote'),
    cta: () => $('serviceModalCTA'),
    progress: () => $('serviceModalProgress')
  };

  async function loadData() {
    if (state.loaded) return;
    if (state.loadingPromise) return state.loadingPromise;

    state.loadingPromise = (async () => {
      try {
        const res = await fetch(JSON_URL, { cache: 'no-store' });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const arr = await res.json();
        if (Array.isArray(arr)) {
          arr.forEach(o => { if (o && o.id) state.data[o.id] = o; });
        }
      } catch (e) {
        console.error('[services] error cargando JSON:', e);
        state.data = {};
      } finally {
        state.loaded = true;
      }
    })();
    return state.loadingPromise;
  }

  function setLoading(isLoading) {
    const l = els.loading(), c = els.content();
    if (l && c) {
      l.classList.toggle('d-none', !isLoading);
      c.classList.toggle('d-none', isLoading);
      c.setAttribute('aria-busy', isLoading ? 'true' : 'false');
    }
    progress(isLoading ? 10 : 100);
  }

  function progress(pct) {
    const bar = els.progress();
    if (!bar) return;
    requestAnimationFrame(() => { bar.style.width = pct + '%'; });
  }

  function buildFallback(card) {
    if (!card) return {};
    const title = card.querySelector('.card-title')?.textContent?.trim() || '';
    const resumen = card.querySelector('.card-text')?.textContent?.trim() || '';
    const imgEl = card.querySelector('.service-img-container img, img.card-img-top, img');
    const img = imgEl ? imgEl.getAttribute('src') : '';
    return {
      titulo: title,
      resumen,
      imagen: img,
      caracteristicas: [
        'Atención personalizada',
        'Monitoreo y trazabilidad',
        'Optimización de costos'
      ],
      extraHtml:
        `<h6 class="fw-bold mt-3">Descripción ampliada</h6>
         <p class="small mb-2">${resumen || 'Servicio adaptado a tus necesidades operativas.'}</p>
         <p class="small mb-0">Amplía tu solicitud para recibir un análisis especializado.</p>`,
      nota: 'Detalle generado (agrega extraHtml en tu JSON para reemplazar).'
    };
  }

  function merge(raw, fallback) {
    return {
      ...fallback,
      ...raw,
      caracteristicas: (raw.caracteristicas && raw.caracteristicas.length)
        ? raw.caracteristicas
        : fallback.caracteristicas,
      extraHtml: raw.extraHtml || raw.detalleLargo || fallback.extraHtml,
      imagen: raw.imagen || fallback.imagen,
      resumen: raw.resumen || fallback.resumen,
      nota: raw.nota || fallback.nota
    };
  }

  function sanitize(html) {
    // Asumes control del JSON; si necesitas sanear más, aplica whitelist.
    return html;
  }

  function render(id, card) {
    const raw = state.data[id];
    if (!raw && !card) {
      if (els.title()) els.title().textContent = 'Servicio no disponible';
      if (els.resumen()) els.resumen().textContent = 'No se encontró información.';
      if (els.features()) els.features().innerHTML = '';
      if (els.image()) els.image().classList.add('d-none');
      if (els.extra()) els.extra().innerHTML = '';
      if (els.foot()) els.foot().textContent = '';
      if (els.cta()) {
        els.cta().textContent = 'Cerrar';
        els.cta().onclick = () => state.modal?.hide();
        els.cta().classList.add('disabled');
      }
      return;
    }

    const data = merge(raw || {}, buildFallback(card));
    state.currentId = id;

    if (els.title()) els.title().textContent = data.titulo || 'Servicio';
    if (els.subtitle()) els.subtitle().textContent = data.subtitulo || '';
    if (els.resumen()) els.resumen().textContent = data.resumen || '';

    if (els.image()) {
      if (data.imagen) {
        els.image().src = data.imagen;
        els.image().alt = data.titulo || 'Servicio';
        els.image().classList.remove('d-none');
      } else {
        els.image().classList.add('d-none');
      }
    }

    if (els.features()) {
      els.features().innerHTML = (data.caracteristicas || []).map(f =>
        `<li class="mb-1 d-flex">
           <i class="fas fa-check text-success me-2 mt-1"></i><span>${f}</span>
         </li>`).join('') || '<li class="text-muted small">Sin características.</li>';
    }

    if (els.extra()) els.extra().innerHTML = sanitize(data.extraHtml || '');
    if (els.foot()) els.foot().textContent = data.nota || '';
    if (els.cta()) {
      els.cta().textContent = data.cta || 'Continuar';
      els.cta().classList.toggle('disabled', !data.ctaLink);
      els.cta().onclick = () => { if (data.ctaLink) window.location.href = data.ctaLink; };
    }
  }

  async function open(id, card) {
    progress(0);
    setLoading(true);
    await loadData();
    progress(60);
    render(id, card);
    setLoading(false);

    if (!state.modal) {
      const modalEl = $(MODAL_ID);
      if (!modalEl) {
        console.error('Modal no encontrado (id=' + MODAL_ID + ').');
        return;
      }
      if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap JS no cargado.');
        return;
      }
      state.modal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: true });
      modalEl.addEventListener('hidden.bs.modal', () => {
        progress(0);
      });
    }
    state.modal.show();
  }

  // Delegación global
  document.addEventListener('click', e => {
    const btn = e.target.closest(BTN_SELECTOR);
    if (!btn) return;
    e.preventDefault();
    const id = btn.getAttribute('data-service');
    if (!id) return;
    const card = btn.closest('.service-card, .card');
    open(id, card);
  });

  // Deep link (#servicio=ID)
  document.addEventListener('DOMContentLoaded', () => {
    const hash = location.hash.slice(1);
    if (hash.startsWith('servicio=')) {
      const id = hash.split('=')[1];
      const btn = document.querySelector(`${BTN_SELECTOR}[data-service="${id}"]`);
      open(id, btn?.closest('.service-card, .card') || null);
    }
  });
})();
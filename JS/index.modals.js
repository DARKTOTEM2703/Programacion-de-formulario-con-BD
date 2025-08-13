/**
 * Modal dinámico servicios (versión verificada para móvil + escritorio)
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

  const $ = id => document.getElementById(id);
  const els = {
    modalEl: () => $(MODAL_ID),
    title: () => $('serviceModalLabel'),
    subtitle: () => $('serviceModalSubtitle'),
    resumen: () => $('serviceModalResumen'),
    features: () => $('serviceModalFeatures'),
    extra: () => $('serviceModalExtra'),
    image: () => $('serviceModalHeroImg') || $('serviceModalImage'),
    loading: () => $('serviceModalLoading'),
    content: () => $('serviceModalContent'),
    foot: () => $('serviceModalFootNote'),
    cta: () => $('serviceModalCTA'),
    progress: () => $('serviceModalProgress')
  };

  /* ========== DATA ========== */
  async function loadData() {
    if (state.loaded) return;
    if (state.loadingPromise) { await state.loadingPromise; return; }
    state.loadingPromise = (async () => {
      try {
        const r = await fetch(JSON_URL, { cache: 'no-store' });
        if (!r.ok) throw new Error(r.status);
        const arr = await r.json();
        if (Array.isArray(arr)) {
          arr.forEach(o => { if (o && o.id) state.data[o.id] = o; });
        }
      } catch (e) {
        console.error('[services] fallo JSON:', e);
      } finally {
        state.loaded = true;
        state.loadingPromise = null;
      }
    })();
    await state.loadingPromise;
  }

  /* ========== UI HELPERS ========== */
  function progress(p) {
    const bar = els.progress();
    if (!bar) return;
    requestAnimationFrame(() => { bar.style.width = p + '%'; });
  }

  function setLoading(v) {
    const l = els.loading();
    const c = els.content();
    if (l) l.classList.toggle('d-none', !v);
    if (c) {
      c.classList.toggle('d-none', v);
      c.setAttribute('aria-busy', v ? 'true' : 'false');
    }
    progress(v ? 15 : 100);
  }

  function buildFallback(card) {
    if (!card) return {};
    const title = card.querySelector('.card-title')?.textContent?.trim() || '';
    const resumen = card.querySelector('.card-text')?.textContent?.trim() || '';
    const imgEl = card.querySelector('.service-img-container img, img.card-img-top, img');
    return {
      titulo: title,
      resumen,
      imagen: imgEl ? imgEl.src : '',
      caracteristicas: [
        'Atención personalizada',
        'Monitoreo y trazabilidad',
        'Optimización de costos'
      ],
      extraHtml: `<h6 class="fw-bold mt-3">Descripción</h6>
        <p class="small mb-2">${resumen || 'Servicio adaptado a tus necesidades.'}</p>
        <p class="small mb-0">Amplía tu JSON para más detalle real.</p>`,
      nota: 'Detalle generado automáticamente.'
    };
  }

  function merge(raw, fb) {
    return {
      ...fb,
      ...raw,
      caracteristicas: raw?.caracteristicas?.length ? raw.caracteristicas : fb.caracteristicas,
      extraHtml: raw.extraHtml || raw.detalleLargo || fb.extraHtml,
      imagen: raw.imagen || fb.imagen,
      resumen: raw.resumen || fb.resumen,
      nota: raw.nota || fb.nota
    };
  }

  function render(id, card) {
    const raw = state.data[id];
    if (!raw && !card) {
      if (els.title()) els.title().textContent = 'Servicio no disponible';
      if (els.resumen()) els.resumen().textContent = 'No hay información.';
      return;
    }
    const data = merge(raw || {}, buildFallback(card));
    state.currentId = id;

    if (els.title()) els.title().textContent = data.titulo || 'Servicio';
    if (els.subtitle()) els.subtitle().textContent = data.subtitulo || '';
    if (els.resumen()) els.resumen().textContent = data.resumen || '';

    const img = els.image();
    if (img) {
      if (data.imagen) {
        img.src = data.imagen;
        img.alt = data.titulo || 'Servicio';
        img.classList.remove('d-none');
      } else {
        img.classList.add('d-none');
      }
    }

    if (els.features()) {
      els.features().innerHTML = (data.caracteristicas || [])
        .map(c => `<li class="mb-1 d-flex"><i class="fas fa-check text-success me-2 mt-1"></i><span>${c}</span></li>`)
        .join('') || '<li class="text-muted small">Sin características.</li>';
    }

    if (els.extra()) els.extra().innerHTML = data.extraHtml || '';
    if (els.foot()) els.foot().textContent = data.nota || '';
    if (els.cta()) {
      els.cta().textContent = data.cta || 'Continuar';
      els.cta().classList.toggle('disabled', !data.ctaLink);
      els.cta().onclick = () => { if (data.ctaLink) window.location.href = data.ctaLink; };
    }
  }

  /* ========== OPEN MODAL ========== */
  async function open(id, card) {
    progress(0);
    setLoading(true);
    await loadData();
    progress(55);
    render(id, card);
    setLoading(false);

    if (!state.modal) {
      const el = els.modalEl();
      if (!el) { console.error('No se encontró el modal #' + MODAL_ID); return; }
      if (typeof bootstrap === 'undefined') { console.error('Bootstrap no cargado'); return; }

      // Habilitar cierre por backdrop en móvil (quitamos 'static')
      state.modal = new bootstrap.Modal(el, { backdrop: true, keyboard: true });

      el.addEventListener('shown.bs.modal', () => document.body.classList.add('modal-service-open'));
      el.addEventListener('hidden.bs.modal', () => {
        document.body.classList.remove('modal-service-open');
        progress(0);
      });

      // Cierra con gesto swipe-down (móvil)
      let touchStartY = null;
      el.addEventListener('touchstart', ev => {
        if (ev.touches.length === 1) touchStartY = ev.touches[0].clientY;
      }, { passive: true });
      el.addEventListener('touchmove', ev => {
        if (touchStartY === null) return;
        const delta = ev.touches[0].clientY - touchStartY;
        if (delta > 140) { // deslizó hacia abajo
          touchStartY = null;
          state.modal.hide();
        }
      }, { passive: true });
      el.addEventListener('touchend', () => { touchStartY = null; });
    }
    state.modal.show();
  }

  /* ========== EVENT DELEGATION (click + teclado accesible) ========== */
  function isActivator(el) {
    return el && el.matches(BTN_SELECTOR);
  }

  document.addEventListener('click', e => {
    const btn = e.target.closest(BTN_SELECTOR);
    if (!btn) return;
    e.preventDefault();
    const id = btn.getAttribute('data-service');
    if (!id) return;
    open(id, btn.closest('.service-card, .card'));
  }, { passive: true });

  // Activación con Enter / Space para accesibilidad
  document.addEventListener('keydown', e => {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    const btn = e.target;
    if (isActivator(btn)) {
      e.preventDefault();
      const id = btn.getAttribute('data-service');
      if (id) open(id, btn.closest('.service-card, .card'));
    }
  });

  /* ========== DEEP LINK (#servicio=ID) ========== */
  document.addEventListener('DOMContentLoaded', () => {
    const hash = location.hash.slice(1);
    if (hash.startsWith('servicio=')) {
      const id = hash.split('=')[1];
      const btn = document.querySelector(`${BTN_SELECTOR}[data-service="${id}"]`);
      open(id, btn?.closest('.service-card, .card') || null);
    }
  });

})();
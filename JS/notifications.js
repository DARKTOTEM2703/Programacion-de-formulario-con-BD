// Requiere: window.BASE_URL definido (puede inyectarse en PHP layout/header)
(function(){
  if (typeof window === 'undefined') return;
  const BASE = window.BASE_URL ? window.BASE_URL.replace(/\/$/,'') : '';
  const API_LIST = (BASE ? BASE : '') + '/api/notifications.php';
  const API_MARK = (BASE ? BASE : '') + '/api/notifications_mark_read.php';
  const POLL_MS = 6000;

  const MOCK = true;
  const now = Date.now();
  const MOCK_DATA = [
    { id: 201, usuario_id: 1, tipo: 'envio_status', titulo: 'Paquete recibido en bodega', mensaje: 'Tu paquete #AZ1234 fue recibido en bodega.', enlace: BASE + '/php/tracking.php?tracking=AZ1234', leida: 0, created_at: new Date(now - 30*1000).toISOString() },
    { id: 202, usuario_id: 1, tipo: 'envio_status', titulo: 'En ruta', mensaje: 'El repartidor sale con tu paquete #AZ1234.', enlace: BASE + '/php/tracking.php?tracking=AZ1234', leida: 0, created_at: new Date(now - 2*60*1000).toISOString() },
    { id: 203, usuario_id: 1, tipo: 'soporte', titulo: 'Soporte respondió', mensaje: 'Respuesta a tu ticket #567: revisa los detalles.', enlace: BASE + '/php/support.php?id=567', leida: 0, created_at: new Date(now - 8*60*1000).toISOString() },
    { id: 204, usuario_id: 1, tipo: 'factura', titulo: 'Factura lista', mensaje: 'La factura F-890 ya está disponible.', enlace: BASE + '/php/facturas.php', leida: 1, created_at: new Date(now - 30*60*1000).toISOString() },
    { id: 205, usuario_id: 1, tipo: 'promo', titulo: 'Oferta especial', mensaje: '10% de descuento en envíos grandes esta semana.', enlace: BASE + '/php/promos.php', leida: 0, created_at: new Date(now - 2*3600*1000).toISOString() },
    { id: 206, usuario_id: 1, tipo: 'envio_status', titulo: 'Entrega programada', mensaje: 'Tu entrega #AZ999 se programó para mañana.', enlace: BASE + '/php/tracking.php?tracking=AZ999', leida: 0, created_at: new Date(now - 6*3600*1000).toISOString() },
    { id: 207, usuario_id: 1, tipo: 'alerta', titulo: 'Problema con dirección', mensaje: 'Hubo un problema con la dirección de tu envío #AZ777.', enlace: BASE + '/php/tracking.php?tracking=AZ777', leida: 1, created_at: new Date(now - 24*3600*1000).toISOString() },
    { id: 208, usuario_id: 1, tipo: 'recordatorio', titulo: 'Pago pendiente', mensaje: 'Tienes un pago pendiente en la factura F-901.', enlace: BASE + '/php/payment.php', leida: 0, created_at: new Date(now - 48*3600*1000).toISOString() }
  ];

  let lastSeenIds = new Set();
  let badge = null;
  let dropdown = null;
  let toastContainer = null;
  const bellId = 'notifBell';

  function el(id){ return document.getElementById(id); }
  function formatTime(dt){ try { return new Date(dt).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'}); } catch(e){ return ''; } }
  function escapeHtml(s){ return String(s || '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m])); }

  function normalizeNotifications(arr){
    return (arr || []).map(n => ({
      id: n.id,
      tipo: n.tipo || n.type || '',
      asunto: n.titulo || n.asunto || '',
      contenido: n.mensaje || n.contenido || '',
      link: n.enlace || n.link || '',
      status: (n.leida === 1 || n.status === 'leido' || n.status === 'leído') ? 'leido' : 'pendiente',
      created_at: n.created_at || n.createdAt || new Date().toISOString()
    }));
  }

  // animate badge (pop)
  function popBadge(){
    badge = badge || document.querySelector('.notification-badge');
    if (!badge) return;
    badge.classList.remove('pop');
    // force reflow to restart animation
    void badge.offsetWidth;
    badge.classList.add('pop');
    setTimeout(()=> badge.classList.remove('pop'), 1100);
  }

  async function fetchNotifs(showToastsForNew = true){
    try{
      if (MOCK) {
        const json = { ok: true, notifications: normalizeNotifications(MOCK_DATA) };
        renderBadge(json.notifications);
        renderDropdownList(json.notifications);
        if (showToastsForNew) {
          json.notifications.forEach(n => {
            if (!lastSeenIds.has(n.id) && n.status === 'pendiente') {
              lastSeenIds.add(n.id);
              showToast(n);
              // marcar visual nuevo en listado si está abierto
              markItemAsNew(n.id);
              popBadge();
            }
          });
        }
        json.notifications.forEach(n => lastSeenIds.add(n.id));
        return;
      }
      const res = await fetch(API_LIST, { credentials: 'include' });
      if (!res.ok) return;
      const json = await res.json();
      if (!json.ok) return;
      const items = normalizeNotifications(json.notifications);
      renderBadge(items);
      renderDropdownList(items);
      if (showToastsForNew) {
        items.forEach(n => {
          if (!lastSeenIds.has(n.id) && n.status === 'pendiente') {
            lastSeenIds.add(n.id);
            showToast(n);
            markItemAsNew(n.id);
            popBadge();
          }
        });
      }
      items.forEach(n => lastSeenIds.add(n.id));
    }catch(e){}
  }

  function renderBadge(notifs){
    badge = badge || document.querySelector('.notification-badge');
    if(!badge) return;
    const count = notifs.filter(n => n.status === 'pendiente').length;
    badge.textContent = count > 0 ? String(count) : '';
    badge.style.display = count > 0 ? 'inline-block' : 'none';
  }

  function ensureDropdown(){
    if (dropdown) return dropdown;
    dropdown = document.createElement('div');
    dropdown.id = 'notif-dropdown';
    dropdown.style.position = 'absolute';
    dropdown.style.width = '340px';
    dropdown.style.maxHeight = '60vh';
    dropdown.style.overflow = 'auto';
    dropdown.style.borderRadius = '10px';
    dropdown.style.display = 'none';
    dropdown.style.zIndex = 99999;
    dropdown.style.backdropFilter = 'blur(6px)';
    dropdown.innerHTML = '<div id="notif-header" style="padding:10px 12px;border-bottom:1px solid rgba(0,0,0,0.04);display:flex;justify-content:space-between;align-items:center"><strong>Notificaciones</strong><button id="markAllReadBtn" style="background:transparent;border:0;cursor:pointer">Marcar todas</button></div><div id="notif-list"></div>';
    document.body.appendChild(dropdown);
    return dropdown;
  }

  function markItemAsNew(id){
    const d = ensureDropdown();
    const item = d.querySelector(`.notif-item[data-id="${id}"]`);
    if (item) {
      item.classList.add('new');
      // hacer scroll al top del dropdown para mostrar el nuevo
      d.scrollTo({ top: 0, behavior: 'smooth' });
      // quitar clase "new" después de un tiempo
      setTimeout(()=> item.classList.remove('new'), 2500);
    }
  }

  function renderDropdownList(notifs){
    const d = ensureDropdown();
    const list = d.querySelector('#notif-list');
    if (!Array.isArray(notifs) || notifs.length === 0) {
      list.innerHTML = `<div style="padding:14px;color:var(--notif-msg)">Sin notificaciones recientes</div>`;
      return;
    }
    const items = notifs.map(n => {
      const unread = n.status === 'pendiente';
      const contenidoShort = (n.contenido || '').slice(0, 140);
      return `
        <a href="#" data-id="${n.id}" data-link="${escapeHtml(n.link || '')}" class="notif-item ${unread ? 'unread' : 'read'}">
          <div class="item-left"></div>
          <div class="item-body">
            <div class="item-title">${escapeHtml(n.asunto)}</div>
            <div class="item-msg">${escapeHtml(contenidoShort)}</div>
            <div class="item-time">${formatTime(n.created_at)}</div>
          </div>
        </a>
      `;
    }).join('');
    list.innerHTML = items;

    // wire clicks (mantiene comportamiento previo)
    list.querySelectorAll('.notif-item').forEach(a => {
      a.addEventListener('click', async function(ev){
        ev.preventDefault();
        const id = parseInt(this.dataset.id || 0, 10);
        const link = this.dataset.link || '';
        if (id && !MOCK) await markRead(id);
        if (link) location.href = link;
        else this.remove();
      });
    });
    const btn = d.querySelector('#markAllReadBtn');
    if(btn) btn.addEventListener('click', async function(ev){
      ev.stopPropagation();
      if (!MOCK) await markAllRead();
      d.style.display = 'none';
      if (badge) badge.textContent = '';
    });
  }

  async function markRead(id){
    try{
      await fetch(API_MARK, {
        method: 'POST',
        credentials: 'include',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({id})
      });
    }catch(e){}
  }

  async function markAllRead(){
    try{
      await fetch(API_MARK, {
        method: 'POST',
        credentials: 'include',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({all:true})
      });
    }catch(e){}
  }

  function showToast(n){
    toastContainer = toastContainer || (function(){
      const c = document.createElement('div');
      c.id = 'notif-toast-container';
      c.style.position = 'fixed';
      c.style.top = '1rem';
      c.style.right = '1rem';
      c.style.zIndex = 999999;
      c.style.display = 'flex';
      c.style.flexDirection = 'column';
      c.style.gap = '0.6rem';
      document.body.appendChild(c);
      return c;
    })();

    const elmt = document.createElement('div');
    // elegir clase de acento según tipo
    let accentClass = 'accent-blue';
    if (n.tipo && /factura|invoice/i.test(n.tipo)) accentClass = 'accent-green';
    if (n.tipo && /soporte|support/i.test(n.tipo)) accentClass = 'accent-gold';
    if (n.tipo && /alerta|error|warning/i.test(n.tipo)) accentClass = 'accent-red';

    elmt.className = 'notif-toast ' + accentClass;
    elmt.innerHTML = `
      <div c
      lass="nt-body">
        <div class="nt-title">${escapeHtml(n.asunto)}</div>
        <div class="nt-msg">${escapeHtml(n.contenido)}</div>
      </div>
      <div class="nt-meta">
        <div class="nt-time">${formatTime(n.created_at)}</div>
        <button class="nt-close" aria-label="Cerrar">&times;</button>
      </div>
    `;

    // click en tarjeta: marcar y navegar
    elmt.addEventListener('click', async (ev)=>{
      // si se pulsa el botón cerrar, se maneja aparte
      if (ev.target && ev.target.classList && ev.target.classList.contains('nt-close')) return;
      if (n.id && !MOCK) await markRead(n.id);
      if (n.link) window.location.href = n.link;
      elmt.remove();
    });

    // cerrar al pulsar la X
    elmt.querySelector('.nt-close').addEventListener('click', (ev)=>{
      ev.stopPropagation();
      elmt.remove();
    });

    toastContainer.prepend(elmt);
    popBadge();
    // auto dismiss
    setTimeout(()=>{ try{ elmt.remove(); }catch(e){} }, 12000);
  }

  function positionDropdown(){
    const bell = el(bellId);
    const d = ensureDropdown();
    if (!bell || !d) return;
    const rect = bell.getBoundingClientRect();
    const top = rect.bottom + window.scrollY + 8;
    if (window.innerWidth <= 480) {
      d.style.width = '92%';
      d.style.left = '4%';
      d.style.right = 'auto';
      d.style.top = (rect.bottom + window.scrollY + 6) + 'px';
      return;
    }
    const rightOffset = Math.max(8, window.innerWidth - rect.right - 8);
    d.style.top = top + 'px';
    d.style.right = rightOffset + 'px';
    d.style.left = 'auto';
    d.style.width = '340px';
  }

  function initBellToggle(){
    const bell = el(bellId);
    if (!bell) return;
    bell.addEventListener('click', function(ev){
      ev.stopPropagation();
      const d = ensureDropdown();
      if (d.style.display === 'block') {
        d.style.display = 'none';
        return;
      }
      fetchNotifs(false).then(()=>{ positionDropdown(); const dd = ensureDropdown(); dd.style.display = 'block'; });
    });
    document.addEventListener('click', function(e){
      const d = document.getElementById('notif-dropdown');
      const bellEl = el(bellId);
      if (!d) return;
      if (!d.contains(e.target) && !(bellEl && bellEl.contains(e.target))) d.style.display = 'none';
    });
    window.addEventListener('resize', ()=>{ if (dropdown && dropdown.style.display === 'block') positionDropdown(); });
    window.addEventListener('scroll', ()=>{ if (dropdown && dropdown.style.display === 'block') positionDropdown(); });
  }

  // función global para simular llegada de notificación nueva (útil en consola)
  window.simulateNotification = function(payload){
    const nextId = Math.floor(Math.random()*900000) + 300;
    const nowIso = new Date().toISOString();
    const item = {
      id: nextId,
      usuario_id: 1,
      tipo: payload.tipo || 'test',
      titulo: payload.titulo || 'Notificación de prueba',
      mensaje: payload.mensaje || 'Mensaje de prueba',
      enlace: payload.enlace || '',
      leida: 0,
      created_at: nowIso
    };
    // insertar al inicio del mock (si MOCK) y forzar render + toast
    if (MOCK) {
      MOCK_DATA.unshift(item);
      fetchNotifs(true);
      return;
    }
    // en producción puedes llamar a un endpoint que cree la notificación
    fetch(payload.pushEndpoint || (BASE + '/api/notifications_test_push.php'), {
      method: 'POST',
      credentials: 'include',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify(item)
    }).then(()=> fetchNotifs(true)).catch(()=> fetchNotifs(true));
  };

  // inicial
  initBellToggle();
  fetchNotifs(false);
  setInterval(()=>fetchNotifs(true), POLL_MS);

})();
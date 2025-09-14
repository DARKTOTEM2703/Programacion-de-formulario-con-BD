// Requiere: window.BASE_URL definido (puede inyectarse en PHP layout/header)
(function(){
  if (typeof window === 'undefined') return;
  const BASE = window.BASE_URL ? window.BASE_URL.replace(/\/$/,'') : '';
  const API_LIST = (BASE ? BASE : '') + '/api/notifications.php';
  const API_MARK = (BASE ? BASE : '') + '/api/notifications_mark_read.php';
  const POLL_MS = 6000;

  // Modo de prueba: datos mock en cliente
  const MOCK = true; // poner false para usar el backend

  // 8 notificaciones "dumb" de ejemplo
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

  function formatTime(dt){
    try { return new Date(dt).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'}); } catch(e){ return ''; }
  }

  function escapeHtml(s){ return String(s || '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m])); }

  // Mapea estructura DB -> UI (soporta 'titulo'/'mensaje' o 'asunto'/'contenido')
  function normalizeNotifications(arr){
    return (arr || []).map(n => {
      return {
        id: n.id,
        tipo: n.tipo || n.type || '',
        asunto: n.titulo || n.asunto || '',
        contenido: n.mensaje || n.contenido || '',
        link: n.enlace || n.link || '',
        status: (n.leida === 1 || n.status === 'leido' || n.status === 'leído') ? 'leido' : 'pendiente',
        created_at: n.created_at || n.createdAt || new Date().toISOString()
      };
    });
  }

  // Theme handling: detecta preferencia y aplica estilos al dropdown y toasts
  const mq = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
  let currentTheme = mq && mq.matches ? 'dark' : 'light';

  function applyThemeStylesToDropdown(d){
    if (!d) return;
    if (currentTheme === 'dark') {
      d.style.background = 'linear-gradient(180deg, rgba(18,24,33,0.98), rgba(12,16,22,0.98))';
      d.style.color = '#e6eef8';
      d.style.boxShadow = '0 10px 30px rgba(2,6,23,0.6)';
      d.style.border = '1px solid rgba(255,255,255,0.04)';
    } else {
      d.style.background = '#ffffff';
      d.style.color = '#222';
      d.style.boxShadow = '0 8px 24px rgba(0,0,0,0.08)';
      d.style.border = '1px solid rgba(0,0,0,0.06)';
    }
    // update inner header colors if exists
    const hdr = d.querySelector('#notif-header strong');
    if (hdr) hdr.style.color = currentTheme === 'dark' ? '#cfe6ff' : '#003366';
    const markBtn = d.querySelector('#markAllReadBtn');
    if (markBtn) markBtn.style.color = currentTheme === 'dark' ? '#9fc5ff' : '#0d6efd';
  }

  function applyThemeStylesToToast(elmt){
    if (!elmt) return;
    if (currentTheme === 'dark') {
      elmt.style.background = 'linear-gradient(180deg,#0f1724,#0b1220)';
      elmt.style.color = '#eaf4ff';
      elmt.style.boxShadow = '0 8px 30px rgba(2,6,23,0.6)';
    } else {
      elmt.style.background = '#fff';
      elmt.style.color = '#111';
      elmt.style.boxShadow = '0 8px 20px rgba(0,0,0,0.08)';
    }
  }

  if (mq && typeof mq.addEventListener === 'function') {
    mq.addEventListener('change', (ev) => {
      currentTheme = ev.matches ? 'dark' : 'light';
      if (dropdown) applyThemeStylesToDropdown(dropdown);
    });
  } else if (mq && typeof mq.addListener === 'function') {
    mq.addListener((ev) => {
      currentTheme = ev.matches ? 'dark' : 'light';
      if (dropdown) applyThemeStylesToDropdown(dropdown);
    });
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

    // inner structure
    dropdown.innerHTML = '<div id="notif-header" style="padding:10px 12px;border-bottom:1px solid rgba(0,0,0,0.04);display:flex;justify-content:space-between;align-items:center"><strong>Notificaciones</strong><button id="markAllReadBtn" style="background:transparent;border:0;cursor:pointer">Marcar todas</button></div><div id="notif-list"></div>';
    document.body.appendChild(dropdown);
    applyThemeStylesToDropdown(dropdown);
    return dropdown;
  }

  function renderDropdownList(notifs){
    const d = ensureDropdown();
    const list = d.querySelector('#notif-list');
    if (!Array.isArray(notifs) || notifs.length === 0) {
      list.innerHTML = `<div style="padding:14px;color:${currentTheme === 'dark' ? '#9aa3b2' : '#666'}">Sin notificaciones recientes</div>`;
      return;
    }
    const items = notifs.map(n => {
      const unread = n.status === 'pendiente';
      const contenidoShort = (n.contenido || '').slice(0, 120);
      return `
        <a href="#" data-id="${n.id}" data-link="${escapeHtml(n.link || '')}" class="notif-item" style="display:block;padding:12px;border-bottom:1px solid rgba(0,0,0,0.04);text-decoration:none;color:inherit">
          <div style="display:flex;gap:10px;align-items:flex-start">
            <div style="flex:1">
              <div style="font-weight:700;color:${unread ? (currentTheme === 'dark' ? '#cfe6ff' : '#0d6efd') : (currentTheme === 'dark' ? '#d7e4f6' : '#333')}">${escapeHtml(n.asunto)}</div>
              <div style="font-size:0.92rem;color:${currentTheme === 'dark' ? '#b8c6d9' : '#666'};margin-top:6px;line-height:1.2">${escapeHtml(contenidoShort)}</div>
              <div style="font-size:0.78rem;color:${currentTheme === 'dark' ? '#94a6bb' : '#888'};margin-top:8px">${formatTime(n.created_at)}</div>
            </div>
            <div style="margin-left:10px;display:flex;align-items:flex-start;flex-direction:column;gap:6px">
              ${unread ? `<span style="background:${currentTheme === 'dark' ? '#2b6df6' : '#0d6efd'};color:#fff;padding:4px 6px;border-radius:6px;font-size:0.72rem">Nuevo</span>` : `<span style="font-size:0.76rem;color:${currentTheme === 'dark' ? '#6f8196' : '#6c757d'}">Leído</span>`}
            </div>
          </div>
        </a>
      `;
    }).join('');
    list.innerHTML = items;

    // wire clicks
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
    elmt.style.minWidth = '260px';
    elmt.style.padding = '12px 14px';
    elmt.style.borderRadius = '8px';
    elmt.style.cursor = 'pointer';
    applyThemeStylesToToast(elmt);
    elmt.innerHTML = `<div style="font-weight:700;color:${currentTheme === 'dark' ? '#cfe6ff' : '#0b2a66'}">${escapeHtml(n.asunto)}</div><div style="color:${currentTheme === 'dark' ? '#c0d6ea' : '#333'};margin-top:6px;font-size:0.95rem">${escapeHtml(n.contenido)}</div><div style="font-size:0.8rem;color:${currentTheme === 'dark' ? '#9fb3cc' : '#666'};margin-top:8px">${formatTime(n.created_at)}</div>`;
    elmt.addEventListener('click', async ()=>{
      if (n.id && !MOCK) await markRead(n.id);
      if (n.link) location.href = n.link;
      elmt.remove();
    });
    toastContainer.prepend(elmt);
    setTimeout(()=>elmt.remove(), 12000);
  }

  // posiciona dropdown relativo al botón del timbre (adaptativo)
  function positionDropdown(){
    const bell = el(bellId);
    const d = ensureDropdown();
    if (!bell || !d) return;
    const rect = bell.getBoundingClientRect();
    const top = rect.bottom + window.scrollY + 8;

    // si pantalla pequeña, centrar y ajustar ancho al 92%
    if (window.innerWidth <= 480) {
      d.style.width = '92%';
      d.style.left = '4%';
      d.style.right = 'auto';
      d.style.top = (rect.bottom + window.scrollY + 6) + 'px';
      return;
    }

    // alinear a la derecha del botón en pantallas grandes
    const rightOffset = Math.max(8, window.innerWidth - rect.right - 8);
    d.style.top = top + 'px';
    d.style.right = rightOffset + 'px';
    d.style.left = 'auto';
    d.style.width = '340px';
  }

  // toggle al hacer click en la campana
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
      // actualizar y mostrar
      fetchNotifs(false).then(()=>{ positionDropdown(); const dd = ensureDropdown(); dd.style.display = 'block'; });
    });
    // cerrar al click fuera
    document.addEventListener('click', function(e){
      const d = document.getElementById('notif-dropdown');
      const bellEl = el(bellId);
      if (!d) return;
      if (!d.contains(e.target) && !(bellEl && bellEl.contains(e.target))) d.style.display = 'none';
    });
    // reposicionar en resize/scroll
    window.addEventListener('resize', ()=>{ if (dropdown && dropdown.style.display === 'block') positionDropdown(); });
    window.addEventListener('scroll', ()=>{ if (dropdown && dropdown.style.display === 'block') positionDropdown(); });
  }

  // inicial + polling
  initBellToggle();
  fetchNotifs(false);
  setInterval(()=>fetchNotifs(true), POLL_MS);

})();
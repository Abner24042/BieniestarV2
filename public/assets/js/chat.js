/**
 * BIENIESTAR - Sistema de Chat
 * Modo pro:    panel dos columnas en /profesional
 * Modo usuario: FAB + drawer en /dashboard
 */

let chatConvActiva  = null;   // { id, nombre, otroId, pendingInit? }
let chatLastMsgId   = 0;
let chatPollingInt  = null;
let chatBadgeInt    = null;
let chatConvInt     = null;
let chatMiId        = null;
let chatEsPro       = false;

/* ─────────────────────────────────────────────────────────────────────────
   INICIALIZACIÓN
───────────────────────────────────────────────────────────────────────── */

function chatInitPro(userId) {
    chatMiId  = userId;
    chatEsPro = true;
    chatCargarConversaciones();
    chatBadgeInt = setInterval(chatActualizarBadgeGlobal, 5000);
    chatActualizarBadgeGlobal();
    // Polling automático de lista de conversaciones (nuevas conversaciones, badges)
    if (document.getElementById('chatConvList')) {
        chatConvInt = setInterval(chatCargarConversaciones, 5000);
    }
}

function chatInitUser(userId) {
    chatMiId  = userId;
    chatEsPro = false;
    chatBadgeInt = setInterval(chatActualizarBadgeGlobal, 5000);
    chatActualizarBadgeGlobal();
    // Polling automático de lista de conversaciones (nuevas conversaciones, badges)
    if (document.getElementById('chatConvList')) {
        chatConvInt = setInterval(chatCargarConversaciones, 5000);
    }
}

/* ─────────────────────────────────────────────────────────────────────────
   CONVERSACIONES
───────────────────────────────────────────────────────────────────────── */

async function chatCargarConversaciones() {
    try {
        const res  = await fetch(API_URL + '/chat/conversaciones');
        const data = await res.json();
        if (!data.success) return;
        chatRenderConvList(data.conversaciones, 'chatConvList');
    } catch (e) {}
}

function chatRenderConvList(convs, listId) {
    const lista = document.getElementById(listId);
    if (!lista) return;

    if (!convs.length) {
        lista.innerHTML = '<div class="chat-empty-state">Sin conversaciones aún.</div>';
        return;
    }

    lista.innerHTML = convs.map(c => {
        const inicial  = (c.otro_nombre || '?').charAt(0).toUpperCase();
        const preview  = c.ultimo_contenido ? chatTruncar(c.ultimo_contenido, 34) : 'Sin mensajes';
        const badge    = c.no_leidos > 0 ? `<span class="chat-conv-badge">${c.no_leidos}</span>` : '';
        const active   = chatConvActiva?.id === c.id ? 'active' : '';
        const clickFn  = listId === 'chatConvList'
            ? `chatAbrirConv(${c.id},'${chatEscAttr(c.otro_nombre)}',${c.otro_id})`
            : `chatDrawerAbrirConv(${c.id},'${chatEscAttr(c.otro_nombre)}',${c.otro_id})`;
        return `
        <div class="chat-conv-item ${active}" onclick="${clickFn}" id="chatConvItem_${c.id}">
            <div class="chat-conv-avatar">${inicial}</div>
            <div class="chat-conv-info">
                <div class="chat-conv-name">${chatEsc(c.otro_nombre)}</div>
                <div class="chat-conv-preview">${chatEsc(preview)}</div>
            </div>
            ${badge}
        </div>`;
    }).join('');
}

/* ─────────────────────────────────────────────────────────────────────────
   PANEL PROFESIONAL — abrir conversación
───────────────────────────────────────────────────────────────────────── */

async function chatAbrirConv(convId, nombre, otroId) {
    chatConvActiva = { id: convId, nombre, otroId };
    chatLastMsgId  = 0;

    const header = document.getElementById('chatMainHeader');
    if (header) header.innerHTML =
        `<span style="flex:1;">${chatEsc(nombre)}</span>
         <button class="chat-header-del-btn" onclick="chatEliminarChat()">Borrar chat</button>`;

    const msgs = document.getElementById('chatMessages');
    if (msgs) msgs.innerHTML = '';

    document.getElementById('chatInputArea').style.display = 'flex';

    // Mark active in list
    document.querySelectorAll('.chat-conv-item').forEach(el => el.classList.remove('active'));
    const item = document.getElementById('chatConvItem_' + convId);
    if (item) item.classList.add('active');

    await chatCargarMensajes('chatMessages', true);

    fetch(API_URL + '/chat/marcar-leido', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ conversacion_id: convId })
    });

    chatReiniciarPolling(3000, 'chatMessages');
    chatActualizarBadgeGlobal();
}

/* Modal: nuevo chat (profesional inicia) */
async function chatAbrirModalNuevo() {
    const modal = document.getElementById('modalNuevoChat');
    if (!modal) return;

    const sel = document.getElementById('chatNuevoUsuario');
    if (sel && sel.options.length <= 1) {
        try {
            const res  = await fetch(API_URL + '/chat/usuarios-disponibles');
            const data = await res.json();
            if (data.success) {
                sel.innerHTML = '<option value="">— Selecciona un destinatario —</option>' +
                    data.usuarios.map(u => {
                        const extra = u.rol ? ` [${chatEsc(u.rol)}]` : '';
                        return `<option value="${u.id}" data-nombre="${chatEscAttr(u.nombre)}">${chatEsc(u.nombre)}${extra} (${chatEsc(u.correo)})</option>`;
                    }).join('');
            }
        } catch (e) {}
    }
    modal.style.display = 'flex';
}

function chatIniciarDesdeModal() {
    const sel  = document.getElementById('chatNuevoUsuario');
    const opt  = sel?.options[sel.selectedIndex];
    if (!sel?.value) { alert('Selecciona un usuario'); return; }

    const destinatarioId = parseInt(sel.value);
    const nombre = opt?.dataset?.nombre || 'Usuario';

    document.getElementById('modalNuevoChat').style.display = 'none';

    // Set as pending: conversation created on first send
    chatConvActiva = { id: null, nombre, otroId: destinatarioId, pendingInit: true };
    chatLastMsgId  = 0;

    const header = document.getElementById('chatMainHeader');
    if (header) header.textContent = nombre;

    const msgs = document.getElementById('chatMessages');
    if (msgs) msgs.innerHTML = '';

    document.getElementById('chatInputArea').style.display = 'flex';
    document.getElementById('chatInput')?.focus();

    document.querySelectorAll('.chat-conv-item').forEach(el => el.classList.remove('active'));
}

/* ─────────────────────────────────────────────────────────────────────────
   ADJUNTAR ARCHIVO
───────────────────────────────────────────────────────────────────────── */

function chatTriggerArchivo() {
    const input = document.getElementById('chatFileInput');
    if (input) input.click();
}

async function chatEnviarArchivo(input) {
    const file = input?.files?.[0];
    if (!file || !chatConvActiva) return;

    const maxMB = 10;
    if (file.size > maxMB * 1024 * 1024) {
        chatShowToast(`El archivo excede el límite de ${maxMB} MB`, 'error');
        input.value = '';
        return;
    }

    const otroId = chatConvActiva.otroId;
    if (!otroId) { chatShowToast('Selecciona una conversación primero', 'error'); input.value = ''; return; }

    const formData = new FormData();
    formData.append('archivo', file);
    formData.append('destinatario_id', otroId);

    // Show sending indicator
    const btn = document.querySelector('.chat-attach-btn');
    if (btn) { btn.disabled = true; btn.style.opacity = '0.5'; }

    try {
        const res  = await fetch(API_URL + '/chat/subir-archivo', { method: 'POST', body: formData });
        const data = await res.json();
        if (!data.success) {
            chatShowToast(data.message || 'Error al enviar archivo', 'error');
        } else {
            if (chatConvActiva.pendingInit) {
                chatConvActiva.id          = data.conversacion_id;
                chatConvActiva.pendingInit = false;
                chatLastMsgId = 0;
                chatCargarConversaciones();
                chatReiniciarPolling(3000, 'chatMessages');
            }
            await chatCargarMensajes('chatMessages', true);
            if (chatEsPro) chatCargarConversaciones();
        }
    } catch (e) {
        chatShowToast('Error de conexión', 'error');
    } finally {
        input.value = '';
        if (btn) { btn.disabled = false; btn.style.opacity = ''; }
    }
}

/* ─────────────────────────────────────────────────────────────────────────
   ENVIAR (panel profesional)
───────────────────────────────────────────────────────────────────────── */

async function chatEnviar() {
    const input    = document.getElementById('chatInput');
    const contenido = input?.value.trim();
    if (!contenido || !chatConvActiva) return;

    input.value = '';
    input.style.height = 'auto';

    if (chatConvActiva.pendingInit) {
        // Primera vez: el POST crea la conversación
        try {
            const res  = await fetch(API_URL + '/chat/enviar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ destinatario_id: chatConvActiva.otroId, contenido })
            });
            const data = await res.json();
            if (!data.success) { chatShowToast(data.message || 'Error al enviar', 'error'); return; }
            chatConvActiva.id          = data.conversacion_id;
            chatConvActiva.pendingInit = false;
            chatLastMsgId = 0;
            await chatCargarMensajes('chatMessages', true);
            chatCargarConversaciones();
            chatReiniciarPolling(3000, 'chatMessages');
        } catch (e) {}
        return;
    }

    try {
        const res  = await fetch(API_URL + '/chat/enviar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ destinatario_id: chatConvActiva.otroId, contenido })
        });
        const data = await res.json();
        if (data.success) await chatCargarMensajes('chatMessages', true);
    } catch (e) {}
}

/* ─────────────────────────────────────────────────────────────────────────
   DRAWER (dashboard usuario)
───────────────────────────────────────────────────────────────────────── */

function chatToggleDrawer() {
    const drawer = document.getElementById('chatDrawer');
    if (!drawer) return;
    const isOpen = drawer.classList.toggle('open');
    if (isOpen) {
        chatDrawerCargarConversaciones();
    } else {
        chatDrawerCerrar();
    }
}

function chatDrawerCerrar() {
    document.getElementById('chatDrawer')?.classList.remove('open');
    if (chatPollingInt) { clearInterval(chatPollingInt); chatPollingInt = null; }
    chatConvActiva = null;
    // Reset to list view
    const lista   = document.getElementById('chatDrawerList');
    const chatArea = document.getElementById('chatDrawerChat');
    if (lista)    lista.style.display = 'block';
    if (chatArea) chatArea.style.display = 'none';
    document.getElementById('chatDrawerTitle').textContent = 'Mensajes';
    document.getElementById('chatDrawerBack').style.display = 'none';
}

async function chatDrawerCargarConversaciones() {
    try {
        const res  = await fetch(API_URL + '/chat/conversaciones');
        const data = await res.json();
        if (!data.success) return;
        chatRenderConvList(data.conversaciones, 'chatDrawerConvList');
    } catch (e) {}
}

async function chatDrawerAbrirConv(convId, nombre, otroId) {
    chatConvActiva = { id: convId, nombre, otroId };
    chatLastMsgId  = 0;

    document.getElementById('chatDrawerTitle').textContent = nombre;
    document.getElementById('chatDrawerBack').style.display = 'inline';

    const lista    = document.getElementById('chatDrawerList');
    const chatArea = document.getElementById('chatDrawerChat');
    if (lista)    lista.style.display = 'none';
    if (chatArea) chatArea.style.display = 'flex';

    const msgs = document.getElementById('chatDrawerMessages');
    if (msgs) msgs.innerHTML = '';

    await chatCargarMensajes('chatDrawerMessages', true);

    fetch(API_URL + '/chat/marcar-leido', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ conversacion_id: convId })
    });

    chatReiniciarPolling(3000, 'chatDrawerMessages');
    chatActualizarBadgeGlobal();
}

function chatDrawerVolverLista() {
    if (chatPollingInt) { clearInterval(chatPollingInt); chatPollingInt = null; }
    chatConvActiva = null;
    chatLastMsgId  = 0;

    document.getElementById('chatDrawerTitle').textContent = 'Mensajes';
    document.getElementById('chatDrawerBack').style.display = 'none';

    const lista    = document.getElementById('chatDrawerList');
    const chatArea = document.getElementById('chatDrawerChat');
    if (lista)    lista.style.display = 'block';
    if (chatArea) chatArea.style.display = 'none';

    chatDrawerCargarConversaciones();
    chatActualizarBadgeGlobal();
}

async function chatDrawerEnviar() {
    const input    = document.getElementById('chatDrawerInput');
    const contenido = input?.value.trim();
    if (!contenido || !chatConvActiva?.id) return;

    input.value = '';
    try {
        const res  = await fetch(API_URL + '/chat/enviar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ destinatario_id: chatConvActiva.otroId, contenido })
        });
        const data = await res.json();
        if (data.success) await chatCargarMensajes('chatDrawerMessages', true);
    } catch (e) {}
}

/* ─────────────────────────────────────────────────────────────────────────
   MENSAJES — carga incremental compartida
───────────────────────────────────────────────────────────────────────── */

async function chatCargarMensajes(containerId, scroll = false) {
    if (!chatConvActiva?.id) return;
    try {
        const res  = await fetch(`${API_URL}/chat/mensajes?conversacion_id=${chatConvActiva.id}&desde_id=${chatLastMsgId}`);
        const data = await res.json();
        if (!data.success || !data.mensajes.length) return;

        const container = document.getElementById(containerId);
        if (!container) return;

        const wasAtBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 60;

        data.mensajes.forEach(m => {
            const isMine = m.remitente_id == chatMiId;
            const wrap = document.createElement('div');
            wrap.className = 'chat-bubble ' + (isMine ? 'mine' : 'theirs');
            wrap.dataset.msgId = m.id;

            if (m.tipo === 'archivo') {
                const nombre = m.archivo_nombre || 'Archivo';
                const ext = nombre.split('.').pop().toLowerCase();
                const iconMap = { pdf:'📄', doc:'📝', docx:'📝', xls:'📊', xlsx:'📊', ppt:'📑', pptx:'📑',
                                  jpg:'🖼️', jpeg:'🖼️', png:'🖼️', gif:'🖼️', zip:'🗜️', mp4:'🎬', mp3:'🎵', txt:'📃', csv:'📊' };
                const icon = iconMap[ext] || '📎';
                const fileEl = document.createElement('a');
                fileEl.className = 'chat-file-msg';
                fileEl.href = m.archivo_url;
                fileEl.target = '_blank';
                fileEl.rel = 'noopener noreferrer';
                fileEl.innerHTML = `<span class="chat-file-icon">${icon}</span><span class="chat-file-name">${chatEsc(nombre)}</span>`;
                wrap.appendChild(fileEl);
            } else {
                const txt = document.createElement('div');
                txt.textContent = m.contenido;
                wrap.appendChild(txt);
            }

            const time = document.createElement('div');
            time.className = 'chat-bubble-time';
            time.textContent = chatFormatTime(m.created_at);

            wrap.appendChild(time);

            if (isMine) {
                const del = document.createElement('button');
                del.className = 'chat-bubble-delete';
                del.title = 'Eliminar mensaje';
                del.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>';
                del.onclick = (e) => { e.stopPropagation(); chatEliminarMensaje(m.id); };
                wrap.appendChild(del);
                wrap.addEventListener('mouseenter', () => del.style.display = 'flex');
                wrap.addEventListener('mouseleave', () => del.style.display = 'none');
            }

            container.appendChild(wrap);
            chatLastMsgId = Math.max(chatLastMsgId, m.id);
        });

        if (scroll || wasAtBottom) container.scrollTop = container.scrollHeight;

        // Refresh sidebar badges
        if (chatEsPro) chatCargarConversaciones();
        else chatDrawerCargarConversaciones();
    } catch (e) {}
}

/* ─────────────────────────────────────────────────────────────────────────
   POLLING
───────────────────────────────────────────────────────────────────────── */

function chatReiniciarPolling(intervalo, containerId) {
    if (chatPollingInt) clearInterval(chatPollingInt);
    chatPollingInt = setInterval(() => {
        if (chatConvActiva?.id) chatCargarMensajes(containerId, false);
    }, intervalo);
}

/* ─────────────────────────────────────────────────────────────────────────
   BADGE GLOBAL
───────────────────────────────────────────────────────────────────────── */

async function chatActualizarBadgeGlobal() {
    try {
        const res  = await fetch(API_URL + '/chat/no-leidos');
        const data = await res.json();
        if (!data.success) return;
        const total = data.total;

        // Sidebar badge
        document.querySelectorAll('.chat-nav-badge').forEach(el => {
            el.textContent = total;
            el.style.display = total > 0 ? 'inline' : 'none';
        });

        // FAB badge
        const fabBadge = document.getElementById('chatFabBadge');
        if (fabBadge) {
            fabBadge.textContent = total;
            fabBadge.style.display = total > 0 ? 'flex' : 'none';
        }
    } catch (e) {}
}

/* ─────────────────────────────────────────────────────────────────────────
   ELIMINAR MENSAJE / CHAT
───────────────────────────────────────────────────────────────────────── */

async function chatEliminarMensaje(msgId) {
    if (!confirm('¿Eliminar este mensaje?')) return;
    try {
        const res  = await fetch(API_URL + '/chat/eliminar-mensaje', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mensaje_id: msgId })
        });
        const data = await res.json();
        if (data.success) {
            const el = document.querySelector(`[data-msg-id="${msgId}"]`);
            if (el) el.remove();
        } else {
            chatShowToast('No se pudo eliminar el mensaje', 'error');
        }
    } catch (e) {}
}

async function chatEliminarChat() {
    if (!chatConvActiva?.id) return;
    if (!confirm('¿Eliminar esta conversación y todos sus mensajes? Esta acción no se puede deshacer.')) return;
    try {
        const res  = await fetch(API_URL + '/chat/eliminar-chat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ conversacion_id: chatConvActiva.id })
        });
        const data = await res.json();
        if (data.success) {
            if (chatPollingInt) { clearInterval(chatPollingInt); chatPollingInt = null; }
            chatConvActiva = null;
            chatLastMsgId  = 0;

            const header = document.getElementById('chatMainHeader');
            if (header) header.textContent = 'Selecciona una conversación';

            const msgs = document.getElementById('chatMessages');
            if (msgs) msgs.innerHTML = '<div class="chat-placeholder">Selecciona una conversación o inicia una nueva.</div>';

            const inputArea = document.getElementById('chatInputArea');
            if (inputArea) inputArea.style.display = 'none';

            document.querySelectorAll('.chat-conv-item').forEach(el => el.classList.remove('active'));
            chatCargarConversaciones();
        } else {
            chatShowToast('No se pudo eliminar la conversación', 'error');
        }
    } catch (e) {}
}

/* ─────────────────────────────────────────────────────────────────────────
   HELPERS
───────────────────────────────────────────────────────────────────────── */

function chatEsc(str) {
    if (str === null || str === undefined) return '';
    const d = document.createElement('div');
    d.textContent = String(str);
    return d.innerHTML;
}

function chatEscAttr(str) {
    return chatEsc(str).replace(/'/g, '&#39;');
}

function chatTruncar(str, max) {
    return str.length > max ? str.substring(0, max) + '…' : str;
}

function chatFormatTime(isoStr) {
    if (!isoStr) return '';
    const d   = new Date(isoStr);
    const now = new Date();
    const isToday = d.toDateString() === now.toDateString();
    const time = d.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
    if (isToday) return time;
    return d.toLocaleDateString('es-MX', { day: 'numeric', month: 'short' }) + ' ' + time;
}

function chatShowToast(msg, type = 'error') {
    if (typeof showToast === 'function') { showToast(msg, type); return; }
    const t = document.createElement('div');
    t.textContent = msg;
    t.style.cssText = `position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:${type==='error'?'#f44336':'#4caf50'};color:#fff;padding:10px 22px;border-radius:8px;z-index:9999;font-size:0.9rem;`;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3000);
}

/* Enter para enviar (Shift+Enter = nueva línea) */
document.addEventListener('DOMContentLoaded', () => {
    const inputPro = document.getElementById('chatInput');
    if (inputPro) {
        inputPro.addEventListener('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); chatEnviar(); }
        });
    }
    const inputDrw = document.getElementById('chatDrawerInput');
    if (inputDrw) {
        inputDrw.addEventListener('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); chatDrawerEnviar(); }
        });
    }
});

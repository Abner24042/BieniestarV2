<?php
require_once '../../../app/config/config.php';
require_once '../../../app/controllers/AuthController.php';

$authController = new AuthController();

if (!$authController->isAuthenticated()) { redirect('login'); }
if (!isAdmin()) { redirect('dashboard'); }

$user = $authController->getCurrentUser();
$currentPage = 'admin';
$pageTitle = 'Recomendaciones';
$additionalCSS = ['admin.css'];
?>

<?php include '../../../app/views/layouts/header.php'; ?>

<div class="content-wrapper">
    <div class="page-header">
        <div style="display:flex;align-items:center;gap:1rem;">
            <a href="<?php echo url('admin'); ?>" class="btn btn-secondary btn-sm">← Volver</a>
            <div>
                <h1>💡 Recomendaciones</h1>
                <p>Recomendaciones emitidas por los especialistas</p>
            </div>
        </div>
    </div>

    <div class="admin-dashboard">
        <div class="admin-stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background:#e3f2fd;"><span style="font-size:1.5rem;">💡</span></div>
                <div class="stat-content"><h3>TOTAL</h3><p class="stat-number" id="statTotal">—</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#e8f5e9;"><span style="font-size:1.5rem;">✅</span></div>
                <div class="stat-content"><h3>ACTIVAS</h3><p class="stat-number" id="statActivas">—</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#f5f5f5;"><span style="font-size:1.5rem;">📂</span></div>
                <div class="stat-content"><h3>INACTIVAS</h3><p class="stat-number" id="statInactivas">—</p></div>
            </div>
        </div>

        <div class="admin-section" style="width:100%;">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:1.2rem;">
                <h2 style="margin:0;">Lista de Recomendaciones</h2>
                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                    <input type="text" id="searchRec" placeholder="Buscar usuario o título..." style="padding:7px 12px;border:1px solid #ddd;border-radius:6px;font-size:0.87rem;min-width:220px;">
                    <select id="filterTipo" style="padding:7px 12px;border:1px solid #ddd;border-radius:6px;font-size:0.87rem;">
                        <option value="">Todos los tipos</option>
                        <option value="general">General</option>
                        <option value="alimentacion">Alimentación</option>
                        <option value="ejercicio">Ejercicio</option>
                        <option value="salud-mental">Salud Mental</option>
                    </select>
                    <button class="btn btn-secondary btn-sm" onclick="loadRecomendaciones()">↻ Recargar</button>
                </div>
            </div>

            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Especialista</th>
                            <th>Título</th>
                            <th>Tipo</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="recBody">
                        <tr><td colspan="8" class="empty-message">Cargando recomendaciones...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal editar recomendación -->
<div id="modalEditRec" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:520px;">
        <div class="modal-header">
            <h3>✏️ Editar Recomendación</h3>
            <button class="modal-close" onclick="cerrarModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="editRecId">
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-weight:600;margin-bottom:4px;">Título</label>
                <input type="text" id="editRecTitulo" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:0.9rem;box-sizing:border-box;">
            </div>
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-weight:600;margin-bottom:4px;">Contenido</label>
                <textarea id="editRecContenido" rows="4" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:0.9rem;resize:vertical;box-sizing:border-box;"></textarea>
            </div>
            <div style="display:flex;gap:12px;margin-bottom:1rem;">
                <div style="flex:1;">
                    <label style="display:block;font-weight:600;margin-bottom:4px;">Tipo</label>
                    <select id="editRecTipo" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:0.9rem;">
                        <option value="general">General</option>
                        <option value="alimentacion">Alimentación</option>
                        <option value="ejercicio">Ejercicio</option>
                        <option value="salud-mental">Salud Mental</option>
                    </select>
                </div>
                <div style="flex:1;">
                    <label style="display:block;font-weight:600;margin-bottom:4px;">Estado</label>
                    <select id="editRecActivo" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:0.9rem;">
                        <option value="1">Activa</option>
                        <option value="0">Inactiva</option>
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
                <button class="btn btn-primary" onclick="guardarRecomendacion()">Guardar cambios</button>
            </div>
        </div>
    </div>
</div>

<div id="adminToast" style="position:fixed;bottom:24px;right:24px;z-index:9999;display:none;min-width:240px;padding:12px 18px;border-radius:8px;font-size:0.9rem;font-weight:600;box-shadow:0 4px 16px rgba(0,0,0,.15);"></div>

<style>
.badge-tipo { border-radius:4px;padding:2px 7px;font-size:0.75rem;font-weight:700; }
.badge-tipo-general       { background:#e3f2fd;color:#1565c0; }
.badge-tipo-alimentacion  { background:#e8f5e9;color:#2e7d32; }
.badge-tipo-ejercicio     { background:#fff3e0;color:#e65100; }
.badge-tipo-salud-mental  { background:#fce4ec;color:#880e4f; }
.modal { position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;display:flex;align-items:center;justify-content:center; }
.modal-content { background:#fff;border-radius:12px;width:90%;max-width:520px;box-shadow:0 8px 32px rgba(0,0,0,.2); }
.modal-header { display:flex;justify-content:space-between;align-items:center;padding:1.2rem 1.5rem;border-bottom:1px solid #eee; }
.modal-header h3 { margin:0;font-size:1.1rem; }
.modal-close { background:none;border:none;font-size:1.5rem;cursor:pointer;color:#666;line-height:1; }
.modal-body { padding:1.5rem; }
</style>

<script>
let allRec = [];

async function loadRecomendaciones() {
    try {
        const res  = await fetch(API_URL + '/admin/recomendaciones');
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Error al cargar');
        allRec = data.recomendaciones || [];
        updateStats();
        renderTable();
    } catch(e) {
        document.getElementById('recBody').innerHTML =
            `<tr><td colspan="8" class="empty-message" style="color:#c62828;">Error: ${e.message}</td></tr>`;
    }
}

function updateStats() {
    document.getElementById('statTotal').textContent     = allRec.length;
    document.getElementById('statActivas').textContent   = allRec.filter(r => r.activo == 1).length;
    document.getElementById('statInactivas').textContent = allRec.filter(r => r.activo != 1).length;
}

function renderTable() {
    const q    = document.getElementById('searchRec').value.toLowerCase();
    const tipo = document.getElementById('filterTipo').value;
    let rows   = allRec;
    if (tipo) rows = rows.filter(r => r.tipo === tipo);
    if (q) rows = rows.filter(r =>
        (r.usuario_nombre || r.usuario_correo || '').toLowerCase().includes(q) ||
        (r.titulo || '').toLowerCase().includes(q) ||
        (r.profesional_nombre || r.profesional_id || '').toString().toLowerCase().includes(q)
    );
    const tbody = document.getElementById('recBody');
    if (!rows.length) { tbody.innerHTML = '<tr><td colspan="8" class="empty-message">Sin resultados</td></tr>'; return; }
    tbody.innerHTML = rows.map(r => {
        const tipoCls = 'badge-tipo badge-tipo-' + (r.tipo || 'general').replace(/\s/g,'-');
        return `<tr>
            <td>${r.id}</td>
            <td>${esc(r.usuario_nombre || r.usuario_correo || 'ID:' + r.usuario_id)}</td>
            <td>${esc(r.profesional_nombre || r.profesional_correo || r.profesional_id || '—')}</td>
            <td>${esc(r.titulo)}</td>
            <td><span class="${tipoCls}">${esc(r.tipo || 'general')}</span></td>
            <td>${formatDate(r.created_at)}</td>
            <td><span style="color:${r.activo==1?'#2e7d32':'#999'};font-weight:600;">${r.activo==1?'Activa':'Inactiva'}</span></td>
            <td style="display:flex;gap:4px;flex-wrap:wrap;align-items:center;">
                <button class="btn btn-sm" onclick="abrirEditar(${r.id})" style="font-size:0.73rem;padding:3px 9px;background:#1565c0;color:#fff;border:none;border-radius:6px;cursor:pointer;">✏️ Editar</button>
                <button class="btn btn-sm" onclick="eliminar(${r.id})" style="font-size:0.73rem;padding:3px 9px;background:#e53935;color:#fff;border:none;border-radius:6px;cursor:pointer;">🗑️ Eliminar</button>
            </td>
        </tr>`;
    }).join('');
}

function abrirEditar(id) {
    const r = allRec.find(x => x.id == id);
    if (!r) return;
    document.getElementById('editRecId').value       = r.id;
    document.getElementById('editRecTitulo').value   = r.titulo || '';
    document.getElementById('editRecContenido').value = r.contenido || '';
    document.getElementById('editRecTipo').value     = r.tipo || 'general';
    document.getElementById('editRecActivo').value   = r.activo == 1 ? '1' : '0';
    document.getElementById('modalEditRec').style.display = 'flex';
}

function cerrarModal() {
    document.getElementById('modalEditRec').style.display = 'none';
}

async function guardarRecomendacion() {
    const id       = document.getElementById('editRecId').value;
    const titulo   = document.getElementById('editRecTitulo').value.trim();
    const contenido = document.getElementById('editRecContenido').value.trim();
    const tipo     = document.getElementById('editRecTipo').value;
    const activo   = document.getElementById('editRecActivo').value;
    if (!titulo) { showToast('El título es obligatorio', '#c62828'); return; }
    try {
        const res  = await fetch(API_URL + '/admin/recomendaciones/update', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ id, titulo, contenido, tipo, activo })
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        cerrarModal();
        showToast('Recomendación actualizada', '#2e7d32');
        loadRecomendaciones();
    } catch(e) {
        showToast('Error: ' + e.message, '#c62828');
    }
}

async function eliminar(id) {
    if (!confirm('¿Eliminar esta recomendación? Esta acción no se puede deshacer.')) return;
    try {
        const res  = await fetch(API_URL + '/admin/recomendaciones/delete', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ id })
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        showToast('Recomendación eliminada', '#2e7d32');
        loadRecomendaciones();
    } catch(e) {
        showToast('Error: ' + e.message, '#c62828');
    }
}

function formatDate(d) {
    if (!d) return '—';
    const dt = new Date(d);
    return isNaN(dt) ? d : dt.toLocaleDateString('es-MX', {day:'2-digit',month:'short',year:'numeric'});
}
function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function showToast(msg, bg) {
    const t = document.getElementById('adminToast');
    t.textContent = msg; t.style.background = bg; t.style.color = '#fff'; t.style.display = 'block';
    setTimeout(() => t.style.display = 'none', 3000);
}

document.getElementById('searchRec').addEventListener('input', renderTable);
document.getElementById('filterTipo').addEventListener('change', renderTable);
document.getElementById('modalEditRec').addEventListener('click', e => { if (e.target === e.currentTarget) cerrarModal(); });
document.addEventListener('DOMContentLoaded', loadRecomendaciones);
</script>

<?php
$additionalJS = [];
include '../../../app/views/layouts/footer.php';
?>

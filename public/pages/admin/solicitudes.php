<?php
require_once '../../../app/config/config.php';
require_once '../../../app/controllers/AuthController.php';

$authController = new AuthController();

if (!$authController->isAuthenticated()) { redirect('login'); }
if (!isAdmin()) { redirect('dashboard'); }

$user = $authController->getCurrentUser();
$currentPage = 'admin';
$pageTitle = 'Solicitudes';
$additionalCSS = ['admin.css'];
?>

<?php include '../../../app/views/layouts/header.php'; ?>

<div class="content-wrapper">
    <div class="page-header">
        <div style="display:flex;align-items:center;gap:1rem;">
            <a href="<?php echo url('admin'); ?>" class="btn btn-secondary btn-sm">← Volver</a>
            <div>
                <h1>⏳ Solicitudes</h1>
                <p>Solicitudes de citas enviadas por los usuarios</p>
            </div>
        </div>
    </div>

    <div class="admin-dashboard">
        <div class="admin-stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background:#ede7f6;"><span style="font-size:1.5rem;">📋</span></div>
                <div class="stat-content"><h3>TOTAL</h3><p class="stat-number" id="statTotal">—</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff8e1;"><span style="font-size:1.5rem;">⏳</span></div>
                <div class="stat-content"><h3>PENDIENTES</h3><p class="stat-number" id="statPendientes">—</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#e8f5e9;"><span style="font-size:1.5rem;">✅</span></div>
                <div class="stat-content"><h3>ACEPTADAS</h3><p class="stat-number" id="statAceptadas">—</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#fce4ec;"><span style="font-size:1.5rem;">❌</span></div>
                <div class="stat-content"><h3>DENEGADAS</h3><p class="stat-number" id="statDenegadas">—</p></div>
            </div>
        </div>

        <div class="admin-section" style="width:100%;">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:1.2rem;">
                <h2 style="margin:0;">Lista de Solicitudes</h2>
                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                    <input type="text" id="searchSolicitudes" placeholder="Buscar paciente o profesional..." style="padding:7px 12px;border:1px solid #ddd;border-radius:6px;font-size:0.87rem;min-width:220px;">
                    <button class="btn-filter active" data-filter="all"        onclick="setFilter('all',this)">Todas</button>
                    <button class="btn-filter"         data-filter="pendiente"  onclick="setFilter('pendiente',this)">Pendientes</button>
                    <button class="btn-filter"         data-filter="aceptada"   onclick="setFilter('aceptada',this)">Aceptadas</button>
                    <button class="btn-filter"         data-filter="denegada"   onclick="setFilter('denegada',this)">Denegadas</button>
                    <button class="btn btn-secondary btn-sm" onclick="loadSolicitudes()">↻ Recargar</button>
                </div>
            </div>

            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Paciente</th>
                            <th>Profesional solicitado</th>
                            <th>Tipo / Motivo</th>
                            <th>Fecha solicitud</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="solicitudesBody">
                        <tr><td colspan="7" class="empty-message">Cargando solicitudes...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal editar solicitud -->
<div id="modalEditSol" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:480px;">
        <div class="modal-header">
            <h3>✏️ Editar Solicitud</h3>
            <button class="modal-close" onclick="cerrarModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="editSolId">
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-weight:600;margin-bottom:4px;">Estado</label>
                <select id="editSolEstado" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:0.9rem;">
                    <option value="pendiente">Pendiente</option>
                    <option value="aceptada">Aceptada</option>
                    <option value="denegada">Denegada</option>
                </select>
            </div>
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-weight:600;margin-bottom:4px;">Motivo / Notas</label>
                <textarea id="editSolMotivo" rows="3" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:0.9rem;resize:vertical;box-sizing:border-box;"></textarea>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
                <button class="btn btn-primary" onclick="guardarSolicitud()">Guardar cambios</button>
            </div>
        </div>
    </div>
</div>

<div id="adminToast" style="position:fixed;bottom:24px;right:24px;z-index:9999;display:none;min-width:240px;padding:12px 18px;border-radius:8px;font-size:0.9rem;font-weight:600;box-shadow:0 4px 16px rgba(0,0,0,.15);"></div>

<style>
.btn-filter { padding:5px 14px;border:1px solid #ddd;background:#fff;border-radius:20px;cursor:pointer;font-size:0.82rem;font-weight:600;color:#555;transition:all .15s; }
.btn-filter:hover { background:#f5f5f5; }
.btn-filter.active { background:#ff6b35;color:#fff;border-color:#ff6b35; }
.badge-pendiente  { background:#fff8e1;color:#f57f17;border-radius:4px;padding:2px 7px;font-size:0.75rem;font-weight:700; }
.badge-aceptada   { background:#e8f5e9;color:#2e7d32;border-radius:4px;padding:2px 7px;font-size:0.75rem;font-weight:700; }
.badge-denegada   { background:#fce4ec;color:#c62828;border-radius:4px;padding:2px 7px;font-size:0.75rem;font-weight:700; }
.badge-reasignada { background:#e3f2fd;color:#1565c0;border-radius:4px;padding:2px 7px;font-size:0.75rem;font-weight:700; }
.modal { position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;display:flex;align-items:center;justify-content:center; }
.modal-content { background:#fff;border-radius:12px;width:90%;max-width:480px;box-shadow:0 8px 32px rgba(0,0,0,.2); }
.modal-header { display:flex;justify-content:space-between;align-items:center;padding:1.2rem 1.5rem;border-bottom:1px solid #eee; }
.modal-header h3 { margin:0;font-size:1.1rem; }
.modal-close { background:none;border:none;font-size:1.5rem;cursor:pointer;color:#666;line-height:1; }
.modal-body { padding:1.5rem; }
</style>

<script>
let allSolicitudes = [];
let currentFilter = 'all';

async function loadSolicitudes() {
    try {
        const res  = await fetch(API_URL + '/admin/solicitudes');
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Error al cargar');
        allSolicitudes = data.solicitudes || [];
        updateStats();
        renderTable();
    } catch(e) {
        document.getElementById('solicitudesBody').innerHTML =
            `<tr><td colspan="7" class="empty-message" style="color:#c62828;">Error: ${e.message}</td></tr>`;
    }
}

function updateStats() {
    document.getElementById('statTotal').textContent      = allSolicitudes.length;
    document.getElementById('statPendientes').textContent = allSolicitudes.filter(s => s.sol_estado === 'pendiente').length;
    document.getElementById('statAceptadas').textContent  = allSolicitudes.filter(s => s.sol_estado === 'aceptada').length;
    document.getElementById('statDenegadas').textContent  = allSolicitudes.filter(s => s.sol_estado === 'denegada' || s.sol_estado === 'rechazada').length;
}

function setFilter(f, btn) {
    currentFilter = f;
    document.querySelectorAll('.btn-filter').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    renderTable();
}

function renderTable() {
    const q  = document.getElementById('searchSolicitudes').value.toLowerCase();
    let rows = allSolicitudes;
    if (currentFilter !== 'all') rows = rows.filter(s => s.sol_estado === currentFilter || (currentFilter === 'denegada' && s.sol_estado === 'rechazada'));
    if (q) rows = rows.filter(s =>
        (s.correo || '').toLowerCase().includes(q) ||
        (s.sol_profesional || '').toLowerCase().includes(q) ||
        (s.sol_tipo || '').toLowerCase().includes(q)
    );
    const tbody = document.getElementById('solicitudesBody');
    if (!rows.length) { tbody.innerHTML = '<tr><td colspan="7" class="empty-message">Sin resultados</td></tr>'; return; }
    tbody.innerHTML = rows.map(s => {
        const estado   = s.sol_estado || 'pendiente';
        const badgeCls = 'badge-' + estado;
        const isPending = estado === 'pendiente';
        return `<tr>
            <td>${s.id}</td>
            <td>${esc(s.correo || s.usuario_nombre || '—')}</td>
            <td>${esc(s.sol_profesional || '—')}</td>
            <td>
                <strong>${esc(s.sol_tipo || s.titulo || '—')}</strong>
                ${s.sol_motivo ? `<br><small style="color:#666;">${esc(s.sol_motivo)}</small>` : ''}
            </td>
            <td>${formatDate(s.fecha)}</td>
            <td><span class="${badgeCls}">${estado}</span></td>
            <td style="display:flex;flex-wrap:wrap;gap:4px;align-items:center;">
                ${isPending ? `
                    <button class="btn btn-primary btn-sm" onclick="accion(${s.id},'aceptar')" style="font-size:0.73rem;padding:3px 9px;">✅ Aceptar</button>
                    <button class="btn btn-sm" onclick="accion(${s.id},'denegar')" style="font-size:0.73rem;padding:3px 9px;background:#e53935;color:#fff;border:none;border-radius:6px;cursor:pointer;">❌ Denegar</button>
                ` : ''}
                <button class="btn btn-sm" onclick="abrirEditar(${s.id})" style="font-size:0.73rem;padding:3px 9px;background:#1565c0;color:#fff;border:none;border-radius:6px;cursor:pointer;">✏️ Editar</button>
                <button class="btn btn-sm" onclick="eliminar(${s.id})" style="font-size:0.73rem;padding:3px 9px;background:#555;color:#fff;border:none;border-radius:6px;cursor:pointer;">🗑️ Eliminar</button>
            </td>
        </tr>`;
    }).join('');
}

function abrirEditar(id) {
    const s = allSolicitudes.find(x => x.id == id);
    if (!s) return;
    document.getElementById('editSolId').value     = s.id;
    document.getElementById('editSolEstado').value = s.sol_estado || 'pendiente';
    document.getElementById('editSolMotivo').value = s.sol_motivo || '';
    document.getElementById('modalEditSol').style.display = 'flex';
}

function cerrarModal() {
    document.getElementById('modalEditSol').style.display = 'none';
}

async function guardarSolicitud() {
    const id     = document.getElementById('editSolId').value;
    const estado = document.getElementById('editSolEstado').value;
    const motivo = document.getElementById('editSolMotivo').value;
    try {
        const res  = await fetch(API_URL + '/admin/solicitudes/update', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ id, estado, motivo })
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        cerrarModal();
        showToast('Solicitud actualizada', '#2e7d32');
        loadSolicitudes();
    } catch(e) {
        showToast('Error: ' + e.message, '#c62828');
    }
}

async function accion(id, tipo) {
    if (!confirm(`¿${tipo === 'aceptar' ? 'Aceptar' : 'Denegar'} esta solicitud?`)) return;
    try {
        const res  = await fetch(API_URL + '/admin/solicitudes/accion', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ id, accion: tipo })
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        showToast(data.message, '#2e7d32');
        loadSolicitudes();
    } catch(e) {
        showToast('Error: ' + e.message, '#c62828');
    }
}

async function eliminar(id) {
    if (!confirm('¿Eliminar esta solicitud? Esta acción no se puede deshacer.')) return;
    try {
        const res  = await fetch(API_URL + '/admin/solicitudes/delete', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ id })
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        showToast('Solicitud eliminada', '#2e7d32');
        loadSolicitudes();
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

document.getElementById('searchSolicitudes').addEventListener('input', renderTable);
document.getElementById('modalEditSol').addEventListener('click', e => { if (e.target === e.currentTarget) cerrarModal(); });
document.addEventListener('DOMContentLoaded', loadSolicitudes);
</script>

<?php
$additionalJS = [];
include '../../../app/views/layouts/footer.php';
?>

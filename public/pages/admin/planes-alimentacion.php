<?php
require_once '../../../app/config/config.php';
require_once '../../../app/controllers/AuthController.php';

$authController = new AuthController();

if (!$authController->isAuthenticated()) { redirect('login'); }
if (!isAdmin()) { redirect('dashboard'); }

$user = $authController->getCurrentUser();
$currentPage = 'admin';
$pageTitle = 'Planes de Alimentación';
$additionalCSS = ['admin.css'];
?>

<?php include '../../../app/views/layouts/header.php'; ?>

<div class="content-wrapper">
    <div class="page-header">
        <div style="display:flex;align-items:center;gap:1rem;">
            <a href="<?php echo url('admin'); ?>" class="btn btn-secondary btn-sm">← Volver</a>
            <div>
                <h1>🍽️ Planes de Alimentación</h1>
                <p>Recetas asignadas a usuarios por los nutriólogos</p>
            </div>
        </div>
    </div>

    <div class="admin-dashboard">
        <div class="admin-stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background:#e8f5e9;"><span style="font-size:1.5rem;">📋</span></div>
                <div class="stat-content"><h3>TOTAL ASIGNACIONES</h3><p class="stat-number" id="statTotal">—</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff3e0;"><span style="font-size:1.5rem;">👤</span></div>
                <div class="stat-content"><h3>USUARIOS CON PLAN</h3><p class="stat-number" id="statUsuarios">—</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#fce4ec;"><span style="font-size:1.5rem;">🍎</span></div>
                <div class="stat-content"><h3>RECETAS DISTINTAS</h3><p class="stat-number" id="statRecetas">—</p></div>
            </div>
        </div>

        <div class="admin-section" style="width:100%;">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:1.2rem;">
                <h2 style="margin:0;">Asignaciones de Recetas</h2>
                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                    <input type="text" id="searchPlan" placeholder="Buscar usuario o receta..." style="padding:7px 12px;border:1px solid #ddd;border-radius:6px;font-size:0.87rem;min-width:220px;">
                    <button class="btn btn-secondary btn-sm" onclick="loadPlanes()">↻ Recargar</button>
                </div>
            </div>

            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Receta</th>
                            <th>Asignado por</th>
                            <th>Día</th>
                            <th>Notas</th>
                            <th>Fecha asignación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="planBody">
                        <tr><td colspan="8" class="empty-message">Cargando planes...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal editar plan alimentación -->
<div id="modalEdit" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:460px;">
        <div class="modal-header">
            <h3>✏️ Editar Asignación</h3>
            <button class="modal-close" onclick="cerrarModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="editId">
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-weight:600;margin-bottom:4px;">Día de la semana</label>
                <select id="editDia" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:0.9rem;">
                    <option value="0">Todos los días</option>
                    <option value="1">Lunes</option>
                    <option value="2">Martes</option>
                    <option value="3">Miércoles</option>
                    <option value="4">Jueves</option>
                    <option value="5">Viernes</option>
                    <option value="6">Sábado</option>
                    <option value="7">Domingo</option>
                </select>
            </div>
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-weight:600;margin-bottom:4px;">Notas</label>
                <textarea id="editNotas" rows="3" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:0.9rem;resize:vertical;box-sizing:border-box;" placeholder="Notas opcionales..."></textarea>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
                <button class="btn btn-primary" onclick="guardar()">Guardar cambios</button>
            </div>
        </div>
    </div>
</div>

<div id="adminToast" style="position:fixed;bottom:24px;right:24px;z-index:9999;display:none;min-width:240px;padding:12px 18px;border-radius:8px;font-size:0.9rem;font-weight:600;box-shadow:0 4px 16px rgba(0,0,0,.15);"></div>

<style>
.modal { position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;display:flex;align-items:center;justify-content:center; }
.modal-content { background:#fff;border-radius:12px;width:90%;max-width:460px;box-shadow:0 8px 32px rgba(0,0,0,.2); }
.modal-header { display:flex;justify-content:space-between;align-items:center;padding:1.2rem 1.5rem;border-bottom:1px solid #eee; }
.modal-header h3 { margin:0;font-size:1.1rem; }
.modal-close { background:none;border:none;font-size:1.5rem;cursor:pointer;color:#666;line-height:1; }
.modal-body { padding:1.5rem; }
</style>

<script>
const DIAS = ['Todos','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
let allPlanes = [];

async function loadPlanes() {
    try {
        const res  = await fetch(API_URL + '/admin/planes-alimentacion');
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Error al cargar');
        allPlanes = data.planes || [];
        updateStats();
        renderTable();
    } catch(e) {
        document.getElementById('planBody').innerHTML =
            `<tr><td colspan="8" class="empty-message" style="color:#c62828;">Error: ${e.message}</td></tr>`;
    }
}

function updateStats() {
    const uids = new Set(allPlanes.map(p => p.usuario_id));
    const rids = new Set(allPlanes.map(p => p.receta_id));
    document.getElementById('statTotal').textContent    = allPlanes.length;
    document.getElementById('statUsuarios').textContent = uids.size;
    document.getElementById('statRecetas').textContent  = rids.size;
}

function renderTable() {
    const q    = document.getElementById('searchPlan').value.toLowerCase();
    let rows   = allPlanes;
    if (q) rows = rows.filter(p =>
        (p.usuario_nombre || p.usuario_correo || '').toLowerCase().includes(q) ||
        (p.receta_titulo  || '').toLowerCase().includes(q) ||
        (p.asignado_por   || '').toLowerCase().includes(q)
    );
    const tbody = document.getElementById('planBody');
    if (!rows.length) { tbody.innerHTML = '<tr><td colspan="8" class="empty-message">Sin resultados</td></tr>'; return; }
    tbody.innerHTML = rows.map(p => `<tr>
        <td>${p.id}</td>
        <td>${esc(p.usuario_nombre || p.usuario_correo || 'ID:' + p.usuario_id)}</td>
        <td>${esc(p.receta_titulo  || 'ID:' + p.receta_id)}</td>
        <td>${esc(p.asignado_por   || '—')}</td>
        <td>${DIAS[parseInt(p.dia_semana) || 0] || 'Todos'}</td>
        <td>${esc(p.notas || '—')}</td>
        <td>${formatDate(p.fecha_asignacion)}</td>
        <td style="display:flex;gap:4px;flex-wrap:wrap;align-items:center;">
            <button class="btn btn-sm" onclick="abrirEditar(${p.id})" style="font-size:0.73rem;padding:3px 9px;background:#1565c0;color:#fff;border:none;border-radius:6px;cursor:pointer;">✏️ Editar</button>
            <button class="btn btn-sm" onclick="eliminar(${p.id})" style="font-size:0.73rem;padding:3px 9px;background:#e53935;color:#fff;border:none;border-radius:6px;cursor:pointer;">🗑️ Eliminar</button>
        </td>
    </tr>`).join('');
}

function abrirEditar(id) {
    const p = allPlanes.find(x => x.id == id);
    if (!p) return;
    document.getElementById('editId').value    = p.id;
    document.getElementById('editDia').value   = p.dia_semana || 0;
    document.getElementById('editNotas').value = p.notas || '';
    document.getElementById('modalEdit').style.display = 'flex';
}

function cerrarModal() {
    document.getElementById('modalEdit').style.display = 'none';
}

async function guardar() {
    const id    = document.getElementById('editId').value;
    const dia   = document.getElementById('editDia').value;
    const notas = document.getElementById('editNotas').value.trim();
    try {
        const res  = await fetch(API_URL + '/admin/planes-alimentacion/update', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ id, dia_semana: dia, notas })
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        cerrarModal();
        showToast('Asignación actualizada', '#2e7d32');
        loadPlanes();
    } catch(e) {
        showToast('Error: ' + e.message, '#c62828');
    }
}

async function eliminar(id) {
    if (!confirm('¿Eliminar esta asignación del plan? El usuario dejará de tener esta receta en su plan.')) return;
    try {
        const res  = await fetch(API_URL + '/admin/planes-alimentacion/delete', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ id })
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        showToast('Asignación eliminada', '#2e7d32');
        loadPlanes();
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

document.getElementById('searchPlan').addEventListener('input', renderTable);
document.getElementById('modalEdit').addEventListener('click', e => { if (e.target === e.currentTarget) cerrarModal(); });
document.addEventListener('DOMContentLoaded', loadPlanes);
</script>

<?php
$additionalJS = [];
include '../../../app/views/layouts/footer.php';
?>

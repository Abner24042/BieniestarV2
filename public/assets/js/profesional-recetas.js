/**
 * BIENIESTAR - Profesional: Gestión de Recetas (Nutriólogo)
 */

let proRecetasData = [];
let allPendingRecetas = [];
let pendingFiltrados  = [];
let pendingVisible    = 4;

function porFilaP() {
    const el = document.getElementById('pendingRecetasGrid');
    const w  = el ? (el.clientWidth || el.offsetWidth) : (window.innerWidth - 260);
    return Math.max(1, Math.floor((w + 16) / (220 + 16)));
}

document.addEventListener('DOMContentLoaded', function() {
    cargarProRecetas();
    cargarPendingRecetas();

    document.getElementById('btnNuevaRecetaPro').addEventListener('click', function() {
        document.getElementById('modalRecetaProTitle').textContent = 'Nueva Receta';
        document.getElementById('formRecetaPro').reset();
        document.getElementById('pro_receta_id').value = '';
        document.getElementById('modalRecetaPro').style.display = 'flex';
    });

    document.getElementById('formRecetaPro').addEventListener('submit', function(e) {
        e.preventDefault();
        guardarProReceta();
    });
});

async function cargarProRecetas() {
    try {
        const response = await fetch(API_URL + '/pro/recetas');
        const data = await response.json();
        const tbody = document.getElementById('proRecetasBody');

        if (data.success && data.recetas.length > 0) {
            proRecetasData = data.recetas;
            tbody.innerHTML = data.recetas.map(r => `
                <tr>
                    <td>${esc(r.titulo)}</td>
                    <td>${cap(r.categoria || 'comida')}</td>
                    <td>${r.calorias || '-'} kcal</td>
                    <td><span style="color:${r.activo == 1 ? '#34A853' : '#999'};font-weight:600;">${r.activo == 1 ? 'Activa' : 'Inactiva'}</span></td>
                    <td style="display:flex;gap:0.4rem;">
                        <button class="btn btn-secondary btn-sm" onclick="editarProReceta(${r.id})">Editar</button>
                        <button class="btn btn-sm" style="background:#F44336;color:white;" onclick="eliminarProReceta(${r.id})">Eliminar</button>
                    </td>
                </tr>
            `).join('');
        } else {
            proRecetasData = [];
            tbody.innerHTML = '<tr><td colspan="5" class="empty-message">No tienes recetas aún. ¡Crea tu primera!</td></tr>';
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function editarProReceta(id) {
    const r = proRecetasData.find(item => item.id == id);
    if (!r) return;

    document.getElementById('modalRecetaProTitle').textContent = 'Editar Receta';
    document.getElementById('pro_receta_id').value = r.id;
    document.getElementById('pro_receta_titulo').value = r.titulo || '';
    document.getElementById('pro_receta_descripcion').value = r.descripcion || '';
    document.getElementById('pro_receta_ingredientes').value = r.ingredientes || '';
    document.getElementById('pro_receta_instrucciones').value = r.instrucciones || '';
    document.getElementById('pro_receta_tiempo').value = r.tiempo_preparacion || '';
    document.getElementById('pro_receta_porciones').value = r.porciones || '';
    document.getElementById('pro_receta_calorias').value = r.calorias || '';
    document.getElementById('pro_receta_proteinas').value = r.proteinas || '';
    document.getElementById('pro_receta_carbohidratos').value = r.carbohidratos || '';
    document.getElementById('pro_receta_grasas').value = r.grasas || '';
    document.getElementById('pro_receta_fibra').value = r.fibra || '';
    document.getElementById('pro_receta_categoria').value = r.categoria || 'comida';
    document.getElementById('modalRecetaPro').style.display = 'flex';
}

async function cargarPendingRecetas() {
    try {
        const res  = await fetch(API_URL + '/pro/recetas/pending');
        const data = await res.json();
        if (!data.success || data.recetas.length === 0) {
            document.getElementById('sectionPendingRecetas').style.display = 'none';
            return;
        }
        allPendingRecetas = data.recetas;
        document.getElementById('pendingCount').textContent = data.recetas.length;
        aplicarFiltrosPending();
    } catch (e) {
        console.error('Error cargando pendientes:', e);
    }
}

function aplicarFiltrosPending() {
    const q   = (document.getElementById('pendingSearch')?.value || '').toLowerCase().trim();
    const cat = (document.getElementById('pendingCatFilter')?.value || '').toLowerCase();
    pendingFiltrados = allPendingRecetas.filter(r => {
        const matchText = !q || r.titulo.toLowerCase().includes(q) || (r.categoria||'').toLowerCase().includes(q);
        const matchCat  = !cat || (r.categoria||'').toLowerCase() === cat;
        return matchText && matchCat;
    });
    pendingVisible = porFilaP();
    renderPendingGrid();
}

function mostrarMasPending() {
    pendingVisible += porFilaP();
    renderPendingGrid();
}

function renderPendingGrid() {
    const grid  = document.getElementById('pendingRecetasGrid');
    const items = pendingFiltrados.slice(0, pendingVisible);
    const btn   = document.getElementById('btnMostrarMasPending');
    if (items.length === 0) {
        grid.innerHTML = '<p style="color:#999;grid-column:1/-1;">No se encontraron recetas con ese filtro.</p>';
    } else {
        grid.innerHTML = items.map(r => pendingCard(r)).join('');
    }
    if (btn) btn.style.display = pendingVisible < pendingFiltrados.length ? '' : 'none';
}

function pendingCard(r) {
    const img = r.imagen || 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=300&q=70';
    const cal = r.calorias ? Math.round(r.calorias) + ' kcal' : '—';
    const cat = cap(r.categoria || 'receta');
    return `<div onclick="abrirDetallePending(${r.id})" style="background:#1e1e1e;border-radius:12px;overflow:hidden;border:1px solid #333;cursor:pointer;transition:border-color .2s;" onmouseover="this.style.borderColor='#ff6b35'" onmouseout="this.style.borderColor='#333'">
        <img src="${esc(img)}" alt="${esc(r.titulo)}" style="width:100%;height:140px;object-fit:cover;"
             onerror="this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=300&q=70'">
        <div style="padding:0.75rem;">
            <p style="font-weight:600;font-size:0.85rem;margin-bottom:4px;line-height:1.3;">${esc(r.titulo)}</p>
            <p style="font-size:0.75rem;color:#999;margin-bottom:0.75rem;">${cat} · ${cal}</p>
            <div style="display:flex;gap:0.4rem;">
                <button onclick="event.stopPropagation();aprobarReceta(${r.id})"
                    style="flex:1;padding:6px;background:#34A853;color:white;border:none;border-radius:6px;cursor:pointer;font-size:0.75rem;font-weight:600;">
                    ✓ Aprobar
                </button>
                <button onclick="event.stopPropagation();rechazarReceta(${r.id})"
                    style="flex:1;padding:6px;background:#F44336;color:white;border:none;border-radius:6px;cursor:pointer;font-size:0.75rem;font-weight:600;">
                    ✕ Eliminar
                </button>
            </div>
        </div>
    </div>`;
}

function abrirDetallePending(id) {
    const r = allPendingRecetas.find(x => x.id == id);
    if (!r) return;
    const img  = r.imagen || 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=300&q=70';
    const cat  = cap(r.categoria || 'receta');
    const cal  = r.calorias  ? Math.round(r.calorias) + ' kcal'  : '—';
    const prot = r.proteinas ? r.proteinas + 'g proteínas'        : '';
    const carb = r.carbohidratos ? r.carbohidratos + 'g carbos'   : '';
    const gras = r.grasas    ? r.grasas + 'g grasas'              : '';
    const macros = [prot, carb, gras].filter(Boolean).join(' · ');

    const ingList = r.ingredientes
        ? r.ingredientes.split('\n').map(i => i.trim()).filter(Boolean).map(i => `<li style="margin-bottom:4px;">${esc(i)}</li>`).join('')
        : '<li style="color:#999;">—</li>';
    const insList = r.instrucciones
        ? r.instrucciones.split('\n').map(i => i.trim()).filter(Boolean).map(i => `<li style="margin-bottom:6px;">${esc(i)}</li>`).join('')
        : '<li style="color:#999;">—</li>';

    document.getElementById('modalPendingContent').innerHTML = `
        <!-- Cabecera: imagen izquierda + info derecha -->
        <div style="display:flex;min-height:240px;">
            <img src="${esc(img)}" style="width:45%;min-width:160px;max-width:260px;object-fit:cover;border-radius:0;flex-shrink:0;"
                 onerror="this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=300&q=70'">
            <div style="flex:1;padding:1.25rem 1.25rem 1rem;display:flex;flex-direction:column;justify-content:center;gap:0.6rem;overflow:hidden;">
                <h2 style="font-size:1.1rem;line-height:1.3;margin:0;">${esc(r.titulo)}</h2>
                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                    <span style="background:#ff6b35;color:white;padding:3px 10px;border-radius:20px;font-size:0.75rem;">${cat}</span>
                    <span style="background:#2a2a2a;color:#ccc;padding:3px 10px;border-radius:20px;font-size:0.75rem;">🔥 ${cal}</span>
                </div>
                ${r.tiempo_preparacion ? `<div style="display:flex;align-items:center;gap:8px;background:#222;border-radius:8px;padding:8px 12px;">
                    <span style="color:#999;font-size:0.75rem;">Tiempo</span>
                    <span style="color:#ff6b35;font-weight:700;">${r.tiempo_preparacion} min</span>
                </div>` : ''}
                ${r.porciones ? `<div style="display:flex;align-items:center;gap:8px;background:#222;border-radius:8px;padding:8px 12px;">
                    <span style="color:#999;font-size:0.75rem;">Porciones</span>
                    <span style="color:#ff6b35;font-weight:700;">${r.porciones}</span>
                </div>` : ''}
                ${macros ? `<p style="color:#888;font-size:0.75rem;margin:0;">${macros}</p>` : ''}
                ${r.descripcion ? `<p style="color:#bbb;font-size:0.8rem;margin:0;line-height:1.4;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;">${esc(r.descripcion)}</p>` : ''}
            </div>
        </div>
        <!-- Cuerpo: ingredientes e instrucciones -->
        <div style="padding:1.25rem 1.5rem 1.5rem;">
            ${r.descripcion ? '' : ''}
            <h3 style="margin-bottom:0.5rem;font-size:0.9rem;color:#ff6b35;text-transform:uppercase;letter-spacing:.5px;">Ingredientes</h3>
            <ul style="padding-left:1.25rem;margin-bottom:1.25rem;color:#ccc;font-size:0.85rem;line-height:1.7;">${ingList}</ul>
            <h3 style="margin-bottom:0.5rem;font-size:0.9rem;color:#ff6b35;text-transform:uppercase;letter-spacing:.5px;">Instrucciones</h3>
            <ol style="padding-left:1.25rem;margin-bottom:1.5rem;color:#ccc;font-size:0.85rem;line-height:1.8;">${insList}</ol>
            <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
                <button onclick="aprobarReceta(${r.id});cerrarDetallePending()"
                    style="flex:1;padding:10px;background:#34A853;color:white;border:none;border-radius:8px;cursor:pointer;font-weight:600;">✓ Aprobar</button>
                <button onclick="rechazarReceta(${r.id});cerrarDetallePending()"
                    style="flex:1;padding:10px;background:#F44336;color:white;border:none;border-radius:8px;cursor:pointer;font-weight:600;">✕ Eliminar</button>
                <button onclick="cerrarDetallePending()"
                    style="padding:10px 20px;background:#2a2a2a;color:#ccc;border:1px solid #444;border-radius:8px;cursor:pointer;">Cerrar</button>
            </div>
        </div>`;
    document.getElementById('modalPendingDetalle').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function cerrarDetallePending() {
    document.getElementById('modalPendingDetalle').style.display = 'none';
    document.body.style.overflow = '';
}

async function aprobarReceta(id) {
    try {
        const res    = await fetch(API_URL + '/pro/recetas/approve', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const result = await res.json();
        if (result.success) {
            showToast('Receta aprobada — se quedará permanentemente', 'success');
            cargarPendingRecetas();
        } else {
            showToast('Error al aprobar', 'error');
        }
    } catch (e) {
        showToast('Error de comunicación', 'error');
    }
}

async function rechazarReceta(id) {
    if (!confirm('¿Eliminar esta receta?')) return;
    try {
        const res    = await fetch(API_URL + '/pro/recetas/delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const result = await res.json();
        if (result.success) {
            showToast('Receta eliminada', 'success');
            cargarPendingRecetas();
        } else {
            showToast('Error al eliminar', 'error');
        }
    } catch (e) {
        showToast('Error de comunicación', 'error');
    }
}

function filtrarProRecetas() {
    const q = document.getElementById('proRecetasSearch')?.value.toLowerCase().trim() || '';
    document.querySelectorAll('#proRecetasBody tr').forEach(tr => {
        tr.style.display = !q || tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}

async function guardarProReceta() {
    const form = document.getElementById('formRecetaPro');
    const formData = new FormData(form);

    try {
        const response = await fetch(API_URL + '/pro/recetas/save', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.success) {
            showToast(result.message, 'success');
            document.getElementById('modalRecetaPro').style.display = 'none';
            cargarProRecetas();
        } else {
            showToast(result.message || 'Error al guardar', 'error');
        }
    } catch (error) {
        showToast('Error de comunicación', 'error');
    }
}

async function eliminarProReceta(id) {
    if (!confirm('¿Estás seguro de que quieres eliminar esta receta?')) return;

    try {
        const response = await fetch(API_URL + '/pro/recetas/delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        const result = await response.json();

        if (result.success) {
            showToast(result.message, 'success');
            cargarProRecetas();
        } else {
            showToast(result.message || 'Error al eliminar', 'error');
        }
    } catch (error) {
        showToast('Error de comunicación', 'error');
    }
}

function esc(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function cap(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

/**
 * BIENESTAR - Gestión de Rutinas (Coach)
 */

let ejerciciosDisponibles = [];

const NIVEL_COLORS = { principiante: '#4caf50', intermedio: '#ff9800', avanzado: '#f44336' };

document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('proRutinasBody')) {
        cargarRutinas();
        cargarEjerciciosDisponibles();
        document.getElementById('btnNuevaRutina').addEventListener('click', () => abrirModalRutina());
    }
    if (document.getElementById('modalAsignarRutina')) {
        cargarRutinasSelector();
    }
});

async function cargarRutinas() {
    const tbody = document.getElementById('proRutinasBody');
    if (!tbody) return;
    try {
        const res = await fetch(API_URL + '/pro/rutinas');
        const data = await res.json();
        if (!data.success) throw new Error();
        if (!data.rutinas.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="empty-message">No tienes rutinas aún. ¡Crea tu primera!</td></tr>';
            return;
        }
        tbody.innerHTML = data.rutinas.map(r => `
            <tr>
                <td><strong>${escR(r.nombre)}</strong>${r.descripcion ? `<br><small style="color:#999;">${escR(r.descripcion.substring(0,60))}${r.descripcion.length>60?'…':''}</small>` : ''}</td>
                <td><span style="color:${NIVEL_COLORS[r.nivel]||'#999'};font-weight:600;font-size:0.82rem;">${escR(r.nivel)}</span></td>
                <td style="text-align:center;">${r.num_ejercicios}</td>
                <td style="color:#999;">${r.duracion_total ? r.duracion_total + ' min' : '—'}</td>
                <td>
                    <button class="btn btn-secondary btn-sm" onclick="editarRutina(${r.id})">Editar</button>
                    <button class="btn btn-sm" style="background:#ff6b35;color:white;border:none;margin-left:4px;" onclick="exportarRutinaPDF(${r.id})">PDF</button>
                    <button class="btn btn-sm" style="background:#c0392b;color:white;border:none;margin-left:4px;" onclick="eliminarRutina(${r.id},'${escR(r.nombre)}')">Eliminar</button>
                </td>
            </tr>
        `).join('');
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="5" class="empty-message">Error al cargar rutinas.</td></tr>';
    }
}

async function cargarEjerciciosDisponibles() {
    try {
        const res = await fetch(API_URL + '/ejercicios');
        const data = await res.json();
        if (data.success) ejerciciosDisponibles = data.ejercicios || [];
    } catch (e) {}
}

async function cargarRutinasSelector() {
    try {
        const res = await fetch(API_URL + '/pro/rutinas');
        const data = await res.json();
        if (!data.success) return;
        const sel = document.getElementById('asignarRutinaSelect');
        if (!sel) return;
        sel.innerHTML = '<option value="">— Elige una rutina —</option>' +
            data.rutinas.map(r => `<option value="${r.id}">${escR(r.nombre)} (${r.num_ejercicios} ejerc.)</option>`).join('');
    } catch (e) {}
}

function abrirModalRutina(id = null) {
    document.getElementById('rutina_id').value = id || '';
    document.getElementById('rutina_nombre').value = '';
    document.getElementById('rutina_descripcion').value = '';
    document.getElementById('rutina_nivel').value = 'principiante';
    document.getElementById('rutina_duracion').value = '';
    document.getElementById('rutinaEjerciciosList').innerHTML = '';
    document.getElementById('modalRutinaTitle').textContent = id ? 'Editar Rutina' : 'Nueva Rutina';
    document.getElementById('modalRutina').style.display = 'flex';
}

async function editarRutina(id) {
    abrirModalRutina(id);
    try {
        const res = await fetch(API_URL + '/pro/rutinas/detail?id=' + id);
        const data = await res.json();
        if (!data.success) throw new Error();
        const r = data.rutina;
        document.getElementById('rutina_nombre').value = r.nombre;
        document.getElementById('rutina_descripcion').value = r.descripcion || '';
        document.getElementById('rutina_nivel').value = r.nivel;
        document.getElementById('rutina_duracion').value = r.duracion_total || '';
        document.getElementById('rutinaEjerciciosList').innerHTML = '';
        (r.ejercicios || []).forEach(ej => agregarEjercicioRutina(ej));
    } catch (e) {
        showToastR('Error al cargar la rutina', 'error');
    }
}

function cerrarModalRutina() {
    document.getElementById('modalRutina').style.display = 'none';
}

function agregarEjercicioRutina(datos = null) {
    const container = document.getElementById('rutinaEjerciciosList');
    const row = document.createElement('div');
    row.className = 'rutina-ej-row';
    row.style.cssText = 'display:grid;grid-template-columns:2fr 1fr 1fr 1fr 1fr auto;gap:8px;align-items:end;margin-bottom:10px;padding:10px;background:rgba(255,107,53,0.06);border-radius:8px;border:1px solid rgba(255,107,53,0.15);';

    const optsEj = ejerciciosDisponibles.map(e =>
        `<option value="${e.id}" ${datos && datos.ejercicio_id == e.id ? 'selected' : ''}>${escR(e.titulo)}</option>`
    ).join('');

    row.innerHTML = `
        <div><label style="font-size:0.75rem;color:#999;display:block;margin-bottom:3px;">Ejercicio</label>
            <select class="rej-ejercicio" style="width:100%;padding:6px 8px;border-radius:6px;border:1px solid rgba(255,255,255,0.1);background:var(--color-bg-secondary,#f7f7f7);color:var(--color-text-primary,#111);font-size:0.85rem;">
                <option value="">— Elige —</option>${optsEj}
            </select></div>
        <div><label style="font-size:0.75rem;color:#999;display:block;margin-bottom:3px;">Series</label>
            <input type="number" class="rej-series" value="${datos ? datos.series || 3 : 3}" min="1" style="width:100%;padding:6px 8px;border-radius:6px;border:1px solid rgba(255,255,255,0.1);background:var(--color-bg-secondary,#f7f7f7);color:var(--color-text-primary,#111);font-size:0.85rem;"></div>
        <div><label style="font-size:0.75rem;color:#999;display:block;margin-bottom:3px;">Reps / Duración</label>
            <input type="text" class="rej-reps" value="${datos ? datos.repeticiones || '' : ''}" placeholder="Ej: 12 o 30 seg" style="width:100%;padding:6px 8px;border-radius:6px;border:1px solid rgba(255,255,255,0.1);background:var(--color-bg-secondary,#f7f7f7);color:var(--color-text-primary,#111);font-size:0.85rem;"></div>
        <div><label style="font-size:0.75rem;color:#999;display:block;margin-bottom:3px;">Descanso (seg)</label>
            <input type="number" class="rej-descanso" value="${datos ? datos.descanso_seg || 60 : 60}" min="0" style="width:100%;padding:6px 8px;border-radius:6px;border:1px solid rgba(255,255,255,0.1);background:var(--color-bg-secondary,#f7f7f7);color:var(--color-text-primary,#111);font-size:0.85rem;"></div>
        <div><label style="font-size:0.75rem;color:#999;display:block;margin-bottom:3px;">Notas</label>
            <input type="text" class="rej-notas" value="${datos ? escR(datos.notas || '') : ''}" placeholder="Opcional" style="width:100%;padding:6px 8px;border-radius:6px;border:1px solid rgba(255,255,255,0.1);background:var(--color-bg-secondary,#f7f7f7);color:var(--color-text-primary,#111);font-size:0.85rem;"></div>
        <div style="padding-bottom:2px;"><button type="button" onclick="this.closest('.rutina-ej-row').remove()" style="padding:6px 10px;border:1px solid #f44336;background:transparent;color:#f44336;border-radius:6px;cursor:pointer;font-size:1rem;line-height:1;">✕</button></div>
    `;
    container.appendChild(row);
}

async function guardarRutina() {
    const nombre = document.getElementById('rutina_nombre').value.trim();
    if (!nombre) { showToastR('El nombre es requerido', 'error'); return; }

    const rows = document.querySelectorAll('.rutina-ej-row');
    const ejercicios = [];
    for (const row of rows) {
        const ejId = row.querySelector('.rej-ejercicio').value;
        if (!ejId) { showToastR('Selecciona un ejercicio en cada fila', 'error'); return; }
        ejercicios.push({
            ejercicio_id: ejId,
            series: parseInt(row.querySelector('.rej-series').value) || 3,
            repeticiones: row.querySelector('.rej-reps').value.trim() || null,
            descanso_seg: parseInt(row.querySelector('.rej-descanso').value) || 60,
            notas: row.querySelector('.rej-notas').value.trim() || null,
        });
    }

    const payload = {
        id: document.getElementById('rutina_id').value || null,
        nombre,
        descripcion: document.getElementById('rutina_descripcion').value.trim() || null,
        nivel: document.getElementById('rutina_nivel').value,
        duracion_total: parseInt(document.getElementById('rutina_duracion').value) || null,
        ejercicios,
    };

    try {
        const res = await fetch(API_URL + '/pro/rutinas/save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        cerrarModalRutina();
        showToastR(payload.id ? 'Rutina actualizada' : 'Rutina creada');
        cargarRutinas();
        cargarRutinasSelector();
    } catch (e) {
        showToastR(e.message || 'Error al guardar', 'error');
    }
}

async function eliminarRutina(id, nombre) {
    if (!confirm(`¿Eliminar la rutina "${nombre}"?\nEsta acción no se puede deshacer.`)) return;
    try {
        const res = await fetch(API_URL + '/pro/rutinas/delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        showToastR('Rutina eliminada');
        cargarRutinas();
    } catch (e) {
        showToastR(e.message || 'Error al eliminar', 'error');
    }
}

async function exportarRutinaPDF(id) {
    try {
        const res = await fetch(API_URL + '/pro/rutinas/detail?id=' + id);
        const data = await res.json();
        if (!data.success) throw new Error();
        generarRutinaPDF(data.rutina);
    } catch (e) {
        showToastR('Error al generar PDF', 'error');
    }
}

function generarRutinaPDF(rutina) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    const orange = [255, 107, 53];
    const dark = [17, 17, 17];
    const gray = [100, 100, 100];
    const especialista = (typeof PROFESSIONAL_USER !== 'undefined' && PROFESSIONAL_USER.nombre) ? PROFESSIONAL_USER.nombre : '';

    // Header
    doc.setFillColor(...orange);
    doc.rect(0, 0, 210, 32, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(17);
    doc.setFont('helvetica', 'bold');
    doc.text('RUTINA DE ENTRENAMIENTO', 14, 13);
    doc.setFontSize(10);
    doc.setFont('helvetica', 'normal');
    doc.text('Bienestar — Plan Personalizado', 14, 21);
    if (especialista) {
        doc.text('Coach: ' + especialista, 14, 28);
    }

    // Rutina info
    let y = 42;
    doc.setTextColor(...dark);
    doc.setFontSize(15);
    doc.setFont('helvetica', 'bold');
    doc.text(rutina.nombre, 14, y);
    y += 7;

    const nivelCap = (rutina.nivel || 'principiante').charAt(0).toUpperCase() + (rutina.nivel || '').slice(1);
    doc.setFontSize(9);
    doc.setFont('helvetica', 'normal');
    doc.setTextColor(...gray);
    const infoLinea = [
        'Nivel: ' + nivelCap,
        'Ejercicios: ' + (rutina.ejercicios || []).length,
        'Duración: ' + (rutina.duracion_total ? rutina.duracion_total + ' min' : 'N/D'),
    ].join('   |   ');
    doc.text(infoLinea, 14, y);
    y += 5;

    if (rutina.descripcion) {
        y += 2;
        const lines = doc.splitTextToSize(rutina.descripcion, 182);
        doc.text(lines, 14, y);
        y += lines.length * 4.5 + 1;
    }

    // Separator
    y += 4;
    doc.setDrawColor(...orange);
    doc.setLineWidth(0.5);
    doc.line(14, y, 196, y);
    y += 7;

    // Exercises table — columnas suman 182mm (ancho útil exacto)
    if (rutina.ejercicios && rutina.ejercicios.length) {
        const tableBody = rutina.ejercicios.map((ej, i) => [
            i + 1,
            ej.ejercicio_titulo || '—',
            ej.series != null ? String(ej.series) : '—',
            ej.repeticiones || '—',
            ej.descanso_seg != null ? ej.descanso_seg + 's' : '—',
            ej.musculo_objetivo || '—',
            ej.notas || '',
        ]);

        doc.autoTable({
            startY: y,
            margin: { left: 14, right: 14 },
            head: [['#', 'Ejercicio', 'Series', 'Reps', 'Descanso', 'Músculo', 'Notas']],
            body: tableBody,
            styles: {
                fontSize: 9,
                cellPadding: { top: 4, right: 4, bottom: 4, left: 4 },
                valign: 'middle',
                overflow: 'linebreak',
            },
            headStyles: {
                fillColor: orange,
                textColor: 255,
                fontStyle: 'bold',
                valign: 'middle',
                halign: 'center',
            },
            alternateRowStyles: { fillColor: [255, 248, 244] },
            columnStyles: {
                0: { cellWidth: 10, halign: 'center' },
                1: { cellWidth: 52 },
                2: { cellWidth: 15, halign: 'center' },
                3: { cellWidth: 22, halign: 'center' },
                4: { cellWidth: 18, halign: 'center' },
                5: { cellWidth: 30 },
                6: { cellWidth: 35 },
            },
        });
    } else {
        doc.setTextColor(...gray);
        doc.setFontSize(10);
        doc.text('Sin ejercicios registrados.', 14, y);
    }

    // Footer
    const pageCount = doc.internal.getNumberOfPages();
    const fechaHoy = new Date().toLocaleDateString('es-MX', { day: '2-digit', month: 'long', year: 'numeric' });
    for (let i = 1; i <= pageCount; i++) {
        doc.setPage(i);
        doc.setFontSize(8);
        doc.setTextColor(180, 180, 180);
        doc.text('Bienestar — Generado el ' + fechaHoy, 14, 290);
        doc.text('Página ' + i + ' de ' + pageCount, 196, 290, { align: 'right' });
    }

    doc.save('rutina-' + rutina.nombre.replace(/[^a-z0-9]/gi, '_').toLowerCase() + '.pdf');
}

// ─── Asignar rutina a usuario ─────────────────────────────────────

function abrirModalAsignarRutina() {
    if (!planUsuarioActual) { showToastR('Selecciona un usuario primero', 'error'); return; }
    const notas = document.getElementById('asignarRutinaNotas');
    if (notas) notas.value = '';
    document.getElementById('modalAsignarRutina').style.display = 'flex';
}

async function confirmarAsignarRutina() {
    const rutinaId = document.getElementById('asignarRutinaSelect').value;
    const notas = document.getElementById('asignarRutinaNotas').value.trim();
    if (!rutinaId) { showToastR('Selecciona una rutina', 'error'); return; }

    try {
        const res = await fetch(API_URL + '/pro/rutinas/asignar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ usuario_id: planUsuarioActual, rutina_id: rutinaId, notas: notas || null }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        document.getElementById('modalAsignarRutina').style.display = 'none';
        showToastR(data.message || 'Rutina asignada');
        if (typeof cargarPlanUsuario === 'function') cargarPlanUsuario(planUsuarioActual);
    } catch (e) {
        showToastR(e.message || 'Error al asignar', 'error');
    }
}

function showToastR(msg, type = 'success') {
    if (typeof showToast === 'function') { showToast(msg, type); return; }
    const toast = document.createElement('div');
    toast.textContent = msg;
    toast.style.cssText = `position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:${type === 'error' ? '#f44336' : '#4caf50'};color:#fff;padding:10px 24px;border-radius:8px;z-index:9999;font-size:0.9rem;`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

function escR(str) {
    if (str === null || str === undefined) return '';
    const d = document.createElement('div');
    d.textContent = String(str);
    return d.innerHTML;
}

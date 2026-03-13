/**
 * BIENESTAR - Gestión de Planes Alimenticios (Nutriólogo)
 */

let recetasDisponiblesPA = [];

const DIAS_SEMANA = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
const TIEMPOS_COMIDA = ['desayuno', 'almuerzo', 'merienda', 'cena', 'comida'];

document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('proPlanesAlimBody')) {
        cargarPlanesAlimenticios();
        cargarRecetasDisponiblesPA();
        document.getElementById('btnNuevoPlanAlim').addEventListener('click', () => abrirModalPlanAlim());
    }
    if (document.getElementById('modalAsignarPlanAlim')) {
        cargarPlanesAlimSelector();
    }
});

async function cargarPlanesAlimenticios() {
    const tbody = document.getElementById('proPlanesAlimBody');
    if (!tbody) return;
    try {
        const res = await fetch(API_URL + '/pro/planes-alimenticios');
        const data = await res.json();
        if (!data.success) throw new Error();
        if (!data.planes.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="empty-message">No tienes planes alimenticios aún. ¡Crea el primero!</td></tr>';
            return;
        }
        tbody.innerHTML = data.planes.map(p => `
            <tr>
                <td><strong>${escPA(p.nombre)}</strong>${p.descripcion ? `<br><small style="color:#999;">${escPA(p.descripcion.substring(0,60))}${p.descripcion.length>60?'…':''}</small>` : ''}</td>
                <td style="color:#999;font-size:0.85rem;">${p.objetivo ? escPA(p.objetivo) : '—'}</td>
                <td style="text-align:center;">${p.duracion_semanas} sem.</td>
                <td style="text-align:center;">${p.num_recetas}</td>
                <td>
                    <button class="btn btn-secondary btn-sm" onclick="editarPlanAlim(${p.id})">Editar</button>
                    <button class="btn btn-sm" style="background:#4caf50;color:white;border:none;margin-left:4px;" onclick="exportarPlanAlimPDF(${p.id})">PDF</button>
                    <button class="btn btn-sm" style="background:#c0392b;color:white;border:none;margin-left:4px;" onclick="eliminarPlanAlim(${p.id},'${escPA(p.nombre)}')">Eliminar</button>
                </td>
            </tr>
        `).join('');
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="5" class="empty-message">Error al cargar planes.</td></tr>';
    }
}

async function cargarRecetasDisponiblesPA() {
    try {
        const res = await fetch(API_URL + '/pro/recetas');
        const data = await res.json();
        if (data.success) recetasDisponiblesPA = data.recetas || [];
    } catch (e) {}
}

async function cargarPlanesAlimSelector() {
    try {
        const res = await fetch(API_URL + '/pro/planes-alimenticios');
        const data = await res.json();
        if (!data.success) return;
        const sel = document.getElementById('asignarPlanAlimSelect');
        if (!sel) return;
        sel.innerHTML = '<option value="">— Elige un plan —</option>' +
            data.planes.map(p => `<option value="${p.id}">${escPA(p.nombre)} (${p.num_recetas} recetas)</option>`).join('');
    } catch (e) {}
}

function abrirModalPlanAlim(id = null) {
    document.getElementById('plan_alim_id').value = id || '';
    document.getElementById('plan_alim_nombre').value = '';
    document.getElementById('plan_alim_descripcion').value = '';
    document.getElementById('plan_alim_objetivo').value = '';
    document.getElementById('plan_alim_duracion').value = '1';
    document.getElementById('planAlimRecetasList').innerHTML = '';
    document.getElementById('modalPlanAlimTitle').textContent = id ? 'Editar Plan Alimenticio' : 'Nuevo Plan Alimenticio';
    document.getElementById('modalPlanAlim').style.display = 'flex';
}

async function editarPlanAlim(id) {
    abrirModalPlanAlim(id);
    try {
        const res = await fetch(API_URL + '/pro/planes-alimenticios/detail?id=' + id);
        const data = await res.json();
        if (!data.success) throw new Error();
        const p = data.plan;
        document.getElementById('plan_alim_nombre').value = p.nombre;
        document.getElementById('plan_alim_descripcion').value = p.descripcion || '';
        document.getElementById('plan_alim_objetivo').value = p.objetivo || '';
        document.getElementById('plan_alim_duracion').value = p.duracion_semanas || 1;
        document.getElementById('planAlimRecetasList').innerHTML = '';
        (p.recetas || []).forEach(r => agregarRecetaPlanAlim(r));
    } catch (e) {
        showToastPA('Error al cargar el plan', 'error');
    }
}

function cerrarModalPlanAlim() {
    document.getElementById('modalPlanAlim').style.display = 'none';
}

function agregarRecetaPlanAlim(datos = null) {
    const container = document.getElementById('planAlimRecetasList');
    const row = document.createElement('div');
    row.className = 'plan-rec-row';
    row.style.cssText = 'display:grid;grid-template-columns:2fr 1fr 1fr 0.6fr 1.5fr auto;gap:8px;align-items:end;margin-bottom:10px;padding:10px;background:rgba(76,175,80,0.06);border-radius:8px;border:1px solid rgba(76,175,80,0.2);';

    const optsRec = recetasDisponiblesPA.map(r =>
        `<option value="${r.id}" ${datos && datos.receta_id == r.id ? 'selected' : ''}>${escPA(r.titulo)}</option>`
    ).join('');

    const optsDia = DIAS_SEMANA.slice(1).map((d, i) =>
        `<option value="${i+1}" ${datos && datos.dia_semana == i+1 ? 'selected' : ''}>${d}</option>`
    ).join('');

    const optsTiempo = TIEMPOS_COMIDA.map(t =>
        `<option value="${t}" ${datos && datos.tiempo_comida === t ? 'selected' : ''}>${t.charAt(0).toUpperCase() + t.slice(1)}</option>`
    ).join('');

    const inputStyle = 'width:100%;padding:6px 8px;border-radius:6px;border:1px solid rgba(255,255,255,0.1);background:var(--color-bg-secondary,#f7f7f7);color:var(--color-text-primary,#111);font-size:0.85rem;';

    row.innerHTML = `
        <div><label style="font-size:0.75rem;color:#999;display:block;margin-bottom:3px;">Receta</label>
            <select class="prec-receta" style="${inputStyle}">
                <option value="">— Elige —</option>${optsRec}
            </select></div>
        <div><label style="font-size:0.75rem;color:#999;display:block;margin-bottom:3px;">Día</label>
            <select class="prec-dia" style="${inputStyle}">${optsDia}</select></div>
        <div><label style="font-size:0.75rem;color:#999;display:block;margin-bottom:3px;">Momento</label>
            <select class="prec-tiempo" style="${inputStyle}">${optsTiempo}</select></div>
        <div><label style="font-size:0.75rem;color:#999;display:block;margin-bottom:3px;">Porciones</label>
            <input type="number" class="prec-porciones" value="${datos ? datos.porciones || 1 : 1}" min="0.5" step="0.5" style="${inputStyle}"></div>
        <div><label style="font-size:0.75rem;color:#999;display:block;margin-bottom:3px;">Notas</label>
            <input type="text" class="prec-notas" value="${datos ? escPA(datos.notas || '') : ''}" placeholder="Opcional" style="${inputStyle}"></div>
        <div style="padding-bottom:2px;"><button type="button" onclick="this.closest('.plan-rec-row').remove()" style="padding:6px 10px;border:1px solid #f44336;background:transparent;color:#f44336;border-radius:6px;cursor:pointer;font-size:1rem;line-height:1;">✕</button></div>
    `;
    container.appendChild(row);
}

async function guardarPlanAlim() {
    const nombre = document.getElementById('plan_alim_nombre').value.trim();
    if (!nombre) { showToastPA('El nombre es requerido', 'error'); return; }

    const rows = document.querySelectorAll('.plan-rec-row');
    const recetas = [];
    for (const row of rows) {
        const recId = row.querySelector('.prec-receta').value;
        if (!recId) { showToastPA('Selecciona una receta en cada fila', 'error'); return; }
        recetas.push({
            receta_id: recId,
            dia_semana: parseInt(row.querySelector('.prec-dia').value),
            tiempo_comida: row.querySelector('.prec-tiempo').value,
            porciones: parseFloat(row.querySelector('.prec-porciones').value) || 1,
            notas: row.querySelector('.prec-notas').value.trim() || null,
        });
    }

    const payload = {
        id: document.getElementById('plan_alim_id').value || null,
        nombre,
        descripcion: document.getElementById('plan_alim_descripcion').value.trim() || null,
        objetivo: document.getElementById('plan_alim_objetivo').value.trim() || null,
        duracion_semanas: parseInt(document.getElementById('plan_alim_duracion').value) || 1,
        recetas,
    };

    try {
        const res = await fetch(API_URL + '/pro/planes-alimenticios/save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        cerrarModalPlanAlim();
        showToastPA(payload.id ? 'Plan actualizado' : 'Plan creado');
        cargarPlanesAlimenticios();
        cargarPlanesAlimSelector();
    } catch (e) {
        showToastPA(e.message || 'Error al guardar', 'error');
    }
}

async function eliminarPlanAlim(id, nombre) {
    if (!confirm(`¿Eliminar el plan "${nombre}"?\nEsta acción no se puede deshacer.`)) return;
    try {
        const res = await fetch(API_URL + '/pro/planes-alimenticios/delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        showToastPA('Plan eliminado');
        cargarPlanesAlimenticios();
    } catch (e) {
        showToastPA(e.message || 'Error al eliminar', 'error');
    }
}

async function exportarPlanAlimPDF(id) {
    try {
        const res = await fetch(API_URL + '/pro/planes-alimenticios/detail?id=' + id);
        const data = await res.json();
        if (!data.success) throw new Error();
        generarPlanAlimPDF(data.plan);
    } catch (e) {
        showToastPA('Error al generar PDF', 'error');
    }
}

function generarPlanAlimPDF(plan) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    const green = [76, 175, 80];
    const dark = [17, 17, 17];
    const gray = [100, 100, 100];
    const especialista = (typeof PROFESSIONAL_USER !== 'undefined' && PROFESSIONAL_USER.nombre) ? PROFESSIONAL_USER.nombre : '';

    // Header
    doc.setFillColor(...green);
    doc.rect(0, 0, 210, 32, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(17);
    doc.setFont('helvetica', 'bold');
    doc.text('PLAN ALIMENTICIO', 14, 13);
    doc.setFontSize(10);
    doc.setFont('helvetica', 'normal');
    doc.text('Bienestar — Plan Nutricional Personalizado', 14, 21);
    if (especialista) {
        doc.text('Nutriólogo/a: ' + especialista, 14, 28);
    }

    // Plan info
    let y = 42;
    doc.setTextColor(...dark);
    doc.setFontSize(15);
    doc.setFont('helvetica', 'bold');
    doc.text(plan.nombre, 14, y);
    y += 7;

    doc.setFontSize(9);
    doc.setFont('helvetica', 'normal');
    doc.setTextColor(...gray);
    const partes = ['Duración: ' + plan.duracion_semanas + ' semana(s)', 'Recetas: ' + (plan.recetas || []).length];
    if (plan.objetivo) partes.push('Objetivo: ' + plan.objetivo);
    doc.text(partes.join('   |   '), 14, y);
    y += 5;

    if (plan.descripcion) {
        y += 2;
        const lines = doc.splitTextToSize(plan.descripcion, 182);
        doc.text(lines, 14, y);
        y += lines.length * 4.5 + 1;
    }

    y += 4;
    doc.setDrawColor(...green);
    doc.setLineWidth(0.5);
    doc.line(14, y, 196, y);
    y += 7;

    // Group recipes by day — columnas suman 182mm
    if (plan.recetas && plan.recetas.length) {
        const byDay = {};
        plan.recetas.forEach(r => {
            const d = r.dia_semana;
            if (!byDay[d]) byDay[d] = [];
            byDay[d].push(r);
        });

        const tableBody = [];
        Object.keys(byDay).sort((a, b) => a - b).forEach(dia => {
            byDay[dia].forEach((r, i) => {
                const diaLabel = i === 0 ? (DIAS_SEMANA[dia] || 'Día ' + dia) : '';
                tableBody.push([
                    diaLabel,
                    r.tiempo_comida ? r.tiempo_comida.charAt(0).toUpperCase() + r.tiempo_comida.slice(1) : '—',
                    r.receta_titulo || '—',
                    r.porciones != null ? String(r.porciones) : '1',
                    r.calorias ? r.calorias + ' kcal' : '—',
                    r.notas || '',
                ]);
            });
        });

        doc.autoTable({
            startY: y,
            margin: { left: 14, right: 14 },
            head: [['Día', 'Momento', 'Receta', 'Porc.', 'Calorías', 'Notas']],
            body: tableBody,
            styles: {
                fontSize: 9,
                cellPadding: { top: 4, right: 4, bottom: 4, left: 4 },
                valign: 'middle',
                overflow: 'linebreak',
            },
            headStyles: {
                fillColor: green,
                textColor: 255,
                fontStyle: 'bold',
                valign: 'middle',
                halign: 'center',
            },
            alternateRowStyles: { fillColor: [240, 249, 240] },
            columnStyles: {
                0: { cellWidth: 25, fontStyle: 'bold' },
                1: { cellWidth: 22, halign: 'center' },
                2: { cellWidth: 58 },
                3: { cellWidth: 15, halign: 'center' },
                4: { cellWidth: 24, halign: 'center' },
                5: { cellWidth: 38 },
            },
        });
    } else {
        doc.setTextColor(...gray);
        doc.setFontSize(10);
        doc.text('Sin recetas registradas.', 14, y);
    }

    // Footer
    const pageCount = doc.internal.getNumberOfPages();
    const fechaHoy = new Date().toLocaleDateString('es-MX', { day: '2-digit', month: 'long', year: 'numeric' });
    for (let i = 1; i <= pageCount; i++) {
        doc.setPage(i);
        doc.setFontSize(8);
        doc.setTextColor(180, 180, 180);
        doc.text('Bienestar — Generado el ' + fechaHoy, 14, 290);
        doc.text(`Página ${i} de ${pageCount}`, 196, 290, { align: 'right' });
    }

    doc.save(`plan-alimenticio-${plan.nombre.replace(/[^a-z0-9]/gi, '_').toLowerCase()}.pdf`);
}

// ─── Asignar plan a usuario ────────────────────────────────────────

function abrirModalAsignarPlanAlim() {
    if (!planUsuarioActual) { showToastPA('Selecciona un usuario primero', 'error'); return; }
    const notas = document.getElementById('asignarPlanAlimNotas');
    if (notas) notas.value = '';
    document.getElementById('modalAsignarPlanAlim').style.display = 'flex';
}

async function confirmarAsignarPlanAlim() {
    const planId = document.getElementById('asignarPlanAlimSelect').value;
    const notas = document.getElementById('asignarPlanAlimNotas').value.trim();
    if (!planId) { showToastPA('Selecciona un plan', 'error'); return; }

    try {
        const res = await fetch(API_URL + '/pro/planes-alimenticios/asignar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ usuario_id: planUsuarioActual, plan_id: planId, notas: notas || null }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        document.getElementById('modalAsignarPlanAlim').style.display = 'none';
        showToastPA(data.message || 'Plan asignado');
        if (typeof cargarPlanUsuario === 'function') cargarPlanUsuario(planUsuarioActual);
    } catch (e) {
        showToastPA(e.message || 'Error al asignar', 'error');
    }
}

function showToastPA(msg, type = 'success') {
    if (typeof showToast === 'function') { showToast(msg, type); return; }
    const toast = document.createElement('div');
    toast.textContent = msg;
    toast.style.cssText = `position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:${type === 'error' ? '#f44336' : '#4caf50'};color:#fff;padding:10px 24px;border-radius:8px;z-index:9999;font-size:0.9rem;`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

function escPA(str) {
    if (str === null || str === undefined) return '';
    const d = document.createElement('div');
    d.textContent = String(str);
    return d.innerHTML;
}

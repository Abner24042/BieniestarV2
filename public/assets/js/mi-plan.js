/**
 * BIENIESTAR - Mi Plan Personal
 */

let planData = {};

document.addEventListener('DOMContentLoaded', function () {
    cargarPlan();
    initTabs();
});

async function cargarPlan() {
    try {
        const res  = await fetch(API_URL + '/mi-plan');
        const data = await res.json();
        if (!data.success) throw new Error();
        planData = data.plan;
        renderPlan(planData);
    } catch (e) {
        ['planEjerciciosGrid','planRecetasGrid','planRecomendacionesGrid'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = '<p class="plan-loading">No se pudo cargar el plan.</p>';
        });
    }
}

function renderPlan(plan) {
    // Counters
    document.getElementById('numEjercicios').textContent      = plan.ejercicios.length;
    document.getElementById('numRecetas').textContent         = plan.recetas.length;
    document.getElementById('numRecomendaciones').textContent = plan.recomendaciones.length;

    // Ejercicios
    const gE = document.getElementById('planEjerciciosGrid');
    gE.innerHTML = plan.ejercicios.length
        ? plan.ejercicios.map(e => ejercicioCard(e)).join('')
        : '<p class="plan-loading">No tienes ejercicios asignados aún.</p>';

    // Recetas
    const gR = document.getElementById('planRecetasGrid');
    const dias = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
    const hoy = dias[new Date().getDay()];
    gR.innerHTML = plan.recetas.length
        ? plan.recetas.map(r => recetaCard(r)).join('')
        : `<p class="plan-loading">No tienes recetas para ${hoy} aún.</p>`;

    // Recomendaciones
    const gRec = document.getElementById('planRecomendacionesGrid');
    gRec.innerHTML = plan.recomendaciones.length
        ? plan.recomendaciones.map(r => recomendacionCard(r)).join('')
        : '<p class="plan-loading" style="padding:40px 0;text-align:center;">No hay recomendaciones aún.</p>';
}

function ejercicioCard(e) {
    const img   = e.imagen ? `<img src="${esc(e.imagen)}" class="plan-card-image" loading="lazy" onerror="this.style.display='none'">` : '';
    const nivel = cap(e.nivel || 'principiante');
    const tipo  = cap(e.tipo  || 'ejercicio');
    const notas = e.notas ? `<div class="plan-card-notas">📝 ${esc(e.notas)}</div>` : '';
    return `
    <div class="plan-card" onclick="openEjercicioModal(${e.id})">
        ${img}
        <div class="plan-card-body">
            <div class="plan-card-badges">
                <span class="plan-card-badge">${tipo}</span>
                <span class="plan-card-badge secondary">${nivel}</span>
            </div>
            <h3 class="plan-card-title">${esc(e.titulo)}</h3>
            <p class="plan-card-meta">${e.duracion ? '⏱ ' + e.duracion + ' min' : ''}${e.calorias_quemadas ? '  🔥 ' + e.calorias_quemadas + ' kcal' : ''}</p>
            ${e.musculo_objetivo ? `<p class="plan-card-meta">💪 ${esc(cap(e.musculo_objetivo))}</p>` : ''}
            ${notas}
        </div>
    </div>`;
}

function recetaCard(r) {
    const img  = r.imagen ? `<img src="${esc(r.imagen)}" class="plan-card-image" loading="lazy" onerror="this.style.display='none'">` : '';
    const cat  = cap(r.categoria || 'receta');
    const notas = r.notas ? `<div class="plan-card-notas">📝 ${esc(r.notas)}</div>` : '';
    return `
    <div class="plan-card" onclick="openRecetaModal(${r.id})">
        ${img}
        <div class="plan-card-body">
            <div class="plan-card-badges">
                <span class="plan-card-badge">${cat}</span>
            </div>
            <h3 class="plan-card-title">${esc(r.titulo)}</h3>
            <p class="plan-card-meta">${r.tiempo_preparacion ? '⏱ ' + r.tiempo_preparacion + ' min' : ''}${r.calorias ? '  🔥 ' + Math.round(r.calorias) + ' kcal' : ''}</p>
            ${notas}
        </div>
    </div>`;
}

function recomendacionCard(r) {
    const tipoColor = { psicologia:'#9c27b0', ejercicio:'#ff6b35', alimentacion:'#4caf50', general:'#2196f3' };
    const color = tipoColor[r.tipo] || tipoColor.general;
    return `
    <div class="recomendacion-card" style="border-left-color:${color}">
        <div class="recomendacion-tipo" style="color:${color};">${esc(cap(r.tipo))}</div>
        <div class="recomendacion-titulo">${esc(r.titulo)}</div>
        ${r.contenido ? `<div class="recomendacion-contenido">${esc(r.contenido)}</div>` : ''}
        <div class="recomendacion-meta">— ${esc(r.profesional_id)}</div>
    </div>`;
}

function openEjercicioModal(id) {
    const e = planData.ejercicios.find(x => x.id == id);
    if (!e) return;
    document.getElementById('planModalTitle').textContent = e.titulo;
    const img = e.imagen ? `<img src="${esc(e.imagen)}" class="plan-modal-img" onerror="this.style.display='none'">` : '';
    const stats = [
        e.duracion          ? statBox('Duración', e.duracion + ' min')         : '',
        statBox('Nivel', cap(e.nivel || 'principiante')),
        statBox('Tipo',  cap(e.tipo  || 'ejercicio')),
        e.calorias_quemadas ? statBox('Calorías', e.calorias_quemadas + ' kcal') : '',
        e.musculo_objetivo  ? statBox('Músculo',  cap(e.musculo_objetivo))       : '',
        e.equipamiento      ? statBox('Equipo',   cap(e.equipamiento))           : '',
    ].join('');
    const instrucciones = formatList(e.instrucciones);
    document.getElementById('planModalBody').innerHTML = `
        ${img}
        <div class="plan-modal-stats">${stats}</div>
        ${e.descripcion ? `<p style="color:var(--color-text-secondary);margin-bottom:16px;">${esc(e.descripcion)}</p>` : ''}
        ${instrucciones ? `<h3 style="margin-bottom:10px;">Instrucciones</h3><ol style="padding-left:18px;line-height:1.8;">${instrucciones}</ol>` : ''}
        ${e.video_url ? `<a href="${esc(e.video_url)}" target="_blank" rel="noopener" style="display:inline-block;margin-top:14px;color:#ff6b35;font-weight:600;">▶ Ver video</a>` : ''}
    `;
    document.getElementById('planModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function openRecetaModal(id) {
    const r = planData.recetas.find(x => x.id == id);
    if (!r) return;
    document.getElementById('planModalTitle').textContent = r.titulo;
    const img = r.imagen ? `<img src="${esc(r.imagen)}" class="plan-modal-img" onerror="this.style.display='none'">` : '';
    const stats = [
        r.tiempo_preparacion ? statBox('Tiempo',    r.tiempo_preparacion + ' min') : '',
        r.porciones          ? statBox('Porciones', r.porciones)                   : '',
        r.calorias           ? statBox('Calorías',  Math.round(r.calorias) + ' kcal') : '',
        statBox('Categoría', cap(r.categoria || 'receta')),
    ].join('');
    document.getElementById('planModalBody').innerHTML = `
        ${img}
        <div class="plan-modal-stats">${stats}</div>
        ${r.descripcion ? `<p style="color:var(--color-text-secondary);margin-bottom:16px;">${esc(r.descripcion)}</p>` : ''}
        ${r.ingredientes  ? `<h3 style="margin-bottom:8px;">Ingredientes</h3><ul style="padding-left:18px;line-height:1.8;">${formatList(r.ingredientes, 'li')}</ul>` : ''}
        ${r.instrucciones ? `<h3 style="margin:14px 0 8px;">Preparación</h3><ol style="padding-left:18px;line-height:1.8;">${formatList(r.instrucciones)}</ol>` : ''}
    `;
    document.getElementById('planModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closePlanModal(event) {
    if (event && event.target !== document.getElementById('planModal')) return;
    document.getElementById('planModal').classList.remove('open');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closePlanModal();
});

function initTabs() {
    document.querySelectorAll('.plan-tab').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.plan-tab').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.plan-section').forEach(s => s.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('tab-' + this.dataset.tab).classList.add('active');
        });
    });
}

// Helpers
function statBox(label, value) {
    return `<div class="stat-box"><span class="stat-label">${label}</span><span class="stat-value">${esc(String(value))}</span></div>`;
}

function formatList(text, tag = 'li') {
    if (!text) return '';
    const items = text.includes('\n') ? text.split('\n') : text.split(',');
    return items.map(i => i.trim()).filter(i => i).map(i => `<${tag}>${esc(i)}</${tag}>`).join('');
}

function cap(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function esc(str) {
    if (str === null || str === undefined) return '';
    const d = document.createElement('div');
    d.textContent = String(str);
    return d.innerHTML;
}

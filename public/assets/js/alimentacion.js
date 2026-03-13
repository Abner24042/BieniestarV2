document.addEventListener('DOMContentLoaded', function() {
    setAutoFilterButton();
    loadRecetas();
    initFilters();
    initSearch();
});

function getAutoCategory() {
    const h = new Date().getHours();
    if (h >= 6  && h < 12) return 'desayuno';
    if (h >= 12 && h < 18) return 'comida';
    if (h >= 18)            return 'cena';
    return 'all';
}

function setAutoFilterButton() {
    const cat = getAutoCategory();
    document.querySelectorAll('.filter-btn').forEach(b => {
        b.classList.remove('active');
        if (b.dataset.category === cat || (cat === 'all' && b.dataset.category === 'all')) {
            b.classList.add('active');
        }
    });
}

let recipesData      = [];
let filteredRecipes  = [];
let recipeVisible    = 4;

function porFilaR() {
    const el = document.getElementById('recipesGrid');
    const w  = el ? (el.clientWidth || el.offsetWidth) : (window.innerWidth - 260);
    return Math.max(1, Math.floor((w + 16) / (320 + 16)));
}

async function loadRecetas() {
    try {
        const response = await fetch(API_URL + '/recetas');
        const data = await response.json();

        if (data.success) {
            recipesData = data.recetas || [];
            applyRecipeFilter();
        } else {
            document.getElementById('recipesGrid').innerHTML =
                '<p style="text-align:center;color:#999;grid-column:1/-1;">No hay recetas disponibles</p>';
        }
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('recipesGrid').innerHTML =
            '<p style="text-align:center;color:#e53935;grid-column:1/-1;">Error al cargar recetas</p>';
    }
}

function applyRecipeFilter() {
    const activeBtn = document.querySelector('.filter-btn.active');
    const cat  = activeBtn ? activeBtn.dataset.category : 'all';
    const term = (document.getElementById('searchRecipes')?.value || '').toLowerCase().trim();

    filteredRecipes = recipesData.filter(r => {
        const catOk = cat === 'all' || (r.categoria || '').toLowerCase() === cat;
        if (!term) return catOk;
        const haystack = [
            r.titulo,
            r.categoria,
            r.tipo_cocina,
            r.fuente,
            (() => { try { return JSON.parse(r.etiquetas_dieta || '[]').join(' '); } catch(e) { return ''; } })(),
            (() => { try { return JSON.parse(r.etiquetas_salud || '[]').join(' '); } catch(e) { return ''; } })(),
        ].map(v => (v || '').toLowerCase()).join(' ');
        return catOk && haystack.includes(term);
    });

    recipeVisible = porFilaR();
    renderRecetasPaginated();
}

function renderRecetasPaginated() {
    const grid    = document.getElementById('recipesGrid');
    const visible = filteredRecipes.slice(0, recipeVisible);

    if (filteredRecipes.length === 0) {
        grid.innerHTML = '<p style="text-align:center;color:#999;grid-column:1/-1;">No hay recetas disponibles</p>';
        return;
    }

    let html = visible.map(r => renderRecetaCard(r)).join('');

    if (recipeVisible < filteredRecipes.length) {
        const restantes = filteredRecipes.length - recipeVisible;
        html += `<div style="grid-column:1/-1;text-align:center;margin:1.5rem 0 0.5rem;">
            <button class="btn btn-secondary" onclick="mostrarMasRecetas()" style="min-width:190px;">
                Mostrar más (${restantes} restante${restantes !== 1 ? 's' : ''})
            </button>
        </div>`;
    }

    grid.innerHTML = html;
}

function mostrarMasRecetas() {
    recipeVisible += porFilaR();
    renderRecetasPaginated();
}

function renderRecetaCard(r) {
    const img = r.imagen || 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600&q=80';
    const cat = capitalize(r.categoria || 'comida');

    return `<div class="recipe-card" data-category="${escapeHtml(r.categoria)}" onclick="showRecipeModal(${r.id})" style="cursor:pointer;">
        <div class="recipe-image">
            <img src="${escapeHtml(img)}" alt="${escapeHtml(r.titulo)}"
                 onerror="this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600&q=80'">
            <span class="recipe-badge">${escapeHtml(cat)}</span>
        </div>
        <div class="recipe-content">
            <h3>${escapeHtml(r.titulo)}</h3>
            <div class="recipe-meta">
                <div class="meta-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    ${r.tiempo_preparacion ? r.tiempo_preparacion + ' min' : '—'}
                </div>
                <div class="meta-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Z"/></svg>
                    ${r.porciones ? r.porciones + ' porciones' : '—'}
                </div>
            </div>
        </div>
    </div>`;
}

function showRecipeModal(id) {
    const r = recipesData.find(item => item.id == id);
    if (!r) return;

    const img = r.imagen || 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600&q=80';


    let healthBadges = '';
    if (r.etiquetas_salud) {
        try {
            const tags = JSON.parse(r.etiquetas_salud);
            if (tags.length > 0) {
                healthBadges = `<div style="margin:0.75rem 0 0.5rem;">
                    <strong style="font-size:0.82rem;">Apto para: </strong>
                    ${tags.map(t => `<span style="display:inline-block;font-size:0.7rem;padding:3px 9px;border-radius:20px;background:#e8f5e9;color:#2e7d32;margin:2px;">${escapeHtml(t)}</span>`).join('')}
                </div>`;
            }
        } catch(e) {}
    }

    let dietBadges = '';
    if (r.etiquetas_dieta) {
        try {
            const tags = JSON.parse(r.etiquetas_dieta);
            if (tags.length > 0) {
                dietBadges = `<div style="margin-bottom:0.5rem;">
                    ${tags.map(t => `<span style="display:inline-block;font-size:0.7rem;padding:3px 9px;border-radius:20px;background:#fff3e0;color:#e65100;margin:2px;">${escapeHtml(t)}</span>`).join('')}
                </div>`;
            }
        } catch(e) {}
    }

    const fuenteInfo = (r.fuente || r.tipo_cocina) ? `
        <div style="font-size:0.8rem;color:#888;margin-bottom:0.5rem;">
            ${r.tipo_cocina ? `🍳 ${escapeHtml(r.tipo_cocina)}&nbsp;&nbsp;` : ''}
            ${r.fuente ? `📖 ${escapeHtml(r.fuente)}` : ''}
        </div>` : '';

    const verMas = r.url_fuente ? `
        <a href="${escapeHtml(r.url_fuente)}" target="_blank" rel="noopener noreferrer"
           style="display:inline-block;margin-top:1rem;color:#ff6b35;font-weight:600;text-decoration:none;font-size:0.9rem;">
           Ver receta completa →
        </a>` : '';

    const hasNutBar = r.proteinas || r.carbohidratos || r.grasas || r.calorias || r.fibra;
    const nutBar = hasNutBar ? `
        <div class="recipe-modal-nutbar">
            ${r.calorias    ? `<div class="nutbar-item nutbar-kcal"><span class="nutbar-val">${Math.round(r.calorias)}</span><span class="nutbar-lbl">kcal</span></div>` : ''}
            ${r.proteinas   ? `<div class="nutbar-item nutbar-prot"><span class="nutbar-val">${r.proteinas}g</span><span class="nutbar-lbl">Proteínas</span></div>` : ''}
            ${r.carbohidratos ? `<div class="nutbar-item nutbar-carbs"><span class="nutbar-val">${r.carbohidratos}g</span><span class="nutbar-lbl">Carbos</span></div>` : ''}
            ${r.grasas       ? `<div class="nutbar-item nutbar-fat"><span class="nutbar-val">${r.grasas}g</span><span class="nutbar-lbl">Grasas</span></div>` : ''}
            ${r.fibra        ? `<div class="nutbar-item nutbar-fiber"><span class="nutbar-val">${r.fibra}g</span><span class="nutbar-lbl">Fibra</span></div>` : ''}
        </div>` : '';

    document.getElementById('recipeModalTitle').textContent = r.titulo;
    document.getElementById('recipeModalBody').innerHTML = `
        <div class="recipe-modal-content">
            <!-- Fila superior: imagen izquierda + stats derecha -->
            <div class="recipe-modal-top">
                <div class="recipe-modal-image">
                    <img src="${escapeHtml(img)}" alt="${escapeHtml(r.titulo)}" onerror="this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600&q=80'">
                </div>
                <div class="recipe-modal-side">
                    ${fuenteInfo}
                    ${dietBadges}
                    <div class="stat-box">
                        <span class="stat-label">⏱ Tiempo</span>
                        <span class="stat-value">${r.tiempo_preparacion ? r.tiempo_preparacion + ' min' : '—'}</span>
                    </div>
                    <div class="stat-box">
                        <span class="stat-label">🍽 Porciones</span>
                        <span class="stat-value">${r.porciones || 1}</span>
                    </div>
                </div>
            </div>
            <!-- Barra nutricional full-width -->
            ${nutBar}
            <!-- Detalle scrollable -->
            <div class="recipe-modal-detail">
                ${healthBadges}
                <h3>Ingredientes:</h3>
                <ul class="ingredients-list">${formatList(r.ingredientes)}</ul>
                ${r.instrucciones ? `<h3>Preparación:</h3><ol class="instructions-list">${formatList(r.instrucciones)}</ol>` : ''}
                ${verMas}
            </div>
        </div>
    `;

    document.getElementById('dynamicRecipeModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeRecipeModal() {
    document.getElementById('dynamicRecipeModal').classList.remove('active');
    document.body.style.overflow = '';
}

function formatList(text) {
    if (!text) return '<li>No disponible</li>';
    const items = text.includes('\n') ? text.split('\n') : text.split(',');
    return items.map(item => item.trim()).filter(item => item).map(item => `<li>${escapeHtml(item)}</li>`).join('');
}

function initFilters() {
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            applyRecipeFilter();
        });
    });
}

function initSearch() {
    const input = document.getElementById('searchRecipes');
    if (!input) return;
    input.addEventListener('input', applyRecipeFilter);
}

function capitalize(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}

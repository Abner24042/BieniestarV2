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

const RECIPE_PAGE_SIZE = 6;
let recipesData      = [];
let filteredRecipes  = [];
let recipeVisible    = RECIPE_PAGE_SIZE;

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
        const catOk  = cat === 'all' || r.categoria === cat;
        const termOk = !term || (r.titulo || '').toLowerCase().includes(term);
        return catOk && termOk;
    });

    recipeVisible = RECIPE_PAGE_SIZE;
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
    recipeVisible += RECIPE_PAGE_SIZE;
    renderRecetasPaginated();
}

function renderRecetaCard(r) {
    const img = r.imagen || 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600&q=80';
    const cat = capitalize(r.categoria || 'comida');

    let dietBadges = '';
    if (r.etiquetas_dieta) {
        try {
            const tags = JSON.parse(r.etiquetas_dieta).slice(0, 2);
            dietBadges = tags.map(t => `<span style="display:inline-block;font-size:0.65rem;padding:2px 7px;border-radius:20px;background:#e8f5e9;color:#2e7d32;margin-right:3px;margin-top:4px;">${escapeHtml(t)}</span>`).join('');
        } catch(e) {}
    }

    const tipoCocina = r.tipo_cocina ? `<span style="font-size:0.72rem;color:#888;">🍳 ${escapeHtml(r.tipo_cocina)}&nbsp;</span>` : '';

    return `<div class="recipe-card" data-category="${escapeHtml(r.categoria)}">
        <div class="recipe-image">
            <img src="${escapeHtml(img)}" alt="${escapeHtml(r.titulo)}" onerror="this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600&q=80'">
            <div class="recipe-badge">${escapeHtml(cat)}</div>
        </div>
        <div class="recipe-content">
            <h3>${escapeHtml(r.titulo)}</h3>
            <div style="min-height:18px;margin-bottom:4px;">${tipoCocina}${dietBadges}</div>
            <div class="recipe-meta">
                <div class="meta-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><path d="M12 6V12L16 14" stroke="currentColor" stroke-width="2"/></svg>
                    <span>${r.tiempo_preparacion ? r.tiempo_preparacion + ' min' : '— min'}</span>
                </div>
                <div class="meta-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M13 2L3 14H12L11 22L21 10H12L13 2Z" stroke="currentColor" stroke-width="2"/></svg>
                    <span>${r.calorias ? Math.round(r.calorias) + ' kcal' : '— kcal'}</span>
                </div>
                ${r.porciones ? `<div class="meta-item"><span>👤 ${r.porciones}</span></div>` : ''}
            </div>
            <button class="btn btn-primary btn-block" onclick="showRecipeModal(${r.id})">Ver Receta</button>
        </div>
    </div>`;
}

function showRecipeModal(id) {
    const r = recipesData.find(item => item.id == id);
    if (!r) return;

    const img = r.imagen || 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600&q=80';

    const hasNutrition = r.proteinas || r.carbohidratos || r.grasas || r.fibra || r.calorias;
    const nutritionTable = hasNutrition ? `
        <div style="margin:1rem 0;">
            <h3 style="margin-bottom:0.75rem;">Información Nutricional <small style="font-weight:400;color:#888;font-size:0.8rem;">(por porción)</small></h3>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(70px,1fr));gap:0.5rem;text-align:center;">
                ${r.calorias ? `<div style="background:#fff3e0;border-radius:8px;padding:0.75rem 0.5rem;">
                    <div style="font-size:1.1rem;font-weight:700;color:#e65100;">${Math.round(r.calorias)}</div>
                    <div style="font-size:0.68rem;color:#666;">kcal</div>
                </div>` : ''}
                ${r.proteinas ? `<div style="background:#e8f5e9;border-radius:8px;padding:0.75rem 0.5rem;">
                    <div style="font-size:1.1rem;font-weight:700;color:#2e7d32;">${r.proteinas}g</div>
                    <div style="font-size:0.68rem;color:#666;">Proteínas</div>
                </div>` : ''}
                ${r.carbohidratos ? `<div style="background:#e3f2fd;border-radius:8px;padding:0.75rem 0.5rem;">
                    <div style="font-size:1.1rem;font-weight:700;color:#1565c0;">${r.carbohidratos}g</div>
                    <div style="font-size:0.68rem;color:#666;">Carbos</div>
                </div>` : ''}
                ${r.grasas ? `<div style="background:#fce4ec;border-radius:8px;padding:0.75rem 0.5rem;">
                    <div style="font-size:1.1rem;font-weight:700;color:#880e4f;">${r.grasas}g</div>
                    <div style="font-size:0.68rem;color:#666;">Grasas</div>
                </div>` : ''}
                ${r.fibra ? `<div style="background:#f3e5f5;border-radius:8px;padding:0.75rem 0.5rem;">
                    <div style="font-size:1.1rem;font-weight:700;color:#6a1b9a;">${r.fibra}g</div>
                    <div style="font-size:0.68rem;color:#666;">Fibra</div>
                </div>` : ''}
            </div>
        </div>` : '';

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

    document.getElementById('recipeModalTitle').textContent = r.titulo;
    document.getElementById('recipeModalBody').innerHTML = `
        <div class="recipe-modal-content">
            <div class="recipe-modal-image">
                <img src="${escapeHtml(img)}" alt="${escapeHtml(r.titulo)}" onerror="this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600&q=80'">
            </div>
            <div class="recipe-modal-info">
                ${fuenteInfo}
                ${dietBadges}
                <div class="recipe-stats">
                    <div class="stat-box">
                        <span class="stat-label">Tiempo</span>
                        <span class="stat-value">${r.tiempo_preparacion ? r.tiempo_preparacion + ' min' : '—'}</span>
                    </div>
                    <div class="stat-box">
                        <span class="stat-label">Porciones</span>
                        <span class="stat-value">${r.porciones || 1}</span>
                    </div>
                </div>
                ${nutritionTable}
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

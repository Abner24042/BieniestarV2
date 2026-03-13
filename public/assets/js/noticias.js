/**
 * BIENIESTAR - JavaScript de Noticias (Dinámico)
 */

document.addEventListener('DOMContentLoaded', function() {
    loadNoticias();
    initNewsFilters();
});

let newsData      = [];
let filteredNews  = [];
let newsVisible   = 3;

function porFilaN() {
    const el = document.getElementById('newsGrid');
    const w  = el ? (el.clientWidth || el.offsetWidth) : (window.innerWidth - 260);
    return Math.max(1, Math.floor((w + 16) / (320 + 16)));
}

async function loadNoticias() {
    try {
        const response = await fetch(API_URL + '/noticias');
        const data = await response.json();

        if (data.success) {
            newsData = data.noticias || [];
            if (newsData.length > 0) {
                renderFeatured(newsData[0]);
                applyNewsFilter();
            } else {
                document.getElementById('newsGrid').innerHTML =
                    '<p style="text-align:center;color:#999;grid-column:1/-1;">No hay noticias disponibles</p>';
            }
        } else {
            document.getElementById('newsGrid').innerHTML =
                '<p style="text-align:center;color:#999;grid-column:1/-1;">No hay noticias disponibles</p>';
        }
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('newsGrid').innerHTML =
            '<p style="text-align:center;color:#e53935;grid-column:1/-1;">Error al cargar noticias</p>';
    }
}

function applyNewsFilter() {
    const activeBtn = document.querySelector('.news-filters .filter-btn.active');
    const filter    = activeBtn ? activeBtn.dataset.filter : 'all';

    // El primer item es el destacado, no va en el grid
    const pool = newsData.slice(1);
    filteredNews = filter === 'all' ? pool : pool.filter(n => n.categoria === filter);

    newsVisible = porFilaN();
    renderNoticiasPaginated();
}

function renderNoticiasPaginated() {
    const grid    = document.getElementById('newsGrid');
    const visible = filteredNews.slice(0, newsVisible);

    if (filteredNews.length === 0) {
        grid.innerHTML = '';
        return;
    }

    let html = visible.map(n => renderNoticiaCard(n)).join('');

    if (newsVisible < filteredNews.length) {
        const restantes = filteredNews.length - newsVisible;
        html += `<div style="grid-column:1/-1;text-align:center;margin:1.5rem 0 0.5rem;">
            <button class="btn btn-secondary" onclick="mostrarMasNoticias()" style="min-width:190px;">
                Mostrar más (${restantes} restante${restantes !== 1 ? 's' : ''})
            </button>
        </div>`;
    }

    grid.innerHTML = html;
}

function mostrarMasNoticias() {
    newsVisible += porFilaN();
    renderNoticiasPaginated();
}

function renderNoticiaCard(n) {
    const img = n.imagen || 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&q=80';
    const cat = capitalize(n.categoria || 'general');
    return `<article class="news-card" data-category="${escapeHtml(n.categoria)}">
        <div class="news-image">
            <img src="${escapeHtml(img)}" alt="${escapeHtml(n.titulo)}" onerror="this.src='https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&q=80'">
            <div class="news-category-badge">${escapeHtml(cat)}</div>
        </div>
        <div class="news-content">
            <div class="news-meta">
                <span class="news-author">${n.autor ? 'Por: ' + escapeHtml(n.autor) : ''}</span>
                <span class="news-date">${formatDate(n.fecha_publicacion)}</span>
            </div>
            <h3>${escapeHtml(n.titulo)}</h3>
            <p>${escapeHtml(n.resumen || truncate(n.contenido, 180))}</p>
            <a href="javascript:void(0)" class="read-more" onclick="showNewsModal(${n.id})">Leer artículo →</a>
        </div>
    </article>`;
}

function renderFeatured(noticia) {
    const featured = document.getElementById('featuredNews');
    const img = noticia.imagen || 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=1200&q=80';

    document.getElementById('featuredImg').src = img;
    document.getElementById('featuredImg').onerror = function() {
        this.src = 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=1200&q=80';
    };
    document.getElementById('featuredCategory').textContent = capitalize(noticia.categoria || 'general');
    document.getElementById('featuredDate').textContent = formatDate(noticia.fecha_publicacion);
    document.getElementById('featuredTitle').textContent = noticia.titulo;
    document.getElementById('featuredResumen').textContent = noticia.resumen || truncate(noticia.contenido, 200);

    var btn = document.getElementById('featuredBtn');
    btn.onclick = function() { showNewsModal(noticia.id); };

    featured.style.display = '';
}

function showNewsModal(id) {
    const n = newsData.find(item => item.id == id);
    if (!n) return;

    const img = n.imagen || 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=1000&q=80';
    const bodyText = (n.contenido && n.contenido.trim()) ? n.contenido : (n.resumen || '');
    const sourceLink = n.url_fuente
        ? `<div class="article-source"><a href="${escapeHtml(n.url_fuente)}" target="_blank" rel="noopener">Leer artículo completo →</a></div>`
        : '';

    document.getElementById('newsModalTitle').textContent = n.titulo;
    document.getElementById('newsModalBody').innerHTML = `
        <div class="article-content">
            <div class="article-meta">
                <span class="article-author">${n.autor ? 'Por: ' + escapeHtml(n.autor) : ''}</span>
                <span class="article-date">${formatDate(n.fecha_publicacion)}</span>
                <span class="article-category">${escapeHtml(capitalize(n.categoria || 'general'))}</span>
            </div>
            <img src="${escapeHtml(img)}" alt="${escapeHtml(n.titulo)}" class="article-image" onerror="this.style.display='none'">
            <div class="article-body">
                ${formatContent(bodyText)}
            </div>
            ${sourceLink}
        </div>
    `;

    document.getElementById('dynamicNewsModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeNewsModal() {
    document.getElementById('dynamicNewsModal').classList.remove('active');
    document.body.style.overflow = '';
}

function initNewsFilters() {
    document.querySelectorAll('.news-filters .filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.news-filters .filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            applyNewsFilter();

            // Featured siempre visible
            const featured = document.getElementById('featuredNews');
            if (featured) featured.style.display = '';
        });
    });
}

function formatContent(text) {
    if (!text) return '<p>Sin contenido disponible.</p>';
    return text.split('\n').filter(p => p.trim()).map(p => `<p>${escapeHtml(p.trim())}</p>`).join('');
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    try {
        const date = new Date(dateStr);
        const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        return date.getDate() + ' ' + months[date.getMonth()] + ' ' + date.getFullYear();
    } catch (e) {
        return dateStr;
    }
}

function truncate(text, maxLen) {
    if (!text) return '';
    if (text.length <= maxLen) return text;
    return text.substring(0, maxLen) + '...';
}

function capitalize(str) {
    if (!str) return '';
    if (str === 'salud-mental') return 'Salud Mental';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

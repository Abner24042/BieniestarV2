<?php
require_once '../../app/config/config.php';
require_once '../../app/controllers/AuthController.php';

$authController = new AuthController();

if (!$authController->isAuthenticated()) {
    redirect('login');
}

$user = $authController->getCurrentUser();
$currentPage = 'noticias';
$pageTitle = 'Noticias';
$additionalCSS = ['filters.css', 'noticias.css'];
?>

<?php include '../../app/views/layouts/header.php'; ?>

<div class="content-wrapper">
    <div class="page-header">
        <h1>Noticias de Bienestar <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ff6b35" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-left:4px"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8V6Z"/></svg></h1>
        <p>Mantente informado sobre las últimas tendencias en salud y bienestar</p>
    </div>

    <!-- Noticia Destacada (dinámica) -->
    <div class="featured-news" id="featuredNews" style="display:none;">
        <img id="featuredImg" class="featured-bg-img" src="" alt="Noticia Destacada">
        <div class="featured-badge">Destacado</div>
        <div class="featured-content">
            <div class="featured-meta">
                <span class="news-category" id="featuredCategory"></span>
                <span class="news-date" id="featuredDate"></span>
            </div>
            <h2 id="featuredTitle"></h2>
            <p id="featuredResumen"></p>
            <button class="btn btn-primary" id="featuredBtn">Leer más</button>
        </div>
    </div>

    <!-- Filtro de Categorías -->
    <div class="news-filters">
        <button class="filter-btn active" data-filter="all">Todas</button>
        <button class="filter-btn" data-filter="alimentacion">Alimentación</button>
        <button class="filter-btn" data-filter="ejercicio">Ejercicio</button>
        <button class="filter-btn" data-filter="salud-mental">Salud Mental</button>
        <button class="filter-btn" data-filter="general">General</button>
    </div>

    <!-- Grid de Noticias -->
    <div class="news-grid" id="newsGrid">
        <p style="text-align:center;color:#999;grid-column:1/-1;">Cargando noticias...</p>
    </div>
</div>

<!-- Modal Dinámico de Noticia -->
<div class="modal" id="dynamicNewsModal">
    <div class="modal-overlay" onclick="closeNewsModal()"></div>
    <div class="modal-container modal-large">
        <div class="modal-header">
            <h3 class="modal-title" id="newsModalTitle"></h3>
            <button class="modal-close" onclick="closeNewsModal()">&times;</button>
        </div>
        <div class="modal-body" id="newsModalBody"></div>
    </div>
</div>

<?php
$additionalJS = ['noticias.js'];
include '../../app/views/layouts/footer.php';
?>

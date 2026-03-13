<?php
require_once '../../app/config/config.php';
require_once '../../app/controllers/AuthController.php';

$authController = new AuthController();

if (!$authController->isAuthenticated()) {
    redirect('login');
}

$user = $authController->getCurrentUser();
$currentPage = 'alimentacion';
$pageTitle = 'Alimentación Saludable';
$additionalCSS = ['filters.css', 'alimentacion.css'];
?>

<?php include '../../app/views/layouts/header.php'; ?>

<div class="content-wrapper">
    <div class="page-header">
        <h1>Alimentación Saludable <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ff6b35" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-left:4px"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Z"/><path d="M21 15v7"/></svg></h1>
        <p>Descubre recetas nutritivas y deliciosas para tu día a día</p>
    </div>

    <!-- Filtros -->
    <div class="filters-section">
        <div class="filter-group">
            <button class="filter-btn active" data-category="all">Todas</button>
            <button class="filter-btn" data-category="desayuno">Desayuno</button>
            <button class="filter-btn" data-category="almuerzo">Almuerzo</button>
            <button class="filter-btn" data-category="comida">Comida</button>
            <button class="filter-btn" data-category="merienda">Merienda</button>
            <button class="filter-btn" data-category="cena">Cena</button>
            <button class="filter-btn" data-category="snack">Snacks</button>
            <button class="filter-btn" data-category="postre">Postre</button>
        </div>

        <div class="search-box">
            <input type="text" id="searchRecipes" placeholder="Buscar recetas...">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
                <path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </div>
    </div>

    <!-- Grid de Recetas -->
    <div class="recipes-grid" id="recipesGrid">
        <p style="text-align:center;color:#999;grid-column:1/-1;">Cargando recetas...</p>
    </div>

    <!-- Tips de Nutrición -->
    <div class="nutrition-tips">
        <h2>Tips de Nutrición <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ff6b35" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-left:4px"><path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/></svg></h2>
        <div class="tips-grid">
            <div class="tip-card">
                <div class="tip-icon"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#2196f3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg></div>
                <h3>Hidrátate</h3>
                <p>Bebe al menos 8 vasos de agua al día para mantenerte hidratado</p>
            </div>

            <div class="tip-card">
                <div class="tip-icon"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#4caf50" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg></div>
                <h3>Come Variado</h3>
                <p>Incluye diferentes colores de frutas y verduras en tu dieta</p>
            </div>

            <div class="tip-card">
                <div class="tip-icon"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#ff9800" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                <h3>Horarios Regulares</h3>
                <p>Mantén horarios consistentes para tus comidas principales</p>
            </div>

            <div class="tip-card">
                <div class="tip-icon"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#9c27b0" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Z"/><path d="M21 15v7"/></svg></div>
                <h3>Porciones Adecuadas</h3>
                <p>Controla el tamaño de tus porciones para evitar excesos</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal Dinámico de Recetas -->
<div class="modal" id="dynamicRecipeModal">
    <div class="modal-overlay" onclick="closeRecipeModal()"></div>
    <div class="modal-container modal-large">
        <div class="modal-header">
            <h3 class="modal-title" id="recipeModalTitle"></h3>
            <button class="modal-close" onclick="closeRecipeModal()">&times;</button>
        </div>
        <div class="modal-body" id="recipeModalBody"></div>
    </div>
</div>

<?php
$additionalJS = ['alimentacion.js'];
include '../../app/views/layouts/footer.php';
?>

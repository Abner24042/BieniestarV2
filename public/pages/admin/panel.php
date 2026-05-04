<?php
require_once '../../../app/config/config.php';
require_once '../../../app/controllers/AuthController.php';

$authController = new AuthController();

// Verificar autenticación
if (!$authController->isAuthenticated()) {
    redirect('login');
}

// Verificar que sea administrador
if (!isAdmin()) {
    redirect('dashboard');
}

$user = $authController->getCurrentUser();
$currentPage = 'admin';
$pageTitle = 'Panel de Administrador';
$additionalCSS = ['admin.css'];
?>

<?php include '../../../app/views/layouts/header.php'; ?>

<div class="content-wrapper">
    <div class="page-header">
        <h1>⚙️ Panel de Administrador</h1>
        <p>Gestión y control del sistema BIENIESTAR</p>
    </div>

    <div class="admin-dashboard">
        <!-- Estadísticas Generales -->
        <div class="admin-stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: #4285F4;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M9 11C11.2091 11 13 9.20914 13 7C13 4.79086 11.2091 3 9 3C6.79086 3 5 4.79086 5 7C5 9.20914 6.79086 11 9 11Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M16 3.13C16.8604 3.3503 17.623 3.8507 18.1676 4.55231C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89317 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <h3>Total Usuarios</h3>
                    <p class="stat-number" id="statUsuarios">—</p>
                    <span class="stat-change positive" id="statUsuariosNuevos">cargando...</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: #34A853;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M19 4H5C3.89543 4 3 4.89543 3 6V20C3 21.1046 3.89543 22 5 22H19C20.1046 22 21 21.1046 21 20V6C21 4.89543 20.1046 4 19 4Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M16 2V6" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M8 2V6" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M3 10H21" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <h3>Citas Programadas</h3>
                    <p class="stat-number" id="statCitas">—</p>
                    <span class="stat-change positive" id="statCitasNuevos">cargando...</span>
                </div>
            </div>

        </div>

        <!-- Acciones Rápidas -->
        <div class="admin-sections">
            <div class="admin-section">
                <h2>👥 Gestión de Usuarios</h2>
                <p>Administrar cuentas de usuario del sistema</p>
                <div class="section-actions">
                    <button class="btn btn-primary" id="btnVerUsuarios">Ver Todos los Usuarios</button>
                    <button class="btn btn-secondary" onclick="descargarCSV('usuarios')" style="background:#2d6a4f;">⬇️ Exportar</button>
                </div>
            </div>

            <div class="admin-section">
                <h2>📅 Gestión de Citas</h2>
                <p>Ver y administrar todas las citas del sistema</p>
                <div class="section-actions">
                    <button class="btn btn-primary" id="btnVerCitas">Ver Todas las Citas</button>
                    <button class="btn btn-secondary" onclick="descargarCSV('citas')" style="background:#2d6a4f;">⬇️ Exportar</button>
                </div>
            </div>

            <div class="admin-section">
                <h2>📊 Reportes y Estadísticas</h2>
                <p>Analizar el uso y rendimiento del sistema</p>
                <div class="section-actions">
                    <button class="btn btn-primary" id="btnVerDashboard">Ver Dashboard</button>
                </div>
            </div>

            <div class="admin-section">
                <h2>⏳ Solicitudes</h2>
                <p>Revisar y gestionar solicitudes de cita enviadas por usuarios</p>
                <div class="section-actions">
                    <a href="<?php echo url('admin/solicitudes'); ?>" class="btn btn-primary">Ver Solicitudes</a>
                </div>
            </div>

            <div class="admin-section">
                <h2>💡 Recomendaciones</h2>
                <p>Ver todas las recomendaciones emitidas por los especialistas</p>
                <div class="section-actions">
                    <a href="<?php echo url('admin/recomendaciones'); ?>" class="btn btn-primary">Ver Recomendaciones</a>
                </div>
            </div>

            <div class="admin-section">
                <h2>🍽️ Planes de Alimentación</h2>
                <p>Recetas asignadas a usuarios por los nutriólogos</p>
                <div class="section-actions">
                    <a href="<?php echo url('admin/planes-alimentacion'); ?>" class="btn btn-primary">Ver Planes</a>
                    <button class="btn btn-secondary" onclick="descargarCSV('recetas')" style="background:#2d6a4f;">⬇️ Exportar</button>
                </div>
            </div>

            <div class="admin-section">
                <h2>💪 Planes de Ejercicio</h2>
                <p>Rutinas asignadas a usuarios por los coaches</p>
                <div class="section-actions">
                    <a href="<?php echo url('admin/planes-ejercicio'); ?>" class="btn btn-primary">Ver Planes</a>
                    <button class="btn btn-secondary" onclick="descargarCSV('ejercicios')" style="background:#2d6a4f;">⬇️ Exportar</button>
                </div>
            </div>

            <div class="admin-section">
                <h2>📰 Gestión de Noticias</h2>
                <p>Administrar artículos y publicaciones</p>
                <div class="section-actions">
                    <a href="<?php echo url('admin/noticias'); ?>" class="btn btn-primary">Gestionar Noticias</a>
                    <button class="btn btn-secondary" onclick="descargarCSV('noticias')" style="background:#2d6a4f;">⬇️ Exportar</button>
                </div>
            </div>

        </div>

    </div>
</div>

<!-- Modal: Dashboard de Estadísticas -->
<div id="modalDashboard" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 620px;">
        <div class="modal-header">
            <h3>📊 Estadísticas del Sistema</h3>
            <button class="modal-close" onclick="cerrarModalDashboard()">&times;</button>
        </div>
        <div class="modal-body" id="dashboardStatsContent">
            <p style="text-align: center; color: #666; padding: 2rem 0;">Cargando estadísticas...</p>
        </div>
    </div>
</div>

<!-- Modal: Exportar Datos -->
<div id="modalExportar" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3>⬇️ Exportar Datos</h3>
            <button class="modal-close" onclick="cerrarModalExportar()">&times;</button>
        </div>
        <div class="modal-body">
            <p style="margin-bottom: 1.25rem; color: #555;">Selecciona qué datos deseas exportar en formato CSV:</p>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <button class="btn btn-primary" onclick="descargarCSV('usuarios')">👥 Exportar Usuarios</button>
                <button class="btn btn-secondary" onclick="descargarCSV('citas')">📅 Exportar Citas</button>
                <button class="btn btn-secondary" onclick="descargarCSV('ejercicios')">💪 Exportar Ejercicios</button>
                <button class="btn btn-secondary" onclick="descargarCSV('recetas')">🍽️ Exportar Recetas</button>
                <button class="btn btn-secondary" onclick="descargarCSV('noticias')">📰 Exportar Noticias</button>
            </div>
        </div>
    </div>
</div>

<?php
$additionalJS = ['admin.js'];
include '../../../app/views/layouts/footer.php';
?>

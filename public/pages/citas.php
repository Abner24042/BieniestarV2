<?php
require_once '../../app/config/config.php';
require_once '../../app/controllers/AuthController.php';

$authController = new AuthController();
if (!$authController->isAuthenticated()) {
    redirect('login');
}

$user = $authController->getCurrentUser();
$currentPage = 'citas';
$pageTitle = 'Mis Citas';
$additionalCSS = ['citas.css'];
?>

<?php include '../../app/views/layouts/header.php'; ?>

<div class="content-wrapper">
    <div class="page-header">
        <h1>Mis Citas <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ff6b35" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-left:4px"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></h1>
        <p>Consulta las citas agendadas por tu especialista</p>
    </div>

    <div class="appointment-container">
        <!-- Izquierda: Calendario -->
        <div class="calendar-container">
            <div class="calendar-header">
                <h3><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>Calendario de Citas</h3>
                <div class="calendar-controls">
                    <button type="button" id="prevMonth">‹</button>
                    <span class="calendar-month-year" id="currentMonthYear"></span>
                    <button type="button" id="nextMonth">›</button>
                </div>
            </div>
            <div id="calendar"></div>
        </div>

        <!-- Derecha: Detalle día + Lista de citas -->
        <div class="citas-panel">
            <!-- Detalle del día seleccionado -->
            <div id="dayDetail" class="appointments-list" style="display: none;">
                <h3 id="dayDetailTitle">Citas del día</h3>
                <div id="dayDetailContent"></div>
            </div>

            <!-- Lista de citas programadas -->
            <div class="appointments-list">
                <h3>Mis Citas Programadas</h3>
                <div id="appointmentsList"></div>
            </div>
        </div>
    </div>
</div>

<script>
// Pasar datos del usuario de PHP a JavaScript
const CURRENT_USER = {
    nombre: '<?php echo addslashes($user['nombre']); ?>',
    correo: '<?php echo addslashes($user['correo']); ?>',
    rol: '<?php echo addslashes($user['rol']); ?>'
};
</script>

<?php
$additionalJS = ['emailConfig.js', 'citas.js'];
include '../../app/views/layouts/footer.php';
?>
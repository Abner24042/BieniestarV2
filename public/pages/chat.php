<?php
$pageTitle   = 'Mensajes';
$currentPage = 'chat';
$additionalCSS = ['admin.css', 'chat.css'];

require_once '../../app/config/config.php';
require_once '../../app/controllers/AuthController.php';

$authController = new AuthController();
if (!$authController->isAuthenticated()) {
    redirect('login');
}

$user       = $authController->getCurrentUser();
$esPro      = isProfessional();
$esAdmin    = isAdmin();
$puedeNuevo = $esPro || $esAdmin;
$rolesProf  = ['coach', 'nutriologo', 'psicologo'];

include '../../app/views/layouts/header.php';
?>

<div class="content-wrapper">
    <div class="page-header" style="display:flex;align-items:center;justify-content:space-between;">
        <div>
            <h1>Mensajes</h1>
            <p><?php echo $esPro ? 'Conversaciones con tus usuarios' : ($esAdmin ? 'Conversaciones con todos los usuarios' : 'Mensajes de tus profesionales'); ?></p>
        </div>
        <?php if ($puedeNuevo): ?>
        <button class="btn btn-primary" onclick="chatAbrirModalNuevo()" style="font-size:0.88rem;">
            + Nuevo mensaje
        </button>
        <?php endif; ?>
    </div>

    <div class="chat-container" style="height:calc(100vh - 220px); min-height:420px;">
        <!-- Lista izquierda -->
        <div class="chat-sidebar">
            <div class="chat-sidebar-header">
                <span>Conversaciones</span>
            </div>
            <div class="chat-conv-list" id="chatConvList">
                <div class="chat-empty-state">Cargando conversaciones…</div>
            </div>
        </div>

        <!-- Ventana derecha -->
        <div class="chat-main">
            <div class="chat-main-header" id="chatMainHeader">
                Selecciona una conversación
            </div>
            <div class="chat-messages" id="chatMessages">
                <div class="chat-placeholder">
                    <?php if ($puedeNuevo): ?>
                        Selecciona una conversación o inicia una nueva.
                    <?php else: ?>
                        Aquí aparecerán los mensajes de tus especialistas.
                    <?php endif; ?>
                </div>
            </div>
            <div class="chat-input-area" id="chatInputArea" style="display:none;">
                <textarea class="chat-input" id="chatInput"
                    placeholder="Escribe un mensaje… (Enter para enviar)" rows="1"></textarea>
                <button class="chat-send-btn" onclick="chatEnviar()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"/>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                    </svg>
                    Enviar
                </button>
            </div>
        </div>
    </div>
</div>

<?php if ($puedeNuevo): ?>
<!-- Modal nuevo chat -->
<div id="modalNuevoChat" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:420px;">
        <div class="modal-header">
            <h3>Nuevo Mensaje</h3>
            <button class="modal-close"
                onclick="document.getElementById('modalNuevoChat').style.display='none'">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>Usuario</label>
                <select id="chatNuevoUsuario">
                    <option value="">— Selecciona un usuario —</option>
                </select>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary"
                    onclick="document.getElementById('modalNuevoChat').style.display='none'">Cancelar</button>
                <button class="btn btn-primary" onclick="chatIniciarDesdeModal()">Iniciar chat</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    <?php if ($esPro || $esAdmin): ?>
    chatInitPro(<?php echo (int)$user['id']; ?>);
    <?php if ($esAdmin): ?>
    window.chatEsAdmin = true;
    <?php endif; ?>
    <?php else: ?>
    chatInitUser(<?php echo (int)$user['id']; ?>);
    chatCargarConversaciones();
    <?php endif; ?>
});
</script>

<?php
$additionalJS = ['chat.js'];
include '../../app/views/layouts/footer.php';
?>

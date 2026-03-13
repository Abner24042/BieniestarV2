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

$user    = $authController->getCurrentUser();
$esPro   = isProfessional();
$esAdmin = isAdmin();

include '../../app/views/layouts/header.php';
?>

<style>
.chat-container { height: calc(100vh - 280px) !important; min-height: 380px; }
</style>

<div class="content-wrapper">
    <div class="page-header" style="display:flex;align-items:center;justify-content:space-between;">
        <div>
            <h1>Mensajes</h1>
            <p><?php echo $esPro ? 'Conversaciones con tus usuarios' : ($esAdmin ? 'Conversaciones con todos los usuarios' : 'Mensajes con tus especialistas'); ?></p>
        </div>
        <button class="btn btn-primary" onclick="chatAbrirModalNuevo()" style="font-size:0.88rem;">
            + Nuevo mensaje
        </button>
    </div>

    <div class="chat-container">
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
                    Selecciona una conversación o inicia una nueva.
                </div>
            </div>
            <div class="chat-input-area" id="chatInputArea" style="display:none;">
                <button type="button" class="chat-attach-btn" onclick="chatTriggerArchivo()" title="Adjuntar archivo">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
                    </svg>
                </button>
                <input type="file" id="chatFileInput" style="display:none;"
                       accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.zip,.txt,.mp4,.mp3,.csv"
                       onchange="chatEnviarArchivo(this)">
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

<!-- Modal nuevo chat — disponible para todos -->
<div id="modalNuevoChat" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:420px;">
        <div class="modal-header">
            <h3>Nuevo Mensaje</h3>
            <button class="modal-close"
                onclick="document.getElementById('modalNuevoChat').style.display='none'">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>Destinatario</label>
                <select id="chatNuevoUsuario">
                    <option value="">— Selecciona un destinatario —</option>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    chatInitPro(<?php echo (int)$user['id']; ?>);
});
</script>

<?php
$additionalJS = ['chat.js'];
include '../../app/views/layouts/footer.php';
?>

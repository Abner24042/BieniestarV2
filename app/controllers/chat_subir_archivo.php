<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Chat.php';
require_once __DIR__ . '/AuthController.php';

header('Content-Type: application/json');

$authController = new AuthController();
if (!$authController->isAuthenticated()) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

try {
    $destinatarioId = (int)($_POST['destinatario_id'] ?? 0);
    if (!$destinatarioId) throw new Exception('Destinatario requerido');

    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        $errCode = $_FILES['archivo']['error'] ?? -1;
        if ($errCode === UPLOAD_ERR_INI_SIZE || $errCode === UPLOAD_ERR_FORM_SIZE) {
            throw new Exception('El archivo excede el tamaño permitido');
        }
        throw new Exception('No se recibió ningún archivo');
    }

    $file = $_FILES['archivo'];

    if ($file['size'] > 10 * 1024 * 1024) throw new Exception('El archivo excede el límite de 10 MB');

    $allowedExt = ['pdf','doc','docx','xls','xlsx','ppt','pptx','jpg','jpeg','png','gif','zip','txt','mp4','mp3','csv'];
    $originalName = basename($file['name']);
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt)) throw new Exception('Tipo de archivo no permitido');

    $yo   = $authController->getCurrentUser();
    $yoId = (int)$yo['id'];

    if ($yoId === $destinatarioId) throw new Exception('No puedes enviarte archivos a ti mismo');

    $model = new Chat();
    if (!$model->getRolDestinatario($destinatarioId)) throw new Exception('Usuario no encontrado');

    // Save file
    $uploadDir = UPLOAD_PATH . '/chat/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $safeName   = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
    $uniqueName = time() . '_' . $yoId . '_' . $safeName;
    $destPath   = $uploadDir . $uniqueName;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        throw new Exception('Error al guardar el archivo');
    }

    $archivoUrl = ASSETS_URL . '/uploads/chat/' . $uniqueName;

    // Create or get conversation, then store file message
    $usuarioId     = min($yoId, $destinatarioId);
    $profesionalId = max($yoId, $destinatarioId);

    $conversacionId = $model->getOCrearConversacion($usuarioId, $profesionalId);
    $msgId = $model->enviarArchivo($conversacionId, $yoId, $originalName, $archivoUrl);

    if (!$msgId) throw new Exception('Error al registrar el archivo');

    echo json_encode(['success' => true, 'mensaje_id' => $msgId, 'conversacion_id' => $conversacionId]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

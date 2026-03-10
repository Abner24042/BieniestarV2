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

$body      = json_decode(file_get_contents('php://input'), true);
$mensajeId = (int)($body['mensaje_id'] ?? 0);

if (!$mensajeId) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

$user  = $authController->getCurrentUser();
$model = new Chat();
$ok    = $model->eliminarMensaje($mensajeId, (int)$user['id']);

echo json_encode(['success' => $ok]);

<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Chat.php';
require_once __DIR__ . '/AuthController.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

$authController = new AuthController();
if (!$authController->isAuthenticated()) {
    echo json_encode(['success' => false]);
    exit;
}

$yo = $authController->getCurrentUser();

$model = new Chat();
$conversaciones = $model->getConversaciones((int)$yo['id']);
echo json_encode(['success' => true, 'conversaciones' => $conversaciones]);

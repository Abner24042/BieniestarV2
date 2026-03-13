<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Plan.php';
require_once __DIR__ . '/AuthController.php';

header('Content-Type: application/json');

$authController = new AuthController();
if (!$authController->isAuthenticated()) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$user = $authController->getCurrentUser();
$model = new Plan();
$plan  = $model->getMiPlan($user['id'], true);

echo json_encode(['success' => true, 'plan' => $plan]);

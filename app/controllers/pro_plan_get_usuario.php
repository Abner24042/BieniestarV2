<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Plan.php';
require_once __DIR__ . '/AuthController.php';

header('Content-Type: application/json');

$authController = new AuthController();
if (!$authController->isAuthenticated() || !isProfessional()) {
    echo json_encode(['success' => false]);
    exit;
}

$usuarioId = $_GET['usuario_id'] ?? null;
if (!$usuarioId) {
    echo json_encode(['success' => false, 'message' => 'ID requerido']);
    exit;
}

$profesional = $authController->getCurrentUser();
$model = new Plan();
$planBase = $model->getMiPlan($usuarioId);

// Solo mostrar las recomendaciones que puso este profesional
$planBase['recomendaciones'] = $model->getRecomendacionesPorProEnPlan($usuarioId, $profesional['correo']);

$ejerciciosDisponibles = $model->getEjerciciosDisponibles();
$recetasDisponibles    = $profesional['rol'] === 'nutriologo'
    ? $model->getRecetasDisponibles($profesional['correo'])
    : $model->getRecetasDisponibles();

echo json_encode([
    'success'   => true,
    'plan'      => $planBase,
    'ejercicios_disponibles' => $ejerciciosDisponibles,
    'recetas_disponibles'    => $recetasDisponibles,
]);

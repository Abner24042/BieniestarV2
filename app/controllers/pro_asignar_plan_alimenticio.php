<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/PlanAlimenticio.php';
require_once __DIR__ . '/../models/Plan.php';
require_once __DIR__ . '/AuthController.php';

header('Content-Type: application/json');

$auth = new AuthController();
if (!$auth->isAuthenticated() || !isProfessional()) {
    echo json_encode(['success' => false, 'message' => 'Sin permisos']);
    exit;
}

$user = $auth->getCurrentUser();
$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['usuario_id']) || empty($input['plan_id'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$planAlimModel = new PlanAlimenticio();
$plan = $planAlimModel->getDetail($input['plan_id']);
if (!$plan) {
    echo json_encode(['success' => false, 'message' => 'Plan no encontrado']);
    exit;
}

if (empty($plan['recetas'])) {
    echo json_encode(['success' => false, 'message' => 'El plan no tiene recetas']);
    exit;
}

$dias = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
$planModel = new Plan();
$asignados = 0;

foreach ($plan['recetas'] as $r) {
    $diaSemana = (int)$r['dia_semana'];
    $dia = $dias[$diaSemana] ?? 'Día ' . $diaSemana;
    $notas = 'Plan: ' . $plan['nombre'] . ' — ' . $dia . ' (' . ucfirst($r['tiempo_comida']) . ')';
    if (!empty($input['notas'])) $notas .= ' — ' . $input['notas'];
    $planModel->asignarReceta($input['usuario_id'], $r['receta_id'], $user['correo'], $notas, $diaSemana);
    $asignados++;
}

echo json_encode(['success' => true, 'asignados' => $asignados, 'message' => "$asignados receta(s) agregadas al plan del usuario"]);

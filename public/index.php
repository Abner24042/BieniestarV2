<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/Router.php';

$basePath = parse_url(BASE_URL, PHP_URL_PATH) ?: '';
$router = new Router($basePath);


// RUTAS PÚBLICAS

$router->get('/', APP_PATH . '/views/landing.php');
$router->get('/login', PUBLIC_PATH . '/pages/login.php');
$router->get('/about', PUBLIC_PATH . '/pages/about.php');


// RUTAS DE AUTENTICACIÓN

$router->post('/auth/login', ROOT_PATH . '/controllers/login.php');
$router->any('/auth/logout', ROOT_PATH . '/controllers/logout.php');
$router->get('/auth/google', ROOT_PATH . '/controllers/google_login.php');
$router->get('/auth/google/callback', ROOT_PATH . '/controllers/google_callback.php');


// PÁGINAS DE USUARIO

$router->get('/dashboard', PUBLIC_PATH . '/pages/dashboard.php');
$router->get('/alimentacion', PUBLIC_PATH . '/pages/alimentacion.php');
$router->get('/ejercicio', PUBLIC_PATH . '/pages/ejercicio.php');
$router->get('/salud-mental', PUBLIC_PATH . '/pages/salud-mental.php');
$router->get('/noticias', PUBLIC_PATH . '/pages/noticias.php');
$router->get('/citas', PUBLIC_PATH . '/pages/citas.php');
$router->get('/calendario', PUBLIC_PATH . '/pages/calendario.php');
$router->get('/perfil', PUBLIC_PATH . '/pages/perfil.php');
$router->get('/mi-plan',   PUBLIC_PATH . '/pages/mi-plan.php');
$router->get('/favoritos', PUBLIC_PATH . '/pages/favoritos.php');


// PÁGINAS ADMIN

$router->get('/admin', PUBLIC_PATH . '/pages/admin/panel.php');
$router->get('/admin/usuarios', PUBLIC_PATH . '/pages/admin/usuarios.php');
$router->get('/admin/citas', PUBLIC_PATH . '/pages/admin/citas.php');
$router->get('/admin/recetas', PUBLIC_PATH . '/pages/admin/recetas.php');
$router->get('/admin/ejercicios', PUBLIC_PATH . '/pages/admin/ejercicios.php');
$router->get('/admin/noticias', PUBLIC_PATH . '/pages/admin/noticias.php');
$router->get('/admin/configuracion',      PUBLIC_PATH . '/pages/admin/configuracion.php');
$router->get('/admin/logs',               PUBLIC_PATH . '/pages/admin/logs.php');
$router->get('/admin/solicitudes',        PUBLIC_PATH . '/pages/admin/solicitudes.php');
$router->get('/admin/recomendaciones',    PUBLIC_PATH . '/pages/admin/recomendaciones.php');
$router->get('/admin/planes-alimentacion',PUBLIC_PATH . '/pages/admin/planes-alimentacion.php');
$router->get('/admin/planes-ejercicio',   PUBLIC_PATH . '/pages/admin/planes-ejercicio.php');


// PÁGINA PROFESIONAL

$router->get('/profesional', PUBLIC_PATH . '/pages/profesional/panel.php');
$router->get('/chat',        PUBLIC_PATH . '/pages/chat.php');
$router->get('/mensajes',    function() { redirect('chat'); });


// ── RecetaController (migrado) ────────────────────────────────────────────────
$router->get('/api/recetas',                        'RecetaController@index');
$router->get('/api/admin/recetas',                  'RecetaController@adminIndex');
$router->post('/api/admin/recetas',                 'RecetaController@adminStore');
$router->post('/api/admin/recetas/{id}/toggle',     'RecetaController@adminToggle');
$router->delete('/api/admin/recetas/{id}',          'RecetaController@adminDestroy');
$router->get('/api/pro/recetas',                    'RecetaController@proIndex');
$router->get('/api/pro/recetas/pending',            'RecetaController@proPending');
$router->post('/api/pro/recetas',                   'RecetaController@proStore');
$router->delete('/api/pro/recetas/{id}',            'RecetaController@proDestroy');
$router->post('/api/pro/recetas/{id}/approve',      'RecetaController@proApprove');


// ── EjercicioController (migrado) ────────────────────────────────────────────
$router->get('/api/ejercicios',                    'EjercicioController@index');
$router->get('/api/admin/ejercicios',              'EjercicioController@adminIndex');
$router->post('/api/admin/ejercicios',             'EjercicioController@adminStore');
$router->post('/api/admin/ejercicios/{id}/toggle', 'EjercicioController@adminToggle');
$router->delete('/api/admin/ejercicios/{id}',      'EjercicioController@adminDestroy');
$router->get('/api/pro/ejercicios',                'EjercicioController@proIndex');
$router->post('/api/pro/ejercicios',               'EjercicioController@proStore');
$router->delete('/api/pro/ejercicios/{id}',        'EjercicioController@proDestroy');
$router->post('/api/pro/plan/asignar-ejercicio',   'EjercicioController@proAsignar');
$router->post('/api/actividad/ejercicio',          'EjercicioController@logEjercicio');


// API - Endpoints públicos

$getCtrl = APP_PATH . '/controllers/get_controller.php';
$router->get('/api/noticias',                   $getCtrl);
$router->get('/api/appointments',               $getCtrl);
$router->get('/api/appointments/next',          $getCtrl);
$router->get('/api/professional-appointments',  $getCtrl);
$router->get('/api/users',                      $getCtrl);
$router->get('/api/mi-plan',                    $getCtrl);
$router->get('/api/especialistas',              $getCtrl);
$router->get('/api/test/last',                  $getCtrl);
$router->get('/api/dashboard/stats',            $getCtrl);
$router->post('/api/appointments/save', APP_PATH . '/controllers/save_appointment.php');
$router->post('/api/appointments/request', APP_PATH . '/controllers/request_appointment.php');
$router->post('/api/appointments/save-professional', APP_PATH . '/controllers/save_professional_appointment.php');
$router->post('/api/appointments/delete', APP_PATH . '/controllers/delete_appointment.php');
$router->post('/api/appointments/cancel', APP_PATH . '/controllers/cancel_appointment.php');
$router->post('/api/profile/update', APP_PATH . '/controllers/update_profile.php');
$router->post('/api/profile/upload-photo', APP_PATH . '/controllers/upload_photo.php');
$router->post('/api/profile/remove-photo', APP_PATH . '/controllers/remove_photo.php');
$router->post('/api/profile/change-password', APP_PATH . '/controllers/change_password.php');
$router->post('/api/test/save', APP_PATH . '/controllers/save_test_result.php');
$router->get('/api/mis-solicitudes',        APP_PATH . '/controllers/mis_solicitudes_get.php');
$router->post('/api/mis-solicitudes/vista', APP_PATH . '/controllers/mis_solicitudes_vista.php');
$proCtrl = APP_PATH . '/controllers/pro_controller.php';
$router->get('/api/pro/usuarios-list',                  $proCtrl);
$router->post('/api/pro/usuario/salud',                 $proCtrl);
$router->get('/api/pro/recomendaciones',                $proCtrl);
$router->get('/api/pro/plan/get-usuario',               $proCtrl);
$router->post('/api/pro/plan/asignar-receta',           $proCtrl);
$router->post('/api/pro/plan/recomendar',               $proCtrl);
$router->post('/api/pro/plan/remove',                   $proCtrl);


// API - Endpoints admin


$adminCtrl = APP_PATH . '/controllers/admin_controller.php';
$router->get('/api/admin/stats',                    $adminCtrl);
$router->get('/api/admin/appointments',             $adminCtrl);
$router->post('/api/admin/appointments/delete',     $adminCtrl);
$router->get('/api/admin/users',            $adminCtrl);
$router->post('/api/admin/users/save',      $adminCtrl);
$router->get('/api/admin/noticias',         $adminCtrl);
$router->post('/api/admin/noticias/save',   $adminCtrl);
$router->post('/api/admin/noticias/delete', $adminCtrl);
$router->get('/api/admin/export',                 $adminCtrl);
$router->get('/api/admin/solicitudes',                  $adminCtrl);
$router->post('/api/admin/solicitudes/accion',          $adminCtrl);
$router->post('/api/admin/solicitudes/update',          $adminCtrl);
$router->post('/api/admin/solicitudes/delete',          $adminCtrl);
$router->get('/api/admin/recomendaciones',              $adminCtrl);
$router->post('/api/admin/recomendaciones/update',      $adminCtrl);
$router->post('/api/admin/recomendaciones/delete',      $adminCtrl);
$router->get('/api/admin/planes-alimentacion',          $adminCtrl);
$router->post('/api/admin/planes-alimentacion/update',  $adminCtrl);
$router->post('/api/admin/planes-alimentacion/delete',  $adminCtrl);
$router->get('/api/admin/planes-ejercicio',             $adminCtrl);
$router->post('/api/admin/planes-ejercicio/update',     $adminCtrl);
$router->post('/api/admin/planes-ejercicio/delete',     $adminCtrl);


$router->get('/api/pro/historial-usuario',              $proCtrl);
$router->get('/api/pro/solicitudes',                    $proCtrl);
$router->get('/api/pro/solicitudes/count',              $proCtrl);
$router->post('/api/pro/solicitudes/accion',            $proCtrl);
$router->get('/api/pro/noticias',                       $proCtrl);
$router->post('/api/pro/noticias/save',                 $proCtrl);
$router->post('/api/pro/noticias/delete',               $proCtrl);
$router->get('/api/pro/rutinas',                        $proCtrl);
$router->get('/api/pro/rutinas/detail',                 $proCtrl);
$router->post('/api/pro/rutinas/save',                  $proCtrl);
$router->post('/api/pro/rutinas/delete',                $proCtrl);
$router->post('/api/pro/rutinas/asignar',               $proCtrl);
$router->get('/api/pro/planes-alimenticios',            $proCtrl);
$router->get('/api/pro/planes-alimenticios/detail',     $proCtrl);
$router->post('/api/pro/planes-alimenticios/save',      $proCtrl);
$router->post('/api/pro/planes-alimenticios/delete',    $proCtrl);
$router->post('/api/pro/planes-alimenticios/asignar',   $proCtrl);


// API - Chat

$chatCtrl = APP_PATH . '/controllers/chat_controller.php';
$router->get('/api/chat/conversaciones',       $chatCtrl);
$router->get('/api/chat/mensajes',             $chatCtrl);
$router->get('/api/chat/no-leidos',            $chatCtrl);
$router->get('/api/chat/usuarios-disponibles', $chatCtrl);
$router->get('/api/admin/todos-usuarios',      $chatCtrl);
$router->post('/api/chat/enviar',              $chatCtrl);
$router->post('/api/chat/marcar-leido',        $chatCtrl);
$router->post('/api/chat/eliminar-mensaje',    $chatCtrl);
$router->post('/api/chat/eliminar-chat',       $chatCtrl);
$router->post('/api/chat/subir-archivo',       $chatCtrl);


// API - Favoritos

$router->get('/api/favoritos',         APP_PATH . '/controllers/favoritos_get.php');
$router->post('/api/favoritos/toggle', APP_PATH . '/controllers/favoritos_toggle.php');


// CRON

$router->get('/cron/news',    APP_PATH . '/controllers/cron_news.php');
$router->get('/cron/recetas',    APP_PATH . '/controllers/cron_recetas.php');


// RATE LIMITING

(function () {
    require_once __DIR__ . '/../app/models/RateLimiter.php';

    $uri      = strtok($_SERVER['REQUEST_URI'], '?');
    $basePath = parse_url(BASE_URL, PHP_URL_PATH) ?: '';
    $path     = '/' . ltrim(substr($uri, strlen($basePath)), '/');

    // Determinar tipo de ruta
    if (preg_match('#^/auth/(login|google)#', $path)) {
        $type = 'auth';
    } elseif (strncmp($path, '/api/chat', 9) === 0) {
        $type = 'chat';
    } elseif (strncmp($path, '/api', 4) === 0) {
        $type = 'api';
    } else {
        $type = 'page';
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    try {
        $limiter = new RateLimiter();
        if (!$limiter->check($ip, $type)) {
            http_response_code(429);
            header('Retry-After: 60');
            if (strncmp($path, '/api', 4) === 0) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Demasiadas solicitudes. Espera un momento e intenta de nuevo.']);
            } else {
                echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>429 – Demasiadas solicitudes</title>'
                   . '<style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f5f5f5;}'
                   . '.box{text-align:center;background:#fff;padding:48px 40px;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08);max-width:420px;}'
                   . 'h1{font-size:3rem;margin:0 0 8px}h2{color:#333;margin:0 0 16px}p{color:#666;line-height:1.6}'
                   . 'a{display:inline-block;margin-top:24px;padding:10px 28px;background:#ff6b35;color:#fff;border-radius:8px;text-decoration:none;font-weight:600;}'
                   . '</style></head>'
                   . '<body><div class="box"><h1>⚠️</h1><h2>Demasiadas solicitudes</h2>'
                   . '<p>Has superado el límite de peticiones permitidas.<br>Por favor espera un momento antes de continuar.</p>'
                   . '<a href="/">Volver al inicio</a></div></body></html>';
            }
            exit;
        }
    } catch (Exception $e) {
        error_log('RateLimiter init error: ' . $e->getMessage());
        // Fail-open: si el limiter falla, se permite la solicitud
    }
})();


// DISPATCH

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);

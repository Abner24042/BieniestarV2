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
$router->get('/mi-plan', PUBLIC_PATH . '/pages/mi-plan.php');


// PÁGINAS ADMIN

$router->get('/admin', PUBLIC_PATH . '/pages/admin/panel.php');
$router->get('/admin/usuarios', PUBLIC_PATH . '/pages/admin/usuarios.php');
$router->get('/admin/citas', PUBLIC_PATH . '/pages/admin/citas.php');
$router->get('/admin/recetas', PUBLIC_PATH . '/pages/admin/recetas.php');
$router->get('/admin/ejercicios', PUBLIC_PATH . '/pages/admin/ejercicios.php');
$router->get('/admin/noticias', PUBLIC_PATH . '/pages/admin/noticias.php');
$router->get('/admin/configuracion', PUBLIC_PATH . '/pages/admin/configuracion.php');
$router->get('/admin/logs', PUBLIC_PATH . '/pages/admin/logs.php');


// PÁGINA PROFESIONAL

$router->get('/profesional', PUBLIC_PATH . '/pages/profesional/panel.php');
$router->get('/chat',        PUBLIC_PATH . '/pages/chat.php');


// API - Endpoints públicos

$router->get('/api/recetas', APP_PATH . '/controllers/get_recetas.php');
$router->get('/api/ejercicios', APP_PATH . '/controllers/get_ejercicios.php');
$router->get('/api/noticias', APP_PATH . '/controllers/get_noticias.php');
$router->get('/api/appointments', APP_PATH . '/controllers/get_appointments.php');
$router->get('/api/users', APP_PATH . '/controllers/get_users.php');
$router->get('/api/professional-appointments', APP_PATH . '/controllers/get_professional_appointments.php');
$router->post('/api/appointments/save', APP_PATH . '/controllers/save_appointment.php');
$router->post('/api/appointments/save-professional', APP_PATH . '/controllers/save_professional_appointment.php');
$router->post('/api/appointments/delete', APP_PATH . '/controllers/delete_appointment.php');
$router->post('/api/appointments/cancel', APP_PATH . '/controllers/cancel_appointment.php');
$router->post('/api/profile/update', APP_PATH . '/controllers/update_profile.php');
$router->post('/api/profile/upload-photo', APP_PATH . '/controllers/upload_photo.php');
$router->post('/api/profile/remove-photo', APP_PATH . '/controllers/remove_photo.php');
$router->post('/api/profile/change-password', APP_PATH . '/controllers/change_password.php');
$router->post('/api/test/save', APP_PATH . '/controllers/save_test_result.php');
$router->get('/api/test/last', APP_PATH . '/controllers/get_test_result.php');
$router->get('/api/appointments/next', APP_PATH . '/controllers/get_next_appointment.php');
$router->get('/api/mi-plan', APP_PATH . '/controllers/get_mi_plan.php');
$router->get('/api/pro/usuarios-list', APP_PATH . '/controllers/pro_get_usuarios_list.php');
$router->get('/api/pro/recomendaciones', APP_PATH . '/controllers/pro_get_recomendaciones.php');
$router->get('/api/pro/plan/get-usuario', APP_PATH . '/controllers/pro_plan_get_usuario.php');
$router->post('/api/pro/plan/asignar-ejercicio', APP_PATH . '/controllers/pro_plan_asignar_ejercicio.php');
$router->post('/api/pro/plan/asignar-receta', APP_PATH . '/controllers/pro_plan_asignar_receta.php');
$router->post('/api/pro/plan/recomendar', APP_PATH . '/controllers/pro_plan_recomendar.php');
$router->post('/api/pro/plan/remove', APP_PATH . '/controllers/pro_plan_remove.php');


// API - Endpoints admin

$router->get('/api/admin/recetas', APP_PATH . '/controllers/admin_get_recetas.php');
$router->post('/api/admin/recetas/save', APP_PATH . '/controllers/admin_save_receta.php');
$router->post('/api/admin/recetas/delete', APP_PATH . '/controllers/admin_delete_receta.php');
$router->get('/api/admin/ejercicios', APP_PATH . '/controllers/admin_get_ejercicios.php');
$router->post('/api/admin/ejercicios/save', APP_PATH . '/controllers/admin_save_ejercicio.php');
$router->post('/api/admin/ejercicios/delete', APP_PATH . '/controllers/admin_delete_ejercicio.php');
$router->get('/api/admin/noticias', APP_PATH . '/controllers/admin_get_noticias.php');
$router->post('/api/admin/noticias/save', APP_PATH . '/controllers/admin_save_noticia.php');
$router->post('/api/admin/noticias/delete', APP_PATH . '/controllers/admin_delete_noticia.php');
$router->get('/api/admin/appointments', APP_PATH . '/controllers/admin_get_appointments.php');
$router->get('/api/admin/users', APP_PATH . '/controllers/admin_get_users.php');
$router->post('/api/admin/users/save', APP_PATH . '/controllers/admin_save_user.php');
$router->get('/api/admin/stats', APP_PATH . '/controllers/admin_get_stats.php');
$router->get('/api/admin/export', APP_PATH . '/controllers/admin_export.php');


// API - Endpoints profesional

$router->get('/api/pro/recetas', APP_PATH . '/controllers/pro_get_recetas.php');
$router->get('/api/pro/recetas/pending', APP_PATH . '/controllers/pro_get_pending_recetas.php');
$router->post('/api/pro/recetas/save', APP_PATH . '/controllers/pro_save_receta.php');
$router->post('/api/pro/recetas/delete', APP_PATH . '/controllers/pro_delete_receta.php');
$router->post('/api/pro/recetas/approve', APP_PATH . '/controllers/pro_approve_receta.php');
$router->get('/api/pro/ejercicios', APP_PATH . '/controllers/pro_get_ejercicios.php');
$router->post('/api/pro/ejercicios/save', APP_PATH . '/controllers/pro_save_ejercicio.php');
$router->post('/api/pro/ejercicios/delete', APP_PATH . '/controllers/pro_delete_ejercicio.php');
$router->get('/api/pro/noticias', APP_PATH . '/controllers/pro_get_noticias.php');
$router->post('/api/pro/noticias/save', APP_PATH . '/controllers/pro_save_noticia.php');
$router->post('/api/pro/noticias/delete', APP_PATH . '/controllers/pro_delete_noticia.php');


// API - Chat

$router->get('/api/chat/conversaciones', APP_PATH . '/controllers/chat_get_conversaciones.php');
$router->get('/api/chat/mensajes',       APP_PATH . '/controllers/chat_get_mensajes.php');
$router->get('/api/chat/no-leidos',      APP_PATH . '/controllers/chat_get_no_leidos.php');
$router->post('/api/chat/enviar',        APP_PATH . '/controllers/chat_enviar.php');
$router->post('/api/chat/marcar-leido',      APP_PATH . '/controllers/chat_marcar_leido.php');
$router->post('/api/chat/eliminar-mensaje', APP_PATH . '/controllers/chat_eliminar_mensaje.php');
$router->post('/api/chat/eliminar-chat',    APP_PATH . '/controllers/chat_eliminar_chat.php');


// CRON

$router->get('/cron/news',    APP_PATH . '/controllers/cron_news.php');
$router->get('/cron/recetas',    APP_PATH . '/controllers/cron_recetas.php');


// DISPATCH

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);

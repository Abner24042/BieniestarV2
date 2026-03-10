<?php
require_once '../../app/config/config.php';

// Si ya está autenticado, redirigir al dashboard
if (isAuthenticated()) {
    redirect('dashboard');
}

$error = '';
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - BIENIESTAR</title>
    <link rel="icon" type="image/svg+xml" href="<?php echo asset('img/content/AAX-Form-Grafico.svg'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/main.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/auth.css'); ?>">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-box">
            <!-- Logo -->
            <div class="auth-logo">
                <h1>BIEN<span>IEST</span>AR</h1>
                <p>Ingresa a tu cuenta</p>
            </div>

            <!-- Mensajes de error -->
            <?php if ($error): ?>
            <div class="alert alert-error">
                <span class="alert-icon">⚠</span>
                <span><?php echo $error; ?></span>
            </div>
            <?php endif; ?>

            <!-- Formulario de Login -->
            <form id="loginForm" action="<?php echo BASE_URL; ?>/auth/login" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="correo">Correo Electrónico</label>
                    <input
                        type="email"
                        id="correo"
                        name="correo"
                        placeholder="tu@correo.com"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="••••••••"
                        required
                    >
                </div>

                <div class="form-options">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember">
                        <span>Recordarme</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    Iniciar Sesión
                </button>
            </form>

            <!-- Divider -->
            <div class="divider">
                <span>O continúa con</span>
            </div>

            <!-- Login con Google -->
            <a href="<?php echo BASE_URL; ?>/auth/google" class="btn btn-google btn-block">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                    <path d="M17.64 9.20456C17.64 8.56637 17.5827 7.95274 17.4764 7.36365H9V10.845H13.8436C13.635 11.97 13.0009 12.9232 12.0477 13.5614V15.8196H14.9564C16.6582 14.2527 17.64 11.9455 17.64 9.20456Z" fill="#4285F4"/>
                    <path d="M9 18C11.43 18 13.4673 17.1941 14.9564 15.8195L12.0477 13.5614C11.2418 14.1013 10.2109 14.4204 9 14.4204C6.65591 14.4204 4.67182 12.8372 3.96409 10.71H0.957275V13.0418C2.43818 15.9832 5.48182 18 9 18Z" fill="#34A853"/>
                    <path d="M3.96409 10.7098C3.78409 10.1698 3.68182 9.59301 3.68182 8.99983C3.68182 8.40665 3.78409 7.82983 3.96409 7.28983V4.95801H0.957273C0.347727 6.17301 0 7.54755 0 8.99983C0 10.4521 0.347727 11.8266 0.957273 13.0416L3.96409 10.7098Z" fill="#FBBC05"/>
                    <path d="M9 3.57955C10.3214 3.57955 11.5077 4.03364 12.4405 4.92545L15.0218 2.34409C13.4632 0.891818 11.4259 0 9 0C5.48182 0 2.43818 2.01682 0.957275 4.95818L3.96409 7.29C4.67182 5.16273 6.65591 3.57955 9 3.57955Z" fill="#EA4335"/>
                </svg>
                Continuar con Google
            </a>

            <!-- Link a home -->
            <div class="back-home">
                <a href="<?php echo BASE_URL; ?>/" class="link-secondary">← Volver al inicio</a>
            </div>
        </div>

        <!-- Info Side (opcional) -->
        <div class="auth-info">
            <div class="info-content">
                <h2>Bienvenido a BIENIESTAR</h2>
                <p>Tu plataforma integral de salud, alimentación y ejercicio</p>
                <ul class="info-features">
                    <li>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Planes nutricionales personalizados</span>
                    </li>
                    <li>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Rutinas de ejercicio adaptadas</span>
                    </li>
                    <li>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Tests de salud mental</span>
                    </li>
                    <li>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Noticias y tips de bienestar</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <script defer src="<?php echo asset('js/main.js'); ?>"></script>
    <script defer src="<?php echo asset('js/auth.js'); ?>"></script>
</body>
</html>
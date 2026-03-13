<?php
require_once '../../../app/config/config.php';
require_once '../../../app/controllers/AuthController.php';

$authController = new AuthController();

if (!$authController->isAuthenticated()) {
    redirect('login');
}

if (!isProfessional()) {
    redirect('dashboard');
}

$user = $authController->getCurrentUser();
$currentPage = 'profesional';
$pageTitle = 'Panel Profesional';
$additionalCSS = ['admin.css', 'profesional.css'];
?>

<?php include '../../../app/views/layouts/header.php'; ?>

<div class="content-wrapper">
    <div class="page-header">
        <h1>Panel Profesional</h1>
        <p><?php echo htmlspecialchars(getRoleLabel($user['rol'])); ?> - <?php echo htmlspecialchars($user['nombre']); ?></p>
    </div>

    <div class="admin-dashboard">
        <!-- Stats -->
        <div class="admin-stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: #4285F4;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M8 2V5M16 2V5M3.5 9.09H20.5M21 8.5V17C21 20 19.5 22 16 22H8C4.5 22 3 20 3 17V8.5C3 5.5 4.5 3.5 8 3.5H16C19.5 3.5 21 5.5 21 8.5Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <h3>Citas Hoy</h3>
                    <p class="stat-number" id="citasHoy">0</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #34A853;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M2 17L12 22L22 17" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <h3>Proximas Citas</h3>
                    <p class="stat-number" id="citasProximas">0</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #FBBC04;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 11C14.2091 11 16 9.20914 16 7C16 4.79086 14.2091 3 12 3C9.79086 3 8 4.79086 8 7C8 9.20914 9.79086 11 12 11Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <h3>Pacientes</h3>
                    <p class="stat-number" id="totalPacientes">0</p>
                </div>
            </div>
        </div>

        <!-- Crear Cita -->
        <div class="admin-section" style="width: 100%;">
            <h2>Agendar Cita para Usuario</h2>
            <form id="formProfessionalAppointment" class="pro-form">
                <div class="form-group">
                    <label for="select_user">Usuario</label>
                    <select id="select_user" name="user_email" required>
                        <option value="">Cargando usuarios...</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="pro_title">Titulo</label>
                    <input type="text" id="pro_title" name="title" required placeholder="Ej: Consulta de seguimiento">
                </div>

                <div class="form-group">
                    <label for="pro_date">Fecha</label>
                    <input type="date" id="pro_date" name="date" required min="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label for="pro_time">Hora</label>
                    <select id="pro_time" name="time" required>
                        <option value="">Selecciona una hora</option>
                        <option value="08:00">08:00 AM</option>
                        <option value="09:00">09:00 AM</option>
                        <option value="10:00">10:00 AM</option>
                        <option value="11:00">11:00 AM</option>
                        <option value="12:00">12:00 PM</option>
                        <option value="13:00">01:00 PM</option>
                        <option value="14:00">02:00 PM</option>
                        <option value="15:00">03:00 PM</option>
                        <option value="16:00">04:00 PM</option>
                        <option value="17:00">05:00 PM</option>
                    </select>
                </div>

                <div class="form-group form-full">
                    <label for="pro_description">Descripcion (opcional)</label>
                    <textarea id="pro_description" name="description" rows="3" placeholder="Notas sobre la cita..."></textarea>
                </div>

                <div class="form-actions form-full">
                    <button type="submit" class="btn btn-primary">
                        <span class="btn-text">Crear Cita</span>
                        <span class="btn-loader" style="display: none;">Creando...</span>
                    </button>
                    <label class="checkbox-label">
                        <input type="checkbox" id="syncGoogleCalendar" name="sync_google">
                        Sincronizar con Google Calendar
                    </label>
                </div>
            </form>
        </div>

        <!-- Tabla Agenda -->
        <div class="admin-section" style="width: 100%;">
            <h2>Mi Agenda</h2>
            <div class="pro-agenda-wrapper">
                <table class="pro-agenda-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Titulo</th>
                            <th>Paciente</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody id="appointmentsTableBody">
                        <tr>
                            <td colspan="5" class="empty-message">
                                Cargando citas...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Sección de Contenido según Rol -->
        <?php if ($user['rol'] === 'nutriologo'): ?>

        <!-- Recetas pendientes de aprobación -->
        <div class="admin-section" style="width: 100%;" id="sectionPendingRecetas">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap:wrap; gap:0.5rem;">
                <h2>⏳ Recetas Pendientes de Aprobación <span id="pendingCount" style="font-size:0.85rem;background:#ff6b35;color:white;padding:2px 10px;border-radius:20px;margin-left:8px;"></span></h2>
                <span style="font-size:0.8rem;color:#999;">Se eliminan automáticamente si no se aprueban en 48h</span>
            </div>
            <div style="display:flex;gap:0.75rem;margin-bottom:1rem;flex-wrap:wrap;">
                <input type="text" id="pendingSearch" placeholder="🔍 Buscar receta..." oninput="aplicarFiltrosPending()"
                    style="flex:1;min-width:180px;padding:8px 12px;background:#2a2a2a;border:1px solid #444;border-radius:8px;color:#fff;font-size:0.875rem;">
                <select id="pendingCatFilter" onchange="aplicarFiltrosPending()"
                    style="padding:8px 12px;background:#2a2a2a;border:1px solid #444;border-radius:8px;color:#fff;font-size:0.875rem;">
                    <option value="">Todas las categorías</option>
                    <option value="desayuno">Desayuno</option>
                    <option value="almuerzo">Almuerzo</option>
                    <option value="comida">Comida</option>
                    <option value="merienda">Merienda</option>
                    <option value="cena">Cena</option>
                    <option value="snack">Snack</option>
                    <option value="postre">Postre</option>
                </select>
            </div>
            <div id="pendingRecetasGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem;">
                <p style="color:#999;grid-column:1/-1;">Cargando...</p>
            </div>
            <div style="text-align:center;margin-top:1.25rem;">
                <button id="btnMostrarMasPending" onclick="mostrarMasPending()" class="btn btn-secondary" style="display:none;">Mostrar más</button>
            </div>
        </div>

        <!-- Modal detalle receta pendiente -->
        <div id="modalPendingDetalle" onclick="if(event.target===this)cerrarDetallePending()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:9998;overflow-y:auto;padding:2rem 1rem;">
            <div style="max-width:620px;margin:0 auto;background:#1a1a1a;border-radius:16px;overflow:hidden;position:relative;">
                <button onclick="cerrarDetallePending()" style="position:absolute;top:12px;right:14px;background:none;border:none;color:#aaa;font-size:1.5rem;cursor:pointer;z-index:1;">✕</button>
                <div id="modalPendingContent"></div>
            </div>
        </div>

        <!-- Mis Recetas (manuales y aprobadas) -->
        <div class="admin-section" style="width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2>🍽️ Mis Recetas</h2>
                <button class="btn btn-primary" id="btnNuevaRecetaPro">+ Nueva Receta</button>
            </div>
            <div style="margin-bottom:1rem;">
                <input type="text" id="proRecetasSearch" placeholder="🔍 Buscar en mis recetas..." oninput="filtrarProRecetas()"
                    style="width:100%;max-width:320px;padding:8px 12px;background:#2a2a2a;border:1px solid #444;border-radius:8px;color:#fff;font-size:0.875rem;">
            </div>
            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Categoría</th>
                            <th>Calorías</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="proRecetasBody">
                        <tr><td colspan="5" class="empty-message">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mis Planes Alimenticios -->
        <div class="admin-section" style="width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2>🥗 Mis Planes Alimenticios</h2>
                <button class="btn btn-primary" id="btnNuevoPlanAlim">+ Nuevo Plan</button>
            </div>
            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Objetivo</th>
                            <th>Duración</th>
                            <th>Recetas</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="proPlanesAlimBody">
                        <tr><td colspan="5" class="empty-message">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Plan Alimenticio -->
        <div id="modalPlanAlim" class="modal" style="display:none;">
            <div class="modal-content" style="max-width:860px;">
                <div class="modal-header">
                    <h3 id="modalPlanAlimTitle">Nuevo Plan Alimenticio</h3>
                    <button class="modal-close" onclick="cerrarModalPlanAlim()">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="formPlanAlim">
                        <input type="hidden" id="plan_alim_id">
                        <div class="form-group"><label>Nombre</label><input type="text" id="plan_alim_nombre" required placeholder="Ej: Plan hipocalórico semana 1"></div>
                        <div class="form-group"><label>Objetivo</label><input type="text" id="plan_alim_objetivo" placeholder="Ej: Pérdida de peso, Ganancia muscular..."></div>
                        <div class="form-group"><label>Duración (semanas)</label><input type="number" id="plan_alim_duracion" value="1" min="1"></div>
                        <div class="form-group form-full"><label>Descripción</label><textarea id="plan_alim_descripcion" rows="2" placeholder="Descripción general del plan..."></textarea></div>
                        <div style="grid-column:1/-1; margin-top:0.5rem;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
                                <h4 style="margin:0; font-size:1rem;">🍽️ Recetas del Plan</h4>
                                <button type="button" class="btn btn-secondary" style="padding:5px 14px; font-size:0.82rem;" onclick="agregarRecetaPlanAlim()">+ Agregar receta</button>
                            </div>
                            <div id="planAlimRecetasList"></div>
                        </div>
                        <div class="modal-footer" style="grid-column:1/-1;">
                            <button type="button" class="btn btn-secondary" onclick="cerrarModalPlanAlim()">Cancelar</button>
                            <button type="button" class="btn btn-primary" onclick="guardarPlanAlim()">Guardar Plan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Receta Pro -->
        <div id="modalRecetaPro" class="modal" style="display:none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="modalRecetaProTitle">Nueva Receta</h3>
                    <button class="modal-close" onclick="document.getElementById('modalRecetaPro').style.display='none'">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="formRecetaPro">
                        <input type="hidden" id="pro_receta_id" name="id">
                        <div class="form-group"><label>Título</label><input type="text" id="pro_receta_titulo" name="titulo" required></div>
                        <div class="form-group"><label>Descripción</label><textarea id="pro_receta_descripcion" name="descripcion" rows="2"></textarea></div>
                        <div class="form-group"><label>Ingredientes</label><textarea id="pro_receta_ingredientes" name="ingredientes" rows="4" placeholder="Un ingrediente por línea"></textarea></div>
                        <div class="form-group"><label>Instrucciones</label><textarea id="pro_receta_instrucciones" name="instrucciones" rows="4" placeholder="Una instrucción por línea"></textarea></div>
                        <div class="form-group"><label>Tiempo (min)</label><input type="number" id="pro_receta_tiempo" name="tiempo_preparacion"></div>
                        <div class="form-group"><label>Porciones</label><input type="number" id="pro_receta_porciones" name="porciones"></div>
                        <div class="form-group"><label>Calorías (kcal)</label><input type="number" step="0.1" id="pro_receta_calorias" name="calorias"></div>
                        <div class="form-group" style="grid-column:1/-1;border-top:1px solid rgba(255,255,255,0.1);padding-top:0.75rem;margin-top:0.25rem;">
                            <p style="font-size:0.78rem;color:#999;margin-bottom:0.5rem;">Datos nutricionales por porción — opcionales (déjalos vacíos si no aplica)</p>
                        </div>
                        <div class="form-group"><label>Proteínas (g)</label><input type="number" step="0.1" id="pro_receta_proteinas" name="proteinas" placeholder="Opcional"></div>
                        <div class="form-group"><label>Carbohidratos (g)</label><input type="number" step="0.1" id="pro_receta_carbohidratos" name="carbohidratos" placeholder="Opcional"></div>
                        <div class="form-group"><label>Grasas (g)</label><input type="number" step="0.1" id="pro_receta_grasas" name="grasas" placeholder="Opcional"></div>
                        <div class="form-group"><label>Fibra (g)</label><input type="number" step="0.1" id="pro_receta_fibra" name="fibra" placeholder="Opcional"></div>
                        <div class="form-group">
                            <label>Categoría</label>
                            <select id="pro_receta_categoria" name="categoria">
                                <option value="desayuno">Desayuno</option>
                                <option value="comida">Comida</option>
                                <option value="cena">Cena</option>
                                <option value="snack">Snack</option>
                                <option value="postre">Postre</option>
                            </select>
                        </div>
                        <div class="form-group"><label>Imagen</label><input type="file" id="pro_receta_imagen" name="imagen" accept="image/*"></div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalRecetaPro').style.display='none'">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($user['rol'] === 'coach'): ?>

        <!-- Mis Rutinas -->
        <div class="admin-section" style="width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2>🏃 Mis Rutinas</h2>
                <button class="btn btn-primary" id="btnNuevaRutina">+ Nueva Rutina</button>
            </div>
            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Nivel</th>
                            <th>Ejercicios</th>
                            <th>Duración</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="proRutinasBody">
                        <tr><td colspan="5" class="empty-message">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Rutina -->
        <div id="modalRutina" class="modal" style="display:none;">
            <div class="modal-content" style="max-width:820px;">
                <div class="modal-header">
                    <h3 id="modalRutinaTitle">Nueva Rutina</h3>
                    <button class="modal-close" onclick="cerrarModalRutina()">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="formRutina">
                        <input type="hidden" id="rutina_id">
                        <div class="form-group"><label>Nombre</label><input type="text" id="rutina_nombre" required placeholder="Ej: Rutina Full Body 3x"></div>
                        <div class="form-group"><label>Descripción</label><textarea id="rutina_descripcion" rows="2" placeholder="Descripción general de la rutina..."></textarea></div>
                        <div class="form-group">
                            <label>Nivel</label>
                            <select id="rutina_nivel">
                                <option value="principiante">Principiante</option>
                                <option value="intermedio">Intermedio</option>
                                <option value="avanzado">Avanzado</option>
                            </select>
                        </div>
                        <div class="form-group"><label>Duración estimada (min)</label><input type="number" id="rutina_duracion" placeholder="Ej: 60"></div>
                        <div style="grid-column:1/-1; margin-top:0.5rem;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
                                <h4 style="margin:0; font-size:1rem;">💪 Ejercicios de la Rutina</h4>
                                <button type="button" class="btn btn-secondary" style="padding:5px 14px; font-size:0.82rem;" onclick="agregarEjercicioRutina()">+ Agregar ejercicio</button>
                            </div>
                            <div id="rutinaEjerciciosList"></div>
                        </div>
                        <div class="modal-footer" style="grid-column:1/-1;">
                            <button type="button" class="btn btn-secondary" onclick="cerrarModalRutina()">Cancelar</button>
                            <button type="button" class="btn btn-primary" onclick="guardarRutina()">Guardar Rutina</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Mis Ejercicios -->
        <div class="admin-section" style="width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2>💪 Mis Ejercicios</h2>
                <button class="btn btn-primary" id="btnNuevoEjercicioPro">+ Nuevo Ejercicio</button>
            </div>
            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Tipo</th>
                            <th>Nivel</th>
                            <th>Duración</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="proEjerciciosBody">
                        <tr><td colspan="5" class="empty-message">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Ejercicio Pro -->
        <div id="modalEjercicioPro" class="modal" style="display:none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="modalEjercicioProTitle">Nuevo Ejercicio</h3>
                    <button class="modal-close" onclick="document.getElementById('modalEjercicioPro').style.display='none'">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="formEjercicioPro">
                        <input type="hidden" id="pro_ejercicio_id" name="id">
                        <div class="form-group"><label>Título</label><input type="text" id="pro_ejercicio_titulo" name="titulo" required></div>
                        <div class="form-group"><label>Descripción</label><textarea id="pro_ejercicio_descripcion" name="descripcion" rows="2"></textarea></div>
                        <div class="form-group"><label>Duración (min)</label><input type="number" id="pro_ejercicio_duracion" name="duracion"></div>
                        <div class="form-group">
                            <label>Nivel</label>
                            <select id="pro_ejercicio_nivel" name="nivel">
                                <option value="principiante">Principiante</option>
                                <option value="intermedio">Intermedio</option>
                                <option value="avanzado">Avanzado</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tipo</label>
                            <select id="pro_ejercicio_tipo" name="tipo">
                                <option value="cardio">Cardio</option>
                                <option value="fuerza">Fuerza</option>
                                <option value="flexibilidad">Flexibilidad</option>
                                <option value="equilibrio">Equilibrio</option>
                            </select>
                        </div>
                        <div class="form-group"><label>Calorías quemadas</label><input type="number" id="pro_ejercicio_calorias" name="calorias_quemadas"></div>
                        <div class="form-group"><label>Músculo objetivo</label><input type="text" id="pro_ejercicio_musculo" name="musculo_objetivo" placeholder="Ej: Pectorales, Bíceps..."></div>
                        <div class="form-group"><label>Equipamiento</label><input type="text" id="pro_ejercicio_equipamiento" name="equipamiento" placeholder="Ej: Mancuernas, Barra, Sin equipo..."></div>
                        <div class="form-group"><label>Músculos secundarios</label><input type="text" id="pro_ejercicio_secundarios" name="musculos_secundarios" placeholder="Separados por coma: Tríceps, Deltoides..."></div>
                        <div class="form-group"><label>URL de Video</label><input type="url" id="pro_ejercicio_video" name="video_url" placeholder="https://..."></div>
                        <div class="form-group"><label>Instrucciones</label><textarea id="pro_ejercicio_instrucciones" name="instrucciones" rows="4" placeholder="Una instrucción por línea"></textarea></div>
                        <div class="form-group"><label>Imagen</label><input type="file" id="pro_ejercicio_imagen" name="imagen" accept="image/*"></div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalEjercicioPro').style.display='none'">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($user['rol'] === 'psicologo'): ?>
        <div class="admin-section" style="width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2>📰 Mis Publicaciones</h2>
                <button class="btn btn-primary" id="btnNuevaNoticiaPro">+ Nueva Publicación</button>
            </div>
            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Categoría</th>
                            <th>Publicado</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="proNoticiasBody">
                        <tr><td colspan="5" class="empty-message">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Noticia Pro -->
        <div id="modalNoticiaPro" class="modal" style="display:none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="modalNoticiaProTitle">Nueva Publicación</h3>
                    <button class="modal-close" onclick="document.getElementById('modalNoticiaPro').style.display='none'">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="formNoticiaPro">
                        <input type="hidden" id="pro_noticia_id" name="id">
                        <div class="form-group"><label>Título</label><input type="text" id="pro_noticia_titulo" name="titulo" required></div>
                        <div class="form-group"><label>Resumen</label><textarea id="pro_noticia_resumen" name="resumen" rows="2" maxlength="500"></textarea></div>
                        <div class="form-group"><label>Contenido</label><textarea id="pro_noticia_contenido" name="contenido" rows="8" required style="min-height:200px;"></textarea></div>
                        <div class="form-group">
                            <label>Categoría</label>
                            <select id="pro_noticia_categoria" name="categoria">
                                <option value="salud-mental">Salud Mental</option>
                                <option value="general">General</option>
                                <option value="alimentacion">Alimentación</option>
                                <option value="ejercicio">Ejercicio</option>
                            </select>
                        </div>
                        <div class="form-group"><label>Imagen</label><input type="file" id="pro_noticia_imagen" name="imagen" accept="image/*"></div>
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="pro_noticia_publicado" name="publicado" value="1"> Publicar inmediatamente
                            </label>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalNoticiaPro').style.display='none'">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($user['rol'] === 'psicologo'): ?>
        <!-- Mis Recomendaciones (Psicólogo) -->
        <div class="admin-section" style="width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2>💬 Mis Recomendaciones</h2>
                <button class="btn btn-primary" id="btnNuevaRecPro">+ Nueva Recomendación</button>
            </div>
            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Título</th>
                            <th>Tipo</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="proRecomendacionesBody">
                        <tr><td colspan="5" class="empty-message">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Nueva Recomendación Pro -->
        <div id="modalNuevaRecPro" class="modal" style="display:none;">
            <div class="modal-content" style="max-width:480px;">
                <div class="modal-header">
                    <h3>Nueva Recomendación</h3>
                    <button class="modal-close" onclick="document.getElementById('modalNuevaRecPro').style.display='none'">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="formNuevaRecPro" onsubmit="return false;">
                        <div class="form-group"><label>Usuario</label><select id="recProUsuario"><option value="">Cargando...</option></select></div>
                        <div class="form-group"><label>Título</label><input type="text" id="recProTitulo" placeholder="Ej: Técnica de respiración..."></div>
                        <div class="form-group"><label>Contenido (opcional)</label><textarea id="recProContenido" rows="3" placeholder="Descripción detallada..."></textarea></div>
                        <div class="form-group">
                            <label>Tipo</label>
                            <select id="recProTipo">
                                <option value="psicologia">Psicología</option>
                                <option value="general">General</option>
                                <option value="ejercicio">Ejercicio</option>
                                <option value="alimentacion">Alimentación</option>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalNuevaRecPro').style.display='none'">Cancelar</button>
                            <button type="button" class="btn btn-primary" onclick="guardarRecPro()">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Gestión de Planes Personalizados -->
        <div class="admin-section" style="width: 100%;">
            <h2>📋 Gestión de Planes Personalizados</h2>
            <div class="form-group" style="max-width:420px; margin-bottom:1.5rem;">
                <label for="planUsuarioSelect">Seleccionar usuario</label>
                <select id="planUsuarioSelect" onchange="cargarPlanUsuario(this.value)">
                    <option value="">Cargando usuarios...</option>
                </select>
            </div>

            <div id="planUsuarioContainer" style="display:none;">

                <?php if ($user['rol'] === 'coach'): ?>
                <div style="margin-bottom:2rem;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem;">
                        <h3 style="margin:0;font-size:1rem;">💪 Ejercicios asignados</h3>
                        <div style="display:flex;gap:8px;">
                            <button class="btn btn-secondary" style="padding:6px 14px;font-size:0.82rem;" onclick="abrirModalAsignarEjercicio()">+ Asignar ejercicio</button>
                            <button class="btn btn-primary" style="padding:6px 14px;font-size:0.82rem;" onclick="abrirModalAsignarRutina()">🏃 Asignar rutina</button>
                        </div>
                    </div>
                    <div id="planEjerciciosList" class="plan-pro-list">Selecciona un usuario.</div>
                </div>
                <?php endif; ?>

                <?php if ($user['rol'] === 'nutriologo'): ?>
                <div style="margin-bottom:2rem;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem;">
                        <h3 style="margin:0;font-size:1rem;">🍽️ Recetas asignadas</h3>
                        <div style="display:flex;gap:8px;">
                            <button class="btn btn-secondary" style="padding:6px 14px;font-size:0.82rem;" onclick="abrirModalAsignarReceta()">+ Asignar receta</button>
                            <button class="btn btn-primary" style="padding:6px 14px;font-size:0.82rem;" onclick="abrirModalAsignarPlanAlim()">🥗 Asignar plan</button>
                        </div>
                    </div>
                    <div id="planRecetasList" class="plan-pro-list">Selecciona un usuario.</div>
                </div>
                <?php endif; ?>

                <div style="margin-bottom:2rem;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem;">
                        <h3 style="margin:0;font-size:1rem;">💬 Recomendaciones</h3>
                        <button class="btn btn-primary" style="padding:6px 14px;font-size:0.82rem;" onclick="abrirModalRecomendacion()">+ Agregar recomendación</button>
                    </div>
                    <div id="planRecomendacionesList" class="plan-pro-list">Selecciona un usuario.</div>
                </div>

            </div>
        </div>

        <?php if ($user['rol'] === 'coach'): ?>
        <div id="modalAsignarRutina" class="modal" style="display:none;">
            <div class="modal-content" style="max-width:480px;">
                <div class="modal-header">
                    <h3>Asignar Rutina</h3>
                    <button class="modal-close" onclick="document.getElementById('modalAsignarRutina').style.display='none'">&times;</button>
                </div>
                <div class="modal-body">
                    <form onsubmit="return false;">
                        <div class="form-group"><label>Rutina</label><select id="asignarRutinaSelect"><option value="">Cargando...</option></select></div>
                        <div class="form-group"><label>Notas para el usuario (opcional)</label><textarea id="asignarRutinaNotas" rows="2" placeholder="Ej: Realiza esta rutina 3 veces por semana..."></textarea></div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalAsignarRutina').style.display='none'">Cancelar</button>
                            <button type="button" class="btn btn-primary" onclick="confirmarAsignarRutina()">Asignar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div id="modalAsignarEjercicio" class="modal" style="display:none;">
            <div class="modal-content" style="max-width:480px;">
                <div class="modal-header">
                    <h3>Asignar Ejercicio</h3>
                    <button class="modal-close" onclick="document.getElementById('modalAsignarEjercicio').style.display='none'">&times;</button>
                </div>
                <div class="modal-body">
                    <form onsubmit="return false;">
                        <div class="form-group"><label>Ejercicio</label><select id="planEjercicioSelect"><option value="">Cargando...</option></select></div>
                        <div class="form-group"><label>Notas para el usuario (opcional)</label><textarea id="planEjercicioNotas" rows="2" placeholder="Ej: Hazlo 3 veces por semana..."></textarea></div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalAsignarEjercicio').style.display='none'">Cancelar</button>
                            <button type="button" class="btn btn-primary" onclick="confirmarAsignarEjercicio()">Asignar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($user['rol'] === 'nutriologo'): ?>
        <div id="modalAsignarPlanAlim" class="modal" style="display:none;">
            <div class="modal-content" style="max-width:480px;">
                <div class="modal-header">
                    <h3>Asignar Plan Alimenticio</h3>
                    <button class="modal-close" onclick="document.getElementById('modalAsignarPlanAlim').style.display='none'">&times;</button>
                </div>
                <div class="modal-body">
                    <form onsubmit="return false;">
                        <div class="form-group"><label>Plan</label><select id="asignarPlanAlimSelect"><option value="">Cargando...</option></select></div>
                        <div class="form-group"><label>Notas para el usuario (opcional)</label><textarea id="asignarPlanAlimNotas" rows="2" placeholder="Ej: Seguir este plan durante 4 semanas..."></textarea></div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalAsignarPlanAlim').style.display='none'">Cancelar</button>
                            <button type="button" class="btn btn-primary" onclick="confirmarAsignarPlanAlim()">Asignar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div id="modalAsignarReceta" class="modal" style="display:none;">
            <div class="modal-content" style="max-width:480px;">
                <div class="modal-header">
                    <h3>Asignar Receta</h3>
                    <button class="modal-close" onclick="document.getElementById('modalAsignarReceta').style.display='none'">&times;</button>
                </div>
                <div class="modal-body">
                    <form onsubmit="return false;">
                        <div class="form-group"><label>Receta</label><select id="planRecetaSelect"><option value="">Cargando...</option></select></div>
                        <div class="form-group"><label>Notas para el usuario (opcional)</label><textarea id="planRecetaNotas" rows="2" placeholder="Ej: Consúmela en el desayuno..."></textarea></div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalAsignarReceta').style.display='none'">Cancelar</button>
                            <button type="button" class="btn btn-primary" onclick="confirmarAsignarReceta()">Asignar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div id="modalRecomendacion" class="modal" style="display:none;">
            <div class="modal-content" style="max-width:480px;">
                <div class="modal-header">
                    <h3>Agregar Recomendación</h3>
                    <button class="modal-close" onclick="document.getElementById('modalRecomendacion').style.display='none'">&times;</button>
                </div>
                <div class="modal-body">
                    <form onsubmit="return false;">
                        <div class="form-group"><label>Título</label><input type="text" id="recTitulo" placeholder="Ej: Meditación diaria..."></div>
                        <div class="form-group"><label>Contenido (opcional)</label><textarea id="recContenido" rows="3" placeholder="Descripción o instrucciones..."></textarea></div>
                        <div class="form-group">
                            <label>Tipo</label>
                            <select id="recTipo">
                                <option value="general">General</option>
                                <option value="psicologia">Psicología</option>
                                <option value="ejercicio">Ejercicio</option>
                                <option value="alimentacion">Alimentación</option>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalRecomendacion').style.display='none'">Cancelar</button>
                            <button type="button" class="btn btn-primary" onclick="confirmarRecomendacion()">Agregar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
const PROFESSIONAL_USER = {
    nombre: '<?php echo addslashes($user['nombre']); ?>',
    correo: '<?php echo addslashes($user['correo']); ?>',
    rol: '<?php echo addslashes($user['rol']); ?>'
};
</script>

<!-- Google Calendar API -->
<script async defer src="https://apis.google.com/js/api.js" onload="gapiLoaded()"></script>
<script async defer src="https://accounts.google.com/gsi/client" onload="gisLoaded()"></script>

<?php if (in_array($user['rol'], ['coach', 'nutriologo'])): ?>
<!-- jsPDF para exportar PDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
<?php endif; ?>

<?php
$additionalJS = ['emailConfig.js', 'googleCalendar.js', 'profesional.js', 'profesional-planes.js'];
if ($user['rol'] === 'nutriologo') {
    $additionalJS[] = 'profesional-recetas.js';
    $additionalJS[] = 'profesional-planes-alimenticios.js';
}
if ($user['rol'] === 'coach') {
    $additionalJS[] = 'profesional-ejercicios.js';
    $additionalJS[] = 'profesional-rutinas.js';
}
if ($user['rol'] === 'psicologo') $additionalJS[] = 'profesional-noticias.js';
include '../../../app/views/layouts/footer.php';
?>

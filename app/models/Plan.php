<?php
require_once __DIR__ . '/../config/database.php';

class Plan {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->ensureTables();
        $this->ensureColumns();
    }

    private function ensureColumns() {
        // Add dia_semana to plan_recetas (0=all days, 1-7=Mon-Sun)
        try { $this->db->exec("ALTER TABLE plan_recetas ADD COLUMN dia_semana INT NOT NULL DEFAULT 0"); } catch (PDOException $e) {}
        // Migrate unique key to include dia_semana so same recipe can appear on different days
        try { $this->db->exec("ALTER TABLE plan_recetas DROP INDEX unique_asig_rec"); } catch (PDOException $e) {}
        try { $this->db->exec("ALTER TABLE plan_recetas ADD UNIQUE KEY unique_asig_rec (usuario_id, receta_id, dia_semana)"); } catch (PDOException $e) {}
    }

    private function ensureTables() {
        $sqls = [
            "CREATE TABLE IF NOT EXISTS plan_ejercicios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                ejercicio_id INT NOT NULL,
                asignado_por VARCHAR(255),
                notas TEXT,
                fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_asig_ej (usuario_id, ejercicio_id)
            )",
            "CREATE TABLE IF NOT EXISTS plan_recetas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                receta_id INT NOT NULL,
                asignado_por VARCHAR(255),
                notas TEXT,
                fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_asig_rec (usuario_id, receta_id)
            )",
            "CREATE TABLE IF NOT EXISTS recomendaciones (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                profesional_id VARCHAR(255),
                titulo VARCHAR(255) NOT NULL,
                contenido TEXT,
                tipo VARCHAR(50) DEFAULT 'general',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                activo TINYINT(1) DEFAULT 1
            )"
        ];
        foreach ($sqls as $sql) {
            try { $this->db->exec($sql); } catch (PDOException $e) {}
        }
    }

    public function asignarEjercicio($usuarioId, $ejercicioId, $asignadoPor, $notas = null) {
        try {
            $stmt = $this->db->prepare(
                "INSERT IGNORE INTO plan_ejercicios (usuario_id, ejercicio_id, asignado_por, notas) VALUES (:uid, :eid, :por, :notas)"
            );
            $stmt->execute([':uid' => $usuarioId, ':eid' => $ejercicioId, ':por' => $asignadoPor, ':notas' => $notas]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function asignarReceta($usuarioId, $recetaId, $asignadoPor, $notas = null, $diaSemana = 0) {
        try {
            $stmt = $this->db->prepare(
                "INSERT IGNORE INTO plan_recetas (usuario_id, receta_id, asignado_por, notas, dia_semana) VALUES (:uid, :rid, :por, :notas, :dia)"
            );
            $stmt->execute([':uid' => $usuarioId, ':rid' => $recetaId, ':por' => $asignadoPor, ':notas' => $notas, ':dia' => (int)$diaSemana]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function addRecomendacion($usuarioId, $profesionalId, $titulo, $contenido, $tipo = 'general') {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO recomendaciones (usuario_id, profesional_id, titulo, contenido, tipo) VALUES (:uid, :pid, :titulo, :contenido, :tipo)"
            );
            $stmt->execute([':uid' => $usuarioId, ':pid' => $profesionalId, ':titulo' => $titulo, ':contenido' => $contenido, ':tipo' => $tipo]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getMiPlan($usuarioId, $filtrarPorDia = false) {
        try {
            $stmt = $this->db->prepare("
                SELECT pe.id AS asignacion_id, pe.notas, pe.asignado_por,
                       e.id, e.titulo, e.descripcion, e.tipo, e.nivel, e.duracion,
                       e.calorias_quemadas, e.imagen, e.musculo_objetivo, e.equipamiento,
                       e.instrucciones, e.video_url
                FROM plan_ejercicios pe
                JOIN ejercicios e ON e.id = pe.ejercicio_id
                WHERE pe.usuario_id = :uid AND e.activo = 1
                ORDER BY pe.fecha_asignacion DESC
            ");
            $stmt->execute([':uid' => $usuarioId]);
            $ejercicios = $stmt->fetchAll();

            $stmt = $this->db->prepare("
                SELECT pr.id AS asignacion_id, pr.notas, pr.asignado_por, pr.dia_semana,
                       r.id, r.titulo, r.descripcion, r.categoria, r.tiempo_preparacion,
                       r.calorias, r.imagen, r.ingredientes, r.instrucciones, r.porciones
                FROM plan_recetas pr
                JOIN recetas r ON r.id = pr.receta_id
                WHERE pr.usuario_id = :uid AND r.activo = 1
                  AND (:filterDay = 0 OR pr.dia_semana = 0 OR pr.dia_semana = WEEKDAY(NOW()) + 1)
                ORDER BY pr.dia_semana ASC, pr.fecha_asignacion DESC
            ");
            $stmt->execute([':uid' => $usuarioId, ':filterDay' => $filtrarPorDia ? 1 : 0]);
            $recetas = $stmt->fetchAll();

            $stmt = $this->db->prepare("
                SELECT * FROM recomendaciones
                WHERE usuario_id = :uid AND activo = 1
                ORDER BY created_at DESC
            ");
            $stmt->execute([':uid' => $usuarioId]);
            $recomendaciones = $stmt->fetchAll();

            return compact('ejercicios', 'recetas', 'recomendaciones');
        } catch (PDOException $e) {
            return ['ejercicios' => [], 'recetas' => [], 'recomendaciones' => []];
        }
    }

    public function removeEjercicio($asignacionId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM plan_ejercicios WHERE id = :id");
            $stmt->execute([':id' => $asignacionId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function removeReceta($asignacionId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM plan_recetas WHERE id = :id");
            $stmt->execute([':id' => $asignacionId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function removeRecomendacion($id, $profesionalEmail = null) {
        try {
            if ($profesionalEmail) {
                $stmt = $this->db->prepare("UPDATE recomendaciones SET activo = 0 WHERE id = :id AND profesional_id = :email");
                $stmt->execute([':id' => $id, ':email' => $profesionalEmail]);
            } else {
                $stmt = $this->db->prepare("UPDATE recomendaciones SET activo = 0 WHERE id = :id");
                $stmt->execute([':id' => $id]);
            }
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getRecomendacionesPorProEnPlan($usuarioId, $profesionalEmail) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM recomendaciones
                WHERE usuario_id = :uid AND profesional_id = :email AND activo = 1
                ORDER BY created_at DESC
            ");
            $stmt->execute([':uid' => $usuarioId, ':email' => $profesionalEmail]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getEjerciciosDisponibles() {
        try {
            $stmt = $this->db->prepare(
                "SELECT id, titulo, tipo, nivel FROM ejercicios WHERE activo = 1 ORDER BY titulo ASC"
            );
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getRecetasDisponibles($nutriologoEmail = null) {
        try {
            if ($nutriologoEmail) {
                $stmt = $this->db->prepare(
                    "SELECT id, titulo, categoria, tiempo_preparacion FROM recetas WHERE activo = 1 AND (creado_por = :email OR aprobada = 1) ORDER BY titulo ASC"
                );
                $stmt->execute([':email' => $nutriologoEmail]);
            } else {
                $stmt = $this->db->prepare(
                    "SELECT id, titulo, categoria, tiempo_preparacion FROM recetas WHERE activo = 1 ORDER BY titulo ASC"
                );
                $stmt->execute();
            }
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getRecomendacionesByPro($profesionalEmail) {
        try {
            $stmt = $this->db->prepare("
                SELECT r.id, r.titulo, r.contenido, r.tipo, r.created_at,
                       u.nombre AS usuario_nombre, u.correo AS usuario_correo
                FROM recomendaciones r
                JOIN usuarios u ON u.id = r.usuario_id
                WHERE r.profesional_id = :email AND r.activo = 1
                ORDER BY r.created_at DESC
            ");
            $stmt->execute([':email' => $profesionalEmail]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getUsuarios() {
        try {
            $stmt = $this->db->prepare(
                "SELECT id, nombre, correo FROM usuarios WHERE activo = 1 AND rol = 'usuario' ORDER BY nombre ASC"
            );
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
}

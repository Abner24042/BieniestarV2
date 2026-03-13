<?php
require_once __DIR__ . '/../config/database.php';

class Chat {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->ensureTables();
    }

    private function ensureTables() {
        $sqls = [
            "CREATE TABLE IF NOT EXISTS chat_conversaciones (
                id            INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id    INT NOT NULL,
                profesional_id INT NOT NULL,
                ultimo_mensaje_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_conv (usuario_id, profesional_id)
            )",
            "CREATE TABLE IF NOT EXISTS chat_mensajes (
                id               INT AUTO_INCREMENT PRIMARY KEY,
                conversacion_id  INT NOT NULL,
                remitente_id     INT NOT NULL,
                contenido        TEXT NOT NULL,
                leido            TINYINT(1) DEFAULT 0,
                created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_conv_created (conversacion_id, created_at),
                INDEX idx_leido (conversacion_id, leido)
            )",
        ];
        foreach ($sqls as $sql) {
            try { $this->db->exec($sql); } catch (PDOException $e) {}
        }

        // Add columns safely (works on MySQL < 8.0.3 that lacks IF NOT EXISTS for ALTER)
        $this->ensureColumn('chat_mensajes', 'tipo',           "VARCHAR(10) NOT NULL DEFAULT 'texto'");
        $this->ensureColumn('chat_mensajes', 'archivo_url',    "VARCHAR(600) DEFAULT NULL");
        $this->ensureColumn('chat_mensajes', 'archivo_nombre', "VARCHAR(255) DEFAULT NULL");
    }

    private function ensureColumn($table, $column, $definition) {
        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
            if (!$stmt->fetch()) {
                $this->db->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
            }
        } catch (PDOException $e) {}
    }

    /* ── Encriptación AES-256-CBC ───────────────────────────────────────── */

    private function encrypt($text) {
        if (!defined('CHAT_ENCRYPTION_KEY') || !$text) return $text;
        $iv  = random_bytes(16);
        $enc = openssl_encrypt($text, 'AES-256-CBC', CHAT_ENCRYPTION_KEY, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $enc);
    }

    private function decrypt($data) {
        if (!$data || !defined('CHAT_ENCRYPTION_KEY')) return $data;
        $raw = base64_decode($data, true);
        if ($raw === false || strlen($raw) < 17) return $data; // mensaje antiguo sin encriptar
        $iv  = substr($raw, 0, 16);
        $enc = substr($raw, 16);
        $dec = openssl_decrypt($enc, 'AES-256-CBC', CHAT_ENCRYPTION_KEY, OPENSSL_RAW_DATA, $iv);
        return $dec !== false ? $dec : $data;
    }

    /* ── Conversaciones ─────────────────────────────────────────────────── */

    public function getOCrearConversacion($usuarioId, $profesionalId) {
        $stmt = $this->db->prepare("SELECT id FROM chat_conversaciones WHERE usuario_id = :uid AND profesional_id = :pid");
        $stmt->execute([':uid' => $usuarioId, ':pid' => $profesionalId]);
        $conv = $stmt->fetch();
        if ($conv) return (int)$conv['id'];

        $stmt = $this->db->prepare("INSERT INTO chat_conversaciones (usuario_id, profesional_id) VALUES (:uid, :pid)");
        $stmt->execute([':uid' => $usuarioId, ':pid' => $profesionalId]);
        return (int)$this->db->lastInsertId();
    }

    public function getConversaciones($userId) {
        try {
            // Always query both sides — the min/max ID assignment has nothing to do with roles
            $sql = "SELECT cc.id, cc.ultimo_mensaje_at,
                        u.id   AS otro_id,
                        u.nombre AS otro_nombre,
                        u.correo AS otro_correo,
                        u.rol    AS otro_rol,
                        (SELECT COUNT(*) FROM chat_mensajes cm
                         WHERE cm.conversacion_id = cc.id AND cm.leido = 0 AND cm.remitente_id != :myId) AS no_leidos,
                        (SELECT cm2.contenido FROM chat_mensajes cm2
                         WHERE cm2.conversacion_id = cc.id ORDER BY cm2.created_at DESC LIMIT 1) AS ultimo_contenido
                    FROM chat_conversaciones cc
                    JOIN usuarios u ON u.id = IF(cc.usuario_id = :myId2, cc.profesional_id, cc.usuario_id)
                    WHERE cc.usuario_id = :myId3 OR cc.profesional_id = :myId4
                    ORDER BY cc.ultimo_mensaje_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':myId' => $userId, ':myId2' => $userId, ':myId3' => $userId, ':myId4' => $userId]);
            $rows = $stmt->fetchAll();

            foreach ($rows as &$row) {
                $row['ultimo_contenido'] = $this->decrypt($row['ultimo_contenido'] ?? '');
            }
            return $rows;
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getMensajes($conversacionId, $desdeMensajeId = 0, $limite = 60) {
        try {
            $stmt = $this->db->prepare("
                SELECT cm.id, cm.remitente_id, cm.contenido, cm.leido, cm.created_at,
                       cm.tipo, cm.archivo_url, cm.archivo_nombre,
                       u.nombre AS remitente_nombre
                FROM chat_mensajes cm
                JOIN usuarios u ON u.id = cm.remitente_id
                WHERE cm.conversacion_id = :cid AND cm.id > :desde
                ORDER BY cm.created_at ASC
                LIMIT :limite
            ");
            $stmt->bindValue(':cid',    $conversacionId, PDO::PARAM_INT);
            $stmt->bindValue(':desde',  $desdeMensajeId, PDO::PARAM_INT);
            $stmt->bindValue(':limite', $limite,         PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll();

            foreach ($rows as &$row) {
                $row['contenido'] = $this->decrypt($row['contenido']);
            }
            return $rows;
        } catch (PDOException $e) {
            return [];
        }
    }

    public function enviarMensaje($conversacionId, $remitenteId, $contenido) {
        try {
            $encrypted = $this->encrypt($contenido);
            $stmt = $this->db->prepare("INSERT INTO chat_mensajes (conversacion_id, remitente_id, contenido) VALUES (:cid, :rid, :cont)");
            $stmt->execute([':cid' => $conversacionId, ':rid' => $remitenteId, ':cont' => $encrypted]);
            $msgId = $this->db->lastInsertId();

            $this->db->prepare("UPDATE chat_conversaciones SET ultimo_mensaje_at = CURRENT_TIMESTAMP WHERE id = :id")
                     ->execute([':id' => $conversacionId]);

            return (int)$msgId;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function enviarArchivo($conversacionId, $remitenteId, $archivoNombre, $archivoUrl) {
        try {
            $contenido = $this->encrypt('[Archivo: ' . $archivoNombre . ']');
            $stmt = $this->db->prepare(
                "INSERT INTO chat_mensajes (conversacion_id, remitente_id, contenido, tipo, archivo_url, archivo_nombre)
                 VALUES (:cid, :rid, :cont, 'archivo', :url, :nombre)"
            );
            $stmt->execute([
                ':cid'    => $conversacionId,
                ':rid'    => $remitenteId,
                ':cont'   => $contenido,
                ':url'    => $archivoUrl,
                ':nombre' => $archivoNombre,
            ]);
            $msgId = $this->db->lastInsertId();

            $this->db->prepare("UPDATE chat_conversaciones SET ultimo_mensaje_at = CURRENT_TIMESTAMP WHERE id = :id")
                     ->execute([':id' => $conversacionId]);

            return (int)$msgId;
        } catch (PDOException $e) {
            return false;
        }
    }

    /* ── Eliminar mensaje (solo el remitente puede borrar el suyo) ───────── */

    public function eliminarMensaje($mensajeId, $remitenteId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM chat_mensajes WHERE id = :id AND remitente_id = :rid");
            $stmt->execute([':id' => $mensajeId, ':rid' => $remitenteId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /* ── Eliminar conversación completa ─────────────────────────────────── */

    public function eliminarChat($conversacionId, $userId) {
        try {
            if (!$this->validarParticipante($conversacionId, $userId)) return false;
            $this->db->prepare("DELETE FROM chat_mensajes WHERE conversacion_id = :cid")
                     ->execute([':cid' => $conversacionId]);
            $this->db->prepare("DELETE FROM chat_conversaciones WHERE id = :cid")
                     ->execute([':cid' => $conversacionId]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /* ── Resto de métodos ───────────────────────────────────────────────── */

    public function marcarLeido($conversacionId, $receptorId) {
        try {
            $stmt = $this->db->prepare("UPDATE chat_mensajes SET leido = 1 WHERE conversacion_id = :cid AND remitente_id != :uid AND leido = 0");
            $stmt->execute([':cid' => $conversacionId, ':uid' => $receptorId]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getNoLeidosTotal($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) AS total FROM chat_mensajes cm
                JOIN chat_conversaciones cc ON cc.id = cm.conversacion_id
                WHERE (cc.usuario_id = :uid OR cc.profesional_id = :uid2)
                AND cm.remitente_id != :uid3
                AND cm.leido = 0
            ");
            $stmt->execute([':uid' => $userId, ':uid2' => $userId, ':uid3' => $userId]);
            $row = $stmt->fetch();
            return (int)($row['total'] ?? 0);
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function validarParticipante($conversacionId, $userId) {
        try {
            $stmt = $this->db->prepare("SELECT id FROM chat_conversaciones WHERE id = :cid AND (usuario_id = :uid OR profesional_id = :uid2)");
            $stmt->execute([':cid' => $conversacionId, ':uid' => $userId, ':uid2' => $userId]);
            return (bool)$stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getRolDestinatario($userId) {
        try {
            $stmt = $this->db->prepare("SELECT rol FROM usuarios WHERE id = :id AND activo = 1");
            $stmt->execute([':id' => $userId]);
            $row = $stmt->fetch();
            return $row ? $row['rol'] : null;
        } catch (PDOException $e) {
            return null;
        }
    }
}

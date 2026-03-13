<?php
require_once __DIR__ . '/../config/database.php';

class Receta {
    private $db;
    private $table = 'recetas';

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getActive() {
        try {
            $query = "SELECT * FROM {$this->table} WHERE activo = 1 ORDER BY created_at DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Error en getActive recetas: ' . $e->getMessage());
            return [];
        }
    }

    public function getAll() {
        try {
            $query = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Error en getAll recetas: ' . $e->getMessage());
            return [];
        }
    }

    public function findById($id) {
        try {
            $query = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Error en findById receta: ' . $e->getMessage());
            return false;
        }
    }

    public function getByCreatorOrApproved($email) {
        try {
            $query = "SELECT * FROM {$this->table} WHERE activo = 1 AND (creado_por = :email OR aprobada = 1) ORDER BY titulo ASC";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':email' => $email]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getByCreator($email) {
        try {
            $query = "SELECT * FROM {$this->table} WHERE creado_por = :email AND activo = 1 ORDER BY created_at DESC";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Error en getByCreator recetas: ' . $e->getMessage());
            return [];
        }
    }

    public function create($data) {
        try {
            $query = "INSERT INTO {$this->table} (titulo, descripcion, ingredientes, instrucciones, tiempo_preparacion, porciones, calorias, imagen, categoria, creado_por, proteinas, carbohidratos, grasas, fibra) VALUES (:titulo, :descripcion, :ingredientes, :instrucciones, :tiempo, :porciones, :calorias, :imagen, :categoria, :creado_por, :proteinas, :carbohidratos, :grasas, :fibra)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':titulo'        => $data['titulo'],
                ':descripcion'   => $data['descripcion'] ?? null,
                ':ingredientes'  => $data['ingredientes'],
                ':instrucciones' => $data['instrucciones'],
                ':tiempo'        => $data['tiempo_preparacion'] ?? null,
                ':porciones'     => $data['porciones'] ?? 1,
                ':calorias'      => $data['calorias'] ?? null,
                ':imagen'        => $data['imagen'] ?? null,
                ':categoria'     => $data['categoria'] ?? 'comida',
                ':creado_por'    => $data['creado_por'] ?? null,
                ':proteinas'     => $data['proteinas'] ?? null,
                ':carbohidratos' => $data['carbohidratos'] ?? null,
                ':grasas'        => $data['grasas'] ?? null,
                ':fibra'         => $data['fibra'] ?? null,
            ]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log('Error en create receta: ' . $e->getMessage());
            return false;
        }
    }

    public function update($id, $data) {
        try {
            $fields = [];
            $params = [':id' => $id];

            $allowed = ['titulo', 'descripcion', 'ingredientes', 'instrucciones', 'tiempo_preparacion', 'porciones', 'calorias', 'imagen', 'categoria', 'proteinas', 'carbohidratos', 'grasas', 'fibra'];
            foreach ($allowed as $field) {
                if (array_key_exists($field, $data)) {
                    $fields[] = "$field = :$field";
                    $params[":$field"] = $data[$field];
                }
            }

            if (empty($fields)) return false;

            $query = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
            $stmt = $this->db->prepare($query);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('Error en update receta: ' . $e->getMessage());
            return false;
        }
    }

    public function delete($id) {
        try {
            $query = "DELETE FROM {$this->table} WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Error en delete receta: ' . $e->getMessage());
            return false;
        }
    }

    public function toggleActive($id, $activo) {
        try {
            $query = "UPDATE {$this->table} SET activo = :activo WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':activo' => $activo ? 1 : 0, ':id' => $id]);
            return true;
        } catch (PDOException $e) {
            error_log('Error en toggleActive receta: ' . $e->getMessage());
            return false;
        }
    }

    public function ensureColumns() {
        $cols = [
            "url_fuente      VARCHAR(500)    NULL",
            "fuente          VARCHAR(200)    NULL",
            "tipo_cocina     VARCHAR(100)    NULL",
            "etiquetas_dieta TEXT            NULL",
            "etiquetas_salud TEXT            NULL",
            "proteinas       DECIMAL(8,2)    NULL",
            "carbohidratos   DECIMAL(8,2)    NULL",
            "grasas          DECIMAL(8,2)    NULL",
            "fibra           DECIMAL(8,2)    NULL",
            "auto_generada   TINYINT(1)      NOT NULL DEFAULT 0",
            "aprobada        TINYINT(1)      NOT NULL DEFAULT 0",
        ];
        foreach ($cols as $col) {
            try {
                $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN $col");
            } catch (PDOException $e) {
                // Ya existe — ignorar
            }
        }
    }

    public function saveFromEdamam($data) {
        try {
            // Evitar duplicados por URL
            $stmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE url_fuente = :url LIMIT 1");
            $stmt->execute([':url' => $data['url_fuente']]);
            if ($stmt->fetch()) return false; // ya existe

            $query = "INSERT INTO {$this->table}
                (titulo, descripcion, ingredientes, instrucciones, tiempo_preparacion,
                 porciones, calorias, imagen, categoria, creado_por, activo,
                 url_fuente, fuente, tipo_cocina, etiquetas_dieta, etiquetas_salud,
                 proteinas, carbohidratos, grasas, fibra, auto_generada)
                VALUES
                (:titulo, :descripcion, :ingredientes, :instrucciones, :tiempo,
                 :porciones, :calorias, :imagen, :categoria, 'edamam', 1,
                 :url_fuente, :fuente, :tipo_cocina, :etiquetas_dieta, :etiquetas_salud,
                 :proteinas, :carbohidratos, :grasas, :fibra, 1)";

            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':titulo'          => $data['titulo'],
                ':descripcion'     => $data['descripcion'] ?? null,
                ':ingredientes'    => $data['ingredientes'],
                ':instrucciones'   => '',
                ':tiempo'          => $data['tiempo_preparacion'] ?? null,
                ':porciones'       => $data['porciones'] ?? 1,
                ':calorias'        => $data['calorias'] ?? null,
                ':imagen'          => $data['imagen'] ?? null,
                ':categoria'       => $data['categoria'],
                ':url_fuente'      => $data['url_fuente'],
                ':fuente'          => $data['fuente'] ?? null,
                ':tipo_cocina'     => $data['tipo_cocina'] ?? null,
                ':etiquetas_dieta' => $data['etiquetas_dieta'] ?? null,
                ':etiquetas_salud' => $data['etiquetas_salud'] ?? null,
                ':proteinas'       => $data['proteinas'] ?? null,
                ':carbohidratos'   => $data['carbohidratos'] ?? null,
                ':grasas'          => $data['grasas'] ?? null,
                ':fibra'           => $data['fibra'] ?? null,
            ]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log('Error en saveFromEdamam: ' . $e->getMessage());
            return 'ERROR:' . $e->getMessage();
        }
    }

    public function getAutoUnapproved() {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM {$this->table}
                 WHERE auto_generada = 1 AND aprobada = 0 AND activo = 1
                 ORDER BY created_at DESC"
            );
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Error en getAutoUnapproved: ' . $e->getMessage());
            return [];
        }
    }

    public function aprobar($id) {
        try {
            $stmt = $this->db->prepare(
                "UPDATE {$this->table} SET aprobada = 1, auto_generada = 0 WHERE id = :id"
            );
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log('Error en aprobar receta: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteOldUnapproved($hours) {
        try {
            $stmt = $this->db->prepare(
                "DELETE FROM {$this->table}
                 WHERE auto_generada = 1
                   AND aprobada = 0
                   AND created_at < DATE_SUB(NOW(), INTERVAL :hours HOUR)"
            );
            $stmt->execute([':hours' => $hours]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log('Error en deleteOldUnapproved: ' . $e->getMessage());
            return 0;
        }
    }

    public function deleteOldAuto($days) {
        try {
            $stmt = $this->db->prepare(
                "DELETE FROM {$this->table}
                 WHERE auto_generada = 1
                   AND created_at < DATE_SUB(NOW(), INTERVAL :days DAY)"
            );
            $stmt->execute([':days' => $days]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log('Error en deleteOldAuto recetas: ' . $e->getMessage());
            return 0;
        }
    }
}

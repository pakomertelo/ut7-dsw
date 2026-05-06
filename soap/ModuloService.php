<?php
require_once __DIR__ . '/../config/database.php';

class ModuloService
{
    private $pdo;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo ?? null;
    }

    private function comprobarConexion()
    {
        if (!$this->pdo) {
            return json_encode(['error' => 'No hay conexión con la base de datos'], JSON_UNESCAPED_UNICODE);
        }

        return null;
    }

    public function infoModulo($id)
    {
        $errorConexion = $this->comprobarConexion();
        if ($errorConexion) {
            return $errorConexion;
        }

        $id = (int) $id;
        $stmt = $this->pdo->prepare('SELECT id, curso_escolar, departamento, nivel, especialidad, nomenclatura_modulo, curso, numalumnos FROM modulos WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $modulo = $stmt->fetch();

        if (!$modulo) {
            return json_encode(['error' => 'No existe un módulo con ese ID'], JSON_UNESCAPED_UNICODE);
        }

        return json_encode($modulo, JSON_UNESCAPED_UNICODE);
    }

    public function infoDepartamentos()
    {
        $errorConexion = $this->comprobarConexion();
        if ($errorConexion) {
            return $errorConexion;
        }

        $stmt = $this->pdo->query('SELECT DISTINCT departamento FROM modulos');
        return json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
    }

    public function infoNomenclaturas()
    {
        $errorConexion = $this->comprobarConexion();
        if ($errorConexion) {
            return $errorConexion;
        }

        $stmt = $this->pdo->query('SELECT nomenclatura_modulo FROM modulos');
        return json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
    }
}

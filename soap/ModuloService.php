<?php
require_once __DIR__ . '/../config/database.php';

class ModuloService
{
    private $pdo;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function infoModulo($id)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM modulos WHERE id = :id');
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
        $stmt = $this->pdo->query('SELECT DISTINCT departamento FROM modulos');
        $departamentos = $stmt->fetchAll();

        return json_encode($departamentos, JSON_UNESCAPED_UNICODE);
    }

    public function infoNomenclaturas()
    {
        $stmt = $this->pdo->query('SELECT nomenclatura_modulo FROM modulos');
        $nomenclaturas = $stmt->fetchAll();

        return json_encode($nomenclaturas, JSON_UNESCAPED_UNICODE);
    }
}

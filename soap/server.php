<?php
if (!class_exists('SoapServer')) {
    echo 'La extensión SOAP de PHP no está activada. Activa extension=soap en php.ini y reinicia Apache.';
    exit;
}

require_once __DIR__ . '/ModuloService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo 'Servidor SOAP activo. Este archivo debe ser consumido desde cliente.php.';
    exit;
}

$server = new SoapServer(null, ['uri' => 'http://localhost/ut7-dsw/soap']);
$server->setClass('ModuloService');
$server->handle();

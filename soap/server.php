<?php
require_once __DIR__ . '/ModuloService.php';

$server = new SoapServer(null, ['uri' => 'urn:ModuloService']);
$server->setClass('ModuloService');
$server->handle();

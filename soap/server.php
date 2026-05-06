<?php
require_once __DIR__ . '/ModuloService.php';

$options = ['uri' => 'http://localhost/ut7-dsw/soap'];
$server = new SoapServer(null, $options);
$server->setClass('ModuloService');
$server->handle();

<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/aemet_config.php';

$accion = $_GET['accion'] ?? '';
$apiKey = defined('AEMET_API_KEY') ? trim((string) AEMET_API_KEY) : '';

$endpoints = [
    'mapa' => 'https://opendata.aemet.es/opendata/api/mapasygraficos/analisis',
    'canarias' => 'https://opendata.aemet.es/opendata/api/prediccion/ccaa/hoy/coo',
    'gran_canaria' => 'https://opendata.aemet.es/opendata/api/prediccion/provincia/manana/35'
];

$titulos = [
    'mapa' => 'Mapa de isobaras',
    'canarias' => 'Predicción Canarias',
    'gran_canaria' => 'Predicción Gran Canaria / Las Palmas'
];

function respuestaError($error, $detalle = '', $status = null)
{
    echo json_encode([
        'ok' => false,
        'error' => $error,
        'detalle' => $detalle,
        'status' => $status
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function anadirApiKey($url, $apiKey)
{
    $separador = strpos($url, '?') === false ? '?' : '&';
    return $url . $separador . 'api_key=' . urlencode($apiKey);
}

function peticionAemet($url, $aceptaJson = true)
{
    $ch = curl_init();
    $headers = ['User-Agent: UT7-DSW-PHP/1.0'];
    if ($aceptaJson) {
        $headers[] = 'Accept: application/json';
    }

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER => $headers
    ]);

    $body = curl_exec($ch);
    $error = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($body === false && strpos(strtolower($error), 'ssl') !== false) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    }

    curl_close($ch);

    return [
        'ok' => $body !== false,
        'status' => $status,
        'body' => $body,
        'error' => $error
    ];
}

function leerJsonSeguro($body, $status)
{
    $json = json_decode((string) $body, true);
    if (!is_array($json)) {
        return [
            'ok' => false,
            'error' => 'Respuesta inicial no válida de AEMET',
            'detalle' => 'HTTP ' . $status . ' | ' . substr(trim((string) $body), 0, 160)
        ];
    }

    return ['ok' => true, 'json' => $json];
}

if (!isset($endpoints[$accion])) {
    respuestaError('Acción no válida');
}

if ($apiKey === '' || $apiKey === 'PON_AQUI_TU_API_KEY') {
    respuestaError('Falta configurar la API key de AEMET');
}

$urlPrimera = anadirApiKey($endpoints[$accion], $apiKey);
$primera = peticionAemet($urlPrimera, true);
if (!$primera['ok']) {
    respuestaError('Error en la primera petición a AEMET', $primera['error'], $primera['status']);
}

$inicioJson = leerJsonSeguro($primera['body'], $primera['status']);
if (!$inicioJson['ok']) {
    respuestaError($inicioJson['error'], $inicioJson['detalle'], $primera['status']);
}

$data1 = $inicioJson['json'];
if (empty($data1['datos'])) {
    respuestaError('AEMET no devolvió URL de datos', $data1['descripcion'] ?? 'Sin descripción', $primera['status']);
}

$segunda = peticionAemet($data1['datos'], false);
if (!$segunda['ok']) {
    respuestaError('Error en la segunda petición a AEMET', $segunda['error'], $segunda['status']);
}

if ($accion === 'mapa') {
    $mapaJson = json_decode((string) $segunda['body'], true);
    if (is_array($mapaJson) && isset($mapaJson[0]['imagen'])) {
        echo json_encode(['ok' => true, 'tipo' => 'imagen', 'titulo' => $titulos[$accion], 'datos' => $mapaJson[0]['imagen']], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (filter_var($data1['datos'], FILTER_VALIDATE_URL)) {
        echo json_encode(['ok' => true, 'tipo' => 'imagen', 'titulo' => $titulos[$accion], 'datos' => $data1['datos']], JSON_UNESCAPED_UNICODE);
        exit;
    }

    respuestaError('No se pudo interpretar el mapa de isobaras', substr(trim((string) $segunda['body']), 0, 160), $segunda['status']);
}

$predJson = json_decode((string) $segunda['body'], true);
if (is_array($predJson) && isset($predJson[0]['prediccion']['texto'])) {
    echo json_encode(['ok' => true, 'tipo' => 'texto', 'titulo' => $titulos[$accion], 'datos' => $predJson[0]['prediccion']['texto']], JSON_UNESCAPED_UNICODE);
    exit;
}

if (is_array($predJson) && isset($predJson[0]['elaborado'])) {
    echo json_encode(['ok' => true, 'tipo' => 'texto', 'titulo' => $titulos[$accion], 'datos' => 'AEMET responde, pero no incluye texto resumido en este momento.'], JSON_UNESCAPED_UNICODE);
    exit;
}

respuestaError('No se pudo obtener la predicción', substr(trim((string) $segunda['body']), 0, 160), $segunda['status']);

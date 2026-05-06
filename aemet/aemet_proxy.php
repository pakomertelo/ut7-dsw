<?php
ob_start();

require_once __DIR__ . '/../config/aemet_config.php';

function responder($array)
{
    if (ob_get_length()) {
        ob_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($array, JSON_UNESCAPED_UNICODE);
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

    return ['ok' => $body !== false, 'status' => $status, 'body' => $body, 'error' => $error];
}

function leerJsonSeguro($body, $status)
{
    $json = json_decode((string) $body, true);
    if (!is_array($json)) {
        return [
            'ok' => false,
            'error' => 'Respuesta inicial no válida de AEMET',
            'detalle' => 'HTTP ' . $status . ' | ' . substr(trim((string) $body), 0, 180)
        ];
    }
    return ['ok' => true, 'json' => $json];
}

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

if (!isset($endpoints[$accion])) {
    responder(['ok' => false, 'error' => 'Acción no válida', 'detalle' => '', 'status' => null]);
}

if (!function_exists('curl_init')) {
    responder(['ok' => false, 'error' => 'La extensión cURL no está activada en PHP', 'detalle' => '', 'status' => null]);
}

if ($apiKey === '' || $apiKey === 'PON_AQUI_TU_API_KEY') {
    responder(['ok' => false, 'error' => 'Falta configurar la API key de AEMET', 'detalle' => '', 'status' => null]);
}

$primera = peticionAemet(anadirApiKey($endpoints[$accion], $apiKey), true);
if (!$primera['ok']) {
    responder(['ok' => false, 'error' => 'Error en la primera petición a AEMET', 'detalle' => $primera['error'], 'status' => $primera['status']]);
}

if (trim((string) $primera['body']) === '') {
    responder(['ok' => false, 'error' => 'Respuesta vacía de AEMET en la primera petición', 'detalle' => '', 'status' => $primera['status']]);
}

$json1 = leerJsonSeguro($primera['body'], $primera['status']);
if (!$json1['ok']) {
    responder(['ok' => false, 'error' => $json1['error'], 'detalle' => $json1['detalle'], 'status' => $primera['status']]);
}

$data1 = $json1['json'];
if (empty($data1['datos'])) {
    responder([
        'ok' => false,
        'error' => 'AEMET no devolvió datos para esta consulta',
        'detalle' => $data1['descripcion'] ?? 'Sin detalle',
        'status' => $primera['status']
    ]);
}

$segunda = peticionAemet($data1['datos'], false);
if (!$segunda['ok']) {
    responder(['ok' => false, 'error' => 'Error en la segunda petición a AEMET', 'detalle' => $segunda['error'], 'status' => $segunda['status']]);
}

if (trim((string) $segunda['body']) === '') {
    responder(['ok' => false, 'error' => 'La segunda respuesta de AEMET llegó vacía', 'detalle' => '', 'status' => $segunda['status']]);
}

if ($accion === 'mapa') {
    $mapa = json_decode((string) $segunda['body'], true);
    if (is_array($mapa) && isset($mapa[0]['imagen'])) {
        responder(['ok' => true, 'tipo' => 'imagen', 'titulo' => $titulos[$accion], 'datos' => $mapa[0]['imagen']]);
    }
    responder(['ok' => true, 'tipo' => 'imagen', 'titulo' => $titulos[$accion], 'datos' => $data1['datos']]);
}

$prediccion = json_decode((string) $segunda['body'], true);
if (is_array($prediccion) && isset($prediccion[0]['prediccion']['texto'])) {
    responder(['ok' => true, 'tipo' => 'texto', 'titulo' => $titulos[$accion], 'datos' => $prediccion[0]['prediccion']['texto']]);
}

responder([
    'ok' => true,
    'tipo' => 'texto',
    'titulo' => $titulos[$accion],
    'datos' => trim(strip_tags((string) $segunda['body']))
]);

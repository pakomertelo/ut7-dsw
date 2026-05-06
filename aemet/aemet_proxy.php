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
    'canarias' => 'Predicción de Canarias',
    'gran_canaria' => 'Predicción de Gran Canaria / Las Palmas'
];

if (!isset($endpoints[$accion])) {
    echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
    exit;
}

if ($apiKey === '' || $apiKey === 'PON_AQUI_TU_API_KEY') {
    echo json_encode(['ok' => false, 'error' => 'Falta configurar la API key de AEMET']);
    exit;
}

$url1 = $endpoints[$accion] . '?api_key=' . urlencode($apiKey);
$ch = curl_init();
curl_setopt_array($ch, [CURLOPT_URL => $url1, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
$respuesta1 = curl_exec($ch);
if ($respuesta1 === false) {
    echo json_encode(['ok' => false, 'error' => 'Error en la primera petición a AEMET', 'detalle' => curl_error($ch)]);
    curl_close($ch);
    exit;
}
curl_close($ch);

$data1 = json_decode($respuesta1, true);
if (!is_array($data1)) {
    echo json_encode(['ok' => false, 'error' => 'Respuesta inicial no válida de AEMET']);
    exit;
}

if (!isset($data1['datos'])) {
    echo json_encode([
        'ok' => false,
        'error' => 'AEMET no devolvió URL de datos',
        'detalle' => $data1['descripcion'] ?? 'Sin descripción adicional'
    ]);
    exit;
}

$ch2 = curl_init();
curl_setopt_array($ch2, [CURLOPT_URL => $data1['datos'], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
$respuesta2 = curl_exec($ch2);
if ($respuesta2 === false) {
    echo json_encode(['ok' => false, 'error' => 'Error en la segunda petición a AEMET', 'detalle' => curl_error($ch2)]);
    curl_close($ch2);
    exit;
}
$tipo2 = (string) curl_getinfo($ch2, CURLINFO_CONTENT_TYPE);
curl_close($ch2);

if ($accion === 'mapa') {
    $mapa = json_decode($respuesta2, true);
    if (is_array($mapa) && isset($mapa[0]['imagen'])) {
        echo json_encode(['ok' => true, 'tipo' => 'imagen', 'datos' => $mapa[0]['imagen'], 'titulo' => $titulos[$accion]]);
        exit;
    }

    if (strpos($tipo2, 'image/') !== false) {
        echo json_encode(['ok' => true, 'tipo' => 'imagen', 'datos' => $data1['datos'], 'titulo' => $titulos[$accion]]);
        exit;
    }

    echo json_encode(['ok' => false, 'error' => 'No se pudo obtener la imagen del mapa', 'detalle' => $data1['descripcion'] ?? 'Formato de respuesta no esperado']);
    exit;
}

$prediccion = json_decode($respuesta2, true);
if (is_array($prediccion) && isset($prediccion[0]['prediccion']['texto'])) {
    echo json_encode(['ok' => true, 'tipo' => 'texto', 'datos' => $prediccion[0]['prediccion']['texto'], 'titulo' => $titulos[$accion]]);
    exit;
}

if (is_array($prediccion) && isset($prediccion[0]['elaborado'])) {
    echo json_encode(['ok' => true, 'tipo' => 'texto', 'datos' => 'AEMET responde pero no incluye texto resumido en este momento.', 'titulo' => $titulos[$accion]]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'No se pudo obtener la predicción', 'detalle' => $data1['descripcion'] ?? 'Sin descripción adicional']);

<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/aemet_config.php';

$accion = $_GET['accion'] ?? '';

$endpoints = [
    'mapa' => 'https://opendata.aemet.es/opendata/api/mapasygraficos/analisis',
    'canarias' => 'https://opendata.aemet.es/opendata/api/prediccion/ccaa/hoy/coo',
    'gran_canaria' => 'https://opendata.aemet.es/opendata/api/prediccion/provincia/manana/35'
];

if (!isset($endpoints[$accion])) {
    echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
    exit;
}

if (AEMET_API_KEY === 'PON_AQUI_TU_API_KEY') {
    echo json_encode(['ok' => false, 'error' => 'Falta configurar la API key']);
    exit;
}

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $endpoints[$accion],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['api_key: ' . AEMET_API_KEY],
    CURLOPT_TIMEOUT => 20
]);

$primeraRespuesta = curl_exec($ch);
if ($primeraRespuesta === false) {
    echo json_encode(['ok' => false, 'error' => 'Error en la primera petición']);
    curl_close($ch);
    exit;
}
curl_close($ch);

$primeraData = json_decode($primeraRespuesta, true);
if (!isset($primeraData['datos'])) {
    echo json_encode(['ok' => false, 'error' => 'No se recibió URL de datos']);
    exit;
}

$ch2 = curl_init();
curl_setopt_array($ch2, [
    CURLOPT_URL => $primeraData['datos'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 20
]);

$segundaRespuesta = curl_exec($ch2);
if ($segundaRespuesta === false) {
    echo json_encode(['ok' => false, 'error' => 'Error en la segunda petición']);
    curl_close($ch2);
    exit;
}
$tipoContenido = curl_getinfo($ch2, CURLINFO_CONTENT_TYPE);
curl_close($ch2);

if ($accion === 'mapa') {
    $mapa = json_decode($segundaRespuesta, true);
    if (is_array($mapa) && isset($mapa[0]['imagen'])) {
        echo json_encode(['ok' => true, 'imagen' => $mapa[0]['imagen']]);
        exit;
    }

    if (strpos((string) $tipoContenido, 'image') !== false) {
        echo json_encode(['ok' => true, 'imagen' => $primeraData['datos']]);
        exit;
    }

    echo json_encode(['ok' => false, 'error' => 'No se encontró imagen del mapa']);
    exit;
}

$prediccion = json_decode($segundaRespuesta, true);

if (is_array($prediccion) && isset($prediccion[0]['prediccion']['texto'])) {
    echo json_encode(['ok' => true, 'texto' => $prediccion[0]['prediccion']['texto']]);
    exit;
}

if (is_array($prediccion) && isset($prediccion[0]['elaborado'])) {
    echo json_encode(['ok' => true, 'texto' => 'Predicción disponible, revisar detalle en AEMET.']);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'No se pudo obtener la predicción']);

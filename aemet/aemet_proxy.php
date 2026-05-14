<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '0');

function responder($datos)
{
    if (ob_get_level() > 0) {
        ob_get_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function errorJson($mensaje, $detalle = '', $status = null)
{
    responder([
        'ok' => false,
        'error' => $mensaje,
        'detalle' => $detalle,
        'status' => $status
    ]);
}

set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (ob_get_level() > 0) {
            ob_get_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => false,
            'error' => 'Error interno del proxy PHP',
            'detalle' => $error['message']
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
});

function anadirApiKey($url, $apiKey)
{
    return $url . (strpos($url, '?') === false ? '?' : '&') . 'api_key=' . urlencode($apiKey);
}

function peticionAemet($url)
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT => 'UT7-DSW-PHP/1.0',
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $body = curl_exec($ch);
    $error = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'ok' => $body !== false,
        'status' => $status,
        'body' => $body,
        'error' => $error
    ];
}

function extraerTexto($value, &$textos)
{
    if (is_string($value)) {
        $limpio = trim(strip_tags($value));
        if ($limpio !== '') {
            $textos[] = $limpio;
        }
        return;
    }

    if (is_array($value)) {
        foreach ($value as $item) {
            extraerTexto($item, $textos);
        }
        return;
    }

    if (is_object($value)) {
        foreach (get_object_vars($value) as $item) {
            extraerTexto($item, $textos);
        }
    }
}

function obtenerTextoPrediccion($body)
{
    $trim = trim((string) $body);
    if ($trim === '') {
        return '';
    }

    $json = json_decode($trim, true);
    if (is_array($json)) {
        $textos = [];
        extraerTexto($json, $textos);
        $textos = array_values(array_unique($textos));
        return trim(implode(' | ', array_slice($textos, 0, 25)));
    }

    return trim(strip_tags($trim));
}

try {
    require_once __DIR__ . '/../config/aemet_config.php';

    $acciones = ['mapa', 'canarias', 'gran_canaria', 'diagnostico'];
    $accion = $_GET['accion'] ?? '';

    if ($accion === 'diagnostico') {
        responder([
            'ok' => true,
            'aemet_api_key_configurada' => defined('AEMET_API_KEY') && trim((string) AEMET_API_KEY) !== '' && trim((string) AEMET_API_KEY) !== 'PON_AQUI_TU_API_KEY',
            'curl_disponible' => function_exists('curl_init'),
            'openssl_disponible' => extension_loaded('openssl'),
            'php_version' => PHP_VERSION,
            'acciones_disponibles' => $acciones
        ]);
    }

    if (!in_array($accion, ['mapa', 'canarias', 'gran_canaria'], true)) {
        errorJson('Acción no válida', 'Usa: mapa, canarias, gran_canaria o diagnostico');
    }

    if (!function_exists('curl_init')) {
        errorJson('La extensión cURL no está activada en PHP');
    }

    if (!defined('AEMET_API_KEY')) {
        errorJson('No existe la constante AEMET_API_KEY en config/aemet_config.php');
    }

    $apiKey = trim((string) AEMET_API_KEY);
    if ($apiKey === '' || $apiKey === 'PON_AQUI_TU_API_KEY') {
        errorJson('Falta configurar la API key de AEMET');
    }

    $base = 'https://opendata.aemet.es/opendata/api';
    $endpoints = [
        'mapa' => $base . '/mapasygraficos/analisis',
        'canarias' => $base . '/prediccion/ccaa/hoy/coo',
        'gran_canaria' => $base . '/prediccion/provincia/manana/35'
    ];
    $titulos = [
        'mapa' => 'Mapa de isobaras',
        'canarias' => 'Información Canarias',
        'gran_canaria' => 'Información Gran Canaria'
    ];

    $primera = peticionAemet(anadirApiKey($endpoints[$accion], $apiKey));
    if (!$primera['ok']) {
        errorJson('Error en la primera petición a AEMET', $primera['error'], $primera['status']);
    }
    if (trim((string) $primera['body']) === '') {
        errorJson('Respuesta vacía de AEMET en la primera petición', '', $primera['status']);
    }

    $json1 = json_decode((string) $primera['body'], true);
    if (!is_array($json1)) {
        errorJson('Respuesta inicial no válida de AEMET', substr(trim((string) $primera['body']), 0, 300), $primera['status']);
    }

    if (empty($json1['datos'])) {
        errorJson('AEMET no devolvió datos para esta consulta', $json1['descripcion'] ?? 'Sin detalle', $primera['status']);
    }

    $segunda = peticionAemet($json1['datos']);
    if (!$segunda['ok']) {
        errorJson('Error en la segunda petición a AEMET', $segunda['error'], $segunda['status']);
    }
    if (trim((string) $segunda['body']) === '') {
        errorJson('La segunda respuesta de AEMET llegó vacía', '', $segunda['status']);
    }

    if ($accion === 'mapa') {
        $jsonMapa = json_decode((string) $segunda['body'], true);
        if (is_array($jsonMapa) && isset($jsonMapa[0]['imagen']) && is_string($jsonMapa[0]['imagen'])) {
            responder(['ok' => true, 'tipo' => 'imagen', 'titulo' => $titulos[$accion], 'datos' => $jsonMapa[0]['imagen']]);
        }
        responder(['ok' => true, 'tipo' => 'imagen', 'titulo' => $titulos[$accion], 'datos' => $json1['datos']]);
    }

    $texto = obtenerTextoPrediccion($segunda['body']);
    if ($texto === '') {
        errorJson('No se pudo obtener texto de predicción', substr(trim((string) $segunda['body']), 0, 300), $segunda['status']);
    }

    responder(['ok' => true, 'tipo' => 'texto', 'titulo' => $titulos[$accion], 'datos' => $texto]);
} catch (Throwable $e) {
    errorJson('Error interno del proxy PHP', $e->getMessage());
}

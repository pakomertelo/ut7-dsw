<?php
$resultadoModulo = null;
$errorModulo = null;
$departamentos = [];
$nomenclaturas = [];
$errorGeneral = null;

if (!class_exists('SoapClient')) {
    $errorGeneral = 'La extensión SOAP de PHP no está activada. Activa extension=soap en php.ini y reinicia Apache.';
} else {
    $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $serverUrl = $protocolo . '://' . $_SERVER['HTTP_HOST'] . $base . '/server.php';

    try {
        $client = new SoapClient(null, [
            'location' => $serverUrl,
            'uri' => 'http://localhost/ut7-dsw/soap',
            'exceptions' => true,
            'connection_timeout' => 15
        ]);

        $jsonDepartamentos = $client->__soapCall('infoDepartamentos', []);
        $departamentos = json_decode($jsonDepartamentos, true);
        if (!is_array($departamentos)) {
            $errorGeneral = 'El servicio no devolvió JSON válido en departamentos.';
            $departamentos = [];
        }

        $jsonNomenclaturas = $client->__soapCall('infoNomenclaturas', []);
        $nomenclaturas = json_decode($jsonNomenclaturas, true);
        if (!is_array($nomenclaturas)) {
            $errorGeneral = 'El servicio no devolvió JSON válido en nomenclaturas.';
            $nomenclaturas = [];
        }

        if (isset($_GET['id_modulo']) && $_GET['id_modulo'] !== '') {
            $idModulo = (int) $_GET['id_modulo'];
            $jsonModulo = $client->__soapCall('infoModulo', [$idModulo]);
            $resultadoModulo = json_decode($jsonModulo, true);

            if (!is_array($resultadoModulo)) {
                $errorModulo = 'El servicio no devolvió JSON válido.';
                $resultadoModulo = null;
            } elseif (isset($resultadoModulo['error'])) {
                $errorModulo = $resultadoModulo['error'];
                $resultadoModulo = null;
            }
        }
    } catch (SoapFault $e) {
        $errorGeneral = 'No se pudo conectar con server.php por SOAP: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cliente SOAP</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
<div class="contenedor">
    <h1>Cliente SOAP de Módulos</h1>
    <p><a href="../index.php">Volver al inicio</a></p>

    <?php if ($errorGeneral): ?><p class="error"><?= htmlspecialchars($errorGeneral) ?></p><?php endif; ?>

    <section class="tarjeta">
        <h2>Consultar módulo por ID</h2>
        <form method="get">
            <label for="id_modulo">ID del módulo:</label>
            <input type="number" name="id_modulo" id="id_modulo" min="1" value="<?= isset($_GET['id_modulo']) ? htmlspecialchars($_GET['id_modulo']) : '' ?>" required>
            <button type="submit">Consultar</button>
        </form>
        <?php if ($errorModulo): ?><p class="error"><?= htmlspecialchars($errorModulo) ?></p><?php endif; ?>
        <?php if ($resultadoModulo): ?>
            <table><thead><tr><th>Campo</th><th>Valor</th></tr></thead><tbody>
            <?php foreach ($resultadoModulo as $campo => $valor): ?><tr><td><?= htmlspecialchars($campo) ?></td><td><?= htmlspecialchars((string) $valor) ?></td></tr><?php endforeach; ?>
            </tbody></table>
        <?php endif; ?>
    </section>

    <section class="tarjeta">
        <h2>Departamentos</h2>
        <?php if (count($departamentos) > 0): ?><ul><?php foreach ($departamentos as $item): ?><li><?= htmlspecialchars($item['departamento'] ?? '') ?></li><?php endforeach; ?></ul><?php else: ?><p>No hay departamentos disponibles.</p><?php endif; ?>
    </section>

    <section class="tarjeta">
        <h2>Nomenclaturas</h2>
        <?php if (count($nomenclaturas) > 0): ?><ul><?php foreach ($nomenclaturas as $item): ?><li><?= htmlspecialchars($item['nomenclatura_modulo'] ?? '') ?></li><?php endforeach; ?></ul><?php else: ?><p>No hay nomenclaturas disponibles.</p><?php endif; ?>
    </section>
</div>
</body>
</html>

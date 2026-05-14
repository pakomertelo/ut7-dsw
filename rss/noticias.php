<?php
$rssUrl = 'https://www.europapress.es/rss/rss.aspx?ch=00066';
$items = [];
$error = null;

libxml_use_internal_errors(true);
$rss = simplexml_load_file($rssUrl);

if ($rss === false) {
    $error = 'No se pudo cargar el RSS de EuropaPress.';
} elseif (!isset($rss->channel->item)) {
    $error = 'El RSS no contiene noticias en este momento.';
} else {
    foreach ($rss->channel->item as $item) {
        $items[] = [
            'titulo' => (string) $item->title,
            'descripcion' => strip_tags((string) $item->description),
            'link' => (string) $item->link
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Noticias RSS EuropaPress</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
<div class="contenedor">
    <h1>Noticias RSS EuropaPress</h1>
    <p><a href="../index.php">Volver al inicio</a></p>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php elseif (count($items) === 0): ?>
        <p>No hay noticias para mostrar.</p>
    <?php else: ?>
        <table>
            <thead>
            <tr>
                <th>Noticia</th>
                <th>Descripción</th>
                <th>Link</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $noticia): ?>
                <tr>
                    <td><?= htmlspecialchars($noticia['titulo']) ?></td>
                    <td><?= htmlspecialchars($noticia['descripcion']) ?></td>
                    <td><a href="<?= htmlspecialchars($noticia['link']) ?>" target="_blank" rel="noopener noreferrer">Abrir noticia</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>

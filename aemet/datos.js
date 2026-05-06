const resultado = document.getElementById('resultado');

function limpiarYMostrarCarga() {
    resultado.innerHTML = '<p>Cargando...</p>';
}

function pintarError(data) {
    const detalle = data && data.detalle ? '<br><small>' + data.detalle + '</small>' : '';
    const mensaje = data && data.error ? data.error : 'Ha ocurrido un error al consultar AEMET.';
    resultado.innerHTML = '<p class="error">' + mensaje + detalle + '</p>';
}

function pintarRespuesta(data) {
    if (!data.ok) {
        pintarError(data);
        return;
    }

    if (data.tipo === 'imagen') {
        resultado.innerHTML = '<h3>' + data.titulo + '</h3><img src="' + data.datos + '" alt="Mapa" class="imagen-mapa">';
        return;
    }

    if (data.tipo === 'texto') {
        resultado.innerHTML = '<table><thead><tr><th>Título</th><th>Información</th></tr></thead><tbody><tr><td>' + data.titulo + '</td><td>' + data.datos + '</td></tr></tbody></table>';
        return;
    }

    pintarError({ error: 'Formato de respuesta no válido' });
}

function cargar(accion) {
    limpiarYMostrarCarga();
    fetch('aemet_proxy.php?accion=' + accion)
        .then(response => response.json())
        .then(data => pintarRespuesta(data))
        .catch(() => pintarError({ error: 'No se pudo contactar con el servidor.' }));
}

function cargarMapaIsobaras() {
    cargar('mapa');
}

function cargarInformacionCanarias() {
    cargar('canarias');
}

function cargarInformacionGranCanaria() {
    cargar('gran_canaria');
}

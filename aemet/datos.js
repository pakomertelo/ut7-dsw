const resultado = document.getElementById('resultado');

function limpiarYMostrarCarga() {
    resultado.innerHTML = '<p>Cargando...</p>';
}

function mostrarError(mensaje, detalle) {
    let html = '<p class="error">' + mensaje + '</p>';
    if (detalle) {
        html += '<p>' + detalle + '</p>';
    }
    resultado.innerHTML = html;
}

function mostrarTexto(data) {
    resultado.innerHTML = '<table><thead><tr><th>Título</th><th>Información</th></tr></thead><tbody><tr><td>' + data.titulo + '</td><td>' + data.datos + '</td></tr></tbody></table>';
}

function mostrarImagen(data) {
    resultado.innerHTML = '<h3>' + data.titulo + '</h3><img src="' + data.datos + '" alt="Mapa de isobaras" class="imagen-mapa">';
}

function cargar(accion) {
    limpiarYMostrarCarga();

    fetch('aemet_proxy.php?accion=' + accion)
        .then(function (response) {
            return response.text();
        })
        .then(function (texto) {
            let data;
            try {
                data = JSON.parse(texto);
            } catch (e) {
                mostrarError('El proxy no devolvió JSON válido.', texto.substring(0, 140));
                return;
            }

            if (!data.ok) {
                mostrarError(data.error || 'Error al consultar AEMET.', data.detalle || '');
                return;
            }

            if (data.tipo === 'imagen') {
                mostrarImagen(data);
                return;
            }

            if (data.tipo === 'texto') {
                mostrarTexto(data);
                return;
            }

            mostrarError('Formato de respuesta no válido.', '');
        })
        .catch(function () {
            mostrarError('No se pudo contactar con el servidor.', '');
        });
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

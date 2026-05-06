const resultado = document.getElementById('resultado');

function limpiarYMostrarCarga() {
    resultado.innerHTML = '<p>Cargando...</p>';
}

function mostrarError() {
    resultado.innerHTML = '<p class="error">Ha ocurrido un error al consultar AEMET.</p>';
}

function cargarMapaIsobaras() {
    limpiarYMostrarCarga();

    fetch('aemet_proxy.php?accion=mapa')
        .then(response => response.json())
        .then(data => {
            if (!data.ok || !data.imagen) {
                mostrarError();
                return;
            }
            resultado.innerHTML = '<h3>Mapa de isobaras</h3><img src="' + data.imagen + '" alt="Mapa isobaras" class="imagen-mapa">';
        })
        .catch(() => mostrarError());
}

function cargarInformacionCanarias() {
    limpiarYMostrarCarga();

    fetch('aemet_proxy.php?accion=canarias')
        .then(response => response.json())
        .then(data => {
            if (!data.ok || !data.texto) {
                mostrarError();
                return;
            }
            resultado.innerHTML = '<table><thead><tr><th>Zona</th><th>Predicción</th></tr></thead><tbody><tr><td>Canarias</td><td>' + data.texto + '</td></tr></tbody></table>';
        })
        .catch(() => mostrarError());
}

function cargarInformacionGranCanaria() {
    limpiarYMostrarCarga();

    fetch('aemet_proxy.php?accion=gran_canaria')
        .then(response => response.json())
        .then(data => {
            if (!data.ok || !data.texto) {
                mostrarError();
                return;
            }
            resultado.innerHTML = '<table><thead><tr><th>Zona</th><th>Predicción</th></tr></thead><tbody><tr><td>Gran Canaria / Las Palmas</td><td>' + data.texto + '</td></tr></tbody></table>';
        })
        .catch(() => mostrarError());
}

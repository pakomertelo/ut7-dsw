# Proyecto UT7 - Servicios y uso de APIs

Proyecto web sencillo para 2º DAW, hecho en PHP, HTML, CSS y un poco de JavaScript.

## Partes del proyecto

- Servicios SOAP sobre la base de datos `fp` (tabla `modulos`).
- Lectura del RSS de EuropaPress.
- Predicción meteorológica usando API REST de AEMET con proxy en PHP.

## Requisitos

- PHP 8+ con extensión SOAP activada.
- MySQL o MariaDB.
- Apache local (XAMPP, Laragon o similar).
- Extensión curl activa o `allow_url_fopen` para peticiones externas.

## Activar SOAP en PHP

Si aparece error de `SoapClient` o `SoapServer`:

1. Abrir `php.ini`.
2. Buscar `;extension=soap`.
3. Quitar el `;` para dejar `extension=soap`.
4. Reiniciar Apache.

## Estructura

- `index.php`
- `css/estilos.css`
- `config/database.php`
- `config/aemet_config.php`
- `config/aemet_config.example.php`
- `soap/ModuloService.php`
- `soap/server.php`
- `soap/cliente.php`
- `rss/noticias.php`
- `aemet/prediccion_meteorologica.html`
- `aemet/datos.js`
- `aemet/aemet_proxy.php`

## Base de datos

1. Crear base de datos `fp`.
2. Importar el SQL de la UT7 con la tabla `modulos`.
3. Revisar credenciales en `config/database.php`.

## Configurar conexión MySQL

Editar `config/database.php` con:

- Host
- Usuario
- Contraseña
- Nombre de base de datos (`fp`)

## Configurar API key de AEMET

1. Editar `config/aemet_config.php`.
2. Poner tu clave real en `AEMET_API_KEY`.

La API key no se usa en JavaScript ni en HTML, solo en PHP.

## Cómo abrir cada página

- Portada: `http://localhost/ut7-dsw/index.php`
- Cliente SOAP: `http://localhost/ut7-dsw/soap/cliente.php`
- RSS: `http://localhost/ut7-dsw/rss/noticias.php`
- AEMET: `http://localhost/ut7-dsw/aemet/prediccion_meteorologica.html`

## Servicios SOAP

Se usa SOAP nativo de PHP en modo no-WSDL.

Servicios:

- `infoModulo(id)`: devuelve JSON con los datos del módulo o error.
- `infoDepartamentos()`: devuelve JSON con departamentos distintos.
- `infoNomenclaturas()`: devuelve JSON con nomenclaturas.

## RSS EuropaPress

Se carga `https://www.europapress.es/rss/rss.aspx?ch=00066` con SimpleXML y se muestra tabla con Noticia, Descripción y Link.

## Flujo AEMET

1. `datos.js` llama a `aemet_proxy.php` con una acción.
2. El proxy hace primera petición a AEMET con `api_key`.
3. Si AEMET devuelve `datos`, hace segunda petición a esa URL.
4. Devuelve JSON simple al frontend.

Formato correcto:

- `ok: true`
- `tipo: imagen | texto`
- `datos: url o texto`
- `titulo: texto`

Si falla:

- `ok: false`
- `error: mensaje`
- `detalle: descripción de AEMET si existe`

## Qué hacer si AEMET no devuelve datos

- Revisar API key.
- Revisar que el endpoint esté disponible en ese momento.
- Reintentar pasados unos segundos.
- Leer el campo `detalle` que devuelve el proxy, porque incluye la descripción de AEMET cuando existe.

## Cómo probar los tres apartados

1. Entrar a `index.php`.
2. Abrir Cliente SOAP y consultar un ID real de `modulos`.
3. Abrir RSS y comprobar tabla con enlaces.
4. Abrir AEMET y pulsar los tres botones para ver imagen o tabla.


## Extensiones para AEMET

- Activar `curl` y `openssl` en `php.ini`.
- Si en local aparece error SSL (certificados en Windows/Apache), revisar el almacén de certificados o usar la configuración local indicada en el proxy solo para pruebas de práctica.


## Prueba rápida de SOAP

- Abrir `http://localhost/ut7-dsw/soap/server.php` en navegador: debe mostrar mensaje de servidor activo.
- Abrir `http://localhost/ut7-dsw/soap/cliente.php` y consultar un ID.
- Si falla, revisar que `extension=soap` esté activa y que la URL de `server.php` mostrada en cliente sea correcta.

## Prueba rápida de AEMET

- Configurar API key en `config/aemet_config.php`.
- Abrir `aemet/prediccion_meteorologica.html`.
- Pulsar los tres botones.
- Si AEMET responde "No hay datos que satisfagan esos criterios", es una respuesta válida del servicio en ese momento y no implica que el código esté roto.

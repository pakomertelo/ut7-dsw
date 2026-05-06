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
2. Importar el SQL de la UT7 que contiene la tabla `modulos`.
3. Revisar credenciales en `config/database.php`.

## Configurar conexión MySQL

Editar `config/database.php` con:

- Host
- Usuario
- Contraseña
- Nombre de base de datos (`fp`)

## Configurar API key de AEMET

1. Copiar el ejemplo de configuración.
2. Editar `config/aemet_config.php`.
3. Poner tu clave real en `AEMET_API_KEY`.

No se debe poner la clave en `datos.js` ni en el HTML.

## Cómo abrir cada página

- Portada: `http://localhost/ut7-dsw/index.php`
- Cliente SOAP: `http://localhost/ut7-dsw/soap/cliente.php`
- RSS: `http://localhost/ut7-dsw/rss/noticias.php`
- AEMET: `http://localhost/ut7-dsw/aemet/prediccion_meteorologica.html`

## Servicios SOAP

Se usa SOAP nativo de PHP en modo no-WSDL para simplificar la práctica.

Servicios:

- `infoModulo(id)`: devuelve JSON con los datos del módulo o error.
- `infoDepartamentos()`: devuelve JSON con departamentos distintos.
- `infoNomenclaturas()`: devuelve JSON con nomenclaturas.

## RSS EuropaPress

Se carga `https://www.europapress.es/rss/rss.aspx?ch=00066` con SimpleXML y se muestra una tabla con:

- Noticia
- Descripción
- Link

## Flujo AEMET

1. `datos.js` llama a `aemet_proxy.php` con una acción.
2. El proxy hace la primera petición a AEMET con API key.
3. Lee la URL del campo `datos`.
4. Hace una segunda petición a esa URL.
5. Devuelve al frontend la imagen o texto.

## Problemas posibles

- AEMET puede no devolver datos en ese momento.
- A veces hay que esperar unos segundos y volver a probar.
- Si SOAP no funciona, revisar `php.ini` y activar `extension=soap`.

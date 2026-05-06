# CHANGELOG

## 2026-05-06

- Creación inicial del proyecto UT7.
- Servicios SOAP.
- Cliente SOAP.
- RSS EuropaPress.
- Integración AEMET.
- Estilos básicos.
- Corrección de integración AEMET usando `api_key` por query string y mensajes de error con detalle.
- Ajuste de respuesta del proxy AEMET a formato `ok`, `tipo`, `datos`, `titulo`.
- Corrección de cliente/servidor SOAP para usar el mismo `uri` y llamadas `__soapCall`.
- Mejora de control de errores en SOAP para respuestas JSON inválidas o fallos de conexión.

- Corrección de peticiones AEMET usando cURL reutilizable con control de estado, body y error.
- Mejora de errores de AEMET con detalle técnico y estado HTTP cuando aplica.
- Corrección y revisión de cliente/servidor SOAP con `uri` consistente y control de ausencia de extensión SOAP.
- Mejora de mensajes cuando faltan extensiones PHP (SOAP/cURL/OpenSSL).

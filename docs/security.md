# Seguridad — Televisor

## Superficies

1. **Panel web** — autenticación por sesión, policies por rol, rutas sensibles con `can:manage-content` / `role:admin`.
2. **API `/api/v1/device/*`** — Bearer token por pantalla; pair/activate públicos con rate limit.
3. **Archivos** — media en disco privado; preview panel autenticado; descarga dispositivo autorizada por manifiesto.

## Tokens de dispositivo

- Longitud 64 caracteres; almacenamiento: `hash('sha256', token)`.
- Revocación: status `revoked` + borrado de hash; cache de entrega invalidada.
- No registrar tokens en logs (OkHttp BASIC solo en debug Android).

## Recomendaciones operativas

- Rotar credenciales admin tras instalación inicial.
- Restringir panel por VPN o IP municipal si es posible.
- Mantener Laravel y dependencias actualizadas (`composer update` con pruebas).
- Revisar permisos de `storage/` y `bootstrap/cache/` en el servidor.

## Reporte de incidentes

Ante filtración de token: **revocar pantalla** en el panel y volver a vincular el dispositivo.

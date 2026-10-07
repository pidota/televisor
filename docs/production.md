# Producción y operación (Etapa 15)

## Checklist antes de publicar

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` con HTTPS real.
- [ ] `php artisan config:cache`, `route:cache`, `view:cache`.
- [ ] Cola y scheduler: `php artisan schedule:work` o cron `* * * * * php artisan schedule:run`.
- [ ] Tareas programadas: `urgent-messages:expire`, `televisor:prune-heartbeats`.
- [ ] MySQL 8 con backups; no usar SQLite en producción.
- [ ] Storage privado (`storage/app/private`) no expuesto por URL pública.
- [ ] Certificado TLS en el reverse proxy (Nginx/Apache); forzar HTTPS.
- [ ] Usuario admin con contraseña fuerte; roles mínimos para operadores.
- [ ] Rate limits API dispositivo activos (30/min pair por IP, 180/min por pantalla).

## Seguridad

| Medida | Implementación |
|--------|----------------|
| Token dispositivo | Solo hash SHA-256 en BD; entrega única vía cache al vincular |
| Panel | Sesión + CSRF; cabeceras `X-Frame-Options`, `X-Content-Type-Options` |
| Login | Rate limit en intentos fallidos (`LoginRequest`) |
| Media dispositivo | Descarga solo si el asset está en el manifiesto actual |
| API JSON | 401/403 sin filtrar existencia de recursos sensibles |

Ver también [docs/security.md](security.md).

## Rendimiento

- Manifiesto por pantalla se cachea en memoria durante la misma petición HTTP (`DeviceManifestService`).
- Índices en `device_heartbeats (screen_id, created_at)` y migraciones de dominio.
- Heartbeats: retención por defecto **30 días** (`televisor:prune-heartbeats`).

## Pruebas

```bash
php artisan test
```

Incluye API dispositivo, prioridad de urgentes, monitoreo y cabeceras del panel.

## Android TV

- URL API HTTPS en build release (`API_BASE_URL`).
- Desactivar cleartext en `network_security_config` cuando no use HTTP local.
- Publicar APK/AAB firmado; versionar `versionName` al desplegar backend incompatible.

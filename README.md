# Televisor — Cartelería digital municipal

Sistema para administrar y reproducir contenido multimedia en pantallas Android TV / Google TV de una municipalidad.

## Requisitos

- PHP 8.2+
- Composer
- MySQL 8 (recomendado en producción) o SQLite (desarrollo)
- Node.js (opcional, assets Vite en etapas del panel)
- Extensiones PHP: `pdo`, `mbstring`, `openssl`, `fileinfo`

## Instalación

```bash
cd c:\laragon\www\televisor
composer install
cp .env.example .env   # en Windows: copy .env.example .env
php artisan key:generate
```

### MySQL (Laragon)

En `.env`:

```env
APP_NAME=Televisor
APP_TIMEZONE=America/Santiago

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=televisor
DB_USERNAME=root
DB_PASSWORD=
```

Crear la base de datos `televisor` en MySQL y ejecutar:

```bash
php artisan migrate
php artisan db:seed
```

### SQLite (rápido)

Por defecto el proyecto puede usar `database/database.sqlite`:

```bash
php artisan migrate
php artisan db:seed
```

## Variables de entorno relevantes

| Variable | Uso |
|----------|-----|
| `APP_URL` | URL pública del panel |
| `APP_TIMEZONE` | Zona horaria de la aplicación |
| `DB_*` | Conexión MySQL |
| `FILESYSTEM_DISK` | `local` (futuro: `s3`) |
| `QUEUE_CONNECTION` | Colas para procesamiento de media |

Configuración operativa adicional en tabla `settings` (seed): timezone municipal, intervalos de heartbeat, TTL de códigos de vinculación, tamaño máximo de upload.

## Migraciones

```bash
php artisan migrate
php artisan migrate:fresh --seed   # reinicio completo en desarrollo
```

## Storage

En etapas posteriores el contenido multimedia se guardará en `storage/app/...` con disco configurable por registro.

## Documentación

- [Arquitectura](docs/architecture.md)
- [Base de datos](docs/database.md)
- [API dispositivos](docs/api.md) (Etapa 8)
- [Android TV](docs/android-tv.md) (Etapas 10+)

## Panel de administración (Etapa 2)

Tras migrar y sembrar datos:

```bash
php artisan serve
# o virtual host Laragon: http://televisor.test
```

Ruta de ingreso: `/login`

| Campo | Valor (desarrollo) |
|-------|---------------------|
| Correo | `admin@televisor.local` |
| Contraseña | `Televisor2026!` |

Roles sembrados: `admin`, `operator`, `viewer`. Solo `admin` ve **Usuarios** y **Configuración**.

## Estado del desarrollo

- **Etapa 1:** Base de datos y modelos — completada
- **Etapa 2:** Auth, layout, sidebar, dashboard, roles básicos — completada
- **Etapa 3:** Gestión de pantallas (CRUD, vinculación, API pair/activate) — completada
- **Etapa 4:** Biblioteca multimedia (MP4, JPG/PNG/WebP, checksum, preview) — completada
- **Etapa 5:** Playlists (orden drag & drop, duración imágenes) — completada
- **Etapa 6:** Asignación playlists ↔ pantallas/grupos + `manifest_version` — completada
- **Etapa 7:** Programación (fechas UTC, franjas locales, resolución de playlist) — completada
- **Etapa 8:** API REST (`config`, `playlist`, `heartbeat`, descarga media) — completada
- **Etapa 9:** Mensajes urgentes (panel + prioridad en API/manifiesto) — completada
- **Etapa 10:** App Android TV (vinculación + API) — completada → [android/README.md](android/README.md)
- **Etapa 11:** Sync Android (manifiesto, SHA-256, offline) — completada
- **Etapa 12:** Reproductor Media3 (loop local) — completada
- **Etapa 13:** Monitoreo ampliado (dashboard, alertas, heartbeats) — completada
- **Etapa 14:** Urgentes en Android (UI fullscreen) — completada
- **Etapa 15:** Pruebas, seguridad, optimización — completada → [docs/production.md](docs/production.md)

### Probar API dispositivo (Etapa 8)

Tras vincular pantalla y obtener token en `activate`:

```powershell
$token = "TOKEN_DE_64_CARACTERES"
$headers = @{ Authorization = "Bearer $token" }

Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/device/config" -Headers $headers
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/device/playlist" -Headers $headers
Invoke-RestMethod -Method Post -Uri "http://127.0.0.1:8000/api/v1/device/heartbeat" -Headers $headers `
  -ContentType "application/json" -Body '{"status":"online","app_version":"1.0.0"}'
```

Documentación completa: [docs/api.md](docs/api.md) · [Producción](docs/production.md) · [Seguridad](docs/security.md)

### Calidad (Etapa 15)

```powershell
php artisan test
php artisan televisor:prune-heartbeats --days=30
```

### App Android TV (Etapa 10)

Proyecto Gradle en **`android/`**. Abrir con Android Studio, servir Laravel accesible en la red local y vincular desde la app (código 6 dígitos) con el panel.

### Mensajes urgentes (Etapa 9)

- Panel: **Mensajes urgentes** (operadores y admin pueden crear/activar; solo admin elimina).
- Con un mensaje activo, `GET /api/v1/device/playlist` devuelve `source: "urgent"` y `urgent_message` (sin ítems de playlist).
- Expiración: comando `php artisan urgent-messages:expire` (programado cada 5 min con el scheduler de Laravel).

### Biblioteca multimedia

- Rutas: **Contenido → Videos / Imágenes**
- Almacenamiento privado: `storage/app/private/media/{uuid}/`
- Límite de tamaño: setting `media.max_upload_mb` (default 512 MB)
- Metadatos de video: requiere `ffprobe` en PATH (opcional; sin él el archivo queda listo sin duración/resolución)
- Ajuste PHP recomendado en Laragon: `upload_max_filesize` y `post_max_size` acordes al límite municipal

### Probar vinculación (Etapa 3)

```bash
# 1. Simular televisor (generar código)
curl -s -X POST http://127.0.0.1:8000/api/v1/device/pair ^
  -H "Content-Type: application/json" ^
  -d "{\"screen_uuid\":\"550e8400-e29b-41d4-a716-446655440000\"}"

# 2. En el panel: Pantallas → Vincular pantalla → ingresar código → completar datos

# 3. Simular entrega de token al dispositivo
curl -s -X POST http://127.0.0.1:8000/api/v1/device/activate ^
  -H "Content-Type: application/json" ^
  -d "{\"screen_uuid\":\"550e8400-e29b-41d4-a716-446655440000\"}"
```

## Licencia

Uso interno municipal (definir según política de la institución).

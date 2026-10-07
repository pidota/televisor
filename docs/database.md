# Base de datos

Motor recomendado: **MySQL 8**. Desarrollo local puede usar SQLite (Laravel por defecto).

## Convenciones

- Claves primarias `bigint` autoincrementales.
- UUIDs (`char(36)`) para identificadores expuestos a dispositivos y descargas.
- Timestamps en UTC.
- Soft delete en `screens`, `media_assets`, `playlists`.

## Tablas

### Autenticación y configuración

| Tabla | Descripción |
|-------|-------------|
| `users` | Funcionarios del panel (Laravel) |
| `roles` | `admin`, `operator`, `viewer` |
| `role_user` | Pivot usuario ↔ rol |
| `settings` | Configuración clave-valor (timezone, heartbeat, uploads) |

### Pantallas

| Tabla | Descripción |
|-------|-------------|
| `screens` | Dispositivo / pantalla digital |
| `pairing_codes` | Códigos temporales de vinculación |
| `screen_groups` | Agrupación lógica (ej. edificios) |
| `screen_group_screen` | Pivot grupo ↔ pantalla |

**`screens.status`:** `pending`, `active`, `disabled`, `revoked`.

Campos de telemetría: `last_seen_at`, `current_playlist_id`, `current_media_asset_id`, `manifest_version`.

### Multimedia y playlists

| Tabla | Descripción |
|-------|-------------|
| `media_assets` | Videos e imágenes |
| `playlists` | Colecciones ordenadas |
| `playlist_items` | Orden, duración opcional (imágenes) |
| `playlist_assignments` | Asignación a `screen` o `screen_group` |

**`media_assets.type`:** `video`, `image`.  
**`media_assets.status`:** `uploading`, `processing`, `ready`, `failed`, `archived`.

Archivos en disco `local` (`storage/app/private`) bajo `media/{uuid}/original.{ext}`. Campo `checksum_sha256` tras subida. Descarga/preview en panel vía ruta autenticada `media.preview` (Etapa 8: URLs firmadas para dispositivos).

### Programación

| Tabla | Descripción |
|-------|-------------|
| `schedules` | Campaña con rango de fechas y playlist |
| `schedule_targets` | Pantalla o grupo destino |
| `schedule_time_rules` | Día ISO (1–7) + franja horaria; vacío = todo el día |

### Mensajes urgentes

| Tabla | Descripción |
|-------|-------------|
| `urgent_messages` | Mensaje prioritario con vigencia |
| `urgent_message_targets` | Pantallas específicas (si no aplica a todas) |

### Dispositivo y auditoría

| Tabla | Descripción |
|-------|-------------|
| `device_heartbeats` | Histórico de latidos (retención por job futuro) |
| `audit_logs` | Acciones administrativas |

## Relaciones polimórficas

Valores en `assignable_type` / `target_type`:

- `screen` → `App\Models\Screen`
- `screen_group` → `App\Models\ScreenGroup`

Mapa registrado en `AppServiceProvider`.

## Índices relevantes

- `screens`: `uuid` (unique), `status`, `last_seen_at`
- `pairing_codes`: `code` (unique), `(code, expires_at)`
- `media_assets`: `(status, type)`, `checksum_sha256`
- `playlist_items`: `(playlist_id, sort_order)` unique
- `schedules`: `(starts_at, ends_at, is_active)`
- `urgent_messages`: `(starts_at, ends_at, status)`

## Restricciones FK

- `playlist_items.media_asset_id` → `restrictOnDelete` (no borrar media en uso).
- `schedules.playlist_id` → `restrictOnDelete`.
- Revocación de pantalla: lógica de aplicación (token nulo + status `revoked`).

## Seed inicial

`php artisan db:seed` ejecuta roles y settings por defecto (`SystemDefaultsSeeder`).

## Diagrama ER

Ver `docs/architecture.md` y el diagrama Mermaid del documento de diseño aprobado en la Etapa 1.

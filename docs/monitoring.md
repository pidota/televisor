# Monitoreo de pantallas (Etapa 13)

## Dashboard

Ruta: **Dashboard** (`/dashboard`).

- Tabla operativa con estado de conexión, último contacto, reproducción reportada, sync de manifiesto, versión de app y espacio libre en el dispositivo.
- Filtros: **Todas**, **Online**, **Offline**, **Con alertas**.
- Bloque **Atención requerida** con pantallas que tienen alertas activas.
- Actualización automática cada **60 segundos** (desactivable).

## Alertas

| Código | Significado |
|--------|-------------|
| `offline` | Pantalla activa sin heartbeat dentro del umbral (`device.offline_threshold_seconds`) |
| `manifest_outdated` | Online pero el último heartbeat reporta `manifest_version` menor que la del servidor |
| `storage_low` | Menos del **10%** de almacenamiento libre reportado |
| `never_connected` | Pantalla activa sin `last_seen_at` |

## Ficha de pantalla

- Badge de sync de manifiesto (reportado vs servidor).
- Tabla de **últimos 25 heartbeats** (manifiesto, playlist, media, disco, app).

## Datos de telemetría

Los dispositivos envían `POST /api/v1/device/heartbeat` y `POST /api/v1/device/playback-status`. El panel refleja el último estado en `screens` y el histórico en `device_heartbeats`.

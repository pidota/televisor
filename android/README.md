# Televisor — App Android TV

## Etapas en la app

| Etapa | Funcionalidad |
|-------|----------------|
| 10 | Vinculación (pair/activate), token cifrado, `config` / `heartbeat` |
| 11 | Sync manifiesto, descarga media, SHA-256, WorkManager periódico |
| 12 | Reproductor Media3 (loop local, imágenes) |
| 14 | Mensajes urgentes — pantalla institucional fullscreen |

## Requisitos

- Android Studio Ladybug o superior
- JDK 17
- Backend Laravel (`php artisan serve --host=0.0.0.0 --port=8000`)

## URL del servidor

| Entorno | URL base API |
|---------|----------------|
| Emulador | `http://10.0.2.2:8000/api/v1/` (debug) |
| Dispositivo físico | `http://IP_PC:8000/api/v1/` |

**Servidor API** en la pantalla de vinculación guarda la URL en el dispositivo.

## Sincronización (Etapa 11)

1. Tras vincular, la app descarga el manifiesto (`GET /device/playlist`).
2. Compara `version` con la copia local (`files/televisor/manifest.json`).
3. Descarga archivos faltantes o con checksum distinto vía URL del manifiesto.
4. Verifica **SHA-256** antes de marcar cada ítem como listo.
5. Elimina archivos locales que ya no están en el manifiesto activo.
6. **WorkManager** reprograma sync según `manifest_poll_seconds` del servidor (mín. 30 s en app).
7. Mensajes **urgentes**: guarda metadatos locales sin descargar media.

Almacenamiento:

- Manifiesto: `files/televisor/manifest.json`
- Media: `files/televisor/media/{uuid}.{ext}`

## Vinculación (Etapa 10)

1. `POST /device/pair` → código 6 dígitos.
2. Panel **Pantallas → Vincular**.
3. Polling `POST /device/activate` → token en EncryptedSharedPreferences.

## Build

```bash
cd android
./gradlew :app:assembleDebug
```

## Reproducción (Etapa 12)

Tras vincular, la app abre **PlayerActivity** a pantalla completa:

- **Videos:** Media3 ExoPlayer desde archivos locales.
- **Imágenes:** duración según manifiesto (default 10 s).
- **Loop** continuo de la playlist sincronizada.
- **Mensaje urgente (Etapa 14):** pantalla azul institucional, franja roja, título grande (marquesina si es largo), cuerpo desplazable, prioridad, vigencia en hora municipal y reloj en vivo. Polling cada 30 s mientras dura el urgente. Telemetría `playback-status` con `status: urgent`.
- **Menú / Info** en el control remoto → pantalla de diagnóstico (sync manual).

Telemetría: `POST /device/playback-status` al cambiar cada ítem.

## Restablecer

**Restablecer vinculación** cancela WorkManager, borra token, manifiesto y carpeta `media/`.

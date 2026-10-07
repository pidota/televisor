# Aplicación Android TV

Código en **`android/`** (monorepo con el backend Laravel).

| Etapa | Alcance | Estado |
|-------|---------|--------|
| 10 | Vinculación, token seguro, `config` / `playlist` / `heartbeat` | Completada |
| 11 | Sync local (manifiesto, checksum, WorkManager) | Completada |
| 12 | Reproductor Media3 (loop offline) | Completada |
| 14 | Mensajes urgentes en UI (fullscreen institucional) | Completada |

## Stack

- Kotlin, Android TV (`LEANBACK_LAUNCHER`)
- Retrofit + Moshi + OkHttp
- EncryptedSharedPreferences (token)
- WorkManager (sync periódico del manifiesto)
- Media3 ExoPlayer (videos + imágenes temporizadas)

## Flujo de vinculación (Etapa 10)

1. App persiste un `screen_uuid` (UUID v4) y llama `POST /api/v1/device/pair`.
2. Muestra código de 6 dígitos en pantalla completa.
3. Operador completa vinculación en el panel (**Pantallas → Vincular**).
4. Polling `POST /api/v1/device/activate` hasta `status: activated` y guardado del `device_token`.
5. Pantalla vinculada: `GET /device/config`, consulta de manifiesto y heartbeat inicial.

Instrucciones de build y URL del servidor: [android/README.md](../android/README.md).

## Mensajes urgentes en TV (Etapa 14)

Cuando el manifiesto resuelve `source: urgent`, el reproductor oculta playlist y muestra `view_urgent_fullscreen.xml`:

- Cabecera **Mensaje urgente municipal** y badge de prioridad.
- Título (marquesina si es muy largo) y cuerpo con scroll para textos extensos.
- **Vigente hasta** en zona horaria del servidor (`config.timezone`, p. ej. `America/Santiago`).
- Reloj en vivo en pantalla.
- Fade al entrar/salir del modo urgente; polling acelerado (30 s) hasta volver a playlist.
- `POST /device/playback-status` con `{ "status": "urgent", "content_id": <id> }`.

# API REST — Dispositivos Android TV

Prefijo base: **`/api/v1`**

Autenticación de dispositivo (excepto pair/activate): header

```http
Authorization: Bearer {device_token}
```

---

## Vinculación (público)

### POST `/device/pair`

Genera código temporal de 6 dígitos.

**Body:** `{ "screen_uuid": "uuid-v4" }`

**Respuesta 200:** `{ "data": { "code", "expires_at", "screen_uuid" } }`

### POST `/device/activate`

Polling hasta que el admin complete la vinculación en el panel.

**Body:** `{ "screen_uuid": "uuid-v4" }`

**Respuesta `data.status`:** `waiting` | `pending` | `activated` (+ `device_token` una vez) | `already_activated` | `inactive` | `invalid`

Rate limit: **30/min** por IP (`api-device-pair`).

---

## Rutas autenticadas

Rate limit: **180/min** por pantalla (`api-device`).

### GET `/device/config`

Configuración de sincronización y datos básicos de la pantalla.

```json
{
  "data": {
    "screen": {
      "uuid": "...",
      "name": "Hall Municipalidad",
      "manifest_version": 3
    },
    "sync": {
      "heartbeat_interval_seconds": 60,
      "manifest_poll_seconds": 120
    },
    "server_time": "2026-09-28T18:00:00+00:00",
    "timezone": "America/Santiago"
  }
}
```

### GET `/device/playlist`

Manifiesto de contenido resuelto (programación > asignación). Usar `version` para sync incremental.

```json
{
  "data": {
    "version": 3,
    "generated_at": "2026-09-28T18:00:00+00:00",
    "source": "schedule",
    "playlist": {
      "id": 5,
      "name": "Institucional",
      "revision": 12
    },
    "items": [
      {
        "id": 81,
        "uuid": "...",
        "item_id": 10,
        "type": "video",
        "name": "Bienvenida",
        "url": "https://.../api/v1/device/media/{uuid}",
        "checksum": "sha256...",
        "size": 45800000,
        "duration": 120,
        "mime_type": "video/mp4"
      }
    ]
  }
}
```

Si no hay playlist: `playlist: null`, `items: []`.

### Mensajes urgentes

**Pantalla completa** (`layout: text_fullscreen`): `source` es `urgent`, sin ítems de playlist.

**Cinta inferior** (`layout: text_ticker`): la playlist sigue activa (`source` assignment/schedule) y el manifiesto incluye `urgent_message` junto con `items`.

Cuando hay un mensaje fullscreen activo para la pantalla, `source` es `urgent` y el manifiesto incluye:

```json
{
  "source": "urgent",
  "urgent_message": {
    "id": 1,
    "title": "Corte de agua potable",
    "body": "Sector La Orilla...",
    "layout": "text_fullscreen",
    "starts_at": "...",
    "ends_at": "...",
    "priority": 10
  },
  "playlist": null,
  "items": []
}
```

Al cancelar o expirar el mensaje, la siguiente consulta devolverá de nuevo la playlist programada/asignada.

### GET `/device/media/{uuid}`

Descarga binaria del archivo. Solo media incluido en el manifiesto actual de la pantalla.

Headers: `Content-Type`, `Content-Length`.

### POST `/device/heartbeat`

Telemetría periódica (recomendado cada **60 s** según config).

**Body (JSON, campos opcionales):**

```json
{
  "status": "online",
  "playlist_id": 8,
  "content_id": 45,
  "manifest_version": 3,
  "storage_free": 4500000000,
  "storage_total": 64000000000,
  "app_version": "1.0.0",
  "device_model": "Xiaomi TV",
  "android_version": "11",
  "resolution": "1920x1080"
}
```

`content_id` es alias de `media_asset_id`.

**Respuesta:**

```json
{
  "data": {
    "accepted": true,
    "manifest_version": 3,
    "server_time": "..."
  }
}
```

El servidor actualiza `last_seen_at` (online/offline en panel según umbral `device.offline_threshold_seconds`).

### POST `/device/playback-status`

Actualización ligera de reproducción (sin histórico completo).

**Body:** `{ "playlist_id": 8, "content_id": 45 }`

---

## Códigos HTTP

| Código | Situación |
|--------|-----------|
| 401 | Sin token o token inválido |
| 403 | Pantalla revocada/deshabilitada o descarga no autorizada |
| 404 | Media inexistente en storage |
| 422 | Validación |
| 429 | Rate limit |

---

## Flujo recomendado (app Android)

1. `POST /device/pair` → mostrar código  
2. Polling `POST /device/activate` → guardar token  
3. `GET /device/config`  
4. Loop: `GET /device/playlist` (comparar `version`) → descargar URLs faltantes verificando `checksum`  
5. `POST /device/heartbeat` cada N segundos  
6. `POST /device/playback-status` al cambiar ítem  

Los mensajes urgentes activos tienen prioridad sobre programación y asignación (ver sección anterior).

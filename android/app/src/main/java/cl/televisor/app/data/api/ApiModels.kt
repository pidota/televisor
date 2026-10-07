package cl.televisor.app.data.api

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

@JsonClass(generateAdapter = true)
data class ApiEnvelope<T>(
    @Json(name = "data") val data: T,
)

@JsonClass(generateAdapter = true)
data class ScreenUuidBody(
    @Json(name = "screen_uuid") val screenUuid: String,
)

@JsonClass(generateAdapter = true)
data class PairResponseData(
    val code: String,
    @Json(name = "expires_at") val expiresAt: String,
    @Json(name = "screen_uuid") val screenUuid: String,
)

@JsonClass(generateAdapter = true)
data class ActivateResponseData(
    val status: String,
    @Json(name = "device_token") val deviceToken: String? = null,
)

@JsonClass(generateAdapter = true)
data class ConfigResponseData(
    val screen: ConfigScreen,
    val sync: ConfigSync,
    @Json(name = "server_time") val serverTime: String,
    val timezone: String,
)

@JsonClass(generateAdapter = true)
data class ConfigScreen(
    val uuid: String,
    val name: String?,
    @Json(name = "manifest_version") val manifestVersion: Int,
)

@JsonClass(generateAdapter = true)
data class ConfigSync(
    @Json(name = "heartbeat_interval_seconds") val heartbeatIntervalSeconds: Int,
    @Json(name = "manifest_poll_seconds") val manifestPollSeconds: Int,
)

@JsonClass(generateAdapter = true)
data class HeartbeatRequest(
    val status: String = "online",
    @Json(name = "app_version") val appVersion: String,
    @Json(name = "device_model") val deviceModel: String? = null,
    @Json(name = "android_version") val androidVersion: String? = null,
    @Json(name = "manifest_version") val manifestVersion: Int? = null,
    @Json(name = "storage_free") val storageFree: Long? = null,
    @Json(name = "storage_total") val storageTotal: Long? = null,
)

@JsonClass(generateAdapter = true)
data class HeartbeatResponseData(
    val accepted: Boolean,
    @Json(name = "manifest_version") val manifestVersion: Int,
    @Json(name = "server_time") val serverTime: String,
)

@JsonClass(generateAdapter = true)
data class ManifestResponseData(
    val version: Int,
    @Json(name = "generated_at") val generatedAt: String,
    val source: String,
    @Json(name = "urgent_message") val urgentMessage: UrgentMessageData? = null,
    val playlist: ManifestPlaylist? = null,
    val items: List<ManifestMediaItem> = emptyList(),
)

@JsonClass(generateAdapter = true)
data class ManifestPlaylist(
    val id: Int,
    val name: String,
    val revision: Int,
)

@JsonClass(generateAdapter = true)
data class UrgentMessageData(
    val id: Int,
    val title: String,
    val body: String,
    val layout: String,
    @Json(name = "starts_at") val startsAt: String,
    @Json(name = "ends_at") val endsAt: String,
    val priority: Int,
)

@JsonClass(generateAdapter = true)
data class PlaybackAck(
    val accepted: Boolean = true,
)

@JsonClass(generateAdapter = true)
data class PlaybackStatusRequest(
    @Json(name = "playlist_id") val playlistId: Int? = null,
    @Json(name = "media_asset_id") val mediaAssetId: Int? = null,
    @Json(name = "content_id") val contentId: Int? = null,
    val status: String = "playing",
)

@JsonClass(generateAdapter = true)
data class ManifestMediaItem(
    val id: Int,
    val uuid: String,
    @Json(name = "item_id") val itemId: Int,
    val type: String,
    val name: String,
    val url: String,
    val checksum: String,
    val size: Long,
    val duration: Int,
    @Json(name = "mime_type") val mimeType: String,
)

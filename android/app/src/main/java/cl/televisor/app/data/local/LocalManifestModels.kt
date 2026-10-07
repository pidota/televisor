package cl.televisor.app.data.local

import cl.televisor.app.data.api.ManifestMediaItem
import cl.televisor.app.data.api.ManifestResponseData
import cl.televisor.app.data.api.UrgentMessageData
import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

@JsonClass(generateAdapter = true)
data class LocalManifestSnapshot(
    val version: Int,
    val generatedAt: String,
    val source: String,
    val playlistId: Int? = null,
    val playlistName: String? = null,
    val playlistRevision: Int? = null,
    val urgentMessage: UrgentMessageData? = null,
    val items: List<LocalMediaEntry> = emptyList(),
    val syncedAtEpochMs: Long = System.currentTimeMillis(),
)

@JsonClass(generateAdapter = true)
data class LocalMediaEntry(
    val uuid: String,
    @Json(name = "media_asset_id") val mediaAssetId: Int = 0,
    val itemId: Int,
    val type: String,
    val name: String,
    val checksum: String,
    val size: Long,
    val duration: Int,
    val mimeType: String,
    val relativePath: String,
    val state: String = STATE_READY,
) {
    companion object {
        const val STATE_READY = "ready"
        const val STATE_FAILED = "failed"

        fun fromRemote(item: ManifestMediaItem, relativePath: String): LocalMediaEntry {
            return LocalMediaEntry(
                uuid = item.uuid,
                mediaAssetId = item.id,
                itemId = item.itemId,
                type = item.type,
                name = item.name,
                checksum = item.checksum,
                size = item.size,
                duration = item.duration,
                mimeType = item.mimeType,
                relativePath = relativePath,
                state = STATE_READY,
            )
        }
    }
}

fun ManifestResponseData.toLocalSnapshot(items: List<LocalMediaEntry>): LocalManifestSnapshot {
    return LocalManifestSnapshot(
        version = version,
        generatedAt = generatedAt,
        source = source,
        playlistId = playlist?.id,
        playlistName = playlist?.name,
        playlistRevision = playlist?.revision,
        urgentMessage = urgentMessage,
        items = items,
        syncedAtEpochMs = System.currentTimeMillis(),
    )
}

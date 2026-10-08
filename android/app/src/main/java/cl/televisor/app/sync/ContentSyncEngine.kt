package cl.televisor.app.sync

import android.os.Build
import cl.televisor.app.BuildConfig
import cl.televisor.app.data.DeviceSession
import cl.televisor.app.data.api.ApiClient
import cl.televisor.app.data.api.HeartbeatRequest
import cl.televisor.app.data.api.ManifestMediaItem
import cl.televisor.app.data.api.ManifestResponseData
import cl.televisor.app.data.local.LocalManifestStore
import cl.televisor.app.data.local.LocalMediaEntry
import cl.televisor.app.data.local.MediaFileStore
import cl.televisor.app.data.local.toLocalSnapshot
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.sync.Mutex
import kotlinx.coroutines.sync.withLock
import kotlinx.coroutines.withContext

class ContentSyncEngine(
    private val session: DeviceSession,
    private val apiClient: ApiClient,
    private val manifestStore: LocalManifestStore,
    private val mediaStore: MediaFileStore,
) {

    private val syncMutex = Mutex()

    suspend fun sync(force: Boolean = false): SyncResult = withContext(Dispatchers.IO) {
        syncMutex.withLock {
            syncUnlocked(force)
        }
    }

    /** Heartbeat ligero para mantener la pantalla online aunque haya una descarga larga en curso. */
    suspend fun sendHeartbeatOnly(): Boolean = withContext(Dispatchers.IO) {
        if (!session.hasDeviceToken()) {
            return@withContext false
        }

        val version = manifestStore.load()?.version
            ?: session.getLastSyncedManifestVersion().takeIf { it >= 0 }
            ?: 0

        runCatching {
            sendHeartbeat(version)
            true
        }.getOrDefault(false)
    }

    private suspend fun syncUnlocked(force: Boolean): SyncResult {
        if (!session.hasDeviceToken()) {
            return SyncResult.Failure("Sin token de dispositivo")
        }

        val configResult = runCatching { apiClient.api().config().data }
        val config = configResult.getOrElse {
            return SyncResult.Failure(it.message ?: "Error de config")
        }

        session.saveSyncIntervals(
            config.sync.heartbeatIntervalSeconds,
            config.sync.manifestPollSeconds,
        )
        session.saveAppTimezone(config.timezone)

        val remoteResult = runCatching { apiClient.api().playlist().data }
        val remote = remoteResult.getOrElse {
            return SyncResult.Failure(it.message ?: "Error de manifiesto")
        }

        sendHeartbeat(remote.version)

        val cached = manifestStore.load()
        if (!force && cached != null && cached.version == remote.version && cached.source == remote.source) {
            val allReady = remote.items.all { item ->
                mediaStore.findVerified(item.uuid, item.checksum) != null
            }
            if (allReady && remote.source != "urgent" && remote.source != "live") {
                return SyncResult.Skipped("Manifiesto v${remote.version} ya sincronizado")
            }
        }

        val syncOutcome = when {
            remote.source == "urgent" -> syncUrgent(remote)
            remote.source == "live" -> syncLive(remote)
            else -> syncPlaylist(remote)
        }

        sendHeartbeat(remote.version)
        return syncOutcome
    }

    fun loadLocalManifest() = manifestStore.load()

    fun clearLocal() {
        manifestStore.clear()
        mediaStore.clearAll()
        session.clearSyncMetadata()
    }

    private fun syncLive(remote: ManifestResponseData): SyncResult {
        val snapshot = remote.toLocalSnapshot(emptyList())
        manifestStore.save(snapshot)
        session.setLastSyncedManifestVersion(remote.version)
        return SyncResult.Success(
            snapshot = snapshot,
            downloaded = 0,
            skipped = 0,
            failed = 0,
            pruned = 0,
        )
    }

    private fun syncUrgent(remote: ManifestResponseData): SyncResult {
        val snapshot = remote.toLocalSnapshot(emptyList())
        manifestStore.save(snapshot)
        session.setLastSyncedManifestVersion(remote.version)
        return SyncResult.Success(
            snapshot = snapshot,
            downloaded = 0,
            skipped = 0,
            failed = 0,
            pruned = 0,
        )
    }

    private suspend fun syncPlaylist(remote: ManifestResponseData): SyncResult {
        var downloaded = 0
        var skipped = 0
        var failed = 0
        var pruned = 0

        if (remote.items.isNotEmpty()) {
            val allowed = remote.items.map { it.uuid }.toSet()
            val before = mediaStore.mediaRootPath().listFiles()?.size ?: 0
            mediaStore.pruneExcept(allowed)
            val after = mediaStore.mediaRootPath().listFiles()?.size ?: 0
            pruned = (before - after).coerceAtLeast(0)
        }

        val client = apiClient.httpClientForDownloads()
        val localItems = mutableListOf<LocalMediaEntry>()

        for (item in remote.items) {
            when (processItem(item, client, localItems)) {
                ItemOutcome.DOWNLOADED -> downloaded++
                ItemOutcome.SKIPPED -> skipped++
                ItemOutcome.FAILED -> failed++
            }
        }

        val snapshot = remote.toLocalSnapshot(localItems)
        manifestStore.save(snapshot)
        session.setLastSyncedManifestVersion(remote.version)

        return SyncResult.Success(
            snapshot = snapshot,
            downloaded = downloaded,
            skipped = skipped,
            failed = failed,
            pruned = pruned,
        )
    }

    private suspend fun processItem(
        item: ManifestMediaItem,
        client: okhttp3.OkHttpClient,
        sink: MutableList<LocalMediaEntry>,
    ): ItemOutcome {
        val existing = mediaStore.findVerified(item.uuid, item.checksum)
        if (existing != null) {
            sink.add(
                LocalMediaEntry.fromRemote(
                    item,
                    mediaStore.relativePathFor(item.uuid, item.mimeType),
                ),
            )
            return ItemOutcome.SKIPPED
        }

        val download = mediaStore.downloadAndVerify(item, client)
        return download.fold(
            onSuccess = {
                sink.add(
                    LocalMediaEntry.fromRemote(
                        item,
                        mediaStore.relativePathFor(item.uuid, item.mimeType),
                    ),
                )
                ItemOutcome.DOWNLOADED
            },
            onFailure = {
                ItemOutcome.FAILED
            },
        )
    }

    private suspend fun sendHeartbeat(manifestVersion: Int) {
        val (used, total) = mediaStore.storageStats()
        runCatching {
            apiClient.api().heartbeat(
                HeartbeatRequest(
                    appVersion = BuildConfig.VERSION_NAME,
                    deviceModel = Build.MODEL,
                    androidVersion = Build.VERSION.RELEASE,
                    manifestVersion = manifestVersion,
                    storageFree = (total - used).coerceAtLeast(0),
                    storageTotal = total,
                ),
            )
        }
    }

    private enum class ItemOutcome {
        DOWNLOADED,
        SKIPPED,
        FAILED,
    }
}

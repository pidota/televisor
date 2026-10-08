package cl.televisor.app.data

import android.content.Context
import cl.televisor.app.sync.ContentSyncEngine
import cl.televisor.app.sync.SyncResult
import cl.televisor.app.sync.SyncScheduler
import cl.televisor.app.data.api.ActivateResponseData
import cl.televisor.app.data.api.ApiClient
import cl.televisor.app.data.api.ConfigResponseData
import cl.televisor.app.data.api.ManifestResponseData
import cl.televisor.app.data.api.PairResponseData
import cl.televisor.app.data.api.ScreenUuidBody
import cl.televisor.app.data.local.LocalManifestSnapshot
import retrofit2.HttpException

class DeviceRepository(
    private val session: DeviceSession,
    private val apiClient: ApiClient,
    private val syncEngine: ContentSyncEngine,
    private val appContext: Context,
) {

    fun screenUuid(): String = session.getOrCreateScreenUuid()

    suspend fun requestPairing(): Result<PairResponseData> = runCatching {
        val uuid = screenUuid()
        apiClient.api().pair(ScreenUuidBody(uuid)).data
    }

    suspend fun pollActivation(): Result<ActivateResponseData> = runCatching {
        val uuid = screenUuid()
        apiClient.api().activate(ScreenUuidBody(uuid)).data
    }.recoverCatching { error ->
        if (error is HttpException) {
            when (error.code()) {
                403 -> ActivateResponseData(status = "inactive")
                422 -> ActivateResponseData(status = "invalid")
                else -> throw error
            }
        } else {
            throw error
        }
    }

    fun persistToken(token: String) {
        session.saveDeviceToken(token)
    }

    fun isLinked(): Boolean = session.hasDeviceToken()

    fun resetLink() {
        SyncScheduler.cancel(appContext)
        syncEngine.clearLocal()
        session.clearPairingState()
        apiClient.invalidate()
    }

    suspend fun fetchConfig(): Result<ConfigResponseData> = runCatching {
        apiClient.api().config().data
    }

    suspend fun fetchManifest(): Result<ManifestResponseData> = runCatching {
        apiClient.api().playlist().data
    }

    suspend fun syncContent(force: Boolean = false): SyncResult {
        return syncEngine.sync(force)
    }

    fun localManifest(): LocalManifestSnapshot? = syncEngine.loadLocalManifest()

    fun startBackgroundSync() {
        SyncScheduler.startAfterLink(appContext, session)
    }

    fun manifestPollSeconds(): Int = session.getManifestPollSeconds()

    fun heartbeatIntervalSeconds(): Int = session.getHeartbeatIntervalSeconds()

    suspend fun sendHeartbeatOnly(): Boolean = syncEngine.sendHeartbeatOnly()

    fun appTimezone(): String? = session.getAppTimezone()

    suspend fun reportUrgentPlayback(urgentMessageId: Int) {
        runCatching {
            apiClient.api().playbackStatus(
                cl.televisor.app.data.api.PlaybackStatusRequest(
                    status = "urgent",
                    contentId = urgentMessageId,
                ),
            )
        }
    }

    suspend fun reportPlayback(
        playlistId: Int?,
        mediaAssetId: Int,
        status: String = "playing",
    ) {
        runCatching {
            apiClient.api().playbackStatus(
                cl.televisor.app.data.api.PlaybackStatusRequest(
                    playlistId = playlistId,
                    mediaAssetId = mediaAssetId,
                    contentId = mediaAssetId,
                    status = status,
                ),
            )
        }
    }
}

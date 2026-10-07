package cl.televisor.app

import android.app.Application
import cl.televisor.app.data.DeviceRepository
import cl.televisor.app.data.DeviceSession
import cl.televisor.app.data.api.ApiClient
import cl.televisor.app.data.local.LocalManifestStore
import cl.televisor.app.data.local.MediaFileStore
import cl.televisor.app.sync.ContentSyncEngine

class TelevisorApplication : Application() {

    lateinit var session: DeviceSession
        private set

    lateinit var apiClient: ApiClient
        private set

    lateinit var localManifestStore: LocalManifestStore
        private set

    lateinit var mediaFileStore: MediaFileStore
        private set

    lateinit var contentSyncEngine: ContentSyncEngine
        private set

    lateinit var deviceRepository: DeviceRepository
        private set

    override fun onCreate() {
        super.onCreate()
        session = DeviceSession(this)
        apiClient = ApiClient(session)
        localManifestStore = LocalManifestStore(this, apiClient.moshi)
        mediaFileStore = MediaFileStore(this)
        contentSyncEngine = ContentSyncEngine(
            session,
            apiClient,
            localManifestStore,
            mediaFileStore,
        )
        deviceRepository = DeviceRepository(
            session,
            apiClient,
            contentSyncEngine,
            applicationContext,
        )
    }
}

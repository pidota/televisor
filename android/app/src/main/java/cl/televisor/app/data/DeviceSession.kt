package cl.televisor.app.data

import android.content.Context
import android.content.SharedPreferences
import androidx.security.crypto.EncryptedSharedPreferences
import androidx.security.crypto.MasterKey
import cl.televisor.app.BuildConfig
import java.util.UUID

class DeviceSession(context: Context) {

    private val appContext = context.applicationContext

    private val generalPrefs: SharedPreferences =
        appContext.getSharedPreferences(PREFS_GENERAL, Context.MODE_PRIVATE)

    private val securePrefs: SharedPreferences by lazy {
        val masterKey = MasterKey.Builder(appContext)
            .setKeyScheme(MasterKey.KeyScheme.AES256_GCM)
            .build()

        EncryptedSharedPreferences.create(
            appContext,
            PREFS_SECURE,
            masterKey,
            EncryptedSharedPreferences.PrefKeyEncryptionScheme.AES256_SIV,
            EncryptedSharedPreferences.PrefValueEncryptionScheme.AES256_GCM,
        )
    }

    fun getOrCreateScreenUuid(): String {
        val existing = generalPrefs.getString(KEY_SCREEN_UUID, null)
        if (existing != null) {
            return existing
        }

        val uuid = UUID.randomUUID().toString()
        generalPrefs.edit().putString(KEY_SCREEN_UUID, uuid).apply()
        return uuid
    }

    fun getDeviceToken(): String? = securePrefs.getString(KEY_DEVICE_TOKEN, null)

    fun saveDeviceToken(token: String) {
        securePrefs.edit().putString(KEY_DEVICE_TOKEN, token).apply()
    }

    fun hasDeviceToken(): Boolean = !getDeviceToken().isNullOrBlank()

    fun clearDeviceToken() {
        securePrefs.edit().remove(KEY_DEVICE_TOKEN).apply()
    }

    fun clearPairingState() {
        clearDeviceToken()
        clearSyncMetadata()
    }

    fun saveSyncIntervals(heartbeatSeconds: Int, pollSeconds: Int) {
        generalPrefs.edit()
            .putInt(KEY_HEARTBEAT_SECONDS, heartbeatSeconds)
            .putInt(KEY_POLL_SECONDS, pollSeconds)
            .apply()
    }

    fun saveAppTimezone(timezone: String) {
        generalPrefs.edit().putString(KEY_APP_TIMEZONE, timezone).apply()
    }

    fun getAppTimezone(): String? = generalPrefs.getString(KEY_APP_TIMEZONE, null)

    fun getManifestPollSeconds(): Int =
        generalPrefs.getInt(KEY_POLL_SECONDS, DEFAULT_POLL_SECONDS)

    fun getHeartbeatIntervalSeconds(): Int =
        generalPrefs.getInt(KEY_HEARTBEAT_SECONDS, DEFAULT_HEARTBEAT_SECONDS)

    fun setLastSyncedManifestVersion(version: Int) {
        generalPrefs.edit()
            .putInt(KEY_LAST_SYNCED_VERSION, version)
            .putLong(KEY_LAST_SYNC_AT, System.currentTimeMillis())
            .apply()
    }

    fun getLastSyncedManifestVersion(): Int =
        generalPrefs.getInt(KEY_LAST_SYNCED_VERSION, -1)

    fun getLastSyncAtEpochMs(): Long =
        generalPrefs.getLong(KEY_LAST_SYNC_AT, 0L)

    fun clearSyncMetadata() {
        generalPrefs.edit()
            .remove(KEY_HEARTBEAT_SECONDS)
            .remove(KEY_POLL_SECONDS)
            .remove(KEY_LAST_SYNCED_VERSION)
            .remove(KEY_LAST_SYNC_AT)
            .remove(KEY_APP_TIMEZONE)
            .apply()
    }

    fun getApiBaseUrl(): String {
        return generalPrefs.getString(KEY_API_BASE_URL, null)
            ?: BuildConfig.API_BASE_URL
    }

    fun setApiBaseUrl(url: String) {
        val normalized = if (url.endsWith("/")) url else "$url/"
        generalPrefs.edit().putString(KEY_API_BASE_URL, normalized).apply()
    }

    companion object {
        private const val PREFS_GENERAL = "televisor_general"
        private const val PREFS_SECURE = "televisor_secure"
        private const val KEY_SCREEN_UUID = "screen_uuid"
        private const val KEY_DEVICE_TOKEN = "device_token"
        private const val KEY_API_BASE_URL = "api_base_url"
        private const val KEY_HEARTBEAT_SECONDS = "heartbeat_seconds"
        private const val KEY_POLL_SECONDS = "poll_seconds"
        private const val KEY_LAST_SYNCED_VERSION = "last_synced_manifest_version"
        private const val KEY_LAST_SYNC_AT = "last_sync_at"
        private const val KEY_APP_TIMEZONE = "app_timezone"
        private const val DEFAULT_POLL_SECONDS = 120
        private const val DEFAULT_HEARTBEAT_SECONDS = 60
    }
}

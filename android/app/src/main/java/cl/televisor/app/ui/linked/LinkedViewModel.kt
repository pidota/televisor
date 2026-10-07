package cl.televisor.app.ui.linked

import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import cl.televisor.app.data.DeviceRepository
import cl.televisor.app.sync.SyncResult
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

class LinkedViewModel(
    private val repository: DeviceRepository,
) : ViewModel() {

    private val _uiState = MutableStateFlow<LinkedUiState>(LinkedUiState.Loading)
    val uiState: StateFlow<LinkedUiState> = _uiState.asStateFlow()

    init {
        repository.startBackgroundSync()
        refresh(forceSync = false)
    }

    fun refresh(forceSync: Boolean = true) {
        _uiState.value = LinkedUiState.Loading
        viewModelScope.launch {
            when (val sync = repository.syncContent(force = forceSync)) {
                is SyncResult.Failure -> {
                    _uiState.value = LinkedUiState.Error(sync.message)
                }
                is SyncResult.Skipped, is SyncResult.Success -> {
                    renderFromLocal(sync)
                }
            }
        }
    }

    private suspend fun renderFromLocal(sync: SyncResult) {
        val config = repository.fetchConfig().getOrNull()
        val local = repository.localManifest()
        val syncLine = when (sync) {
            is SyncResult.Success ->
                "Sync: +${sync.downloaded} descargados, ${sync.skipped} en caché, ${sync.failed} fallidos, ${sync.pruned} eliminados"
            is SyncResult.Skipped -> "Sync: ${sync.reason}"
            is SyncResult.Failure -> ""
        }

        val itemsLine = local?.let { snapshot ->
            val ready = snapshot.items.count { it.state == cl.televisor.app.data.local.LocalMediaEntry.STATE_READY }
            "Contenido local: $ready/${snapshot.items.size} archivos listos"
        } ?: "Sin copia local del manifiesto"

        val urgentLine = local?.urgentMessage?.let { u ->
            "Urgente: ${u.title}"
        }

        val lastSync = repository.localManifest()?.syncedAtEpochMs ?: 0L
        val lastSyncLabel = if (lastSync > 0) {
            SimpleDateFormat("dd/MM/yyyy HH:mm:ss", Locale.getDefault()).format(Date(lastSync))
        } else {
            "—"
        }

        _uiState.value = LinkedUiState.Ready(
            screenName = config?.screen?.name ?: "Sin nombre",
            screenUuid = config?.screen?.uuid ?: "—",
            manifestVersion = local?.version ?: config?.screen?.manifestVersion ?: 0,
            contentSource = local?.source ?: "—",
            playlistName = local?.playlistName,
            heartbeatSeconds = config?.sync?.heartbeatIntervalSeconds ?: 60,
            pollSeconds = config?.sync?.manifestPollSeconds ?: 120,
            timezone = config?.timezone ?: "—",
            syncSummary = syncLine,
            localSummary = itemsLine,
            urgentSummary = urgentLine,
            lastSyncLabel = lastSyncLabel,
        )
    }

    fun resetLink(onDone: () -> Unit) {
        repository.resetLink()
        onDone()
    }

    class Factory(
        private val repository: DeviceRepository,
    ) : ViewModelProvider.Factory {
        @Suppress("UNCHECKED_CAST")
        override fun <T : ViewModel> create(modelClass: Class<T>): T {
            return LinkedViewModel(repository) as T
        }
    }
}

sealed class LinkedUiState {
    data object Loading : LinkedUiState()

    data class Ready(
        val screenName: String,
        val screenUuid: String,
        val manifestVersion: Int,
        val contentSource: String,
        val playlistName: String?,
        val heartbeatSeconds: Int,
        val pollSeconds: Int,
        val timezone: String,
        val syncSummary: String,
        val localSummary: String,
        val urgentSummary: String?,
        val lastSyncLabel: String,
    ) : LinkedUiState()

    data class Error(val message: String) : LinkedUiState()
}

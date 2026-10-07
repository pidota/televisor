package cl.televisor.app.ui.pairing

import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import cl.televisor.app.data.DeviceRepository
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.isActive
import kotlinx.coroutines.launch

class PairingViewModel(
    private val repository: DeviceRepository,
) : ViewModel() {

    private val _uiState = MutableStateFlow<PairingUiState>(PairingUiState.Loading)
    val uiState: StateFlow<PairingUiState> = _uiState.asStateFlow()

    private var pollingJob: Job? = null

    init {
        startPairing()
    }

    fun startPairing() {
        pollingJob?.cancel()
        _uiState.value = PairingUiState.Loading

        viewModelScope.launch {
            repository.requestPairing()
                .onSuccess { pair ->
                    _uiState.value = PairingUiState.ShowCode(
                        code = pair.code,
                        statusMessage = PairingStatus.WAITING,
                    )
                    startActivationPolling()
                }
                .onFailure {
                    _uiState.value = PairingUiState.Error(
                        message = it.message ?: "Error de red",
                    )
                }
        }
    }

    private fun startActivationPolling() {
        pollingJob?.cancel()
        pollingJob = viewModelScope.launch {
            while (isActive) {
                delay(POLL_INTERVAL_MS)
                pollOnce()
            }
        }
    }

    private suspend fun pollOnce() {
        val current = _uiState.value
        if (current !is PairingUiState.ShowCode) {
            return
        }

        repository.pollActivation()
            .onSuccess { activation ->
                when (activation.status) {
                    "activated" -> {
                        val token = activation.deviceToken
                        if (!token.isNullOrBlank()) {
                            repository.persistToken(token)
                            pollingJob?.cancel()
                            _uiState.value = PairingUiState.Activated
                        }
                    }
                    "waiting" -> updateStatus(current, PairingStatus.WAITING)
                    "pending" -> updateStatus(current, PairingStatus.PENDING)
                    "already_activated" -> {
                        pollingJob?.cancel()
                        _uiState.value = PairingUiState.Error(
                            message = "already_activated",
                            isTerminal = true,
                        )
                    }
                    "inactive" -> {
                        pollingJob?.cancel()
                        _uiState.value = PairingUiState.Error(
                            message = "inactive",
                            isTerminal = true,
                        )
                    }
                    "invalid" -> updateStatus(current, PairingStatus.WAITING)
                    else -> updateStatus(current, PairingStatus.WAITING)
                }
            }
            .onFailure {
                updateStatus(current, PairingStatus.WAITING)
            }
    }

    private fun updateStatus(current: PairingUiState.ShowCode, status: PairingStatus) {
        _uiState.value = current.copy(statusMessage = status)
    }

    override fun onCleared() {
        pollingJob?.cancel()
        super.onCleared()
    }

    class Factory(
        private val repository: DeviceRepository,
    ) : ViewModelProvider.Factory {
        @Suppress("UNCHECKED_CAST")
        override fun <T : ViewModel> create(modelClass: Class<T>): T {
            return PairingViewModel(repository) as T
        }
    }

    companion object {
        private const val POLL_INTERVAL_MS = 3_000L
    }
}

sealed class PairingUiState {
    data object Loading : PairingUiState()

    data class ShowCode(
        val code: String,
        val statusMessage: PairingStatus,
    ) : PairingUiState()

    data object Activated : PairingUiState()

    data class Error(
        val message: String,
        val isTerminal: Boolean = false,
    ) : PairingUiState()
}

enum class PairingStatus {
    WAITING,
    PENDING,
}

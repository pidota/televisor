package cl.televisor.app.ui.linked

import android.content.Intent
import android.os.Bundle
import androidx.activity.viewModels
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.lifecycleScope
import androidx.lifecycle.repeatOnLifecycle
import cl.televisor.app.R
import cl.televisor.app.TelevisorApplication
import cl.televisor.app.databinding.ActivityLinkedBinding
import cl.televisor.app.ui.pairing.PairingActivity
import cl.televisor.app.ui.player.PlayerActivity
import kotlinx.coroutines.launch

class LinkedActivity : AppCompatActivity() {

    private lateinit var binding: ActivityLinkedBinding

    private val viewModel: LinkedViewModel by viewModels {
        val app = application as TelevisorApplication
        LinkedViewModel.Factory(app.deviceRepository)
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityLinkedBinding.inflate(layoutInflater)
        setContentView(binding.root)

        binding.btnRefresh.setOnClickListener { viewModel.refresh(forceSync = true) }
        binding.btnBackToPlayer.setOnClickListener {
            startActivity(Intent(this, PlayerActivity::class.java))
            finish()
        }
        binding.btnReset.setOnClickListener {
            viewModel.resetLink {
                startActivity(Intent(this, PairingActivity::class.java))
                finish()
            }
        }

        lifecycleScope.launch {
            repeatOnLifecycle(Lifecycle.State.STARTED) {
                viewModel.uiState.collect { state -> render(state) }
            }
        }
    }

    private fun render(state: LinkedUiState) {
        when (state) {
            LinkedUiState.Loading -> {
                binding.screenName.text = getString(R.string.sync_in_progress)
                binding.details.text = ""
            }
            is LinkedUiState.Ready -> {
                binding.screenName.text = state.screenName
                binding.details.text = buildString {
                    appendLine("UUID: ${state.screenUuid}")
                    appendLine("Manifiesto v${state.manifestVersion} · Origen: ${state.contentSource}")
                    state.playlistName?.let { appendLine("Playlist: $it") }
                    state.urgentSummary?.let { appendLine(it) }
                    appendLine(state.localSummary)
                    appendLine(state.syncSummary)
                    appendLine("Última sync: ${state.lastSyncLabel}")
                    appendLine("Heartbeat: ${state.heartbeatSeconds}s · Poll: ${state.pollSeconds}s")
                    append("Zona horaria: ${state.timezone}")
                }
            }
            is LinkedUiState.Error -> {
                binding.screenName.text = getString(R.string.pairing_error)
                binding.details.text = state.message
            }
        }
    }
}

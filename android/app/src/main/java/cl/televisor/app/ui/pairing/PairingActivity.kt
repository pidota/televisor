package cl.televisor.app.ui.pairing

import android.content.Intent
import android.os.Bundle
import androidx.activity.viewModels
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.lifecycleScope
import androidx.lifecycle.repeatOnLifecycle
import cl.televisor.app.R
import cl.televisor.app.TelevisorApplication
import cl.televisor.app.databinding.ActivityPairingBinding
import cl.televisor.app.ui.player.PlayerActivity
import cl.televisor.app.ui.settings.ApiSettingsActivity
import kotlinx.coroutines.launch

class PairingActivity : AppCompatActivity() {

    private lateinit var binding: ActivityPairingBinding

    private val viewModel: PairingViewModel by viewModels {
        val app = application as TelevisorApplication
        PairingViewModel.Factory(app.deviceRepository)
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityPairingBinding.inflate(layoutInflater)
        setContentView(binding.root)

        binding.btnRetry.setOnClickListener { viewModel.startPairing() }
        binding.btnSettings.setOnClickListener {
            startActivity(Intent(this, ApiSettingsActivity::class.java))
        }

        lifecycleScope.launch {
            repeatOnLifecycle(Lifecycle.State.STARTED) {
                viewModel.uiState.collect { state -> render(state) }
            }
        }
    }

    private fun render(state: PairingUiState) {
        when (state) {
            PairingUiState.Loading -> {
                binding.code.text = "------"
                binding.status.setText(R.string.pairing_waiting)
            }
            is PairingUiState.ShowCode -> {
                binding.code.text = state.code
                binding.status.text = when (state.statusMessage) {
                    PairingStatus.WAITING -> getString(R.string.pairing_waiting)
                    PairingStatus.PENDING -> getString(R.string.pairing_pending)
                }
            }
            PairingUiState.Activated -> {
                startActivity(Intent(this, PlayerActivity::class.java))
                finish()
            }
            is PairingUiState.Error -> {
                binding.code.text = "------"
                binding.status.text = when (state.message) {
                    "already_activated" -> getString(R.string.pairing_already_activated)
                    "inactive" -> getString(R.string.pairing_inactive)
                    else -> getString(R.string.pairing_error)
                }
            }
        }
    }
}

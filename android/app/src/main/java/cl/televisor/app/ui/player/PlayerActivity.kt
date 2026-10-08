package cl.televisor.app.ui.player

import android.content.Intent
import android.os.Bundle
import android.view.KeyEvent
import android.view.WindowManager
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.WindowCompat
import androidx.core.view.WindowInsetsCompat
import androidx.core.view.WindowInsetsControllerCompat
import androidx.lifecycle.lifecycleScope
import androidx.media3.exoplayer.ExoPlayer
import cl.televisor.app.TelevisorApplication
import cl.televisor.app.databinding.ActivityPlayerBinding
import cl.televisor.app.playback.SignagePlaybackController
import cl.televisor.app.playback.TickerOverlayView
import cl.televisor.app.playback.UrgentFullscreenView
import cl.televisor.app.ui.linked.LinkedActivity

class PlayerActivity : AppCompatActivity() {

    private lateinit var binding: ActivityPlayerBinding
    private var exoPlayer: ExoPlayer? = null
    private var playbackController: SignagePlaybackController? = null

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityPlayerBinding.inflate(layoutInflater)
        setContentView(binding.root)

        window.addFlags(WindowManager.LayoutParams.FLAG_KEEP_SCREEN_ON)
        enterImmersive()

        val app = application as TelevisorApplication
        // Respaldo si el sistema pausa la actividad; la sync principal la hace SignagePlaybackController.
        app.deviceRepository.startBackgroundSync()

        val player = ExoPlayer.Builder(this).build()
        exoPlayer = player
        binding.playerView.player = player

        // El <include android:id="@+id/urgentOverlay"> sustituye el id urgentRoot del layout incluido.
        val urgentRoot = binding.urgentOverlay.root
        val urgentView = UrgentFullscreenView(urgentRoot)
        val tickerView = TickerOverlayView(binding.tickerOverlay.root)

        playbackController = SignagePlaybackController(
            player = player,
            playerView = binding.playerView,
            imageView = binding.imageView,
            urgentView = urgentView,
            tickerView = tickerView,
            emptyState = binding.emptyState,
            repository = app.deviceRepository,
            mediaStore = app.mediaFileStore,
            scope = lifecycleScope,
        ).also { it.start() }
    }

    override fun onResume() {
        super.onResume()
        playbackController?.refreshIfNeeded()
    }

    override fun onWindowFocusChanged(hasFocus: Boolean) {
        super.onWindowFocusChanged(hasFocus)
        if (hasFocus) {
            enterImmersive()
        }
    }

    override fun onDestroy() {
        playbackController?.stop()
        playbackController = null
        binding.playerView.player = null
        exoPlayer?.release()
        exoPlayer = null
        super.onDestroy()
    }

    override fun onKeyDown(keyCode: Int, event: KeyEvent?): Boolean {
        if (keyCode == KeyEvent.KEYCODE_MENU || keyCode == KeyEvent.KEYCODE_INFO) {
            startActivity(Intent(this, LinkedActivity::class.java))
            return true
        }
        return super.onKeyDown(keyCode, event)
    }

    private fun enterImmersive() {
        WindowCompat.setDecorFitsSystemWindows(window, false)
        WindowInsetsControllerCompat(window, window.decorView).apply {
            hide(WindowInsetsCompat.Type.systemBars())
            systemBarsBehavior = WindowInsetsControllerCompat.BEHAVIOR_SHOW_TRANSIENT_BARS_BY_SWIPE
        }
    }
}

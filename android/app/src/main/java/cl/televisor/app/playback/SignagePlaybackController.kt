package cl.televisor.app.playback

import android.graphics.BitmapFactory
import android.os.Handler
import android.os.Looper
import android.view.View
import android.widget.ImageView
import android.widget.TextView
import androidx.core.net.toUri
import androidx.lifecycle.LifecycleCoroutineScope
import androidx.media3.common.MediaItem
import androidx.media3.common.Player
import androidx.media3.exoplayer.ExoPlayer
import androidx.media3.ui.PlayerView
import cl.televisor.app.data.DeviceRepository
import cl.televisor.app.data.api.UrgentMessageData
import cl.televisor.app.data.local.LocalManifestSnapshot
import cl.televisor.app.data.local.MediaFileStore
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.isActive
import kotlinx.coroutines.launch

class SignagePlaybackController(
    private val player: ExoPlayer,
    private val playerView: PlayerView,
    private val imageView: ImageView,
    private val urgentView: UrgentFullscreenView,
    private val tickerView: TickerOverlayView,
    private val emptyState: TextView,
    private val repository: DeviceRepository,
    private val mediaStore: MediaFileStore,
    private val scope: LifecycleCoroutineScope,
) {

    private val mainHandler = Handler(Looper.getMainLooper())
    private var playlistItems: List<LocalPlaybackItem> = emptyList()
    private var currentIndex = 0
    private var snapshot: LocalManifestSnapshot? = null
    private var imageAdvanceRunnable: Runnable? = null
    private var monitorJob: Job? = null
    private var syncJob: Job? = null
    private val playerListener = object : Player.Listener {
        override fun onPlaybackStateChanged(playbackState: Int) {
            if (playbackState == Player.STATE_ENDED && snapshot?.source != "live" && playlistItems.isNotEmpty()) {
                playNext()
            }
        }
    }

    fun start() {
        player.addListener(playerListener)
        scope.launch {
            repository.syncContent(force = false)
            applyManifest(repository.localManifest())
        }
        startManifestMonitor()
    }

    fun stop() {
        monitorJob?.cancel()
        syncJob?.cancel()
        cancelImageTimer()
        urgentView.hide(animate = false)
        tickerView.hide()
        player.removeListener(playerListener)
        player.stop()
    }

    fun refreshIfNeeded() {
        scope.launch {
            val local = repository.localManifest()
            val versionChanged = local?.version != snapshot?.version
            val sourceChanged = local?.source != snapshot?.source
            val urgentChanged = local?.urgentMessage != snapshot?.urgentMessage
            if (versionChanged || sourceChanged || urgentChanged) {
                applyManifest(local)
            }
        }
    }

    private fun startManifestMonitor() {
        monitorJob?.cancel()
        monitorJob = scope.launch {
            while (isActive) {
                val pollSeconds = if (isFullscreenUrgent(snapshot)) {
                    URGENT_POLL_SECONDS
                } else {
                    repository.manifestPollSeconds().coerceIn(30, 300)
                }
                delay(pollSeconds * 1000L)
                val before = snapshot?.version to snapshot?.source
                val urgentBefore = snapshot?.urgentMessage?.id
                val liveBefore = snapshot?.liveUrl
                repository.syncContent(force = false)
                val afterManifest = repository.localManifest()
                val after = afterManifest?.version to afterManifest?.source
                val urgentAfter = afterManifest?.urgentMessage?.id
                val liveAfter = afterManifest?.liveUrl
                if (before != after || urgentBefore != urgentAfter || liveBefore != liveAfter) {
                    applyManifest(afterManifest)
                } else if (isFullscreenUrgent(afterManifest)) {
                    afterManifest?.let { refreshUrgentContent(it) }
                } else if (isTickerMessage(afterManifest?.urgentMessage)) {
                    refreshTickerContent(afterManifest)
                }
            }
        }
    }

    private fun applyManifest(manifest: LocalManifestSnapshot?) {
        if (manifest != null && isLive(manifest)) {
            applyLive(manifest)
            return
        }

        if (manifest != null && canContinuePlayback(manifest)) {
            snapshot = manifest
            updateTickerOverlay(manifest)
            return
        }

        cancelImageTimer()
        player.stop()
        player.clearMediaItems()
        snapshot = manifest

        if (manifest == null) {
            urgentView.hide()
            tickerView.hide()
            showEmpty(true)
            scheduleEmptyRetry()
            return
        }

        if (isFullscreenUrgent(manifest)) {
            tickerView.hide()
            showUrgent(manifest)
            return
        }

        urgentView.hide()
        updateTickerOverlay(manifest)

        playlistItems = LocalPlaybackCatalog.orderedFiles(manifest, mediaStore)
        if (playlistItems.isEmpty()) {
            showEmpty(true)
            scheduleEmptyRetry()
            return
        }

        showEmpty(false)
        currentIndex = 0
        playItemAt(currentIndex)
    }

    private fun isLive(manifest: LocalManifestSnapshot): Boolean {
        return manifest.source == "live" && !manifest.liveUrl.isNullOrBlank()
    }

    private fun applyLive(manifest: LocalManifestSnapshot) {
        val url = manifest.liveUrl ?: return
        val continuing = snapshot?.source == "live"
            && snapshot?.liveUrl == url
            && player.playbackState != Player.STATE_IDLE
            && player.playbackState != Player.STATE_ENDED

        snapshot = manifest
        playlistItems = emptyList()
        cancelImageTimer()
        urgentView.hide()
        updateTickerOverlay(manifest)
        showEmpty(false)
        imageView.visibility = View.GONE
        playerView.visibility = View.VISIBLE

        if (continuing) {
            return
        }

        player.setMediaItem(MediaItem.fromUri(url))
        player.prepare()
        player.playWhenReady = true
    }

    private fun canContinuePlayback(manifest: LocalManifestSnapshot): Boolean {
        if (isFullscreenUrgent(manifest) || isFullscreenUrgent(snapshot)) {
            return false
        }
        if (manifest.version != snapshot?.version) {
            return false
        }
        if (manifest.source != snapshot?.source) {
            return false
        }
        if (manifest.playlistRevision != snapshot?.playlistRevision) {
            return false
        }
        if (playlistItems.isEmpty()) {
            return false
        }
        val rebuilt = LocalPlaybackCatalog.orderedFiles(manifest, mediaStore)
        if (rebuilt.size != playlistItems.size) {
            return false
        }
        return rebuilt.map { it.entry.uuid } == playlistItems.map { it.entry.uuid }
    }

    private fun refreshUrgentContent(manifest: LocalManifestSnapshot) {
        val message = manifest.urgentMessage ?: return
        if (urgentView.currentMessageId() == message.id) {
            urgentView.bind(message, repository.appTimezone())
            return
        }
        showUrgent(manifest)
    }

    private fun refreshTickerContent(manifest: LocalManifestSnapshot?) {
        manifest ?: return
        updateTickerOverlay(manifest)
    }

    private fun updateTickerOverlay(manifest: LocalManifestSnapshot) {
        val ticker = manifest.urgentMessage?.takeIf { isTickerMessage(it) }
        if (ticker == null) {
            tickerView.hide()
            return
        }
        tickerView.bind(ticker)
        tickerView.show()
    }

    private fun scheduleEmptyRetry() {
        syncJob?.cancel()
        syncJob = scope.launch {
            delay(15_000)
            repository.syncContent(force = true)
            applyManifest(repository.localManifest())
        }
    }

    private fun showUrgent(manifest: LocalManifestSnapshot) {
        playerView.visibility = View.GONE
        imageView.visibility = View.GONE
        emptyState.visibility = View.GONE
        player.playWhenReady = false

        val urgent = manifest.urgentMessage ?: return
        urgentView.bind(urgent, repository.appTimezone())
        urgentView.show(animate = !urgentView.isVisible())

        scope.launch {
            repository.reportUrgentPlayback(urgent.id)
        }
    }

    private fun showEmpty(show: Boolean) {
        emptyState.visibility = if (show) View.VISIBLE else View.GONE
        if (show) {
            playerView.visibility = View.GONE
            imageView.visibility = View.GONE
        }
    }

    private fun playItemAt(index: Int) {
        if (playlistItems.isEmpty()) {
            return
        }
        val item = playlistItems[index % playlistItems.size]
        currentIndex = index % playlistItems.size

        scope.launch {
            val mediaId = item.entry.mediaAssetId.takeIf { it > 0 } ?: item.entry.itemId
            repository.reportPlayback(
                playlistId = snapshot?.playlistId,
                mediaAssetId = mediaId,
            )
        }

        when (item.entry.type.lowercase()) {
            "image" -> playImage(item)
            else -> playVideo(item)
        }
    }

    private fun playVideo(item: LocalPlaybackItem) {
        cancelImageTimer()
        imageView.visibility = View.GONE
        playerView.visibility = View.VISIBLE
        player.setMediaItem(MediaItem.fromUri(item.file.toUri()))
        player.prepare()
        player.playWhenReady = true
    }

    private fun playImage(item: LocalPlaybackItem) {
        player.playWhenReady = false
        player.stop()
        player.clearMediaItems()
        playerView.visibility = View.GONE
        imageView.visibility = View.VISIBLE

        val bitmap = BitmapFactory.decodeFile(item.file.absolutePath)
        imageView.setImageBitmap(bitmap)

        val seconds = item.entry.duration.takeIf { it > 0 } ?: DEFAULT_IMAGE_SECONDS
        val runnable = Runnable { playNext() }
        imageAdvanceRunnable = runnable
        mainHandler.postDelayed(runnable, seconds * 1000L)
    }

    private fun playNext() {
        if (playlistItems.isEmpty()) {
            return
        }
        currentIndex = (currentIndex + 1) % playlistItems.size
        playItemAt(currentIndex)
    }

    private fun cancelImageTimer() {
        imageAdvanceRunnable?.let { mainHandler.removeCallbacks(it) }
        imageAdvanceRunnable = null
    }

    private fun isFullscreenUrgent(manifest: LocalManifestSnapshot?): Boolean {
        if (manifest == null) {
            return false
        }
        if (manifest.source == "urgent") {
            return true
        }
        val message = manifest.urgentMessage ?: return false
        return !isTickerMessage(message)
    }

    private fun isTickerMessage(message: UrgentMessageData?): Boolean {
        return message?.layout == LAYOUT_TEXT_TICKER
    }

    companion object {
        private const val DEFAULT_IMAGE_SECONDS = 10
        private const val URGENT_POLL_SECONDS = 30
        private const val LAYOUT_TEXT_TICKER = "text_ticker"
    }
}

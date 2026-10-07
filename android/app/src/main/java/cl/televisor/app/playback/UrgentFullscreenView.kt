package cl.televisor.app.playback

import android.os.Handler
import android.os.Looper
import android.view.View
import android.widget.TextView
import cl.televisor.app.R
import cl.televisor.app.data.api.UrgentMessageData
import java.time.Instant
import java.time.ZoneId
import java.time.format.DateTimeFormatter
import java.util.Locale

class UrgentFullscreenView(
    private val root: View,
) {
    private val title: TextView = root.findViewById(R.id.urgentTitle)
    private val body: TextView = root.findViewById(R.id.urgentBody)
    private val endsAt: TextView = root.findViewById(R.id.urgentEndsAt)
    private val clock: TextView = root.findViewById(R.id.urgentClock)
    private val priorityBadge: TextView = root.findViewById(R.id.urgentPriorityBadge)

    private val handler = Handler(Looper.getMainLooper())
    private var clockRunnable: Runnable? = null
    private var displayedMessageId: Int? = null

    private val endsFormatter = DateTimeFormatter.ofPattern("dd/MM/yyyy HH:mm", Locale("es", "CL"))

    fun bind(message: UrgentMessageData, timezoneId: String?) {
        val zone = runCatching { ZoneId.of(timezoneId ?: DEFAULT_TIMEZONE) }
            .getOrDefault(ZoneId.of(DEFAULT_TIMEZONE))

        title.text = message.title
        body.text = message.body.trim()

        if (message.title.length > 36) {
            title.isSelected = true
        }

        endsAt.text = formatInstant(message.endsAt, zone)

        if (message.priority > 0) {
            priorityBadge.visibility = View.VISIBLE
            priorityBadge.text = root.context.getString(R.string.urgent_priority, message.priority)
        } else {
            priorityBadge.visibility = View.GONE
        }

        startClock(zone)
        displayedMessageId = message.id
    }

    fun currentMessageId(): Int? = displayedMessageId

    fun show(animate: Boolean = true) {
        if (root.visibility == View.VISIBLE && root.alpha >= 1f) {
            return
        }
        root.visibility = View.VISIBLE
        if (animate) {
            root.alpha = 0f
            root.animate().alpha(1f).setDuration(350).start()
        } else {
            root.alpha = 1f
        }
    }

    fun hide(animate: Boolean = true) {
        stopClock()
        displayedMessageId = null
        if (!animate) {
            root.visibility = View.GONE
            return
        }
        root.animate()
            .alpha(0f)
            .setDuration(250)
            .withEndAction {
                root.visibility = View.GONE
                root.alpha = 1f
            }
            .start()
    }

    fun isVisible(): Boolean = root.visibility == View.VISIBLE

    private fun startClock(zone: ZoneId) {
        stopClock()
        val tick = object : Runnable {
            override fun run() {
                val now = Instant.now().atZone(zone)
                clock.text = now.format(DateTimeFormatter.ofPattern("HH:mm:ss", Locale.getDefault()))
                handler.postDelayed(this, 1000L)
            }
        }
        clockRunnable = tick
        handler.post(tick)
    }

    private fun stopClock() {
        clockRunnable?.let { handler.removeCallbacks(it) }
        clockRunnable = null
    }

    private fun formatInstant(iso: String, zone: ZoneId): String {
        return runCatching {
            Instant.parse(iso).atZone(zone).format(endsFormatter)
        }.getOrElse { iso }
    }

    companion object {
        private const val DEFAULT_TIMEZONE = "America/Santiago"
    }
}

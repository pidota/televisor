package cl.televisor.app.playback

import android.animation.ObjectAnimator
import android.view.View
import android.widget.FrameLayout
import android.widget.TextView
import cl.televisor.app.R
import cl.televisor.app.data.api.UrgentMessageData
import kotlin.math.ceil

class TickerOverlayView(
    private val root: View,
) {
    private val scrollHost: View = root.findViewById(R.id.tickerScrollHost)
    private val label: TextView = root.findViewById(R.id.tickerLabel)

    private var animator: ObjectAnimator? = null
    private var displayedMessageId: Int? = null
    private var loopUnit: String = ""
    private var loopWidthPx: Float = 0f

    init {
        label.apply {
            ellipsize = null
            isSingleLine = true
            maxLines = 1
            maxWidth = Int.MAX_VALUE
            setHorizontallyScrolling(false)
            includeFontPadding = true
            layoutParams = FrameLayout.LayoutParams(
                FrameLayout.LayoutParams.WRAP_CONTENT,
                FrameLayout.LayoutParams.MATCH_PARENT,
            )
        }
    }

    fun bind(message: UrgentMessageData) {
        val segments = buildList {
            val title = message.title.trim()
            val body = message.body.trim()
            if (title.isNotEmpty()) {
                add(title)
            }
            if (body.isNotEmpty()) {
                add(body)
            }
        }
        val unit = buildLoopUnit(segments)
        if (displayedMessageId == message.id && unit == loopUnit && animator?.isRunning == true) {
            return
        }

        loopUnit = unit
        applyLabelText(unit.repeat(UNIT_REPEAT_COUNT))
        loopWidthPx = measureTextPx(loopUnit)
        displayedMessageId = message.id
        root.post { restartScroll() }
    }

    fun currentMessageId(): Int? = displayedMessageId

    fun show() {
        if (root.visibility != View.VISIBLE) {
            root.visibility = View.VISIBLE
        }
        if (animator?.isRunning != true && loopUnit.isNotEmpty()) {
            root.post { restartScroll() }
        }
    }

    fun hide() {
        stopScroll()
        displayedMessageId = null
        loopUnit = ""
        loopWidthPx = 0f
        label.text = ""
        root.visibility = View.GONE
    }

    fun isVisible(): Boolean = root.visibility == View.VISIBLE

    private fun buildLoopUnit(segments: List<String>): String {
        if (segments.isEmpty()) {
            return ""
        }
        val core = segments.joinToString(TICKER_SEPARATOR)
            .replace('\n', ' ')
            .replace(Regex("\\s+"), " ")
            .trim()
            .uppercase()
        return "$core$TICKER_LOOP_GAP"
    }

    /**
     * El TextView dentro de un contenedor match_parent recibe AT_MOST(ancho pantalla) y recorta
     * con elipsis. Forzamos ancho explícito al texto completo.
     */
    private fun applyLabelText(fullText: String) {
        label.text = fullText
        val contentWidth = ceil(measureTextPx(fullText)).toInt().coerceAtLeast(1)
        val totalWidth = contentWidth + label.paddingLeft + label.paddingRight
        label.layoutParams = FrameLayout.LayoutParams(totalWidth, FrameLayout.LayoutParams.MATCH_PARENT)
        label.requestLayout()
    }

    private fun measureTextPx(text: String): Float {
        if (text.isEmpty()) {
            return 1f
        }
        return label.paint.measureText(text).coerceAtLeast(1f)
    }

    private fun restartScroll() {
        if (root.visibility != View.VISIBLE || loopUnit.isEmpty()) {
            return
        }

        stopScroll()

        val hostWidth = scrollHost.width
        if (hostWidth <= 0) {
            return
        }

        if (loopWidthPx <= 0f) {
            loopWidthPx = measureTextPx(loopUnit)
        }

        val startX = hostWidth.toFloat()
        val endX = startX - loopWidthPx
        label.translationX = startX

        val distance = hostWidth + loopWidthPx
        val durationMs = (distance * MS_PER_PIXEL).toLong().coerceAtLeast(MIN_CYCLE_MS)

        animator = ObjectAnimator.ofFloat(label, View.TRANSLATION_X, startX, endX).apply {
            duration = durationMs
            repeatCount = ObjectAnimator.INFINITE
            repeatMode = ObjectAnimator.RESTART
            start()
        }
    }

    private fun stopScroll() {
        animator?.cancel()
        animator = null
    }

    companion object {
        private const val TICKER_SEPARATOR = "   •   "
        private const val TICKER_LOOP_GAP = "          "
        private const val UNIT_REPEAT_COUNT = 16
        private const val MS_PER_PIXEL = 6f
        private const val MIN_CYCLE_MS = 4_000L
    }
}

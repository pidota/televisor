package cl.televisor.app.playback

import cl.televisor.app.data.local.LocalManifestSnapshot
import cl.televisor.app.data.local.LocalMediaEntry
import cl.televisor.app.data.local.MediaFileStore
import java.io.File

/**
 * Entrada para Etapa 12 (Media3): ítems locales verificados en orden del manifiesto.
 */
object LocalPlaybackCatalog {

    fun orderedFiles(
        snapshot: LocalManifestSnapshot,
        mediaStore: MediaFileStore,
    ): List<LocalPlaybackItem> {
        return snapshot.items
            .filter { it.state == LocalMediaEntry.STATE_READY }
            .mapNotNull { entry ->
                val file = mediaStore.resolve(entry)
                if (!file.exists()) {
                    return@mapNotNull null
                }
                LocalPlaybackItem(
                    entry = entry,
                    file = file,
                )
            }
    }
}

data class LocalPlaybackItem(
    val entry: LocalMediaEntry,
    val file: File,
)

package cl.televisor.app.sync

import cl.televisor.app.data.local.LocalManifestSnapshot

sealed class SyncResult {
    data class Success(
        val snapshot: LocalManifestSnapshot,
        val downloaded: Int,
        val skipped: Int,
        val failed: Int,
        val pruned: Int,
    ) : SyncResult()

    data class Skipped(val reason: String) : SyncResult()

    data class Failure(val message: String) : SyncResult()
}

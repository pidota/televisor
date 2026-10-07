package cl.televisor.app.data.local

import android.content.Context
import com.squareup.moshi.Moshi
import java.io.File

class LocalManifestStore(
    context: Context,
    moshi: Moshi,
) {
    private val manifestFile = File(context.filesDir, "televisor/manifest.json")
    private val adapter = moshi.adapter(LocalManifestSnapshot::class.java)

    fun load(): LocalManifestSnapshot? {
        if (!manifestFile.exists()) {
            return null
        }
        return runCatching {
            adapter.fromJson(manifestFile.readText())
        }.getOrNull()
    }

    fun save(snapshot: LocalManifestSnapshot) {
        manifestFile.parentFile?.mkdirs()
        manifestFile.writeText(adapter.toJson(snapshot))
    }

    fun clear() {
        if (manifestFile.exists()) {
            manifestFile.delete()
        }
    }
}

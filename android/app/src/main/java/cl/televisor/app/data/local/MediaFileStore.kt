package cl.televisor.app.data.local

import android.content.Context
import cl.televisor.app.data.api.ManifestMediaItem
import okhttp3.OkHttpClient
import okhttp3.Request
import java.io.File
import java.io.FileInputStream
import java.io.FileOutputStream
import java.security.MessageDigest

class MediaFileStore(context: Context) {

    private val baseDir = context.filesDir
    private val mediaRoot = File(baseDir, "televisor/media").apply { mkdirs() }

    fun mediaRootPath(): File = mediaRoot

    fun storageStats(): Pair<Long, Long> {
        val files = mediaRoot.listFiles().orEmpty()
        val used = files.sumOf { it.length() }
        val total = mediaRoot.usableSpace + used
        return used to total
    }

    fun findVerified(uuid: String, expectedChecksum: String): File? {
        val file = fileForUuid(uuid)
        if (!file.exists() || file.length() == 0L) {
            return null
        }

        val sidecar = checksumSidecar(file)
        val cached = sidecar.takeIf { it.exists() }?.readText()?.trim()
        if (cached != null && cached.equals(expectedChecksum, ignoreCase = true)) {
            return file
        }

        val hash = sha256Hex(file)
        return if (hash.equals(expectedChecksum, ignoreCase = true)) {
            runCatching { sidecar.writeText(hash) }
            file
        } else {
            sidecar.delete()
            null
        }
    }

    suspend fun downloadAndVerify(
        item: ManifestMediaItem,
        client: OkHttpClient,
    ): Result<File> = runCatching {
        val target = fileForUuid(item.uuid, item.mimeType)
        val temp = File(target.parentFile, "${target.name}.part")

        val request = Request.Builder().url(item.url).get().build()
        client.newCall(request).execute().use { response ->
            if (!response.isSuccessful) {
                error("HTTP ${response.code}")
            }
            val body = response.body ?: error("Cuerpo vacío")
            FileOutputStream(temp).use { out ->
                body.byteStream().use { input -> input.copyTo(out) }
            }
        }

        if (temp.length() != item.size && item.size > 0) {
            temp.delete()
            error("Tamaño incorrecto")
        }

        val hash = sha256Hex(temp)
        if (!hash.equals(item.checksum, ignoreCase = true)) {
            temp.delete()
            error("Checksum inválido")
        }

        if (target.exists()) {
            target.delete()
        }
        if (!temp.renameTo(target)) {
            temp.copyTo(target, overwrite = true)
            temp.delete()
        }
        runCatching { checksumSidecar(target).writeText(item.checksum) }
        target
    }

    /** Descartas parciales de descargas interrumpidas (pueden ocupar cientos de MB cada una). */
    fun purgeIncompleteDownloads() {
        mediaRoot.listFiles()?.forEach { file ->
            if (file.name.endsWith(".part")) {
                file.delete()
            }
        }
    }

    fun relativePathFor(uuid: String, mimeType: String): String {
        return "televisor/media/${fileName(uuid, mimeType)}"
    }

    fun resolve(entry: LocalMediaEntry): File = File(baseDir, entry.relativePath)

    fun pruneExcept(allowedUuids: Set<String>) {
        mediaRoot.listFiles()?.forEach { file ->
            if (file.name.endsWith(".part") || file.name.endsWith(".sha256")) {
                val mediaUuid = uuidFromFileName(file.name)
                if (mediaUuid != null && mediaUuid !in allowedUuids) {
                    file.delete()
                }
                return@forEach
            }

            val uuid = uuidFromFileName(file.name) ?: return@forEach
            if (uuid !in allowedUuids) {
                file.delete()
                checksumSidecar(file).delete()
            }
        }
    }

    fun clearAll() {
        mediaRoot.listFiles()?.forEach { it.delete() }
    }

    private fun fileForUuid(uuid: String, mimeType: String? = null): File {
        val existing = mediaRoot.listFiles()?.firstOrNull {
            it.name.startsWith("$uuid.")
        }
        if (existing != null) {
            return existing
        }
        return File(mediaRoot, fileName(uuid, mimeType ?: "application/octet-stream"))
    }

    private fun fileName(uuid: String, mimeType: String): String {
        val ext = when (mimeType.lowercase()) {
            "video/mp4" -> "mp4"
            "image/jpeg" -> "jpg"
            "image/png" -> "png"
            "image/webp" -> "webp"
            else -> "bin"
        }
        return "$uuid.$ext"
    }

    private fun checksumSidecar(mediaFile: File): File {
        return File(mediaFile.parentFile, "${mediaFile.name}.sha256")
    }

    private fun uuidFromFileName(name: String): String? {
        val base = when {
            name.endsWith(".part") -> name.removeSuffix(".part")
            name.endsWith(".sha256") -> name.removeSuffix(".sha256")
            else -> name
        }
        val dot = base.lastIndexOf('.')
        if (dot <= 0) {
            return null
        }
        return base.substring(0, dot)
    }

    private fun sha256Hex(file: File): String {
        val digest = MessageDigest.getInstance("SHA-256")
        FileInputStream(file).use { input ->
            val buffer = ByteArray(DEFAULT_BUFFER_SIZE)
            var read = input.read(buffer)
            while (read >= 0) {
                if (read > 0) {
                    digest.update(buffer, 0, read)
                }
                read = input.read(buffer)
            }
        }
        return digest.digest().joinToString("") { "%02x".format(it) }
    }
}

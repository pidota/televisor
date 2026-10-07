package cl.televisor.app.sync

import android.content.Context
import androidx.work.ExistingWorkPolicy
import androidx.work.OneTimeWorkRequestBuilder
import androidx.work.WorkManager
import cl.televisor.app.data.DeviceSession
import java.util.concurrent.TimeUnit

object SyncScheduler {

    private const val WORK_NAME = "televisor_manifest_sync"

    fun enqueueNow(context: Context) {
        val request = OneTimeWorkRequestBuilder<ManifestSyncWorker>().build()
        WorkManager.getInstance(context.applicationContext)
            .enqueueUniqueWork(WORK_NAME, ExistingWorkPolicy.REPLACE, request)
    }

    fun scheduleNext(context: Context, delaySeconds: Int) {
        val safeDelay = delaySeconds.coerceIn(30, 3600).toLong()
        val request = OneTimeWorkRequestBuilder<ManifestSyncWorker>()
            .setInitialDelay(safeDelay, TimeUnit.SECONDS)
            .build()
        WorkManager.getInstance(context.applicationContext)
            .enqueueUniqueWork(WORK_NAME, ExistingWorkPolicy.REPLACE, request)
    }

    fun startAfterLink(context: Context, session: DeviceSession) {
        enqueueNow(context)
        scheduleNext(context, session.getManifestPollSeconds())
    }

    fun cancel(context: Context) {
        WorkManager.getInstance(context.applicationContext).cancelUniqueWork(WORK_NAME)
    }
}

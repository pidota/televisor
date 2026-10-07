package cl.televisor.app.sync

import android.content.Context
import androidx.work.CoroutineWorker
import androidx.work.WorkerParameters
import cl.televisor.app.TelevisorApplication

class ManifestSyncWorker(
    appContext: Context,
    params: WorkerParameters,
) : CoroutineWorker(appContext, params) {

    override suspend fun doWork(): Result {
        val app = applicationContext as TelevisorApplication
        if (!app.session.hasDeviceToken()) {
            return Result.failure()
        }

        return when (val outcome = app.contentSyncEngine.sync()) {
            is SyncResult.Failure -> Result.retry()
            is SyncResult.Success, is SyncResult.Skipped -> {
                SyncScheduler.scheduleNext(
                    applicationContext,
                    app.session.getManifestPollSeconds(),
                )
                Result.success()
            }
        }
    }
}

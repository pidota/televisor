package cl.televisor.app.data.api

import cl.televisor.app.BuildConfig
import cl.televisor.app.data.DeviceSession
import com.squareup.moshi.Moshi
import com.squareup.moshi.kotlin.reflect.KotlinJsonAdapterFactory
import okhttp3.Interceptor
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.moshi.MoshiConverterFactory
import java.util.concurrent.TimeUnit

class ApiClient(
    private val session: DeviceSession,
) {
    val moshi: Moshi = Moshi.Builder()
        .add(KotlinJsonAdapterFactory())
        .build()

    @Volatile
    private var cachedBaseUrl: String? = null

    @Volatile
    private var cachedApi: TelevisorApi? = null

    @Volatile
    private var cachedHttpClient: OkHttpClient? = null

    @Volatile
    private var cachedDownloadClient: OkHttpClient? = null

    fun api(): TelevisorApi {
        ensureClients()
        return cachedApi!!
    }

    fun httpClient(): OkHttpClient {
        ensureClients()
        return cachedHttpClient!!
    }

    fun httpClientForDownloads(): OkHttpClient {
        ensureClients()
        return cachedDownloadClient!!
    }

    fun invalidate() {
        synchronized(this) {
            cachedBaseUrl = null
            cachedApi = null
            cachedHttpClient = null
            cachedDownloadClient = null
        }
    }

    private fun ensureClients() {
        val baseUrl = session.getApiBaseUrl()
        if (cachedApi != null && cachedBaseUrl == baseUrl) {
            return
        }

        synchronized(this) {
            if (cachedApi != null && cachedBaseUrl == baseUrl) {
                return
            }

            val standardClient = buildOkHttp(readTimeoutSeconds = 60)
            val downloadClient = buildOkHttp(readTimeoutSeconds = 3600)

            val retrofit = Retrofit.Builder()
                .baseUrl(baseUrl)
                .client(standardClient)
                .addConverterFactory(MoshiConverterFactory.create(moshi))
                .build()

            cachedBaseUrl = baseUrl
            cachedHttpClient = standardClient
            cachedDownloadClient = downloadClient
            cachedApi = retrofit.create(TelevisorApi::class.java)
        }
    }

    private fun buildOkHttp(readTimeoutSeconds: Long): OkHttpClient {
        return OkHttpClient.Builder()
            .connectTimeout(30, TimeUnit.SECONDS)
            .readTimeout(readTimeoutSeconds, TimeUnit.SECONDS)
            .writeTimeout(60, TimeUnit.SECONDS)
            .addInterceptor(authInterceptor())
            .apply {
                if (BuildConfig.DEBUG) {
                    addInterceptor(
                        HttpLoggingInterceptor().apply {
                            level = HttpLoggingInterceptor.Level.BASIC
                        },
                    )
                }
            }
            .build()
    }

    private fun authInterceptor(): Interceptor = Interceptor { chain ->
        val requestBuilder = chain.request().newBuilder()
        session.getDeviceToken()?.let { token ->
            requestBuilder.header("Authorization", "Bearer $token")
        }
        chain.proceed(requestBuilder.build())
    }
}

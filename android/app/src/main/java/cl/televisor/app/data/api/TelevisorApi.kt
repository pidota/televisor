package cl.televisor.app.data.api

import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST

interface TelevisorApi {

    @POST("device/pair")
    suspend fun pair(@Body body: ScreenUuidBody): ApiEnvelope<PairResponseData>

    @POST("device/activate")
    suspend fun activate(@Body body: ScreenUuidBody): ApiEnvelope<ActivateResponseData>

    @GET("device/config")
    suspend fun config(): ApiEnvelope<ConfigResponseData>

    @GET("device/playlist")
    suspend fun playlist(): ApiEnvelope<ManifestResponseData>

    @POST("device/heartbeat")
    suspend fun heartbeat(@Body body: HeartbeatRequest): ApiEnvelope<HeartbeatResponseData>

    @POST("device/playback-status")
    suspend fun playbackStatus(@Body body: PlaybackStatusRequest): ApiEnvelope<PlaybackAck>
}

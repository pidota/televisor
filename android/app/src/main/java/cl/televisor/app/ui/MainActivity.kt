package cl.televisor.app.ui

import android.content.Intent
import android.os.Bundle
import androidx.appcompat.app.AppCompatActivity
import cl.televisor.app.TelevisorApplication
import cl.televisor.app.ui.pairing.PairingActivity
import cl.televisor.app.ui.player.PlayerActivity

class MainActivity : AppCompatActivity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        val app = application as TelevisorApplication
        val repository = app.deviceRepository

        val target = if (repository.isLinked()) {
            PlayerActivity::class.java
        } else {
            PairingActivity::class.java
        }

        startActivity(Intent(this, target))
        finish()
    }
}

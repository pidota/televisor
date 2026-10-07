package cl.televisor.app.ui.settings

import android.os.Bundle
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import cl.televisor.app.R
import cl.televisor.app.TelevisorApplication
import cl.televisor.app.databinding.ActivityApiSettingsBinding

class ApiSettingsActivity : AppCompatActivity() {

    private lateinit var binding: ActivityApiSettingsBinding

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityApiSettingsBinding.inflate(layoutInflater)
        setContentView(binding.root)

        val app = application as TelevisorApplication
        binding.apiUrl.setText(app.session.getApiBaseUrl())

        binding.btnSave.setOnClickListener {
            val url = binding.apiUrl.text?.toString()?.trim().orEmpty()
            if (url.isEmpty() || !url.startsWith("http")) {
                Toast.makeText(this, R.string.settings_hint, Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }
            app.session.setApiBaseUrl(url)
            app.apiClient.invalidate()
            Toast.makeText(this, R.string.settings_save, Toast.LENGTH_SHORT).show()
            finish()
        }
    }
}

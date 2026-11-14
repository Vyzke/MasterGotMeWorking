<?php
// Site settings.
// Copy this file to config.php and change the values for your machine.
// config.php is git-ignored so your passwords and secrets never reach GitHub.
// (If config.php doesn't exist, the site falls back to these defaults.)
return [
    // --- Database (XAMPP defaults) ---
    'db_host' => '127.0.0.1',
    'db_port' => 3306,
    'db_name' => 'uniwise',
    'db_user' => 'root',
    'db_pass' => '',

    // --- Sign in with Google (optional) ---
    // Create these at https://console.cloud.google.com → APIs & Services → Credentials
    // → Create credentials → OAuth client ID → "Web application".
    'google_client_id'     => '',
    'google_client_secret' => '',
    // Leave empty to work it out automatically, e.g. http://localhost/uniwise/google-callback.php
    // It must match an "Authorised redirect URI" in Google Cloud exactly.
    'google_redirect_uri'  => '',
];

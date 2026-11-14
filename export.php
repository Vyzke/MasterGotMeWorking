<?php
// Download everything stored about the logged-in user as a JSON file.
require_once __DIR__ . '/includes/auth.php';
$user = require_login();

$stmt = db()->prepare('
    SELECT u.first_name, u.last_name, u.email, r.name AS region, u.start_year, u.language,
           u.bio, u.school, u.avatar_path,
           u.notify_deadlines, u.notify_days, u.notify_weekly, u.notify_news,
           u.pref_regions, u.pref_majors, u.pref_budget, u.pref_study_level,
           (u.google_id IS NOT NULL) AS google_connected, u.created_at, u.last_login_at
    FROM users u LEFT JOIN regions r ON r.id = u.region_id
    WHERE u.id = ?');
$stmt->execute([$user['id']]);
$data = [
    'exported_at' => date('c'),
    'account'     => $stmt->fetch(),
    // Saved universities, deadlines, essays etc. will be added here as those tools are built.
];

header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="uniwise-my-data-' . date('Y-m-d') . '.json"');
header('X-Content-Type-Options: nosniff');
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

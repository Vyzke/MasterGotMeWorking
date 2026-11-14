<?php
// Developer tool: lists every English string the site translates, and which ones
// each lang/xx.php file is missing.  Run:  C:\xampp\php\php.exe tools\i18n-keys.php
$root = dirname(__DIR__);
$keys = [];

$files = array_merge(glob("$root/*.php"), glob("$root/includes/*.php"));
foreach ($files as $file) {
    $src = file_get_contents($file);
    // t('..'), te('..'), coming_soon('..', '..'), compare_row('..'), google_button('..')
    preg_match_all("/\\b(?:t|te|coming_soon|compare_row|google_button)\\(\\s*'((?:[^'\\\\]|\\\\.)*)'(?:\\s*,\\s*'((?:[^'\\\\]|\\\\.)*)')?/u", $src, $m, PREG_SET_ORDER);
    foreach ($m as $hit) {
        $keys[stripslashes($hit[1])] = true;
        if (!empty($hit[2]) && str_contains($hit[0], 'coming_soon')) $keys[stripslashes($hit[2])] = true;
    }
    // English text kept in arrays (tool configs, home page lists, select labels)
    // includes/avatar.php returns English error messages that are translated by the caller
    if (str_ends_with($file, 'avatar.php') && preg_match_all("/return '((?:[^'\\\\]|\\\\.)*)';/u", $src, $m5)) {
        foreach ($m5[1] as $s) $keys[stripslashes($s)] = true;
    }
    if (preg_match_all("/'(?:title|lead|login_title|empty_title|empty_text|desc)'\\s*=>\\s*'((?:[^'\\\\]|\\\\.)*)'/u", $src, $m2)) {
        foreach ($m2[1] as $s) $keys[stripslashes($s)] = true;
    }
    if (preg_match_all("/\\[\\s*'[a-z.]+(?:\\.php)?'\\s*,\\s*'[a-z]+'\\s*,\\s*'[a-z]+'\\s*,\\s*'((?:[^'\\\\]|\\\\.)*)'\\s*,\\s*'((?:[^'\\\\]|\\\\.)*)'\\s*\\]/u", $src, $m3, PREG_SET_ORDER)) {
        foreach ($m3 as $h) { $keys[stripslashes($h[1])] = true; $keys[stripslashes($h[2])] = true; }
    }
    if (preg_match_all("/\\[\\s*'[a-z]+'\\s*,\\s*'[a-z]+'\\s*,\\s*'((?:[^'\\\\]|\\\\.)*)'\\s*,\\s*'((?:[^'\\\\]|\\\\.)*)'\\s*\\]/u", $src, $m4, PREG_SET_ORDER)) {
        foreach ($m4 as $h) { $keys[stripslashes($h[1])] = true; $keys[stripslashes($h[2])] = true; }
    }
}
// Values that come from code lists / the database but are translated
foreach (['QS rank', 'Employment rate', 'Lowest tuition', 'Name A–Z',
          'English', 'German', 'French', 'Dutch', 'Mandarin', 'Japanese', 'Spanish',
          'Public', 'Private', 'Other', 'Bachelor’s', 'Master’s', 'PhD',
          'North America', 'UK & Ireland', 'Europe', 'Nordics', 'Asia', 'Oceania', 'Middle East', 'Latin America', 'Africa'] as $s) {
    $keys[$s] = true;
}
// Single-character / non-text captures from the regexes
unset($keys['']);
foreach (array_keys($keys) as $k) if (preg_match('/^(\$|i |[a-z]+$)/', $k) && !in_array($k, ['Other'], true)) unset($keys[$k]);

ksort($keys);
$all = array_keys($keys);

if (in_array('--list', $argv, true)) {
    foreach ($all as $k) echo $k, "\n";
    exit;
}
echo count($all), " strings\n";
foreach (glob("$root/lang/*.php") as $langFile) {
    $dict = require $langFile;
    $missing = array_diff($all, array_keys($dict));
    echo basename($langFile), ': ', count($missing), " missing\n";
    foreach ($missing as $k) echo "   - $k\n";
}

<?php
// Translations.
// Write every visible string in English and wrap it: t('Log in'), or te('Log in') to also escape it.
// Each lang/xx.php file maps the English text to the translation. Missing entries fall back to English.
// Placeholders: t('Hi, {name}', ['name' => $first]).

const LANGUAGES = [
    'en' => 'English',
    'es' => 'Español',
    'fr' => 'Français',
    'de' => 'Deutsch',
    'zh' => '中文',
    'id' => 'Bahasa Indonesia',
];
const LANG_COOKIE = 'uniwise_lang';

function current_lang(): string
{
    $code = $_COOKIE[LANG_COOKIE] ?? 'en';
    return isset(LANGUAGES[$code]) ? $code : 'en';
}

/** Remember the language for a year (and for this request). */
function set_lang(string $code): void
{
    if (!isset(LANGUAGES[$code])) return;
    setcookie(LANG_COOKIE, $code, ['expires' => time() + 31536000, 'path' => '/', 'samesite' => 'Lax']);
    $_COOKIE[LANG_COOKIE] = $code;
}

function t(string $text, array $vars = []): string
{
    static $dict = [];
    $lang = current_lang();
    if (!isset($dict[$lang])) {
        $file = __DIR__ . "/../lang/$lang.php";
        $dict[$lang] = ($lang !== 'en' && is_file($file)) ? require $file : [];
    }
    $out = $dict[$lang][$text] ?? $text;
    foreach ($vars as $k => $v) {
        $out = str_replace('{' . $k . '}', (string) $v, $out);
    }
    return $out;
}

/** Translate + HTML-escape. */
function te(string $text, array $vars = []): string
{
    return e(t($text, $vars));
}

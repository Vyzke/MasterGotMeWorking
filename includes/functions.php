<?php
// Small helpers used across pages.
require_once __DIR__ . '/i18n.php';

/** Escape text for HTML output. Use for EVERY value that comes from the database or the URL. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** "$" … "$$$$" price tier from a yearly tuition in USD. */
function tuition_tier($usd): string
{
    if ($usd === null) return '—';
    if ($usd < 5000) return '$';
    if ($usd < 15000) return '$$';
    if ($usd < 35000) return '$$$';
    return '$$$$';
}

/** 32000 → "$32,000" */
function money($usd): string
{
    return $usd === null ? '—' : '$' . number_format((float) $usd);
}

/** 32000 → "$32k" */
function money_k($usd): string
{
    if ($usd === null) return '—';
    return $usd >= 1000 ? '$' . round($usd / 1000) . 'k' : '$' . (int) $usd;
}

/** 93.0 → "93%" */
function pct($value): string
{
    if ($value === null) return '—';
    return rtrim(rtrim(number_format((float) $value, 1), '0'), '.') . '%';
}

/** 28000 → "28k" */
function short_number($n): string
{
    if ($n === null) return '—';
    return $n >= 1000 ? round($n / 1000) . 'k' : (string) $n;
}

/** Letter for the crest badge: "University of Oxford" → "O". */
function crest_letter(string $name): string
{
    foreach (preg_split('/\s+/', $name) as $word) {
        if (!in_array(strtolower($word), ['university', 'of', 'the', 'college'], true)) {
            return mb_strtoupper(mb_substr($word, 0, 1));
        }
    }
    return mb_strtoupper(mb_substr($name, 0, 1));
}

/** Current URL's query string with some values replaced (for pagination / filters). */
function query_with(array $changes): string
{
    $q = array_merge($_GET, $changes);
    $q = array_filter($q, fn($v) => $v !== '' && $v !== null && $v !== 0 && $v !== '0');
    return $q ? '?' . http_build_query($q) : '?';
}

/** Inline SVG line icons (same set the static pages use). */
function icon(string $name, string $class = 'i'): string
{
    static $paths = [
        'pin'      => '<path d="M12 21s-7-6.2-7-12a7 7 0 0 1 14 0c0 5.8-7 12-7 12z"/><circle cx="12" cy="9" r="2.5"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
        'bookmark' => '<path d="M6 3h12v18l-6-4-6 4z"/>',
        'check'    => '<path d="m5 12 5 5 9-10"/>',
        'minus'    => '<path d="M6 12h12"/>',
        'info'     => '<circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1"/>',
        'arrow'    => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'external' => '<path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',
        'search'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
        'star'     => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/>',
        'book'     => '<path d="M3 5h6a3 3 0 0 1 3 3v12a2 2 0 0 0-2-2H3zM21 5h-6a3 3 0 0 0-3 3v12a2 2 0 0 1 2-2h7z"/>',
        'flask'    => '<path d="M9 3h6M10 3v6l-5 9a2 2 0 0 0 1.8 3h10.4a2 2 0 0 0 1.8-3l-5-9V3"/>',
        'briefcase'=> '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M3 13h18"/>',
        'chat'     => '<path d="M4 5h16v11H9l-5 4z"/>',
        'coin'     => '<circle cx="12" cy="12" r="9"/><path d="M15 9.5c-.5-1-1.6-1.5-3-1.5-1.7 0-3 .8-3 2s1.3 1.7 3 2 3 .8 3 2-1.3 2-3 2c-1.4 0-2.5-.5-3-1.5M12 6v2M12 16v2"/>',
        'building' => '<path d="M3 10 12 4l9 6M5 10v9M19 10v9M9 10v9M15 10v9M3 21h18"/>',
        'flag'     => '<path d="M5 21V4M5 4h11l-2 4 2 4H5"/>',
        'folder'   => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
        'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'globe'    => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
        'columns'  => '<rect x="3" y="4" width="7" height="16" rx="1.5"/><rect x="14" y="4" width="7" height="16" rx="1.5"/>',
        'lock'     => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'pen'      => '<path d="M4 20l4-1 11-11-3-3L5 16zM14 6l3 3"/>',
        'chart'    => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'trophy'   => '<path d="M8 4h8v5a4 4 0 0 1-8 0zM8 6H5a3 3 0 0 0 3 4M16 6h3a3 3 0 0 1-3 4M12 13v4M8 21h8M10 17h4"/>',
        'camera'   => '<path d="M4 8h3l2-3h6l2 3h3a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1z"/><circle cx="12" cy="13.5" r="3.5"/>',
        'bell'     => '<path d="M6 9a6 6 0 0 1 12 0c0 6 2.5 7.5 2.5 7.5h-17S6 15 6 9zM10 20a2 2 0 0 0 4 0"/>',
        'compass'  => '<circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-2 5-5 2 2-5z"/>',
        'link'     => '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>',
        'download' => '<path d="M12 4v12M7 11l5 5 5-5M4 20h16"/>',
        'sun'      =>'<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'trash'    => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-13M9 7V4h6v3"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
    ];
    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" aria-hidden="true">' . ($paths[$name] ?? '') . '</svg>';
}

/** Yellow notice box shown when MySQL isn't reachable. */
function db_error_notice(): string
{
    return '<div class="notice">' . icon('info') . '<span><b>' . te('Database not connected.') . '</b> '
        . te('Start MySQL in the XAMPP Control Panel and import {files} in phpMyAdmin.', ['files' => 'database/schema.sql + database/seed.sql'])
        . '</span></div>';
}

/** Placeholder shown while a table has no rows yet. Pass English text; it gets translated. */
function coming_soon(string $title, string $text, string $iconName = 'building'): string
{
    return '<div class="empty-state"><span class="icon-box lg purple">' . icon($iconName) . '</span>'
        . '<h2>' . te($title) . '</h2><p>' . te($text) . '</p></div>';
}

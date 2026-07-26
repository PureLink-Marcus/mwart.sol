<?php
/**
 * mwart.solutions — Besucher-Tracking (Backend)
 * Datenschutzfreundlich: keine Cookies, keine IP-Speicherung.
 * Besucher werden über einen täglich wechselnden Hash gezählt (nicht rückverfolgbar).
 * Ablage: data/visits-JJJJ-MM.jsonl (eine Zeile pro Ereignis).
 */

header('Content-Type: application/json; charset=utf-8');

// Nur POST zulassen
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo '{"ok":false}';
    exit;
}

// Eingabe lesen und begrenzen
$raw = file_get_contents('php://input', false, null, 0, 2048);
$in  = json_decode($raw, true);
if (!is_array($in)) { $in = []; }

$clean = function ($v, $max = 200) {
    return mb_substr(trim(preg_replace('/[\x00-\x1F\x7F]/u', '', (string)$v)), 0, $max);
};

// Täglich wechselnder, anonymer Besucher-Hash (IP wird NICHT gespeichert)
$saltFile = __DIR__ . '/data/.salt';
if (!is_dir(__DIR__ . '/data')) { mkdir(__DIR__ . '/data', 0755, true); }
// Ordner gegen direkten Web-Zugriff schuetzen (Apache); bei nginx per Konfig loesen
$ht = __DIR__ . '/data/.htaccess';
if (!file_exists($ht)) {
    file_put_contents($ht, "Require all denied\n", LOCK_EX);
}
if (!file_exists($saltFile)) {
    file_put_contents($saltFile, bin2hex(random_bytes(32)), LOCK_EX);
}
$salt = file_get_contents($saltFile);
$ip   = $_SERVER['REMOTE_ADDR'] ?? '';
$ua   = $_SERVER['HTTP_USER_AGENT'] ?? '';
$vid  = substr(hash('sha256', $ip . '|' . $ua . '|' . date('Y-m-d') . '|' . $salt), 0, 16);

// Bot-Erkennung (grob)
$isBot = (bool)preg_match('/bot|crawl|spider|slurp|preview|fetch|monitor/i', $ua);

$event = [
    't'    => date('c'),
    'vid'  => $vid,
    'e'    => $clean($in['e'] ?? 'pageview', 40),   // Ereignistyp: pageview, view, click …
    'p'    => $clean($in['p'] ?? '', 200),          // Seite/Sektion
    'r'    => $clean($in['r'] ?? '', 200),          // Referrer
    'lang' => $clean($in['lang'] ?? '', 10),
    'sw'   => (int)($in['w'] ?? 0),                 // Bildschirmbreite
    'thm'  => $clean($in['thm'] ?? '', 10),         // hell/dunkel
    'utm'  => $clean($in['utm'] ?? '', 120),        // Kampagne (utm_source/medium/campaign)
    'bot'  => $isBot ? 1 : 0,
];

$file = __DIR__ . '/data/visits-' . date('Y-m') . '.jsonl';
file_put_contents($file, json_encode($event, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);

echo '{"ok":true}';

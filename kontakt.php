<?php
/**
 * mwART.solutions — Kontaktformular (Backend)
 * Nimmt Anfragen entgegen, prüft auf Spam (Honeypot + Rate-Limit),
 * speichert sie in data/messages.jsonl und mailt sie an den Empfänger.
 *
 * Konfiguration über Umgebungsvariablen (in Coolify setzbar):
 *   KONTAKT_EMPFAENGER — Zieladresse (Standard: kontakt@mwart.solutions)
 *   KONTAKT_ABSENDER   — From-Adresse (Standard: formular@mwart.solutions)
 */

$empfaenger = getenv('KONTAKT_EMPFAENGER') ?: 'kontakt@mwart.solutions';
$absender   = getenv('KONTAKT_ABSENDER')   ?: 'formular@mwart.solutions';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method']);
    exit;
}

$clean = fn($v, $max = 200) => mb_substr(trim(preg_replace('/[\x00-\x1F\x7F]/u', '', (string)$v)), 0, $max);

$name    = $clean($_POST['name'] ?? '', 100);
$email   = $clean($_POST['email'] ?? '', 150);
$thema   = $clean($_POST['thema'] ?? '', 100);
$text    = $clean($_POST['nachricht'] ?? '', 4000);
$honig   = $_POST['firma_webseite'] ?? '';

// Honeypot gefüllt → Bot. Erfolg vortäuschen, nichts speichern.
if ($honig !== '') { echo json_encode(['ok' => true]); exit; }

if ($name === '' || $text === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'validation']);
    exit;
}

// Datenordner sicherstellen (inkl. Zugriffsschutz)
$dir = __DIR__ . '/data';
if (!is_dir($dir)) { mkdir($dir, 0755, true); }
$ht = $dir . '/.htaccess';
if (!file_exists($ht)) { file_put_contents($ht, "Require all denied\n", LOCK_EX); }

// Rate-Limit: max. 5 Anfragen pro IP pro Stunde (IP nur als Hash abgelegt)
$ipHash = substr(hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . date('Y-m-d-H')), 0, 16);
$rlFile = $dir . '/.ratelimit';
$rl = file_exists($rlFile) ? (json_decode(file_get_contents($rlFile), true) ?: []) : [];
$hour = date('Y-m-d-H');
if (($rl['hour'] ?? '') !== $hour) { $rl = ['hour' => $hour, 'counts' => []]; }
$rl['counts'][$ipHash] = ($rl['counts'][$ipHash] ?? 0) + 1;
file_put_contents($rlFile, json_encode($rl), LOCK_EX);
if ($rl['counts'][$ipHash] > 5) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'ratelimit']);
    exit;
}

// Anfrage speichern
$eintrag = [
    't'     => date('c'),
    'name'  => $name,
    'email' => $email,
    'thema' => $thema,
    'text'  => $text,
];
file_put_contents(
    $dir . '/messages.jsonl',
    json_encode($eintrag, JSON_UNESCAPED_UNICODE) . "\n",
    FILE_APPEND | LOCK_EX
);

// E-Mail senden (Reply-To = Absender, damit direktes Antworten klappt)
$betreff = '=?UTF-8?B?' . base64_encode('Anfrage über mwart.solutions: ' . $thema) . '?=';
$body = "Neue Anfrage über das Kontaktformular\n"
      . "------------------------------------\n"
      . "Name:  $name\n"
      . "E-Mail: $email\n"
      . "Thema: $thema\n\n"
      . $text . "\n";
$headers = "From: $absender\r\n"
         . "Reply-To: $email\r\n"
         . "Content-Type: text/plain; charset=utf-8\r\n";
@mail($empfaenger, $betreff, $body, $headers); // gespeichert wird immer, Mail ist Bonus

echo json_encode(['ok' => true]);

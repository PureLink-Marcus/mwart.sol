<?php
/**
 * mwART.solutions — Besucher-Statistik (Dashboard)
 * Aufruf: https://mwart.solutions/stats.php?key=IHR_GEHEIMER_SCHLUESSEL
 *
 * Der Schlüssel kommt aus der Umgebungsvariable STATS_KEY (in Coolify unter
 * "Environment Variables" setzen). Ohne gesetzten Schlüssel ist das
 * Dashboard komplett gesperrt — es gibt bewusst keinen Standardwert.
 */
$statsKey = getenv('STATS_KEY') ?: '';

if ($statsKey === '') {
    http_response_code(503);
    exit('Dashboard nicht konfiguriert: Umgebungsvariable STATS_KEY setzen.');
}
if (!hash_equals($statsKey, $_GET['key'] ?? '')) {
    http_response_code(403);
    exit('Zugriff verweigert.');
}

// Monat wählbar: stats.php?key=…&m=2026-07 (Standard: aktueller Monat)
$month = preg_match('/^\d{4}-\d{2}$/', $_GET['m'] ?? '') ? $_GET['m'] : date('Y-m');
$file  = __DIR__ . '/data/visits-' . $month . '.jsonl';

$events = [];
if (file_exists($file)) {
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $e = json_decode($line, true);
        if (is_array($e) && empty($e['bot'])) { $events[] = $e; } // Bots ausblenden
    }
}

$days = $pages = $refs = $langs = $themes = $clicks = $sections = $utms = [];
$visitors = [];
foreach ($events as $e) {
    $day = substr($e['t'], 0, 10);
    if ($e['e'] === 'pageview') {
        $days[$day]['pv'] = ($days[$day]['pv'] ?? 0) + 1;
        $days[$day]['v'][$e['vid']] = 1;
        $visitors[$e['vid']] = 1;
        if ($e['p'])   { $pages[$e['p']]   = ($pages[$e['p']]   ?? 0) + 1; }
        if ($e['r'])   { $host = parse_url($e['r'], PHP_URL_HOST) ?: $e['r']; $refs[$host] = ($refs[$host] ?? 0) + 1; }
        if ($e['lang']){ $langs[$e['lang']] = ($langs[$e['lang']] ?? 0) + 1; }
        if ($e['thm']) { $themes[$e['thm']] = ($themes[$e['thm']] ?? 0) + 1; }
        if (!empty($e['utm'])) { $utms[$e['utm']] = ($utms[$e['utm']] ?? 0) + 1; }
    } elseif ($e['e'] === 'view')  { $sections[$e['p']] = ($sections[$e['p']] ?? 0) + 1; }
      elseif ($e['e'] === 'click') { $clicks[$e['p']]   = ($clicks[$e['p']]   ?? 0) + 1; }
}
ksort($days);
arsort($pages); arsort($refs); arsort($langs); arsort($themes); arsort($clicks); arsort($sections); arsort($utms);
$totalPV = array_sum(array_column($days, 'pv'));

// Kontaktanfragen des Monats (aus kontakt.php)
$messages = [];
$msgFile = __DIR__ . '/data/messages.jsonl';
if (file_exists($msgFile)) {
    foreach (file($msgFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $m = json_decode($line, true);
        if (is_array($m) && str_starts_with($m['t'] ?? '', $month)) { $messages[] = $m; }
    }
    $messages = array_reverse($messages); // neueste zuerst
}

function tbl($title, $data, $limit = 12) {
    echo "<div class='panel'><h3>" . htmlspecialchars($title) . "</h3><table>";
    if (!$data) { echo "<tr><td class='muted'>Noch keine Daten</td></tr>"; }
    foreach (array_slice($data, 0, $limit, true) as $k => $v) {
        echo "<tr><td>" . htmlspecialchars($k) . "</td><td class='num'>$v</td></tr>";
    }
    echo "</table></div>";
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8"><meta name="robots" content="noindex,nofollow">
<title>Statistik <?= htmlspecialchars($month) ?> — mwart.solutions</title>
<style>
  body{font-family:system-ui,sans-serif;background:#101014;color:#F2F2F5;margin:0;padding:32px}
  h1{font-size:1.4rem} h1 b{color:#C6F24E} h3{margin:0 0 10px;font-size:.95rem;color:#9A9AA8}
  .kpis{display:flex;gap:16px;flex-wrap:wrap;margin:24px 0}
  .kpi{background:#1C1C24;border:1px solid #2C2C36;border-radius:14px;padding:18px 26px}
  .kpi .n{font-size:1.8rem;font-weight:800;color:#C6F24E} .kpi:nth-child(2) .n{color:#F0338D}
  .kpi .l{font-size:.8rem;color:#9A9AA8}
  .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px}
  .panel{background:#1C1C24;border:1px solid #2C2C36;border-radius:14px;padding:18px}
  table{width:100%;border-collapse:collapse;font-size:.85rem}
  td{padding:5px 0;border-bottom:1px solid #26262e} .num{text-align:right;font-weight:700;color:#C6F24E}
  .muted{color:#5C5C66}
  .bars{display:flex;align-items:flex-end;gap:3px;height:120px;margin-top:8px}
  .bar{flex:1;background:linear-gradient(180deg,#C6F24E,#F0338D);border-radius:3px 3px 0 0;min-width:4px}
  .bar span{display:none}
  nav a{color:#F0338D;text-decoration:none;font-size:.85rem}
</style>
</head>
<body>
<h1>📊 Besucherstatistik <b><?= htmlspecialchars($month) ?></b></h1>
<nav>
  <a href="?key=<?= urlencode($statsKey) ?>&m=<?= date('Y-m', strtotime($month . '-01 -1 month')) ?>">← Vormonat</a> ·
  <a href="?key=<?= urlencode($statsKey) ?>">Aktueller Monat</a>
</nav>

<div class="kpis">
  <div class="kpi"><div class="n"><?= $totalPV ?></div><div class="l">Seitenaufrufe</div></div>
  <div class="kpi"><div class="n"><?= count($visitors) ?></div><div class="l">Besucher (anonym, pro Tag)</div></div>
  <div class="kpi"><div class="n"><?= count($days) ?></div><div class="l">aktive Tage</div></div>
  <div class="kpi"><div class="n"><?= count($messages) ?></div><div class="l">Kontaktanfragen</div></div>
</div>

<?php if ($messages): ?>
<div class="panel" style="margin-bottom:16px">
  <h3>📬 Kontaktanfragen (neueste zuerst)</h3>
  <table>
    <?php foreach (array_slice($messages, 0, 20) as $m): ?>
      <tr>
        <td style="white-space:nowrap"><?= htmlspecialchars(substr($m['t'], 0, 16)) ?></td>
        <td><b><?= htmlspecialchars($m['name']) ?></b> &lt;<?= htmlspecialchars($m['email']) ?>&gt;<br>
            <span class="muted"><?= htmlspecialchars($m['thema']) ?></span> —
            <?= htmlspecialchars(mb_substr($m['text'], 0, 160)) ?><?= mb_strlen($m['text']) > 160 ? '…' : '' ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php endif; ?>

<div class="panel" style="margin-bottom:16px">
  <h3>Seitenaufrufe pro Tag</h3>
  <div class="bars">
    <?php $max = max(1, max(array_map(fn($d) => $d['pv'], $days ?: [['pv' => 0]])));
    foreach ($days as $day => $d): ?>
      <div class="bar" style="height:<?= round($d['pv'] / $max * 100) ?>%" title="<?= $day ?>: <?= $d['pv'] ?> Aufrufe, <?= count($d['v']) ?> Besucher"></div>
    <?php endforeach; if (!$days) echo "<span class='muted'>Noch keine Daten</span>"; ?>
  </div>
</div>

<div class="grid">
  <?php
  tbl('Meistbesuchte Seiten', $pages);
  tbl('Gesehene Sektionen', $sections);
  tbl('Klicks (CTA & Links)', $clicks);
  tbl('Herkunft (Referrer)', $refs);
  tbl('Kampagnen (UTM)', $utms);
  tbl('Sprachen', $langs);
  tbl('Hell / Dunkel', $themes);
  ?>
</div>
</body>
</html>

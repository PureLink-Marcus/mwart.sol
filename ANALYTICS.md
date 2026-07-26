# 📊 Analytics & Tracking — mwART.solutions

Vollständige Übersicht über das cookielose Analytics-System.

---

## 🔍 Was wird erfasst?

### **1. Pageviews (Seitenaufrufe)**
- **Event:** `pageview`
- **Daten erfasst:**
  - Seite (page): z.B. `/index.html`, `/impressum.html`
  - Referrer: Woher kommt der Besucher? (Google, direkt, andere Seite)
  - Sprache: Browser-Sprache (de, en, etc.)
  - Viewport-Breite: Gerätetyp (mobile, tablet, desktop)
  - Theme: Hell oder Dunkel-Modus
  - UTM-Parameter: Kampagnen-Tracking (utm_source, utm_medium, utm_campaign)
  - Zeitstempel: Wann war der Besuch?

### **2. Section Views (Sektions-Ansichten)**
- **Event:** `view`
- **Daten erfasst:**
  - Welche Sektion hat der Nutzer angesehen? (#leistungen, #nutzen, #katalog, #kontakt, etc.)
  - Dies wird automatisch erfasst wenn auf einen Navigation-Link geklickt wird

### **3. Button & Link Clicks (CTA-Klicks)**
- **Event:** `click`
- **Daten erfasst:**
  - Welcher Button/Link wurde geklickt? (z.B. "Projekt besprechen", "Leistungen ansehen")
  - Dies wird für alle Links mit `data-cta` Attribut erfasst

### **4. Kontaktanfragen**
- **Separat gespeichert** (nicht im pageview-Tracking)
- **Daten erfasst:**
  - Name, E-Mail, Thema
  - Nachricht
  - Zeitstempel
  - IP-Hash (anonym)

---

## 🔐 Datenschutz & Anonymität

### **Keine IP-Speicherung**
- ❌ IP-Adresse wird NICHT geloggt
- ✅ Stattdessen: IP-Hash mit täglicher Rotation
- ✅ Formula: `sha256(IP + User-Agent + Date + Daily-Salt)`
- ✅ Salt wird täglich neu generiert (.salt Datei)
- ✅ Resultat: 16-stelliger anonymer Hash pro Visitor pro Tag

### **Keine Cookies**
- ✅ Cookieless Tracking (DSGVO-konform)
- ✅ Kein Consent-Banner nötig
- ✅ Nur First-Party Data Collection

### **Bot-Schutz**
- ✅ Bots werden auto-erkannt via User-Agent Regex
- ✅ Bot-Events bekommen `bot: true` Flag
- ✅ Bots werden im Dashboard gefiltert

### **Rate-Limiting**
- ✅ Kontaktformular: max 5 Anfragen pro IP pro Stunde
- ✅ Verhindert Spam-Anfragen
- ✅ HTTP 429 (Too Many Requests) Response

---

## 📈 Dashboard: `/stats.php?key=YOUR_SECRET_KEY`

### **Zugriff**
```bash
# Lokal (Dev):
http://localhost:8734/stats.php?key=test

# Production (Coolify):
https://mwart.solutions/stats.php?key=YOUR_SECRET_KEY
```

### **KPI Cards (oben)**
1. **Seitenaufrufe**: Totale Pageviews im Monat
2. **Besucher**: Unique Daily Visitors (anonym via IP-Hash)
3. **Aktive Tage**: Wie viele Tage hatten Besuche?
4. **Kontaktanfragen**: Wie viele Formular-Anfragen?

### **Daily Chart**
- **Seitenaufrufe pro Tag** (Bar-Chart, 30 Tage)
- Responsive Sampling (max 400px wide)
- Hover: Exakte Zahlen

### **Tabellen**

#### **Meistbesuchte Seiten**
- Top 12 Pages
- Z.B. `/index.html` (1200 views), `/impressum.html` (45 views)

#### **Gesehene Sektionen**
- Welche Sektionen wurden angesehen?
- Z.B. `#leistungen` (450x), `#katalog` (280x), `#kontakt` (120x)
- **Nutzen:** Zeigt welche Content-Sektionen interessieren

#### **Klicks (CTA & Links)**
- Welche Buttons wurden geklickt?
- Z.B. "Projekt besprechen" (80x), "Leistungen ansehen" (45x)
- **Nutzen:** Welche CTA konvertiert am besten?

#### **Herkunft (Referrer)**
- Woher kommen die Besucher?
- Z.B. `google.com` (300), `(direct)` (250), `linkedin.com` (80)
- **Nutzen:** Welche Traffic-Quelle ist wertvoll?

#### **Kampagnen (UTM)**
- UTM-Parameter Tracking
- Z.B. `utm_source=google&utm_medium=cpc&utm_campaign=summer_sale`
- **Nutzen:** ROI von Werbekampagnen messen

#### **Sprachen**
- Browser-Sprachen der Besucher
- Z.B. `de` (280), `en` (100), `fr` (15)

#### **Hell / Dunkel**
- Theme-Verteilung
- Z.B. `dark` (280), `light` (115)
- **Nutzen:** Werden dunkle Themes bevorzugt?

#### **Kontaktanfragen (neueste zuerst)**
- Letzte 20 Anfragen
- Name, Email, Thema, Anfrage-Text (erste 160 Zeichen)
- **Nutzen:** Alle Anfragen im Überblick

---

## 📡 Technische Implementierung

### **Frontend: track.js (inline in index.html)**

```javascript
// Automatisches Tracking aller Events
track({
  e: 'pageview',           // event type
  p: window.location.pathname + window.location.hash,  // page
  r: document.referrer,    // referrer
  lang: navigator.language, // language
  w: window.innerWidth,    // viewport width
  thm: document.documentElement.getAttribute('data-theme') || 'light'  // theme
  // UTM wird aus URL extrahiert
});
```

**Events die automatisch gesendet werden:**
1. **Pageview** — beim Laden jeder Seite
2. **View** — beim Klick auf Navigation-Link (#sektion)
3. **Click** — beim Klick auf Button mit `data-cta` Attribut

### **Backend: track.php**

```php
// POST /track.php
// Payload: e, p, r, lang, w, thm, utm

// 1. Bot-Check
if (isBotUA($ua)) { exit; }  // Skip bots

// 2. Rate-Limit Check
$ipHash = sha256($ip + $ua + date('Y-m-d') + salt);
if (count[ipHash] > daily_limit) { exit; }

// 3. Speichern
file_append('/data/visits-2026-07.jsonl', json_encode({
  't': '2026-07-26T14:30:45+00:00',
  'vid': ipHash,  // visitor ID
  'e': 'pageview',
  'p': '/index.html',
  'r': 'google.com',
  'lang': 'de',
  'w': 1280,
  'thm': 'dark',
  'utm': 'utm_source=google&utm_medium=cpc'
}));
```

### **Backend: stats.php**

```php
// GET /stats.php?key=SECRET&m=2026-07

// 1. Auth: STATS_KEY muss passen
if ($_GET['key'] !== getenv('STATS_KEY')) { exit(403); }

// 2. Monat auswählen (default: aktueller)
$month = $_GET['m'] ?? date('Y-m');

// 3. Lesen & Aggregieren
$visits = parse_jsonl('/data/visits-' . $month . '.jsonl');
$aggregated = aggregate($visits);  // KPIs, Charts, Tabellen

// 4. Rendern (HTML mit Dark-Theme)
render_dashboard($aggregated);
```

---

## 🚀 Deployment: Wie Tracking in Production läuft

### **Coolify Setup**
```bash
# 1. STATS_KEY setzen (Umgebungsvariable)
STATS_KEY=<openssl rand -hex 24>

# 2. Container läuft, data/ Volume persistent

# 3. Besuche werden in /data/visits-YYYY-MM.jsonl geloggt
# 4. Kontaktanfragen in /data/messages.jsonl
```

### **Backup-Strategie**
- Volume `mwart-data` wird vom Host täglich gesichert
- Daten überleben Container-Neustarts/Deploys
- Monitoring: Coolify zeigt Log-Files

---

## 📊 Beispiel-Monat (Fiktiv)

```
Dashboard für: 2026-07

KPIs:
├─ 2,847 Seitenaufrufe
├─ 342 Unique Visitors
├─ 23 aktive Tage
└─ 18 Kontaktanfragen

Top Pages:
├─ /index.html — 1,200 views
├─ /impressum.html — 180 views
└─ /datenschutz.html — 95 views

Sektionen angesehen:
├─ #katalog — 450 views
├─ #leistungen — 320 views
├─ #nutzen — 280 views
├─ #kontakt — 190 views
└─ #hersteller — 160 views

CTA Klicks:
├─ "Projekt besprechen" — 85 clicks
├─ "Leistungen ansehen" — 45 clicks
├─ "Kontakt" → formulär — 28 clicks
└─ "Impressum" — 15 clicks

Herkunft (Referrer):
├─ google.com — 180
├─ (direkt) — 95
├─ linkedin.com — 45
└─ facebook.com — 22

Kampagnen (UTM):
├─ utm_source=google&medium=cpc — 150
├─ utm_source=linkedin&medium=organic — 45
└─ utm_source=newsletter&medium=email — 28

Sprachen:
├─ de (Deutsch) — 280
├─ en (English) — 45
└─ fr (Français) — 17

Theme:
├─ dark (Dunkel) — 220
└─ light (Hell) — 122

Kontaktanfragen (neueste zuerst):
├─ 2026-07-26 14:30 — Max Mustermann <max@example.com> — "Kooperation"
├─ 2026-07-25 09:15 — Erika Muster <erika@example.de> — "Projekt-Anfrage"
└─ [... 16 weitere]
```

---

## 🎯 Wie CTAs aufgebaut sind

### **HTML mit `data-cta` Attribut**
```html
<!-- Button oder Link mit data-cta Attribut -->
<a class="btn btn-primary" href="#kontakt" data-cta="Projekt besprechen">
  Projekt besprechen
</a>

<!-- Im Dashboard wird das als CTA registriert -->
```

### **Automatisches Tracking**
```javascript
document.querySelectorAll('[data-cta]').forEach(el => {
  el.addEventListener('click', () => {
    track({ e: 'click', p: el.dataset.cta });
  });
});
```

---

## 💡 Häufige Fragen

### **Q: Warum zeigen die Zahlen nichts?**
A: Im **Development** (localhost) wird nichts geloggt. Erst auf Production (Live-Site) sehen Sie Daten im Dashboard.

### **Q: Wie lange werden Daten gespeichert?**
A: Unbegrenzt (bis Volume voll ist). Nach ~1000 Tagen sollte eine Archivierung erwogen werden.

### **Q: Kann ich mehrere Websites tracken?**
A: Nein, jede Site braucht ihren eigenen Container + data/ Volume.

### **Q: Wie aktuell ist das Dashboard?**
A: Real-time! Neue Besuche erscheinen sofort nach `track.php` POST.

### **Q: Privacy-konform?**
A: ✅ Ja, vollständig DSGVO-konform:
- Keine Cookies
- Keine IP-Speicherung
- Tägliche Salt-Rotation
- Anonyme Hashes
- Kein Consent nötig

---

## 🔗 Integration-Checklist

- [ ] **Lokal testen:**
  ```bash
  curl -X POST http://localhost:8734/track.php \
    -d "e=pageview&p=/index.html&r=direct"
  ```

- [ ] **Auf Production deployen** → Coolify mit STATS_KEY

- [ ] **Dashboard testen:**
  ```
  https://mwart.solutions/stats.php?key=YOUR_SECRET_KEY
  ```

- [ ] **Erste Daten verifizieren** (nach ~30 Minuten Traffic)

- [ ] **Monitoring:** Coolify Logs überwachen

---

**Documentation:** Tracking läuft vollständig im Hintergrund. Keine Config nötig nach Deployment! 🚀

# 🚀 Production Checklist — mwART.solutions

Bevor die Seite **live geht**, müssen folgende Punkte überprüft und konfiguriert werden.

## 🔐 Sicherheit & Konfiguration

- [ ] **STATS_KEY setzen** (in Coolify → Environment Variables)
  ```bash
  openssl rand -hex 24  # Zufallswert generieren
  ```
  - Ohne diesen Wert ist das Dashboard unter `/stats.php` unzugänglich
  
- [ ] **Kontakt-E-Mail-Adressen setzen** (in Coolify → Environment Variables)
  - `KONTAKT_EMPFAENGER` — wo landen Formularanfragen? (default: kontakt@mwart.solutions)
  - `KONTAKT_ABSENDER` — von welcher Adresse gehen Bestätigungsmails? (default: formular@mwart.solutions)

- [ ] **SMTP-Konfiguration** (optional, für echten Mail-Versand)
  - Derzeit läuft `mail()` ins Leere — Anfragen werden aber immer in `/data/messages.jsonl` gespeichert
  - Für Produktion: PHPMailer oder msmtp im Dockerfile ergänzen

- [ ] **.htaccess geschützt** (wird automatisch erstellt)
  - `/data/` Verzeichnis: `Require all denied` ✅ (schützt Statistik und Kontaktanfragen)
  - `/stats.php` und `/kontakt.php` sind öffentliche Endpunkte, aber durch `STATS_KEY` und Rate-Limit geschützt

---

## 📋 Impressum & Rechtliches

- [ ] **impressum.html aktualisieren**
  - `DE12345678` → **echte USt-IdNr.** eintragen (aktuell Platzhalter)
  - `Registernummer` → Falls Eintrag ins Handelsregister erforderlich, ausfüllen
  - Amtsgericht: Osnabrück (korrekt für Salzbergen, Emsland) ✅

- [ ] **datenschutz.html aktualisieren**
  - Hosting-Provider eintragen (wer hostet die Seite?)
  - Auftragsverarbeitungsvertrag (AVV) mit Provider abschließen
  - Google Analytics oder andere Tracker dokumentieren (falls verwendet)

---

## 📊 Analytics & Tracking

- [ ] **Visitor-Tracking aktiviert** ✅
  - `/track.php` läuft im Hintergrund und speichert Besuche
  - Daten sind anonym (nur IP-Hash, tägliche Salt-Rotation)
  - Dashboard: `/stats.php?key=YOUR_SECRET_KEY`

- [ ] **Kontaktanfragen-Speicherung** ✅
  - Alle Anfragen aus dem Formular gehen in `/data/messages.jsonl`
  - Auch wenn Mail-Versand fehlschlägt, sind Anfragen gespeichert
  - Im Dashboard sichtbar unter `/stats.php?key=...`

---

## 🔧 Deployment mit Coolify

### Schritt 1: Repository verbinden
1. Coolify öffnen → **New Resource** → **Docker Compose**
2. Dieses Repository verbinden (https://github.com/YOUR_REPO/mwart.solutions)

### Schritt 2: Environment Variables setzen
Unter **Environment Variables**:
```
STATS_KEY=<openssl rand -hex 24 Ergebnis>
KONTAKT_EMPFAENGER=kontakt@mwart.solutions
KONTAKT_ABSENDER=formular@mwart.solutions
```

### Schritt 3: Domain konfigurieren
- Domain in Coolify eintragen (z.B. `mwart.solutions`)
- Coolify generiert automatisch HTTPS-Zertifikat (Let's Encrypt)
- Reverse-Proxy wird automatisch eingerichtet

### Schritt 4: Deploy starten
- **Deploy** Button klicken
- Volume `mwart-data` wird automatisch erstellt (persistent über Deployments)
- Seite ist live unter https://mwart.solutions

---

## ✅ Nach dem Livegang

- [ ] **Seite testen auf SSL/TLS** (grünes Schloss 🔒)
  - https://mwart.solutions sollte funktionieren
  - Redirect von http → https ✅

- [ ] **Kontaktformular testen**
  - Test-Anfrage senden
  - In Dashboard unter `/stats.php?key=...` erscheint die Anfrage
  - *(Mail-Benachrichtigung ist optional)*

- [ ] **Statistik-Dashboard testen**
  - `/stats.php?key=...` öffnen
  - Sollte KPIs, Daily Views und Kontaktanfragen zeigen

- [ ] **Responsive Design testen**
  - Mobile (iPhone): 1-spaltig
  - Tablet (iPad): 2-spaltig
  - Desktop: 3-spaltig mit vollen Inhalten

- [ ] **Suchmaschinen-Indexierung**
  - Google Search Console: Domain verifizieren
  - Sitemap hinzufügen (optional, aktuell manuell)
  - robots.txt: erlaubt Indexierung (Standard)

- [ ] **Performance überprüfen**
  - Google PageSpeed Insights
  - Ziel: >80 auf Mobile, >90 auf Desktop

---

## 📞 Support & Wartung

- **Daten-Backup**: Das Volume `mwart-data` muss regelmäßig gesichert werden
  - Enthält: `/data/visits-*.jsonl` und `/data/messages.jsonl`
  - Coolify bietet integrierte Backup-Optionen

- **Log-Überwachung**: Coolify zeigt Apache- und PHP-Fehler in den Logs
  - Bei Problemen prüfen: **Logs** → letzte Einträge

- **Regelmäßige Updates**: 
  - PHP-Sicherheitspatches werden über das PHP-Image automatisch eingespielt
  - Code-Updates: Git Push → Coolify re-deployed automatisch

---

## 🎯 Checkliste für erste Live-Stunde

```
[ ] STATS_KEY in Coolify gesetzt
[ ] Domain auf Coolify konfiguriert
[ ] Deploy erfolgreich durchgelaufen
[ ] SSL-Zertifikat aktiv (grünes Schloss)
[ ] Homepage unter https://mwart.solutions aufrufbar
[ ] Kontaktformular funktioniert
[ ] Dashboard unter /stats.php?key=... abrufbar
[ ] Impressum mit echten Daten gefüllt
[ ] Datenschutz aktualisiert
[ ] Test-Besuche erscheinen im Dashboard
[ ] Mobile-Ansicht responsive
```

---

## 📝 Notizen für zukünftige Updates

- Neue Hersteller hinzufügen: In `index.html` zeile ~750, VENDORS-Objekt erweitern
- Layout-Änderungen: CSS startet bei Zeile ~8
- Neue Seiten: HTML-Template mit `theme.js` und `rechtliches.css` verwenden

**Fragen?** Kontakt: kontakt@mwart.solutions

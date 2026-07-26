# 🚀 COOLIFY SETUP GUIDE — mwART.solutions

Schritt-für-Schritt Anleitung zur Deployment auf Coolify.

---

## 📋 Voraussetzungen

- ✅ Coolify installiert und läuft (z.B. auf einem VPS)
- ✅ GitHub Repository erstellt: https://github.com/PureLink-Marcus/mwart.sol
- ✅ Domain verfügbar: `mwart.solutions`
- ✅ GitHub Token (optional, für private Repos)

---

## 🔧 SCHRITT 1: Neue Resource in Coolify erstellen

### 1.1 Coolify öffnen
```
https://YOUR_COOLIFY_INSTANCE/dashboard
```

### 1.2 New Resource erstellen
```
Dashboard → New Resource
```

### 1.3 Docker Compose wählen
```
Select → Docker Compose
```

### 1.4 Repository verbinden
```
GitHub Repository: https://github.com/PureLink-Marcus/mwart.sol
Branch: main
```

**Ergebnis:** Coolify clont das Repository automatisch

---

## 🔐 SCHRITT 2: Environment Variables setzen

Diese Variablen sind **PFLICHT** für Production:

### 2.1 STATS_KEY generieren
```bash
# Lokal im Terminal:
openssl rand -hex 24

# Beispiel-Output:
# a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f
```

### 2.2 In Coolify eingeben

Gehe zu:
```
Resource → Environment Variables
```

Füge diese ein:

```
STATS_KEY=<result from openssl rand -hex 24>
KONTAKT_EMPFAENGER=kontakt@mwart.solutions
KONTAKT_ABSENDER=formular@mwart.solutions
```

**Wichtig:** 
- STATS_KEY muss sehr lange sein (24 bytes hex = 48 Zeichen)
- Sicher speichern! Ohne diesen Schlüssel funktioniert das Analytics-Dashboard nicht

---

## 🌐 SCHRITT 3: Domain konfigurieren

### 3.1 Custom Domain hinzufügen
```
Resource → Settings → Domains → Add Domain
```

Eingabe:
```
Domain: mwart.solutions
```

### 3.2 Coolify übernimmt automatisch:
- ✅ HTTPS-Zertifikat (Let's Encrypt)
- ✅ Reverse-Proxy
- ✅ SSL/TLS Verschlüsselung
- ✅ Automatische Zertifikat-Erneuerung

---

## 🚀 SCHRITT 4: Deploy starten

### 4.1 Deploy-Button klicken
```
Resource → Deploy
```

### 4.2 Warten auf Deployment
```
Status: Building Docker Image (~1-2 Minuten)
Status: Starting Container (~30 Sekunden)
Status: Running ✅
```

### 4.3 Logs überprüfen
```
Resource → Logs → Live View
```

Erwarteter Output:
```
[notice] Built in 0.xxx seconds
Apache/2.4.x started
PHP 8.3.x running
```

---

## ✅ SCHRITT 5: Live-Tests

### 5.1 Homepage testen
```
https://mwart.solutions
```
Ergebnis: Seite lädt, grünes Schloss 🔒

### 5.2 Analytics-Dashboard testen
```
https://mwart.solutions/stats.php?key=YOUR_STATS_KEY
```
**Wichtig:** STATS_KEY exakt eingeben (Groß-/Kleinschreibung beachtet!)

### 5.3 Kontaktformular testen
```
https://mwart.solutions
→ Zum Footer scrollen
→ "Kontakt" Link klicken
→ Test-Anfrage senden
```

Ergebnis: 
- ✅ Formular sendet erfolgreich
- ✅ Anfrage erscheint im Dashboard (/stats.php)
- ✅ E-Mail wird versendet (optional)

### 5.4 Mobile-Test
```
iPhone/Android öffnet: https://mwart.solutions
→ Responsive Layout (1 Spalte auf Mobile)
→ Alle Links funktionieren
```

---

## 🔧 SCHRITT 6: Troubleshooting

### Problem: "Domain nicht erreichbar"
**Lösung:**
1. DNS-Records prüfen (A-Record auf Coolify-IP zeigen)
2. Warten auf DNS-Propagation (bis zu 24h)
3. Coolify-Status überprüfen (Resource läuft?)

### Problem: "STATS_KEY funktioniert nicht"
**Lösung:**
1. Überprüfe exakte STATS_KEY (copy-paste, keine Leerzeichen)
2. Coolify-Container neustarten
3. Browser-Cache clearen

### Problem: "Kontaktformular sendet nicht"
**Lösung:**
1. Browser-Console überprüfen (F12 → Console)
2. /stats.php öffnen → Kontaktanfragen anschauen
3. Anfrage ist im Dashboard? → Email-Versand ist optional, Speicherung funktioniert!

### Problem: "HTTPS-Zertifikat fehlt"
**Lösung:**
1. Warten Sie 24h (Let's Encrypt braucht Zeit)
2. Oder: Resource → SSL → Force HTTPS aktivieren
3. Domain-DNS überprüfen

---

## 📊 SCHRITT 7: Analytics-Dashboard nutzen

### 7.1 URL
```
https://mwart.solutions/stats.php?key=YOUR_STATS_KEY
```

### 7.2 Was sehen Sie?

**KPIs (oben):**
- Seitenaufrufe gesamt
- Unique Visitors (anonym)
- Aktive Tage
- Kontaktanfragen

**Charts:**
- Daily Pageviews (Bar-Chart)

**Tabellen:**
- Top Pages
- Gesehene Sektionen (#leistungen, #katalog, etc.)
- CTA Clicks (Button-Klicks)
- Referrer (Woher kommen Besuche?)
- Kampagnen (UTM-Parameter)
- Sprachen der Besucher
- Theme-Verteilung (Hell/Dunkel)
- Kontaktanfragen (neueste zuerst)

### 7.3 Monat wechseln
```
URL: https://mwart.solutions/stats.php?key=YOUR_STATS_KEY&m=2026-08
```
Format: `YYYY-MM`

---

## 🔐 SCHRITT 8: Backups & Monitoring

### 8.1 Daten-Volume sichern
```
Coolify → Resource → Volumes → mwart-data

Diese enthält:
- /data/visits-YYYY-MM.jsonl (Analytics)
- /data/messages.jsonl (Kontaktanfragen)
- /data/.ratelimit (Rate-Limit Cache)
- /data/.salt (tägliche Salt für IP-Hash)
```

**Backup-Strategie:**
- Coolify hat integrierte Backup-Funktion
- Oder: manuell via SFTP/rsync

### 8.2 Logs überwachen
```
Coolify → Resource → Logs
```

Überprüfe regelmäßig auf:
- PHP-Fehler
- Apache-Fehler
- 5xx HTTP-Errors

### 8.3 Automatische Updates
- PHP-Sicherheits-Updates: Automatisch via Docker-Image
- Anwendungs-Updates: Manual (git push → Coolify re-deploys)

---

## 🎯 NACH DEM DEPLOYMENT

### Checkliste:
```
[ ] 1. Domain lädt (https://mwart.solutions)
[ ] 2. HTTPS-Zertifikat aktiv (grünes Schloss 🔒)
[ ] 3. /stats.php?key=... funktioniert
[ ] 4. Kontaktformular testet erfolgreich
[ ] 5. Mobile-Ansicht responsive
[ ] 6. Impressum mit echten Daten korrekt
[ ] 7. Besuche im Dashboard sichtbar (nach ~1h)
[ ] 8. Google Search Console verifiziert
```

---

## 📞 SUPPORT & NOTFÄLLE

### Normale Fragen:
1. **Kontakt:** kontakt@mwart.solutions
2. **Dashboard:** https://mwart.solutions/stats.php?key=YOUR_STATS_KEY
3. **Logs:** Coolify → Resource → Logs

### Dringende Fehler:
1. **Container ist down:** Coolify → Resource → Restart
2. **Disk voll:** Coolify → Storage → Clean up old logs
3. **Rate-Limit Cache:** Delete `/data/.ratelimit` (resets counter)

---

## 🚀 FERTIGUNG!

Nach diesen Schritten läuft mwart.solutions auf Production!

```
Status: ✅ LIVE
URL: https://mwart.solutions
Analytics: https://mwart.solutions/stats.php?key=YOUR_STATS_KEY
```

**Viel Erfolg!** 🎉

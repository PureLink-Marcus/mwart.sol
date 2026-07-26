# mwART.solutions

Landingpage mit datensparsamer Besucherstatistik und Kontaktformular.
Reines PHP + Apache, keine Build-Schritte, keine externen Abhängigkeiten.

## Aufbau

| Datei | Zweck |
|---|---|
| `index.html` | Landingpage (alles inline: CSS, JS) |
| `impressum.html`, `datenschutz.html` | Rechtliches, gemeinsames `rechtliches.css` + `theme.js` |
| `track.php` | Nimmt Besucher-Ereignisse entgegen, schreibt nach `data/visits-JJJJ-MM.jsonl` |
| `stats.php` | Dashboard unter `/stats.php?key=…` |
| `kontakt.php` | Kontaktformular: speichert nach `data/messages.jsonl` und versendet E-Mail |
| `alternativen.html` | Design-Entwürfe der Hersteller-Sektion (nicht Teil des Images) |

Alle Laufzeitdaten liegen ausschließlich in `data/` — dieser Ordner ist ein
Docker-Volume und wird weder ins Repository noch ins Image aufgenommen.

## Deployment mit Coolify

1. Repository in Coolify anlegen: **New Resource → Docker Compose**, dieses Repo verbinden.
2. Unter **Environment Variables** setzen:

   | Variable | Bedeutung |
   |---|---|
   | `STATS_KEY` | **Pflicht.** Schützt `/stats.php`. Ohne diesen Wert antwortet das Dashboard mit 503. |
   | `KONTAKT_EMPFAENGER` | Zieladresse für Formularanfragen |
   | `KONTAKT_ABSENDER` | Absenderadresse der Benachrichtigungs-Mails |

   Schlüssel erzeugen:

   ```bash
   openssl rand -hex 24
   ```

3. Domain in Coolify eintragen — HTTPS-Zertifikat und Reverse-Proxy übernimmt Coolify.
   Deshalb veröffentlicht `docker-compose.yml` bewusst keinen Port nach außen.
4. Deploy starten. Das Volume `mwart-data` bleibt über alle künftigen Deployments erhalten.

## Lokal ausprobieren

```bash
docker compose up --build
```

## E-Mail-Versand

Der Container bringt keinen Mailserver mit, daher läuft `mail()` im Standard-Image
ins Leere. Anfragen gehen dabei **nicht verloren** — sie stehen immer im Dashboard
unter `/stats.php`. Für echten Versand einen SMTP-Zugang anbinden (z. B. PHPMailer
oder `msmtp` im Dockerfile ergänzen).

## Vor dem Livegang

- [ ] `STATS_KEY` in Coolify gesetzt
- [ ] Impressum: echte USt-IdNr. statt Platzhalter `DE12345678`, Registernummer nachtragen
- [ ] Datenschutz: Hostinganbieter eintragen, Auftragsverarbeitungsvertrag abschließen
- [ ] SMTP für den Mailversand einrichten

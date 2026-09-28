# OELA – Ökumenischer Lebendiger Adventskalender

<p><img src="docs/assets/oela_icon.png" alt="OELA-Logo" width="120"></p>

OELA ist eine bewusst kleine, mobile-first optimierte Web-App zur Anmeldung und Verwaltung eines Lebendigen Adventskalenders für mehrere Orte. Die öffentliche Seite zeigt die Tage 1.–23. Dezember als Kalender; freie Termine können direkt gebucht werden. Die Verwaltung erfolgt ohne Benutzerkonten über signierte Links.

## Features

- separate öffentliche Kalenderseite pro Ort
- 23 Slots vom 1.–23. Dezember
- freie Termine grün und anklickbar, belegte Termine rot
- Anmeldung mit Name, Ortsangabe, Telefon, optionaler E-Mail und Veröffentlichungseinwilligung
- Schutz vor Doppelbelegung durch serverseitige Prüfung und eindeutige Datenbankbelegung
- mobile-first Oberfläche mit Bootstrap 5
- Verwaltung pro Ort über dauerhaften signierten Link, ohne Benutzerkonten
- vollständige Admin-Liste 1–23; freie Slots können direkt über **Edit** belegt werden
- Bearbeiten und Löschen einzelner Termine; Löschen immer mit Bestätigung
- „Alle löschen“ pro Ort mit Bestätigung
- XLSX-Export pro Ort
- öffentlicher HTML-Fragment-Endpoint pro Ort für `fetch()` aus TYPO3/ELKW-Webbaukasten
- Embed veröffentlicht nur freigegebene Termine mit Name und Anschrift, niemals Telefon oder E-Mail
- SQLite als persistente Datenbank
- automatisches Erzeugen eines sicheren `APP_SECRET` beim ersten Composer-Setup
- CLI `oela` für Setup und Ausgabe der Admin-Links

## Technik

Plain PHP 8.2+, SQLite/PDO, Bootstrap 5 und PhpSpreadsheet. Bewusst kein Laravel, keine Benutzerverwaltung und kein SPA-Framework.

## Voraussetzungen

- Docker mit Docker Compose

## Installation

```bash
./oela init
# Danach APP_URL in .env anpassen.
./oela up -d
./oela admin-links
```

Nach einem Pull aktualisiert und startet `./oela update` die Container neu:

```bash
./oela update
```

Beim ersten `./oela init` wird `.env` automatisch aus `.env.example` angelegt (falls sie noch fehlt) und ein kryptografisch zufälliger `APP_SECRET` erzeugt. Einen bereits gesetzten Secret überschreibt das Setup nicht. Danach nur noch `APP_URL` in `.env` auf die echte Domain setzen. `var/` muss für PHP schreibbar sein. Die SQLite-Datenbank wird automatisch angelegt.

Die Orte werden über eine kommagetrennte `LOCATION`-Einstellung konfiguriert,
zum Beispiel `LOCATION=Nebringen,Öschelbronn,Tailfingen`. Daraus entstehen die
Slugs automatisch (`nebringen`, `oeschelbronn`, `tailfingen`); `ß` wird dabei zu
`ss`.

Die Anwendung ist anschließend unter `http://localhost:8080` erreichbar.
Der Port kann mit `APP_PORT` in `.env` geändert werden. PHP und Composer
werden innerhalb des Containers ausgeführt, beispielsweise mit `./oela composer
install`.

Tests laufen ausschließlich über den getrennten Testpfad:

```bash
./oela test
```

Der Testbefehl setzt `APP_ENV=testing` und verwendet eine kurzlebige SQLite-
Datenbank unter `/tmp/oela-testing.sqlite`; `var/advent.sqlite` wird dabei
nicht verwendet.

## Öffentliche Seiten

- `/nebringen`
- `/oeschelbronn`
- `/tailfingen`

Freie Tage 1–23 sind grün und anklickbar, belegte rot.

## Verwaltung

`php oela admin-links` erzeugt für jeden Ort den dauerhaften signierten Verwaltungslink. Dort stehen immer alle Slots 1–23. `Edit` öffnet den Slot, bei einem freien Slot als leeres Formular. Belegte Slots können nach Bestätigung gelöscht werden. Außerdem gibt es XLSX-Export und „Alle löschen“ mit Bestätigung.

Jeder Ort kann in der Verwaltung vorübergehend gesperrt oder wieder freigegeben
werden. Bei einer Sperre verschwinden die öffentlichen 1–23-Schaltflächen und
auch direkte Anmelde-URLs werden serverseitig abgewiesen.

## TYPO3 / ELKW Embed

Öffentliche Fragment-Endpunkte:

- `/embed/nebringen`
- `/embed/oeschelbronn`
- `/embed/tailfingen`

Sie liefern nur veröffentlichte belegte Termine, z. B.:

```html
<div class="lebendiger-advent-termine" data-ort="nebringen">
  <div class="lebendiger-advent-termin"><strong>01.12.</strong> Familie Maier, Dorfweg 3</div>
</div>
```

Telefon und E-Mail werden nie ausgegeben. Beispiel für vorhandenen Fetch-Code:

```js
fetch('https://advent.example.de/embed/nebringen')
  .then(r => r.text())
  .then(html => document.querySelector('#advent-termine').innerHTML = html);
```

`EMBED_ALLOWED_ORIGIN=*` ist für diese ausschließlich öffentlichen Daten unproblematisch. Falls gewünscht, kann dort stattdessen exakt die Herkunft der TYPO3-Seite eingetragen werden.

## Datenschutz

Die öffentliche Anmeldung verlangt die Einwilligung zur Veröffentlichung von Name und Adresse. Telefon und E-Mail dienen nur der Organisation und erscheinen nicht im Embed. Im Admin-Formular kann die Veröffentlichung unabhängig ein- oder ausgeschaltet werden.

Eine eigene Datenschutzerklärung bzw. Information zur Datenverarbeitung der Kirchengemeinde muss auf der späteren Website ergänzt bzw. verlinkt werden; sie ist bewusst nicht mit einem erfundenen Rechtstext in dieser App vorbelegt.

## Backup

Für ein vollständiges Datenbackup genügt die SQLite-Datei `var/advent.sqlite` (bei laufendem Schreibzugriff idealerweise mit SQLite-Backup-Verfahren sichern).

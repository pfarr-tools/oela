# AGENTS.md — OELA

## Projekt

OELA („Ökumenischer Lebendiger Adventskalender“) ist eine bewusst kleine, mobile-first optimierte Web-App zur Vergabe und Veröffentlichung von Terminen eines Lebendigen Adventskalenders in mehreren Orten.

Die Anwendung soll klein bleiben. Keine Benutzerkonten, kein SPA-Framework und kein großes PHP-Framework ergänzen, solange dafür keine zwingende fachliche Anforderung besteht.

## Technik

- PHP 8.2+
- Plain PHP, kein Laravel/Symfony
- SQLite via PDO
- Bootstrap 5 für die App-Oberfläche
- PhpSpreadsheet für XLSX-Export
- Composer nur für Abhängigkeiten und Autoloading
- Webroot: `public/`
- Persistente Daten: `var/advent.sqlite`
- CLI: `php oela ...`

## Fachliches Modell

Die Orte werden in der Anwendung konfiguriert. Aktuell:

- Nebringen (`nebringen`)
- Öschelbronn (`oeschelbronn`)
- Tailfingen (`tailfingen`)

Jeder Ort besitzt die Slots 1 bis 23 (1.–23. Dezember). In der Datenbank existieren nur belegte Slots; freie Slots werden in der Oberfläche dynamisch ergänzt.

Ein belegter Slot enthält:

- Tag
- Ort
- Name
- Ortsangabe
- Telefon
- E-Mail (optional)
- Kennzeichen/Einwilligung zur Veröffentlichung

Pro Ort und Tag darf höchstens ein Eintrag existieren. Diese Eindeutigkeit muss auch auf Datenbankebene abgesichert bleiben.

## Öffentliche Oberfläche

Route pro Ort, z. B. `/nebringen`.

- Zeigt immer 1–23.
- Frei: grün und anklickbar.
- Belegt: rot und nicht zur Anmeldung anklickbar.
- Klick auf freien Slot öffnet das Anmeldeformular.
- Vor dem Speichern serverseitig erneut prüfen, dass der Slot noch frei ist.
- Formular: Name, Ortsangabe, Telefon, optionale E-Mail, Veröffentlichungseinwilligung.
- Mobile-first und mit möglichst wenig eigenem JavaScript.

## Verwaltung

Keine User-Accounts. Jeder Ort hat einen dauerhaften, HMAC-signierten Admin-Link. Links werden mit

```bash
php oela admin-links
```

erzeugt.

Die Verwaltung zeigt immer alle Slots 1–23:

- Belegte Slots: Daten + Edit + Delete.
- Freie Slots: leer + Edit.
- Edit dient sowohl zum Anlegen in einem freien Slot als auch zum Ändern eines belegten Slots.
- Delete leert den Slot.
- XLSX-Download pro Ort.
- „Alle löschen“ löscht alle Einträge des jeweiligen Orts.
- Jede Löschaktion braucht eine ausdrückliche Bestätigung.

## Öffentlicher Embed

Pro Ort gibt es `/embed/{ort}`. Dieser Endpoint ist für `fetch()` aus dem ELKW/TYPO3-Webbaukasten gedacht und liefert nur ein HTML-Fragment, kein vollständiges Dokument.

Ausgegeben werden ausschließlich belegte Termine mit aktivierter Veröffentlichung, chronologisch sortiert, z. B.:

```html
<div class="lebendiger-advent-termin"><strong>01.12.</strong> Familie Maier, Dorfweg 3</div>
```

Telefonnummer und E-Mail dürfen niemals im Embed erscheinen. Das Fragment soll ohne Bootstrap- oder JavaScript-Abhängigkeit funktionieren und möglichst semantisch/minimal bleiben.

## Sicherheit und Datenschutz

- Ausschließlich Prepared Statements für variable SQL-Werte.
- CSRF-Schutz bei allen zustandsändernden Formularen.
- Serverseitige Validierung ist maßgeblich; Browservalidierung nur ergänzend.
- Admin-Signaturen mit HMAC und `hash_equals()` prüfen.
- `APP_SECRET` nie ins Repository committen.
- Keine personenbezogenen Daten in Logs schreiben, soweit vermeidbar.
- Telefon und E-Mail niemals öffentlich ausgeben.
- Veröffentlichung von Name und Anschrift nur, wenn das entsprechende Kennzeichen gesetzt ist.
- Löschaktionen ausschließlich per POST und mit CSRF-Schutz.

## CLI / Setup

`composer install` ruft `php oela init` auf. `init`:

- legt `.env` aus `.env.example` an, falls sie fehlt;
- erzeugt einen kryptografisch sicheren `APP_SECRET`, falls keiner gesetzt ist;
- überschreibt einen vorhandenen Secret nicht.

Wichtige Befehle:

```bash
php oela init
php oela admin-links
```

Nach dem ersten Setup muss `APP_URL` in `.env` auf die echte öffentliche Basis-URL gesetzt werden.

## Entwicklungsregeln

- Bestehende Einfachheit bewahren; neue Abstraktionen nur bei echtem Nutzen.
- Keine User-/Rollenverwaltung ohne neue fachliche Anforderung.
- Keine Vue-/React-/Inertia-Abhängigkeit einführen.
- Keine externen Services voraussetzen, wenn SQLite/PHP genügt.
- Öffentliche URLs und vorhandene Admin-Links möglichst rückwärtskompatibel halten.
- Änderungen am Datenbankschema müssen bestehende Installationen berücksichtigen.
- HTML escapen, sofern Inhalte nicht ausdrücklich als vertrauenswürdiges eigenes Markup erzeugt werden.
- UI weiterhin mobile-first und Bootstrap-basiert halten.
- Texte und Oberfläche sind deutschsprachig.
- Commits verwenden Conventional Commits mit einer deutschen Commit-Botschaft.

## Vor Abschluss einer Änderung

Mindestens prüfen:

```bash
find . -name '*.php' -print0 | xargs -0 -n1 php -l
php oela init
php oela admin-links
```

Bei Änderungen an Anmeldung/Slots zusätzlich manuell oder automatisiert prüfen:

1. freier Slot kann angemeldet werden;
2. derselbe Slot kann nicht doppelt vergeben werden;
3. Admin-Edit kann freien Slot belegen und bestehenden ändern;
4. Delete gibt den Slot wieder frei;
5. Embed enthält nur freigegebene Termine und keine Kontaktdaten;
6. XLSX-Export enthält die erwarteten Daten;
7. ungültiger Admin-Link wird abgewiesen.

## Lizenz

GPL-3.0. Siehe `LICENSE`.

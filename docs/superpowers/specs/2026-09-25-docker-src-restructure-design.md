# OELA Docker- und `src/`-Struktur

![OELA-Logo](../../assets/oela_icon.png)

## Ziel

OELA soll aus dem Repository-Root über `./oela` bedienbar sein, während alle
Anwendungsquellen unter `src/` liegen und die Webanwendung in einem PHP-
Container läuft. Tests müssen einen ausdrücklich getrennten SQLite-Pfad nutzen
und dürfen die produktive Datei `var/advent.sqlite` nicht öffnen.

## Randbedingungen

- Plain PHP 8.2+ ohne zusätzliches Webframework oder SPA.
- SQLite bleibt die einzige Datenbank; `var/` bleibt persistent und liegt
  außerhalb von `src/`.
- Bootstrap, PhpSpreadsheet, bestehende URLs, CLI-Befehle und Admin-Links
  bleiben erhalten.
- Docker Compose ist der Entwicklungs- und Testzugang; PHP- und Composer-
  Aufrufe werden über `./oela` in den Container geleitet.
- Die Root-`.env` enthält lokale Laufzeitwerte und bleibt unversioniert.

## Struktur und Laufzeit

Die Dateien `composer.json`, `composer.lock`, `public/`, `templates/`, PHP-
Quellen und Tests werden nach `src/` verschoben. Root-Dateien beschreiben den
Entwicklungsbetrieb: `Dockerfile`, `compose.yaml`, `.dockerignore`, README und
der Wrapper `./oela`. Der Container arbeitet in `/var/www/html`, bind-mountet
`./src` dorthin und mountet `./var` als `/var/www/html/var`. Der PHP-
Entwicklungsserver veröffentlicht `public/` auf einem konfigurierbaren Port.

Das Image installiert die Composer-Abhängigkeiten aus `src/composer.lock`.
Ein benanntes Vendor-Volume verhindert, dass der Source-Mount die im Image
installierten Abhängigkeiten verdeckt. Der Container verwendet die Root-`.env`
als Environment-Datei; die Anwendung akzeptiert zusätzlich explizite
Container-Umgebungsvariablen mit Vorrang vor Datei-Werten.

## Wrapper-Befehle

`./oela` bietet mindestens `up`, `down`, `restart`, `status`/`ps`, `logs`,
`shell`, `composer`, `init`, `admin-links` und `test`. `init` erzeugt die Root-
`.env` aus `.env.example` und den Secret-Wert wie bisher. `admin-links` und
`init` führen den bestehenden PHP-CLI-Code im Container aus. Der Wrapper bleibt
die zentrale Schnittstelle und dokumentiert keine direkten Host-PHP-Aufrufe
mehr.

## Testisolation

`./oela test` startet einen kurzlebigen Container mit `APP_ENV=testing`, einem
festen Test-Secret und `DB_PATH=/tmp/oela-testing.sqlite`. Der Testpfad darf
nicht aus `DB_PATH` der Root-`.env` übernommen werden. Ein automatisierter Test
prüft, dass die Testinstanz einen anderen Datenbankpfad verwendet und eine
Eintragung nicht in `var/advent.sqlite` landet. Die Testdatenbank wird beim
Testlauf neu angelegt und mit dem Container verworfen.

## Verifikation

Die Umsetzung wird mit `docker compose config`, Shell-Syntaxprüfung, PHP-
Syntaxprüfung, `./oela init`, `./oela admin-links` und `./oela test` geprüft.
Zusätzlich wird der Quellbaum auf Root-Anwendungsdateien kontrolliert und der
Webcontainer per HTTP-Smoke-Test gestartet, sofern Docker im Arbeitsumfeld
verfügbar ist.

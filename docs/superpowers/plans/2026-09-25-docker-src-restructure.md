# Docker- und `src/`-Struktur Implementation Plan

![OELA-Logo](../../assets/oela_icon.png)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Verschiebe alle OELA-Anwendungsquellen nach `src/`, betreibe OELA per Docker und erzwinge eine vom Produktionspfad getrennte Testdatenbank.

**Architecture:** Ein PHP-8.4-CLI-Container stellt den eingebauten Webserver bereit. Root-Dateien steuern Docker und bleiben Bedienoberfläche; `src/` enthält Composer-Projekt, Webroot, Templates und Tests; `var/` bleibt als SQLite-Datenvolume außerhalb von `src`.

**Tech Stack:** PHP 8.4, SQLite/PDO, Composer, Docker Compose, Bash, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-09-25-docker-src-restructure-design.md`

## Global Constraints

- Plain PHP 8.2+ ohne Laravel/Symfony/Vue/React.
- `src/` enthält `composer.json`, `composer.lock`, `public/`, `templates/`, PHP-Quellen und Tests.
- `var/advent.sqlite` bleibt persistent und wird von Tests nicht geöffnet.
- `./oela` ist die zentrale Schnittstelle für Docker, Setup, Admin-Links und Tests.
- Root-`.env` bleibt unversioniert; `APP_SECRET` wird nicht überschrieben.

## Review Focus

- Ein vorhandener `DB_PATH` in `.env` darf Testläufe nicht auf die Produktionsdatei lenken; Test für expliziten Testpfad.
- Der Source-Mount darf `vendor/` nicht verdecken; Compose-/Container-Smoke-Test.
- Ein bestehender `.env`-Secret darf durch `init` nicht verändert werden; vorhandenen Test beibehalten bzw. ausführen.
- Nach der Verschiebung müssen CLI- und Webpfade ohne Root-`public/` oder Root-`composer.json` funktionieren; Syntax-/CLI-/HTTP-Prüfungen.
- Der persistente `var/`-Mount muss für SQLite beschreibbar bleiben; Init- und Testlauf prüfen.

---

### Task 1: Testisolation im Anwendungskern

**Files:**
- Modify: `src/App.php`
- Create: `src/tests/AppTest.php`
- Modify: `src/composer.json`
- Modify: `src/composer.lock`

**Interfaces:** `App` liest explizite Prozessumgebungsvariablen vor `.env`-Werten; `DB_PATH` bleibt der Datenbankpfad-Schalter.

- [ ] **Step 1: Write the failing test** — Teste, dass ein gesetztes `DB_PATH` gegenüber dem Wert aus `.env` Vorrang hat und eine Speicherung nur in der Testdatei erscheint.
- [ ] **Step 2: Run test to verify it fails** — `php src/tests/AppTest.php` oder PHPUnit-Aufruf; erwarteter Fehler: aktueller `.env`-Pfad wird benutzt bzw. Testklasse fehlt.
- [ ] **Step 3: Write minimal implementation** — Lade `getenv()` vor den Datei-Werten und ergänze Testautoload/ PHPUnit-Dev-Abhängigkeit.
- [ ] **Step 4: Run test to verify it passes** — PHPUnit-Test gegen einen temporären SQLite-Pfad.
- [ ] **Step 5: Commit** — `git add src && git commit -m "test: trenne Testdatenbank vom Produktionspfad"`.

### Task 2: Quellen nach `src/` verschieben und Docker-Dateien ergänzen

**Files:**
- Move: `composer.json`, `composer.lock`, `public/`, `templates/` to `src/`
- Modify: `src/composer.json`
- Create: `Dockerfile`, `compose.yaml`, `.dockerignore`
- Modify: `.env.example`, `.gitignore`

**Interfaces:** Compose service `app` mountet `./src` nach `/var/www/html` und `./var` nach `/var/www/html/var`; der Container lauscht auf Port 8000.

- [ ] **Step 1: Write the failing structure check** — Prüfe per Shell-Test, dass `src/composer.json`, `src/public/index.php` und `src/templates/layout.php` existieren und Root-Anwendungsdateien fehlen.
- [ ] **Step 2: Run test to verify it fails** — Vor dem Verschieben muss die Prüfung wegen der aktuellen Root-Dateien fehlschlagen.
- [ ] **Step 3: Write minimal implementation** — Verschiebe die genannten Pfade; ergänze PHP-Image mit Composer, Compose-Mounts, Vendor-Volume und `.dockerignore`; passe `src/composer.json`-Scripts auf `oela` im Source-Root an.
- [ ] **Step 4: Run test to verify it passes** — `docker compose config` und Shell-Strukturprüfung.
- [ ] **Step 5: Commit** — `git add -A && git commit -m "build: verschiebe OELA nach src und ergänze Docker"`.

### Task 3: Zentralen `./oela`-Wrapper und Testbefehl umsetzen

**Files:**
- Modify: `oela`
- Modify: `README.md`
- Modify: `src/App.php` if required by CLI environment handling

**Interfaces:** `./oela init`, `./oela admin-links`, `./oela up`, `./oela down`, `./oela status`, `./oela logs`, `./oela shell`, `./oela composer`, and `./oela test`.

- [ ] **Step 1: Write the failing wrapper tests** — Shell-Test prüft `bash -n`, vorhandene Befehle und dass `test` `APP_ENV=testing` sowie einen separaten `DB_PATH` an Compose übergibt.
- [ ] **Step 2: Run test to verify it fails** — Aktuelles PHP-CLI-Skript ist kein Bash-Wrapper und bietet die Docker-Befehle nicht.
- [ ] **Step 3: Write minimal implementation** — Ersetze `oela` durch ein Bash-Wrapper-Skript; `init` erzeugt Root-`.env` bei Bedarf und startet den Container; `test` nutzt einen kurzlebigen Container mit `/tmp/oela-testing.sqlite`; `admin-links` delegiert an `php oela admin-links` im Container.
- [ ] **Step 4: Run test to verify it passes** — `bash -n oela`; `./oela init`; `./oela admin-links`; `./oela test`.
- [ ] **Step 5: Commit** — `git add oela README.md src && git commit -m "feat: steuere OELA über Docker-Wrapper"`.

### Task 4: Gesamtverifikation und Abschlussprüfung

**Files:** keine erwarteten neuen Produktdateien.

- [ ] **Step 1: Run PHP syntax checks** — `find src -name '*.php' -print0 | xargs -0 -n1 php -l` beziehungsweise Container-Variante.
- [ ] **Step 2: Run application checks** — `./oela init`, `./oela admin-links`, `./oela test` und `docker compose config`.
- [ ] **Step 3: Run HTTP smoke test** — `./oela up -d`, HTTP-Aufruf der Startseite, anschließend `./oela down`.
- [ ] **Step 4: Inspect final diff** — `git diff --check`, `git status --short`, Prüfung auf versehentlich verfolgte `.env`-/SQLite-Dateien.
- [ ] **Step 5: Commit verification-only adjustments if needed** — Nur notwendige Korrekturen mit passendem semantischem Commit.

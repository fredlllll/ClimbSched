# ClimbSched MVP (API-first)

Dieses MVP ist in plain PHP + PostgreSQL umgesetzt und trennt Seiten, Routing und API voneinander.

> Hinweis: Ein echtes Laravel-Setup konnte in dieser Umgebung nicht installiert werden, da Composer-Zugriff auf Packagist (HTTP 403) blockiert ist.

## Funktionen
- Registrierung/Login mit E-Mail + Passwort
- E-Mail-Verifizierung ist Pflicht vor Nutzung der Kalenderseite
- Passwort-Reset per E-Mail-Link
- Kalenderseite zeigt 8 Tage (gestern, heute, +6 Tage)
- Termine anlegen, beitreten, verlassen
- Löschen nur durch Ersteller
- Speicherung in UTC (`TIMESTAMPTZ`), Anzeige im Browser in lokaler Zeit
- Frontend kommuniziert per JSON-API mit dem Backend

## Struktur
- `public/index.php`: Front Controller + Routing
- `src/Http/*Controller.php`: API- und Page-Controller
- `public/pages/*.html`: getrennte Seiten
- `public/app.ts`: Frontend-Logik (API-Calls)

## Setup
1. Datenbank anlegen und Schema einspielen:
   ```bash
   psql -U postgres -d climbsched -f database/schema.sql
   ```
2. Umgebungsvariablen setzen: `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, optional `APP_URL`
3. App starten:
   ```bash
   php -S 0.0.0.0:8000 -t public
   ```

Mails werden für lokale Entwicklung nach `storage/mail/mail.log` geschrieben.

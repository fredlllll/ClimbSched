# ClimbSched (Laravel)

ClimbSched ist auf Laravel umgestellt (inkl. Eloquent ORM) und kann lokal über **Apache2** betrieben werden.

## MVP-Funktionen
- Registrierung/Login mit E-Mail + Passwort
- E-Mail-Verifizierung erforderlich vor Event-Nutzung
- Passwort-Reset per E-Mail
- 8-Tage-Ansicht: gestern + heute + nächste 6 Tage
- Termine anlegen, beitreten, verlassen
- Löschen nur durch Ersteller
- Speicherung von Terminen in UTC, Anzeige lokal im Browser

## Setup
```bash
cp .env.example .env
php artisan key:generate
```

DB in `.env` auf PostgreSQL setzen (`DB_CONNECTION=pgsql`, Host/User/Pass/DB) und dann:

```bash
php artisan migrate
```

## Apache2 starten (empfohlen)
1. Projekt z. B. nach `/var/www/climbsched` legen.
2. VHost automatisch einrichten:
   ```bash
   ./scripts/apache-setup.sh /var/www/climbsched
   ```
3. Hosts-Datei ergänzen:
   ```text
   127.0.0.1 climbsched.local
   ```
4. App im Browser öffnen: `http://climbsched.local`

Die bereitgestellte VHost-Datei liegt hier:
- `deploy/apache/climbsched.conf`

## Fallback (nur Dev)
Falls Apache lokal nicht verfügbar ist:
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

## Mail
Für lokale Entwicklung kann `MAIL_MAILER=log` verwendet werden; Verifizierungs-/Reset-Mails landen dann in `storage/logs/laravel.log`.

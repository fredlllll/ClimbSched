# ClimbSched (Laravel)

ClimbSched ist jetzt auf Laravel umgestellt (inkl. Eloquent ORM).

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
php artisan serve --host=0.0.0.0 --port=8000
```

## Mail
Für lokale Entwicklung kann `MAIL_MAILER=log` verwendet werden; Verifizierungs-/Reset-Mails landen dann in `storage/logs/laravel.log`.

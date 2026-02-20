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
php artisan migrate
```

Datenbank in `.env` für MySQL konfigurieren:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=climbsched
DB_USERNAME=dein_user
DB_PASSWORD=dein_passwort
```

## Apache2 starten (empfohlen)
1. Apache + PHP-Modul installieren (Ubuntu/Debian):
   ```bash
   sudo apt-get update
   sudo apt-get install -y apache2 libapache2-mod-php php8.3-xml php8.3-curl php8.3-mbstring php8.3-mysql
   ```
2. Projekt z. B. nach `/var/www/climbsched` legen.
3. VHost automatisch einrichten:
   ```bash
   ./scripts/apache-setup.sh /var/www/climbsched
   ```
4. Schreibrechte für Laravel setzen:
   ```bash
   sudo chown -R www-data:www-data storage bootstrap/cache
   sudo chmod -R ug+rwX storage bootstrap/cache
   ```
5. Hosts-Datei ergänzen:
   ```text
   127.0.0.1 climbsched.local
   ```
6. App öffnen: `http://climbsched.local`

Die bereitgestellte VHost-Datei liegt hier:
- `deploy/apache/climbsched.conf`

## Fallback (nur Dev)
Falls Apache lokal nicht verfügbar ist:
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

## Mail
Für lokale Entwicklung kann `MAIL_MAILER=log` verwendet werden; Verifizierungs-/Reset-Mails landen dann in `storage/logs/laravel.log`.

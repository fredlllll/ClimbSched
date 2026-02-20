<?php

declare(strict_types=1);

final class EventController
{
    private static function requireUser(bool $verified = true): ?array
    {
        $user = currentUser();
        if (!$user) {
            jsonResponse(['error' => 'Nicht eingeloggt'], 401);

            return null;
        }

        if ($verified && !$user['email_verified_at']) {
            jsonResponse(['error' => 'E-Mail nicht bestätigt'], 403);

            return null;
        }

        return $user;
    }

    public static function list(): void
    {
        $user = self::requireUser();
        if (!$user) {
            return;
        }

        $start = (string)($_GET['start'] ?? '');
        $end = (string)($_GET['end'] ?? '');
        if ($start === '' || $end === '') {
            jsonResponse(['error' => 'start/end erforderlich'], 422);

            return;
        }

        $stmt = db()->prepare('SELECT e.*, u.name AS creator_name,
            EXISTS(SELECT 1 FROM event_participants ep WHERE ep.event_id = e.id AND ep.user_id = :uid) AS joined,
            (SELECT COUNT(*) FROM event_participants ep2 WHERE ep2.event_id = e.id) AS participants
            FROM climbing_events e
            JOIN users u ON u.id = e.creator_id
            WHERE e.starts_at_utc >= :start AND e.starts_at_utc < :end
            ORDER BY e.starts_at_utc ASC');
        $stmt->execute(['uid' => $user['id'], 'start' => $start, 'end' => $end]);

        $events = array_map(static function (array $event): array {
            return [
                'id' => (int)$event['id'],
                'creator_id' => (int)$event['creator_id'],
                'creator_name' => $event['creator_name'],
                'gym_name' => $event['gym_name'],
                'starts_at_utc' => (new DateTimeImmutable($event['starts_at_utc']))->format(DateTimeInterface::ATOM),
                'notes' => $event['notes'],
                'participants' => (int)$event['participants'],
                'joined' => ((int)$event['joined']) === 1,
            ];
        }, $stmt->fetchAll());

        jsonResponse(['events' => $events]);
    }

    public static function create(): void
    {
        $user = self::requireUser();
        if (!$user) {
            return;
        }

        $body = requestJson();
        if (!requireCsrf($body)) {
            jsonResponse(['error' => 'CSRF token mismatch'], 419);

            return;
        }

        $gym = trim((string)($body['gym_name'] ?? ''));
        $startsLocal = (string)($body['starts_local'] ?? '');
        $notes = trim((string)($body['notes'] ?? ''));

        if ($gym === '' || $startsLocal === '') {
            jsonResponse(['error' => 'Halle und Startzeit erforderlich'], 422);

            return;
        }

        $local = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $startsLocal);
        if (!$local) {
            jsonResponse(['error' => 'Ungültiges Zeitformat'], 422);

            return;
        }

        $utc = $local->setTimezone(new DateTimeZone('UTC'));

        $pdo = db();
        $pdo->beginTransaction();
        $pdo->prepare('INSERT INTO climbing_events (creator_id, gym_name, starts_at_utc, notes) VALUES (:creator_id, :gym, :starts, :notes)')
            ->execute([
                'creator_id' => $user['id'],
                'gym' => $gym,
                'starts' => $utc->format('Y-m-d H:i:sP'),
                'notes' => $notes !== '' ? $notes : null,
            ]);
        $eventId = (int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO event_participants (event_id, user_id) VALUES (:event_id, :user_id)')
            ->execute(['event_id' => $eventId, 'user_id' => $user['id']]);
        $pdo->commit();

        jsonResponse(['ok' => true]);
    }

    public static function join(): void
    {
        $user = self::requireUser();
        if (!$user) {
            return;
        }

        $body = requestJson();
        if (!requireCsrf($body)) {
            jsonResponse(['error' => 'CSRF token mismatch'], 419);

            return;
        }

        $eventId = (int)($body['event_id'] ?? 0);
        if ($eventId <= 0) {
            jsonResponse(['error' => 'event_id fehlt'], 422);

            return;
        }

        db()->prepare('INSERT INTO event_participants (event_id, user_id) VALUES (:event_id, :user_id) ON CONFLICT DO NOTHING')
            ->execute(['event_id' => $eventId, 'user_id' => $user['id']]);

        jsonResponse(['ok' => true]);
    }

    public static function leave(): void
    {
        $user = self::requireUser();
        if (!$user) {
            return;
        }

        $body = requestJson();
        if (!requireCsrf($body)) {
            jsonResponse(['error' => 'CSRF token mismatch'], 419);

            return;
        }

        $eventId = (int)($body['event_id'] ?? 0);
        if ($eventId <= 0) {
            jsonResponse(['error' => 'event_id fehlt'], 422);

            return;
        }

        db()->prepare('DELETE FROM event_participants WHERE event_id = :event_id AND user_id = :user_id')
            ->execute(['event_id' => $eventId, 'user_id' => $user['id']]);

        jsonResponse(['ok' => true]);
    }

    public static function delete(): void
    {
        $user = self::requireUser();
        if (!$user) {
            return;
        }

        $body = requestJson();
        if (!requireCsrf($body)) {
            jsonResponse(['error' => 'CSRF token mismatch'], 419);

            return;
        }

        $eventId = (int)($body['event_id'] ?? 0);
        if ($eventId <= 0) {
            jsonResponse(['error' => 'event_id fehlt'], 422);

            return;
        }

        db()->prepare('DELETE FROM climbing_events WHERE id = :id AND creator_id = :creator_id')
            ->execute(['id' => $eventId, 'creator_id' => $user['id']]);

        jsonResponse(['ok' => true]);
    }
}

<?php

namespace Crm\Services;

/** In-app notifications with duplicate prevention (unique dedupe_key) and per-user preferences. */
final class NotificationService
{
    /** @return int|null id, or null when suppressed (duplicate or user opted out) */
    public static function notify(int $userId, string $event, string $title, ?string $body = null, ?string $link = null, ?string $dedupeKey = null, int $isDemo = 0): ?int
    {
        $db   = Crm::db();
        $pref = $db->table('crm_notification_prefs')->where(['user_id' => $userId, 'event_type' => $event])->get()->getRowArray();
        if ($pref && (int) $pref['in_app'] === 0) {
            return null;
        }
        if ($dedupeKey !== null && $db->table('crm_notifications')->where('dedupe_key', $dedupeKey)->countAllResults() > 0) {
            return null;
        }
        $db->table('crm_notifications')->insert([
            'user_id' => $userId, 'event_type' => $event, 'title' => mb_substr($title, 0, 200), 'body' => $body ? mb_substr($body, 0, 500) : null,
            'link' => $link, 'dedupe_key' => $dedupeKey, 'is_demo' => $isDemo, 'created_at' => Crm::now(),
        ]);

        return (int) $db->insertID();
    }

    /** Notify every active user holding one of the roles. Returns count created. */
    public static function notifyRoles(array $roleSlugs, string $event, string $title, ?string $body = null, ?string $link = null, ?string $dedupePrefix = null): int
    {
        $rows = Crm::db()->query(
            'SELECT DISTINCT u.id FROM admin_users u JOIN user_roles ur ON ur.user_id = u.id JOIN roles r ON r.id = ur.role_id
             WHERE u.is_active = 1 AND r.slug IN ?',
            [$roleSlugs]
        )->getResultArray();
        $n = 0;
        foreach ($rows as $r) {
            $n += self::notify((int) $r['id'], $event, $title, $body, $link, $dedupePrefix ? $dedupePrefix . ':' . $r['id'] : null) ? 1 : 0;
        }

        return $n;
    }

    public static function unreadCount(int $userId): int
    {
        return Crm::db()->table('crm_notifications')->where('user_id', $userId)->where('read_at', null)->countAllResults();
    }

    public static function markRead(int $userId, ?int $id = null): void
    {
        $b = Crm::db()->table('crm_notifications')->where('user_id', $userId)->where('read_at', null);
        if ($id) {
            $b->where('id', $id);
        }
        $b->update(['read_at' => Crm::now()]);
    }
}

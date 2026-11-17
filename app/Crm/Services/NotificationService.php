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

    /**
     * Central Event Dispatcher: dispatches in-app notifications and queues matching emails.
     */
    public static function trigger(string $event, array $payload): void
    {
        $db = Crm::db();
        $msg = $payload['message'] ?? 'Transaction notification';
        $title = $payload['title'] ?? match ($event) {
            'quotation.approval_requested' => 'Commercial Approval Requested',
            'quotation.approved' => 'Quotation Approved',
            'quotation.rejected' => 'Quotation Rejected',
            'quotation.revision_requested' => 'Quotation Returned for Revision',
            'quotation.clarification_requested' => 'Approval Clarification Requested',
            'lead.assigned' => 'New Opportunity Assigned',
            'target.assigned' => 'Sales Target Assigned',
            'order.confirmed' => 'Sales Order Confirmed',
            default => crm_label($event),
        };

        $link = $payload['link'] ?? match ($event) {
            'quotation.approval_requested', 'quotation.clarification_requested' => ! empty($payload['approval_id']) ? 'crm/approvals/' . (int) $payload['approval_id'] : 'crm/approvals',
            'quotation.approved', 'quotation.rejected', 'quotation.revision_requested' => ! empty($payload['quotation_id']) ? 'crm/quotations/' . (int) $payload['quotation_id'] : 'crm/quotations',
            'lead.assigned' => ! empty($payload['enquiry_id']) ? 'crm/enquiries/' . (int) $payload['enquiry_id'] : 'crm/enquiries',
            'target.assigned' => 'crm/targets',
            'order.confirmed' => ! empty($payload['order_id']) ? 'crm/orders/' . (int) $payload['order_id'] : 'crm/orders',
            default => 'crm',
        };

        $recipients = [];
        if (! empty($payload['recipient_id'])) {
            $recipients[] = (int) $payload['recipient_id'];
        } elseif (! empty($payload['recipient_user_id'])) {
            $recipients[] = (int) $payload['recipient_user_id'];
        } elseif (! empty($payload['user_id'])) {
            $recipients[] = (int) $payload['user_id'];
        } elseif (! empty($payload['owner_id'])) {
            $recipients[] = (int) $payload['owner_id'];
        } elseif (str_contains($event, 'approval_requested')) {
            $rows = $db->query("SELECT DISTINCT u.id FROM admin_users u JOIN user_roles ur ON ur.user_id = u.id JOIN roles r ON r.id = ur.role_id WHERE u.is_active = 1 AND r.name IN ('admin', 'sales_manager', 'business_admin')")->getResultArray();
            foreach ($rows as $r) {
                $recipients[] = (int) $r['id'];
            }
        }

        $dedupePrefix = $event . ':' . ($payload['quotation_id'] ?? ($payload['enquiry_id'] ?? time()));
        foreach ($recipients as $uid) {
            self::notify($uid, $event, $title, $msg, $link, $dedupePrefix . ':' . $uid);
        }
    }
}

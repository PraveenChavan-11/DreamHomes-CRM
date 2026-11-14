<?php

namespace Crm\Services;

/** Follow-ups (scheduled tasks), activities (immutable interaction log), site visits and measurements. */
final class ActivityService
{
    public const TYPES = ['call', 'meeting', 'whatsapp', 'email', 'visit', 'other'];

    private static function enquiry(int $id, array $actor): array
    {
        $e = Crm::db()->table('crm_enquiries')->where('id', $id)->get()->getRowArray() ?? throw new CrmException('Enquiry not found.');
        if (! AccessScope::canSeeEnquiry($actor, $id)) {
            throw new CrmException('You do not have access to this enquiry.');
        }

        return $e;
    }

    /** Immutable log entry. */
    public static function log(int $customerId, ?int $enquiryId, string $type, string $summary, ?int $userId, ?string $outcome = null, ?string $details = null, ?string $at = null, ?int $followupId = null, int $isDemo = 0): int
    {
        $db = Crm::db();
        $db->table('crm_activities')->insert([
            'customer_id' => $customerId, 'enquiry_id' => $enquiryId, 'user_id' => $userId, 'activity_type' => $type, 'summary' => mb_substr($summary, 0, 500),
            'details' => $details, 'outcome' => $outcome, 'occurred_at' => $at ?? Crm::now(), 'followup_id' => $followupId, 'is_demo' => $isDemo, 'created_at' => Crm::now(),
        ]);

        return (int) $db->insertID();
    }

    /** Record a customer contact (call/meeting/whatsapp/email). First contact moves assigned → contacted. */
    public static function recordContact(int $enquiryId, array $actor, string $type, string $summary, ?string $outcome = null, ?string $at = null): int
    {
        if (! AccessScope::can($actor, 'followups.manage')) {
            throw new CrmException('You do not have permission to record activities.');
        }
        if (! in_array($type, ['call', 'meeting', 'whatsapp', 'email', 'note'], true) || trim($summary) === '') {
            throw CrmException::validation(['summary' => 'Activity type and summary are required.']);
        }

        return (int) Crm::tx(static function ($db) use ($enquiryId, $actor, $type, $summary, $outcome, $at) {
            $e  = self::enquiry($enquiryId, $actor);
            $id = self::log((int) $e['customer_id'], $enquiryId, $type, $summary, $actor['id'], $outcome, null, $at, null, (int) $e['is_demo']);
            AuditService::workflow('activity.recorded', 'activity', $id, [
                'customer_id' => (int) $e['customer_id'], 'enquiry_id' => $enquiryId, 'entity_ref' => $e['enquiry_no'], 'user_id' => $actor['id'],
                'new' => ['type' => $type, 'summary' => $summary, 'outcome' => $outcome], 'is_demo' => (int) $e['is_demo'],
            ]);
            if ($e['status'] === 'assigned' && $type !== 'note') {
                WorkflowService::transition($enquiryId, 'contacted', $actor, 'First customer contact');
            }

            return $id;
        });
    }

    public static function scheduleFollowup(int $enquiryId, array $actor, string $dueAt, string $type = 'call', ?string $remarks = null, ?int $assignTo = null, ?string $nextAction = null): int
    {
        if (! AccessScope::can($actor, 'followups.manage')) {
            throw new CrmException('You do not have permission to manage follow-ups.');
        }
        if (! in_array($type, self::TYPES, true) || strtotime($dueAt) === false) {
            throw CrmException::validation(['due_at' => 'A valid date/time and activity type are required.']);
        }

        return (int) Crm::tx(static function ($db) use ($enquiryId, $actor, $dueAt, $type, $remarks, $assignTo, $nextAction) {
            $e = self::enquiry($enquiryId, $actor);
            if (! WorkflowService::isActive($e['status'])) {
                throw new CrmException('Follow-ups can only be scheduled on active enquiries.');
            }
            $to = $assignTo ?: ($e['current_owner_id'] ?: $actor['id']);
            $db->table('crm_followups')->insert([
                'customer_id' => $e['customer_id'], 'enquiry_id' => $enquiryId, 'assigned_to' => $to, 'activity_type' => $type, 'due_at' => date('Y-m-d H:i:s', strtotime($dueAt)),
                'status' => 'pending', 'remarks' => $remarks, 'next_action' => $nextAction, 'created_by' => $actor['id'], 'is_demo' => $e['is_demo'],
            ]);
            $id = (int) $db->insertID();
            AuditService::workflow('followup.scheduled', 'followup', $id, [
                'customer_id' => (int) $e['customer_id'], 'enquiry_id' => $enquiryId, 'entity_ref' => $e['enquiry_no'], 'user_id' => $actor['id'],
                'new' => ['due_at' => $dueAt, 'type' => $type, 'assigned_to' => $to], 'is_demo' => (int) $e['is_demo'],
            ]);

            return $id;
        });
    }

    private static function followup(int $id, array $actor): array
    {
        $f = Crm::db()->query('SELECT * FROM crm_followups WHERE id = ? FOR UPDATE', [$id])->getRowArray() ?? throw new CrmException('Follow-up not found.');
        if ($f['enquiry_id']) {
            self::enquiry((int) $f['enquiry_id'], $actor);
        } elseif (! AccessScope::isSuper($actor) && ! in_array((int) $f['assigned_to'], AccessScope::visibleUserIds($actor) ?? [(int) $f['assigned_to']], true)) {
            throw new CrmException('You do not have access to this follow-up.');
        }

        return $f;
    }

    /** Completing never deletes: row is kept as status=completed and an immutable activity is logged. Optionally schedules the next one. */
    public static function completeFollowup(int $id, array $actor, string $outcome, ?string $remarks = null, ?string $nextDueAt = null, ?string $nextAction = null, ?string $at = null): ?int
    {
        if (trim($outcome) === '') {
            throw CrmException::validation(['outcome' => 'Outcome is required to complete a follow-up.']);
        }

        return Crm::tx(static function ($db) use ($id, $actor, $outcome, $remarks, $nextDueAt, $nextAction, $at) {
            $f = self::followup($id, $actor);
            if (! in_array($f['status'], ['pending', 'missed'], true)) {
                throw new CrmException('Only pending or missed follow-ups can be completed.');
            }
            $when = $at ?? Crm::now();
            $db->table('crm_followups')->where('id', $id)->update(['status' => 'completed', 'outcome' => mb_substr($outcome, 0, 255), 'remarks' => $remarks ?? $f['remarks'], 'completed_at' => $when]);
            $act = self::log((int) $f['customer_id'], $f['enquiry_id'] ? (int) $f['enquiry_id'] : null, in_array($f['activity_type'], ['call', 'meeting', 'whatsapp', 'email'], true) ? $f['activity_type'] : 'note', 'Follow-up completed: ' . $outcome, $actor['id'], $outcome, $remarks, $when, $id, (int) $f['is_demo']);
            AuditService::workflow('followup.completed', 'followup', $id, [
                'customer_id' => (int) $f['customer_id'], 'enquiry_id' => $f['enquiry_id'] ? (int) $f['enquiry_id'] : null, 'user_id' => $actor['id'],
                'prev' => ['status' => $f['status']], 'new' => ['status' => 'completed', 'outcome' => $outcome, 'activity_id' => $act], 'is_demo' => (int) $f['is_demo'], 'created_at' => $when,
            ]);
            $next = null;
            if ($nextDueAt && $f['enquiry_id']) {
                $next = self::scheduleFollowup((int) $f['enquiry_id'], $actor, $nextDueAt, $f['activity_type'], null, (int) $f['assigned_to'], $nextAction);
                $db->table('crm_followups')->where('id', $id)->update(['next_followup_id' => $next]);
            }

            return $next;
        });
    }

    /** Reschedule keeps the old row (status=rescheduled) and creates a linked new pending row. */
    public static function rescheduleFollowup(int $id, array $actor, string $newDueAt, string $reason): int
    {
        if (trim($reason) === '' || strtotime($newDueAt) === false) {
            throw CrmException::validation(['reason' => 'A new date/time and a reason are required.']);
        }

        return (int) Crm::tx(static function ($db) use ($id, $actor, $newDueAt, $reason) {
            $f = self::followup($id, $actor);
            if (! in_array($f['status'], ['pending', 'missed'], true)) {
                throw new CrmException('Only pending or missed follow-ups can be rescheduled.');
            }
            $db->table('crm_followups')->where('id', $id)->update(['status' => 'rescheduled', 'remarks' => trim(($f['remarks'] ?? '') . "\nRescheduled: " . $reason)]);
            $db->table('crm_followups')->insert([
                'customer_id' => $f['customer_id'], 'enquiry_id' => $f['enquiry_id'], 'assigned_to' => $f['assigned_to'], 'activity_type' => $f['activity_type'],
                'due_at' => date('Y-m-d H:i:s', strtotime($newDueAt)), 'status' => 'pending', 'remarks' => $f['remarks'], 'next_action' => $f['next_action'],
                'rescheduled_from_id' => $id, 'created_by' => $actor['id'], 'is_demo' => $f['is_demo'],
            ]);
            $new = (int) $db->insertID();
            $db->table('crm_followups')->where('id', $id)->update(['next_followup_id' => $new]);
            AuditService::workflow('followup.rescheduled', 'followup', $id, [
                'customer_id' => (int) $f['customer_id'], 'enquiry_id' => $f['enquiry_id'] ? (int) $f['enquiry_id'] : null, 'user_id' => $actor['id'],
                'prev' => ['due_at' => $f['due_at']], 'new' => ['due_at' => $newDueAt, 'followup_id' => $new], 'reason' => $reason, 'is_demo' => (int) $f['is_demo'],
            ]);

            return $new;
        });
    }

    /**
     * Cron job: mark overdue pending follow-ups as missed (once), remind due-soon ones, escalate long-overdue ones to the manager.
     * Idempotent: unique notification dedupe keys + reminder_sent_at / escalated_at stamps.
     * @return array{reminded:int,missed:int,escalated:int}
     */
    public static function runReminders(?string $now = null): array
    {
        $db  = Crm::db();
        $now = $now ?? Crm::now();
        $out = ['reminded' => 0, 'missed' => 0, 'escalated' => 0];

        $due = $db->query("SELECT f.*, e.enquiry_no FROM crm_followups f LEFT JOIN crm_enquiries e ON e.id = f.enquiry_id
                           WHERE f.status = 'pending' AND f.due_at <= DATE_ADD(?, INTERVAL 30 MINUTE) AND f.due_at > ? AND f.reminder_sent_at IS NULL", [$now, $now])->getResultArray();
        foreach ($due as $f) {
            if (NotificationService::notify((int) $f['assigned_to'], 'followup.due', 'Follow-up due soon' . ($f['enquiry_no'] ? " ({$f['enquiry_no']})" : ''), $f['next_action'] ?: $f['remarks'], $f['enquiry_id'] ? "crm/enquiries/{$f['enquiry_id']}" : 'crm/followups', "fu-due:{$f['id']}", (int) $f['is_demo'])) {
                $out['reminded']++;
            }
            $db->table('crm_followups')->where('id', $f['id'])->update(['reminder_sent_at' => $now]);
        }

        $overdue = $db->query("SELECT f.*, e.enquiry_no FROM crm_followups f LEFT JOIN crm_enquiries e ON e.id = f.enquiry_id WHERE f.status = 'pending' AND f.due_at < ?", [$now])->getResultArray();
        $hours   = (int) Settings::get('followup.escalate_after_hours');
        foreach ($overdue as $f) {
            $db->table('crm_followups')->where('id', $f['id'])->update(['status' => 'missed']);
            $out['missed']++;
            AuditService::workflow('followup.missed', 'followup', (int) $f['id'], [
                'customer_id' => (int) $f['customer_id'], 'enquiry_id' => $f['enquiry_id'] ? (int) $f['enquiry_id'] : null, 'user_id' => null, 'source' => 'cron',
                'prev' => ['status' => 'pending'], 'new' => ['status' => 'missed'], 'is_demo' => (int) $f['is_demo'],
            ]);
            NotificationService::notify((int) $f['assigned_to'], 'followup.overdue', 'Overdue follow-up' . ($f['enquiry_no'] ? " ({$f['enquiry_no']})" : ''), 'Please complete or reschedule.', $f['enquiry_id'] ? "crm/enquiries/{$f['enquiry_id']}" : 'crm/followups', "fu-overdue:{$f['id']}", (int) $f['is_demo']);
        }

        $esc = $db->query("SELECT f.*, e.enquiry_no FROM crm_followups f LEFT JOIN crm_enquiries e ON e.id = f.enquiry_id
                           WHERE f.status = 'missed' AND f.escalated_at IS NULL AND f.due_at < DATE_SUB(?, INTERVAL ? HOUR)", [$now, $hours])->getResultArray();
        foreach ($esc as $f) {
            $mgrs = $db->query('SELECT DISTINCT t.manager_id FROM crm_team_members tm JOIN crm_teams t ON t.id = tm.team_id WHERE tm.user_id = ? AND t.manager_id IS NOT NULL', [$f['assigned_to']])->getResultArray();
            foreach ($mgrs as $m) {
                NotificationService::notify((int) $m['manager_id'], 'followup.escalated', 'Escalation: follow-up overdue ' . $hours . 'h+' . ($f['enquiry_no'] ? " ({$f['enquiry_no']})" : ''), null, $f['enquiry_id'] ? "crm/enquiries/{$f['enquiry_id']}" : 'crm/followups', "fu-esc:{$f['id']}:{$m['manager_id']}", (int) $f['is_demo']);
            }
            $db->table('crm_followups')->where('id', $f['id'])->update(['escalated_at' => $now]);
            AuditService::workflow('followup.escalated', 'followup', (int) $f['id'], ['customer_id' => (int) $f['customer_id'], 'enquiry_id' => $f['enquiry_id'] ? (int) $f['enquiry_id'] : null, 'user_id' => null, 'source' => 'cron', 'is_demo' => (int) $f['is_demo']]);
            $out['escalated']++;
        }

        return $out;
    }

    // ───────────── Site visits & measurements ─────────────

    public static function scheduleVisit(int $enquiryId, array $actor, string $scheduledAt, ?int $assignTo = null, ?string $address = null, ?string $remarks = null): int
    {
        if (! AccessScope::can($actor, 'visits.manage')) {
            throw new CrmException('You do not have permission to manage site visits.');
        }
        if (strtotime($scheduledAt) === false) {
            throw CrmException::validation(['scheduled_at' => 'A valid visit date/time is required.']);
        }

        return (int) Crm::tx(static function ($db) use ($enquiryId, $actor, $scheduledAt, $assignTo, $address, $remarks) {
            $e = self::enquiry($enquiryId, $actor);
            if (! WorkflowService::isActive($e['status'])) {
                throw new CrmException('Site visits can only be scheduled on active enquiries.');
            }
            $no = NumberSequenceService::next('visit');
            $db->table('crm_site_visits')->insert([
                'visit_no' => $no, 'enquiry_id' => $enquiryId, 'customer_id' => $e['customer_id'], 'assigned_to' => $assignTo ?: ($e['current_owner_id'] ?: $actor['id']),
                'scheduled_at' => date('Y-m-d H:i:s', strtotime($scheduledAt)), 'address' => $address ?: $e['site_location'], 'remarks' => $remarks, 'is_demo' => $e['is_demo'],
            ]);
            $id = (int) $db->insertID();
            AuditService::workflow('visit.scheduled', 'site_visit', $id, [
                'customer_id' => (int) $e['customer_id'], 'enquiry_id' => $enquiryId, 'entity_ref' => $no, 'user_id' => $actor['id'], 'new' => ['scheduled_at' => $scheduledAt], 'is_demo' => (int) $e['is_demo'],
            ]);
            if (in_array($e['status'], ['contacted', 'qualified'], true)) {
                WorkflowService::transition($enquiryId, 'site_visit', $actor, "Site visit {$no} scheduled");
            }

            return $id;
        });
    }

    /** @param array<string,mixed> $measurements free-form key/value (e.g. ['Window 1' => '1200x1500 mm']) */
    public static function completeVisit(int $visitId, array $actor, array $measurements, ?string $observations = null, ?string $productReqs = null, ?string $followUpAction = null, ?string $at = null): void
    {
        Crm::tx(static function ($db) use ($visitId, $actor, $measurements, $observations, $productReqs, $followUpAction, $at) {
            $v = $db->query('SELECT * FROM crm_site_visits WHERE id = ? FOR UPDATE', [$visitId])->getRowArray() ?? throw new CrmException('Visit not found.');
            $e = self::enquiry((int) $v['enquiry_id'], $actor);
            if ($v['status'] !== 'scheduled') {
                throw new CrmException('Only scheduled visits can be completed.');
            }
            $when = $at ?? Crm::now();
            $db->table('crm_site_visits')->where('id', $visitId)->update(['status' => 'completed', 'completed_at' => $when, 'observations' => $observations, 'product_requirements' => $productReqs, 'follow_up_action' => $followUpAction]);
            if ($measurements) {
                self::saveMeasurements($visitId, $actor, $measurements, 'Initial measurements', $when);
            }
            self::log((int) $v['customer_id'], (int) $v['enquiry_id'], 'visit', "Site visit {$v['visit_no']} completed", $actor['id'], null, $observations, $when, null, (int) $v['is_demo']);
            AuditService::workflow('visit.completed', 'site_visit', $visitId, [
                'customer_id' => (int) $v['customer_id'], 'enquiry_id' => (int) $v['enquiry_id'], 'entity_ref' => $v['visit_no'], 'user_id' => $actor['id'],
                'prev' => ['status' => 'scheduled'], 'new' => ['status' => 'completed', 'measurement_items' => count($measurements)], 'is_demo' => (int) $v['is_demo'], 'created_at' => $when,
            ]);
            if ($e['status'] === 'site_visit') {
                WorkflowService::transition((int) $v['enquiry_id'], 'quotation_preparation', $actor, 'Site visit completed');
            }
        });
    }

    /** Revising measurements never overwrites: a new version row is written and previous is_current cleared. */
    public static function saveMeasurements(int $visitId, array $actor, array $data, ?string $notes = null, ?string $at = null): int
    {
        return (int) Crm::tx(static function ($db) use ($visitId, $actor, $data, $notes, $at) {
            $v   = $db->table('crm_site_visits')->where('id', $visitId)->get()->getRowArray() ?? throw new CrmException('Visit not found.');
            $max = (int) ($db->query('SELECT MAX(version_no) m FROM crm_site_measurements WHERE visit_id = ?', [$visitId])->getRow()->m ?? 0);
            $db->table('crm_site_measurements')->where('visit_id', $visitId)->update(['is_current' => 0]);
            $db->table('crm_site_measurements')->insert(['visit_id' => $visitId, 'version_no' => $max + 1, 'is_current' => 1, 'data' => json_encode($data, JSON_UNESCAPED_UNICODE), 'notes' => $notes, 'recorded_by' => $actor['id'], 'created_at' => $at ?? Crm::now()]);
            $id = (int) $db->insertID();
            if ($max > 0) {
                AuditService::workflow('measurement.revised', 'site_visit', $visitId, ['customer_id' => (int) $v['customer_id'], 'enquiry_id' => (int) $v['enquiry_id'], 'entity_ref' => $v['visit_no'], 'user_id' => $actor['id'], 'new' => ['version' => $max + 1], 'reason' => $notes]);
            }

            return $id;
        });
    }

    /** Cron: notify the assigned user of site visits starting within the configured lead time. Idempotent. @return int reminders created */
    public static function runVisitReminders(?string $now = null): int
    {
        $db    = Crm::db();
        $now   = $now ?? Crm::now();
        $hours = (int) Settings::get('visit.reminder_hours_before');
        $rows  = $db->query("SELECT v.*, e.enquiry_no FROM crm_site_visits v JOIN crm_enquiries e ON e.id = v.enquiry_id
                             WHERE v.status = 'scheduled' AND v.reminder_sent_at IS NULL AND v.scheduled_at > ? AND v.scheduled_at <= DATE_ADD(?, INTERVAL ? HOUR)", [$now, $now, $hours])->getResultArray();
        $n = 0;
        foreach ($rows as $v) {
            if (NotificationService::notify((int) $v['assigned_to'], 'visit.reminder', "Site visit {$v['visit_no']} on " . date('d M H:i', strtotime($v['scheduled_at'])), $v['address'], "crm/enquiries/{$v['enquiry_id']}", "visit-rem:{$v['id']}", (int) $v['is_demo'])) {
                $n++;
            }
            $db->table('crm_site_visits')->where('id', $v['id'])->update(['reminder_sent_at' => $now]);
        }

        return $n;
    }
}

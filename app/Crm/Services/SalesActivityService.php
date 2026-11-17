<?php

namespace Crm\Services;

use CodeIgniter\Database\BaseConnection;

final class SalesActivityService
{
    private static function db(): BaseConnection
    {
        return Crm::db();
    }

    private static function label(string $val): string
    {
        return function_exists('crm_label') ? crm_label($val) : ucwords(str_replace(['_', '-'], ' ', $val));
    }

    private static function date(?string $val): string
    {
        if (! $val) return '-';
        return function_exists('crm_date') ? crm_date($val) : date('d M Y', strtotime($val));
    }

    public static function list(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $db = self::db();
        $b = $db->table('crm_sales_activities sa')
            ->select("sa.*, o.name AS organisation_name, o.org_no, o.city AS org_city,
                c.first_name, c.last_name, c.mobile AS contact_mobile, c.designation AS contact_designation,
                CONCAT(u.first_name, ' ', u.last_name) AS assigned_user_name, e.enquiry_no, e.project_name AS enquiry_project_name,
                conv.enquiry_no AS converted_lead_no")
            ->join('crm_organisations o', 'o.id = sa.organisation_id', 'left')
            ->join('crm_contacts c', 'c.id = sa.contact_id', 'left')
            ->join('admin_users u', 'u.id = sa.assigned_user_id', 'left')
            ->join('crm_enquiries e', 'e.id = sa.lead_id', 'left')
            ->join('crm_enquiries conv', 'conv.id = sa.converted_to_lead_id', 'left');

        if (! empty($filters['q'])) {
            $q = trim((string) $filters['q']);
            $b->groupStart()
                ->like('sa.activity_no', $q)
                ->orLike('sa.discussion_summary', $q)
                ->orLike('sa.location', $q)
                ->orLike('sa.product_interest', $q)
                ->orLike('o.name', $q)
                ->orLike('c.first_name', $q)
                ->orLike('c.last_name', $q)
                ->orLike('c.mobile', $q)
                ->groupEnd();
        }
        if (! empty($filters['activity_type'])) {
            $b->where('sa.activity_type', $filters['activity_type']);
        }
        if (! empty($filters['status'])) {
            $b->where('sa.status', $filters['status']);
        }
        if (! empty($filters['outcome'])) {
            $b->where('sa.outcome', $filters['outcome']);
        }
        if (! empty($filters['assigned_user_id'])) {
            $b->where('sa.assigned_user_id', (int) $filters['assigned_user_id']);
        }
        if (! empty($filters['organisation_id'])) {
            $b->where('sa.organisation_id', (int) $filters['organisation_id']);
        }
        if (! empty($filters['date_from'])) {
            $b->where('sa.activity_date >=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $b->where('sa.activity_date <=', $filters['date_to']);
        }
        if (! empty($filters['view_preset'])) {
            $today = date('Y-m-d');
            match ($filters['view_preset']) {
                'today' => $b->where('sa.activity_date', $today),
                'upcoming' => $b->where('sa.activity_date >', $today)->where('sa.status !=', 'completed')->where('sa.status !=', 'cancelled'),
                'overdue' => $b->where('sa.activity_date <', $today)->whereNotIn('sa.status', ['completed', 'cancelled']),
                'completed' => $b->where('sa.status', 'completed'),
                'converted' => $b->where('sa.converted_to_lead_id IS NOT NULL'),
                'unconverted' => $b->where('sa.converted_to_lead_id IS NULL')->where('sa.outcome !=', 'no_opportunity'),
                default => null,
            };
        }

        $total = $b->countAllResults(false);
        $offset = max(0, ($page - 1) * $perPage);
        $rows = $b->orderBy('sa.activity_date', 'DESC')->orderBy('sa.id', 'DESC')->limit($perPage, $offset)->get()->getResultArray();

        return [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public static function get(int $id): ?array
    {
        $db = self::db();
        $row = $db->table('crm_sales_activities sa')
            ->select("sa.*, o.name AS organisation_name, o.org_no, o.city AS org_city, o.primary_phone AS org_phone,
                c.first_name, c.last_name, c.mobile AS contact_mobile, c.email AS contact_email, c.designation AS contact_designation,
                CONCAT(u.first_name, ' ', u.last_name) AS assigned_user_name, CONCAT(creator.first_name, ' ', creator.last_name) AS creator_name, e.enquiry_no, conv.enquiry_no AS converted_lead_no")
            ->join('crm_organisations o', 'o.id = sa.organisation_id', 'left')
            ->join('crm_contacts c', 'c.id = sa.contact_id', 'left')
            ->join('admin_users u', 'u.id = sa.assigned_user_id', 'left')
            ->join('admin_users creator', 'creator.id = sa.created_by', 'left')
            ->join('crm_enquiries e', 'e.id = sa.lead_id', 'left')
            ->join('crm_enquiries conv', 'conv.id = sa.converted_to_lead_id', 'left')
            ->where('sa.id', $id)
            ->get()->getRowArray();

        return $row ?: null;
    }

    public static function find(int $id): ?array
    {
        return self::get($id);
    }

    public static function create(array $data, int $actorId): array
    {
        $db = self::db();
        $type = trim((string) ($data['activity_type'] ?? ''));
        if ($type === '') {
            $type = 'call';
        }
        $summary = trim((string) ($data['discussion_summary'] ?? ($data['subject'] ?? ($data['notes'] ?? 'Sales interaction'))));

        $activityNo = NumberSequenceService::next('activity');
        $now = Crm::now();
        $assignedUser = ! empty($data['assigned_user_id']) ? (int) $data['assigned_user_id'] : (! empty($data['assigned_to']) ? (int) $data['assigned_to'] : $actorId);

        $record = [
            'activity_no' => $activityNo,
            'activity_type' => $type,
            'organisation_id' => ! empty($data['organisation_id']) ? (int) $data['organisation_id'] : null,
            'contact_id' => ! empty($data['contact_id']) ? (int) $data['contact_id'] : null,
            'project_id' => ! empty($data['project_id']) ? (int) $data['project_id'] : null,
            'lead_id' => ! empty($data['lead_id']) ? (int) $data['lead_id'] : null,
            'assigned_user_id' => $assignedUser,
            'created_by' => $actorId,
            'activity_date' => ! empty($data['activity_date']) ? $data['activity_date'] : date('Y-m-d'),
            'start_time' => ! empty($data['start_time']) ? $data['start_time'] : null,
            'end_time' => ! empty($data['end_time']) ? $data['end_time'] : null,
            'location' => $data['location'] ?? null,
            'discussion_summary' => $summary,
            'requirement_identified' => $data['requirement_identified'] ?? null,
            'product_interest' => $data['product_interest'] ?? null,
            'est_business_value' => ! empty($data['est_business_value']) ? (float) $data['est_business_value'] : 0,
            'next_action' => $data['next_action'] ?? null,
            'next_followup_date' => ! empty($data['next_followup_date']) ? $data['next_followup_date'] : null,
            'status' => $data['status'] ?? 'scheduled',
            'outcome' => $data['outcome'] ?? 'followup_required',
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $db->table('crm_sales_activities')->insert($record);
        $actId = (int) $db->insertID();

        // Also record in communication log if organisation is linked
        if (! empty($record['organisation_id'])) {
            CommunicationService::log([
                'organisation_id' => $record['organisation_id'],
                'contact_id' => $record['contact_id'],
                'communication_type' => in_array($type, ['cold_call', 'followup_call', 'incoming_call', 'outgoing_call']) ? 'call' : (str_contains($type, 'meeting') ? 'meeting' : 'visit'),
                'direction' => str_contains($type, 'incoming') ? 'incoming' : 'outgoing',
                'subject' => self::label($type) . ' - ' . substr($summary, 0, 50),
                'message_body' => $summary,
                'outcome' => $record['outcome'],
                'next_action' => $record['next_action'],
                'followup_date' => $record['next_followup_date'],
                'related_entity_type' => 'activity',
                'related_entity_id' => $actId,
            ], $actorId);
        }

        AuditService::record('sales_activity.created', 'crm_sales_activities', $actId, [
            'activity_no' => $activityNo,
            'type' => $type,
            'date' => $record['activity_date'],
        ]);

        return self::get($actId);
    }

    public static function update(int $id, array $data, int $actorId): array
    {
        $db = self::db();
        $act = self::get($id) ?? throw new CrmException('Activity not found.');

        $summary = trim((string) ($data['discussion_summary'] ?? $act['discussion_summary']));
        if ($summary === '') {
            throw new CrmException('Discussion summary is required.');
        }

        $fields = [
            'activity_type' => $data['activity_type'] ?? $act['activity_type'],
            'organisation_id' => ! empty($data['organisation_id']) ? (int) $data['organisation_id'] : $act['organisation_id'],
            'contact_id' => ! empty($data['contact_id']) ? (int) $data['contact_id'] : $act['contact_id'],
            'project_id' => ! empty($data['project_id']) ? (int) $data['project_id'] : $act['project_id'],
            'assigned_user_id' => ! empty($data['assigned_user_id']) ? (int) $data['assigned_user_id'] : $act['assigned_user_id'],
            'activity_date' => ! empty($data['activity_date']) ? $data['activity_date'] : $act['activity_date'],
            'start_time' => $data['start_time'] ?? $act['start_time'],
            'end_time' => $data['end_time'] ?? $act['end_time'],
            'location' => $data['location'] ?? $act['location'],
            'discussion_summary' => $summary,
            'requirement_identified' => $data['requirement_identified'] ?? $act['requirement_identified'],
            'product_interest' => $data['product_interest'] ?? $act['product_interest'],
            'est_business_value' => isset($data['est_business_value']) ? (float) $data['est_business_value'] : $act['est_business_value'],
            'next_action' => $data['next_action'] ?? $act['next_action'],
            'next_followup_date' => $data['next_followup_date'] ?? $act['next_followup_date'],
            'status' => $data['status'] ?? $act['status'],
            'outcome' => $data['outcome'] ?? $act['outcome'],
            'updated_at' => Crm::now(),
        ];

        $db->table('crm_sales_activities')->where('id', $id)->update($fields);
        AuditService::record('sales_activity.updated', 'crm_sales_activities', $id, ['changes' => array_keys($data)]);

        return self::get($id);
    }

    public static function complete(int $id, array $data, int $actorId): array
    {
        $db = self::db();
        $act = self::get($id) ?? throw new CrmException('Activity not found.');

        $outcome = $data['outcome'] ?? ($act['outcome'] ?: 'requirement_identified');
        $notes = ! empty($data['completion_notes']) ? $data['completion_notes'] : ($data['discussion_summary'] ?? $act['discussion_summary']);

        $db->table('crm_sales_activities')->where('id', $id)->update([
            'status' => 'completed',
            'outcome' => $outcome,
            'discussion_summary' => $notes,
            'next_action' => $data['next_action'] ?? $act['next_action'],
            'next_followup_date' => $data['next_followup_date'] ?? $act['next_followup_date'],
            'updated_at' => Crm::now(),
        ]);

        AuditService::record('sales_activity.completed', 'crm_sales_activities', $id, ['outcome' => $outcome]);
        return self::get($id);
    }

    public static function reschedule(int $id, string $newDate, ?string $reason, int $actorId): array
    {
        $db = self::db();
        $act = self::get($id) ?? throw new CrmException('Activity not found.');

        $notes = $act['discussion_summary'] . ($reason ? "\n[Rescheduled to {$newDate}: {$reason}]" : "\n[Rescheduled to {$newDate}]");

        $db->table('crm_sales_activities')->where('id', $id)->update([
            'status' => 'rescheduled',
            'activity_date' => $newDate,
            'discussion_summary' => $notes,
            'updated_at' => Crm::now(),
        ]);

        AuditService::record('sales_activity.rescheduled', 'crm_sales_activities', $id, ['new_date' => $newDate]);
        return self::get($id);
    }

    /**
     * Controlled Action: Convert Activity to Lead / Create Opportunity
     * Carries forward Organisation, Contact, Discussion Notes, Product Interest,
     * Estimated Value, Project Info, and links the created lead back to the activity.
     */
    public static function convertToLead(int $activityId, array $overrides, int $actorId): array
    {
        $db = self::db();
        $act = self::get($activityId) ?? throw new CrmException('Activity not found.');

        if (! empty($act['converted_to_lead_id'])) {
            throw new CrmException("This activity has already been converted to lead '{$act['converted_lead_no']}'.");
        }

        // Find or create matching customer for legacy foreign keys
        $customerId = null;
        if (! empty($act['organisation_id'])) {
            $cust = $db->table('crm_customers')->where('organisation_id', $act['organisation_id'])->get()->getRowArray();
            if ($cust) {
                $customerId = (int) $cust['id'];
            } else {
                $org = OrganisationService::get($act['organisation_id']);
                $contact = ! empty($act['contact_id']) ? ContactService::get($act['contact_id']) : null;
                $cusNo = NumberSequenceService::next('customer');
                $db->table('crm_customers')->insert([
                    'customer_no' => $cusNo,
                    'name' => $org['name'],
                    'customer_type' => match ($org['org_type'] ?? 'corporate') {
                        'builder_developer' => 'builder',
                        'architect' => 'architect',
                        'interior_designer' => 'interior',
                        'individual' => 'individual',
                        default => 'company',
                    },
                    'mobile' => $contact['mobile'] ?? ($org['primary_phone'] ?? '9800000000'),
                    'email' => $contact['email'] ?? $org['primary_email'],
                    'city' => $org['city'] ?? 'Pune',
                    'billing_address' => $org['billing_address'],
                    'owner_user_id' => $act['assigned_user_id'] ?: $actorId,
                    'organisation_id' => $act['organisation_id'],
                    'primary_contact_id' => $act['contact_id'],
                    'created_at' => Crm::now(),
                ]);
                $customerId = (int) $db->insertID();
            }
        }

        $enquiryNo = NumberSequenceService::next('enquiry');
        $now = Crm::now();
        $assignedUser = ! empty($overrides['owner_id']) ? (int) $overrides['owner_id'] : ($act['assigned_user_id'] ?: $actorId);

        $enquiryData = [
            'enquiry_no' => $enquiryNo,
            'customer_id' => $customerId,
            'organisation_id' => $act['organisation_id'],
            'contact_id' => $act['contact_id'],
            'project_id' => $act['project_id'],
            'project_name' => $overrides['project_name'] ?? ($act['product_interest'] ? "Requirement for " . $act['product_interest'] : 'New Interior Opportunity'),
            'status' => 'new',
            'priority' => $overrides['priority'] ?? 'medium',
            'source_id' => ! empty($overrides['source_id']) ? (int) $overrides['source_id'] : ($act['lead_source_id'] ?? 1),
            'current_owner_id' => $assignedUser,
            'est_budget' => ! empty($overrides['est_budget']) ? (float) $overrides['est_budget'] : ($act['est_business_value'] ?: 100000.00),
            'description' => "Converted from Activity #{$act['activity_no']} (" . self::label($act['activity_type']) . " on " . self::date($act['activity_date']) . "):\n" . $act['discussion_summary'],
            'created_by' => $actorId,
            'converted_from_activity_id' => $activityId,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $db->table('crm_enquiries')->insert($enquiryData);
        $leadId = (int) $db->insertID();

        // Mark activity as completed and converted
        $db->table('crm_sales_activities')->where('id', $activityId)->update([
            'status' => 'completed',
            'outcome' => 'quotation_requested',
            'converted_to_lead_id' => $leadId,
            'converted_at' => $now,
            'lead_id' => $leadId,
            'updated_at' => $now,
        ]);

        NotificationService::trigger('lead.assigned', [
            'enquiry_id' => $leadId,
            'enquiry_no' => $enquiryNo,
            'owner_id' => $assignedUser,
            'customer_name' => $act['organisation_name'] ?: 'Customer',
            'message' => "Opportunity {$enquiryNo} created from activity {$act['activity_no']}.",
        ]);

        AuditService::record('sales_activity.converted_to_lead', 'crm_sales_activities', $activityId, [
            'lead_id' => $leadId,
            'enquiry_no' => $enquiryNo,
        ]);

        return [
            'lead_id' => $leadId,
            'enquiry_id' => $leadId,
            'enquiry_no' => $enquiryNo,
            'activity_id' => $activityId,
        ];
    }

    public static function convertToEnquiry(int $activityId, int $actorId, array $overrides = []): int
    {
        $res = self::convertToLead($activityId, $overrides, $actorId);
        return (int) ($res['enquiry_id'] ?? $res['lead_id'] ?? 0);
    }

    /**
     * Dashboard statistics for Sales Activity management.
     */
    public static function getDashboardMetrics(array $actor): array
    {
        $db = self::db();
        $today = date('Y-m-d');
        $userId = (int) ($actor['id'] ?? 0);
        $isManager = crm_can('discounts.approve_manager', 'users.manage');

        $b = $db->table('crm_sales_activities');
        if (! $isManager && $userId > 0) {
            $b->where('assigned_user_id', $userId);
        }

        $all = $b->get()->getResultArray();
        $m = [
            'today' => 0,
            'completed' => 0,
            'upcoming' => 0,
            'overdue' => 0,
            'visits' => 0,
            'calls' => 0,
            'meetings' => 0,
            'converted' => 0,
            'no_opportunity' => 0,
            'total' => count($all),
        ];

        foreach ($all as $a) {
            $date = $a['activity_date'];
            $st = $a['status'];
            $type = $a['activity_type'];

            if ($date === $today) {
                $m['today']++;
            }
            if ($st === 'completed') {
                $m['completed']++;
            } elseif ($st !== 'cancelled') {
                if ($date < $today) {
                    $m['overdue']++;
                } else {
                    $m['upcoming']++;
                }
            }

            if (str_contains($type, 'visit')) {
                $m['visits']++;
            } elseif (str_contains($type, 'call')) {
                $m['calls']++;
            } elseif (str_contains($type, 'meeting')) {
                $m['meetings']++;
            }

            if (! empty($a['converted_to_lead_id'])) {
                $m['converted']++;
            }
            if ($a['outcome'] === 'no_opportunity') {
                $m['no_opportunity']++;
            }
        }

        return $m;
    }
}

<?php

namespace Crm\Services;

/** Configurable, validated enquiry status machine. Every change writes history + workflow event in one transaction. */
final class WorkflowService
{
    public const ACTIVE = ['new', 'assigned', 'contacted', 'qualified', 'site_visit', 'quotation_preparation', 'quotation_sent', 'negotiation'];

    public static function isActive(string $status): bool
    {
        return in_array($status, self::ACTIVE, true);
    }

    /** @return list<string> */
    public static function allowedNext(string $from): array
    {
        $dbTransitions = array_column(Crm::db()->table('crm_status_transitions')->select('to_status')->where(['entity' => 'enquiry', 'from_status' => $from, 'is_active' => 1])->get()->getResultArray(), 'to_status');
        if (in_array($from, self::ACTIVE, true)) {
            $extra = array_diff(self::ACTIVE, [$from]);
            $extra[] = 'lost';
            $extra[] = 'on_hold';
            if (in_array($from, ['quotation_preparation', 'quotation_sent', 'negotiation'], true)) {
                $extra[] = 'won';
            }
            return array_values(array_unique(array_merge($dbTransitions, $extra)));
        }
        return $dbTransitions;
    }

    /**
     * @param array<string,mixed> $actor session user (or AccessScope::system())
     * @param array<string,mixed> $extra columns to update on the enquiry in the same transaction
     */
    public static function transition(int $enquiryId, string $to, array $actor, ?string $reason = null, array $extra = []): void
    {
        Crm::tx(static function ($db) use ($enquiryId, $to, $actor, $reason, $extra) {
            $e = $db->query('SELECT * FROM crm_enquiries WHERE id = ? FOR UPDATE', [$enquiryId])->getRowArray() ?? throw new CrmException('Enquiry not found.');
            if (! AccessScope::isSuper($actor) && ! AccessScope::canSeeEnquiry($actor, $enquiryId)) {
                throw new CrmException('You do not have access to this enquiry.');
            }
            $from = $e['status'];
            if ($from === $to) {
                return;
            }
            $rule = $db->table('crm_status_transitions')->where(['entity' => 'enquiry', 'from_status' => $from, 'to_status' => $to, 'is_active' => 1])->get()->getRowArray();
            if (! $rule) {
                // Moving between any active stages is allowed for anyone with enquiries.edit
                if (in_array($from, self::ACTIVE, true) && in_array($to, self::ACTIVE, true) && AccessScope::can($actor, 'enquiries.edit')) {
                    // Valid pipeline progression
                } elseif (AccessScope::isSuper($actor) && in_array($to, array_merge(self::ACTIVE, ['won', 'lost', 'on_hold']), true)) {
                    // Super admin override
                } elseif (AccessScope::can($actor, 'enquiries.close') && in_array($to, ['lost', 'on_hold'], true)) {
                    // Direct close allowed with enquiries.close
                    if (trim((string) $reason) === '') {
                        throw new CrmException('A reason is required for this status change.');
                    }
                } elseif (AccessScope::can($actor, 'enquiries.close') && $to === 'won' && in_array($from, ['quotation_preparation', 'quotation_sent', 'negotiation'], true)) {
                    // Deal won from quotation or negotiation stage
                } elseif (AccessScope::can($actor, 'enquiries.reopen') && in_array($from, ['lost', 'won'], true) && in_array($to, self::ACTIVE, true)) {
                    // Reopening
                } elseif (AccessScope::can($actor, 'enquiries.edit') && $from === 'on_hold' && in_array($to, self::ACTIVE, true)) {
                    // Resuming from on-hold
                } else {
                    throw new CrmException("Status change {$from} → {$to} is not allowed.");
                }
            } else {
                if ($rule['required_permission'] && ! AccessScope::can($actor, $rule['required_permission'])) {
                    throw new CrmException('You do not have permission to make this status change.');
                }
                if ((int) $rule['requires_reason'] === 1 && trim((string) $reason) === '') {
                    throw new CrmException('A reason is required for this status change.');
                }
            }
            $upd = $extra + ['status' => $to, 'previous_status' => $from];
            if ($from === 'on_hold' || $from === 'lost') {
                $upd += ['outcome' => null, 'closed_at' => null, 'closed_by' => null, 'hold_review_date' => null];
            }
            $db->table('crm_enquiries')->where('id', $enquiryId)->update($upd);
            $db->table('crm_enquiry_status_history')->insert([
                'enquiry_id' => $enquiryId, 'from_status' => $from, 'to_status' => $to, 'user_id' => $actor['id'] ?? null, 'reason' => $reason ? mb_substr($reason, 0, 255) : null, 'created_at' => Crm::now(),
            ]);
            AuditService::workflow('enquiry.status_changed', 'enquiry', $enquiryId, [
                'customer_id' => (int) $e['customer_id'], 'enquiry_id' => $enquiryId, 'entity_ref' => $e['enquiry_no'], 'user_id' => $actor['id'] ?? null,
                'prev' => ['status' => $from], 'new' => ['status' => $to], 'reason' => $reason,
            ]);
        });
    }
}

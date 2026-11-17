<?php

namespace Crm\Services;

use CodeIgniter\Database\BaseConnection;

final class ContactService
{
    private static function db(): BaseConnection
    {
        return Crm::db();
    }

    public static function list(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $db = self::db();
        $b = $db->table('crm_contacts c')
            ->select('c.*, c.whatsapp AS whatsapp_no, o.name AS organisation_name, o.name AS org_name, o.org_no, o.org_type, o.city AS org_city')
            ->join('crm_organisations o', 'o.id = c.organisation_id');

        if (! empty($filters['q'])) {
            $q = trim((string) $filters['q']);
            $b->groupStart()
                ->like('c.first_name', $q)
                ->orLike('c.last_name', $q)
                ->orLike('c.mobile', $q)
                ->orLike('c.email', $q)
                ->orLike('c.designation', $q)
                ->orLike('o.name', $q)
                ->groupEnd();
        }
        if (! empty($filters['organisation_id'])) {
            $b->where('c.organisation_id', (int) $filters['organisation_id']);
        }
        if (! empty($filters['contact_role'])) {
            $b->where('c.contact_role', $filters['contact_role']);
        }
        if (isset($filters['is_decision_maker']) && $filters['is_decision_maker'] !== '') {
            $b->where('c.is_decision_maker', (int) $filters['is_decision_maker']);
        }
        if (! empty($filters['status'])) {
            $b->where('c.status', $filters['status']);
        }

        $total = $b->countAllResults(false);
        $offset = max(0, ($page - 1) * $perPage);
        $rows = $b->orderBy('c.is_primary', 'DESC')->orderBy('c.id', 'DESC')->limit($perPage, $offset)->get()->getResultArray();

        return [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public static function forOrganisation(int $orgId): array
    {
        $db = self::db();
        return $db->table('crm_contacts c')
            ->where('organisation_id', $orgId)
            ->where('status', 'active')
            ->orderBy('is_primary', 'DESC')
            ->orderBy('first_name', 'ASC')
            ->get()->getResultArray();
    }

    public static function getByOrganisation(int $orgId): array
    {
        return self::forOrganisation($orgId);
    }

    public static function get(int $id): ?array
    {
        $db = self::db();
        $row = $db->table('crm_contacts c')
            ->select('c.*, c.whatsapp AS whatsapp_no, o.name AS organisation_name, o.name AS org_name, o.org_no, o.org_type, o.city AS org_city')
            ->join('crm_organisations o', 'o.id = c.organisation_id')
            ->where('c.id', $id)
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
        $orgId = (int) ($data['organisation_id'] ?? 0);
        if ($orgId <= 0) {
            throw new CrmException('Organisation selection is required.');
        }
        $firstName = trim((string) ($data['first_name'] ?? ''));
        if ($firstName === '') {
            throw new CrmException('First name is required.');
        }
        $mobile = trim((string) ($data['mobile'] ?? ($data['mobile_number'] ?? '')));
        if ($mobile === '') {
            throw new CrmException('Mobile number is required.');
        }

        $now = Crm::now();
        $contactNo = NumberSequenceService::next('contact');
        $isPrimary = ! empty($data['is_primary']) ? 1 : 0;

        // If marked primary, unset other primaries for this organisation
        if ($isPrimary) {
            $db->table('crm_contacts')->where('organisation_id', $orgId)->update(['is_primary' => 0]);
        }

        $contactTypes = is_array($data['contact_types'] ?? null)
            ? implode(',', $data['contact_types'])
            : ($data['contact_types'] ?? 'primary');

        $record = [
            'contact_no' => $contactNo,
            'organisation_id' => $orgId,
            'first_name' => $firstName,
            'last_name' => $data['last_name'] ?? null,
            'designation' => $data['designation'] ?? null,
            'department' => $data['department'] ?? null,
            'mobile' => $mobile,
            'alternate_number' => $data['alternate_number'] ?? null,
            'email' => $data['email'] ?? null,
            'whatsapp' => $data['whatsapp'] ?? ($data['mobile'] ?? null),
            'contact_role' => $data['contact_role'] ?? 'owner',
            'contact_types' => $contactTypes,
            'is_decision_maker' => ! empty($data['is_decision_maker']) ? 1 : 0,
            'is_primary' => $isPrimary,
            'preferred_channel' => $data['preferred_channel'] ?? 'call',
            'preferred_language' => $data['preferred_language'] ?? 'english',
            'status' => $data['status'] ?? 'active',
            'remarks' => $data['remarks'] ?? null,
            'created_by' => $actorId,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $db->table('crm_contacts')->insert($record);
        $contactId = (int) $db->insertID();

        AuditService::record('contact.created', 'crm_contacts', $contactId, [
            'contact_no' => $contactNo,
            'name' => $firstName . ' ' . ($data['last_name'] ?? ''),
            'organisation_id' => $orgId,
        ]);

        return self::get($contactId);
    }

    public static function update(int $id, array $data, int $actorId): array
    {
        $db = self::db();
        $contact = self::get($id) ?? throw new CrmException('Contact not found.');

        $firstName = trim((string) ($data['first_name'] ?? $contact['first_name']));
        if ($firstName === '') {
            throw new CrmException('First name is required.');
        }

        $isPrimary = isset($data['is_primary']) ? (! empty($data['is_primary']) ? 1 : 0) : (int) $contact['is_primary'];
        if ($isPrimary && (int) $contact['is_primary'] !== 1) {
            $db->table('crm_contacts')->where('organisation_id', $contact['organisation_id'])->update(['is_primary' => 0]);
        }

        $contactTypes = isset($data['contact_types'])
            ? (is_array($data['contact_types']) ? implode(',', $data['contact_types']) : $data['contact_types'])
            : $contact['contact_types'];

        $fields = [
            'first_name' => $firstName,
            'last_name' => $data['last_name'] ?? $contact['last_name'],
            'designation' => $data['designation'] ?? $contact['designation'],
            'department' => $data['department'] ?? $contact['department'],
            'mobile' => $data['mobile'] ?? $contact['mobile'],
            'alternate_number' => $data['alternate_number'] ?? $contact['alternate_number'],
            'email' => $data['email'] ?? $contact['email'],
            'whatsapp' => $data['whatsapp'] ?? $contact['whatsapp'],
            'contact_role' => $data['contact_role'] ?? $contact['contact_role'],
            'contact_types' => $contactTypes,
            'is_decision_maker' => isset($data['is_decision_maker']) ? (! empty($data['is_decision_maker']) ? 1 : 0) : (int) $contact['is_decision_maker'],
            'is_primary' => $isPrimary,
            'preferred_channel' => $data['preferred_channel'] ?? $contact['preferred_channel'],
            'preferred_language' => $data['preferred_language'] ?? $contact['preferred_language'],
            'status' => $data['status'] ?? $contact['status'],
            'remarks' => $data['remarks'] ?? $contact['remarks'],
            'updated_at' => Crm::now(),
        ];

        $db->table('crm_contacts')->where('id', $id)->update($fields);

        AuditService::record('contact.updated', 'crm_contacts', $id, ['changes' => array_keys($data)]);

        return self::get($id);
    }

    public static function setPrimary(int $id): bool
    {
        $db = self::db();
        $contact = self::get($id) ?? throw new CrmException('Contact not found.');
        $db->table('crm_contacts')->where('organisation_id', $contact['organisation_id'])->update(['is_primary' => 0]);
        $db->table('crm_contacts')->where('id', $id)->update(['is_primary' => 1, 'updated_at' => Crm::now()]);
        AuditService::record('contact.set_primary', 'crm_contacts', $id, ['organisation_id' => $contact['organisation_id']]);
        return true;
    }
}

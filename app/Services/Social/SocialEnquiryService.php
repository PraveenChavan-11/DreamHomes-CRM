<?php

namespace App\Services\Social;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * Enterprise Social Enquiries & Lead Management Service
 *
 * Manages social-originated inbound leads from LinkedIn, Instagram, Campaigns,
 * and Manual Entry, providing deduplication, assignment, status lifecycles, and audit records.
 */
class SocialEnquiryService
{
    private BaseConnection $db;
    private SocialSettingsService $settings;

    public const STATUSES = [
        'new'           => 'New Inbound',
        'contacted'     => 'Contacted',
        'qualified'     => 'Qualified Lead',
        'proposal_sent' => 'Proposal Sent',
        'converted'     => 'Converted / Won',
        'closed'        => 'Closed / Lost',
        'not_relevant'  => 'Not Relevant / Spam',
    ];

    public const ENQUIRY_TYPES = [
        'recruitment'      => 'Tech Recruitment & Staffing',
        'it_services'      => 'Custom Software & IT Services',
        'worksphere_hrms'  => 'WorkSphere HRMS Platform',
        'consulting'       => 'Technology & HR Consulting',
        'general'          => 'General Corporate Enquiry',
    ];

    public function __construct(?BaseConnection $db = null, ?SocialSettingsService $settings = null)
    {
        $this->db       = $db ?? Database::connect();
        $this->settings = $settings ?? new SocialSettingsService($this->db);
    }

    /**
     * Create or ingest a social media enquiry with duplicate detection.
     */
    public function captureEnquiry(array $data, ?int $createdByUserId = null): array
    {
        $name    = trim($data['contact_name'] ?? '');
        $email   = trim(strtolower($data['email'] ?? ''));
        $phone   = trim($data['phone'] ?? '');
        $message = trim($data['message'] ?? '');

        if ($name === '' || $message === '') {
            throw new \InvalidArgumentException('Contact name and message are mandatory.');
        }

        // Check for duplicates
        $duplicate = null;
        if ($email !== '') {
            $duplicate = $this->db->table('social_enquiries')->where('email', $email)->get()->getRowArray();
        }
        if (!$duplicate && $phone !== '') {
            $duplicate = $this->db->table('social_enquiries')->where('phone', $phone)->get()->getRowArray();
        }

        $record = [
            'contact_name'    => $name,
            'email'           => $email !== '' ? $email : null,
            'phone'           => $phone !== '' ? $phone : null,
            'company'         => trim($data['company'] ?? '') ?: null,
            'enquiry_type'    => in_array($data['enquiry_type'] ?? '', array_keys(self::ENQUIRY_TYPES), true) ? $data['enquiry_type'] : 'general',
            'message'         => $message,
            'source_platform' => in_array($data['source_platform'] ?? '', ['linkedin', 'instagram', 'website', 'campaign', 'referral', 'manual'], true) ? $data['source_platform'] : 'manual',
            'campaign_id'     => !empty($data['campaign_id']) ? (int) $data['campaign_id'] : null,
            'external_ref'    => $data['external_ref'] ?? null,
            'lead_status'     => $data['lead_status'] ?? 'new',
            'priority'        => in_array($data['priority'] ?? '', ['low', 'normal', 'high', 'urgent'], true) ? $data['priority'] : 'normal',
            'assigned_to'     => !empty($data['assigned_to']) ? (int) $data['assigned_to'] : null,
            'follow_up_date'  => !empty($data['follow_up_date']) ? $data['follow_up_date'] : null,
            'notes'           => $data['notes'] ?? null,
            'created_at'      => date('Y-m-d H:i:s'),
        ];

        $this->db->table('social_enquiries')->insert($record);
        $enquiryId = (int) $this->db->insertID();

        // Create alert notification for administrators
        $this->db->table('social_notifications')->insert([
            'user_id'    => $record['assigned_to'],
            'event_type' => 'new_enquiry',
            'title'      => "New Social Lead: {$name}",
            'message'    => "Received from {$record['source_platform']}: " . mb_substr($message, 0, 100) . '...',
            'link'       => base_url("admin/social/enquiries/{$enquiryId}"),
            'is_read'    => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->settings->logActivity($createdByUserId, 'create_enquiry', 'social_enquiries', (string) $enquiryId, [
            'name'     => $name,
            'email'    => $email,
            'platform' => $record['source_platform'],
        ]);

        return [
            'id'           => $enquiryId,
            'is_duplicate' => $duplicate !== null,
            'duplicate_id' => $duplicate['id'] ?? null,
            'record'       => $record,
        ];
    }

    /**
     * Update enquiry status or assignment.
     */
    public function updateStatus(int $id, string $status, ?string $notes = null, ?int $assignedTo = null, ?int $adminUserId = null): bool
    {
        if (!in_array($status, array_keys(self::STATUSES), true)) {
            throw new \InvalidArgumentException('Invalid lead status.');
        }

        $payload = [
            'lead_status' => $status,
            'updated_at'  => date('Y-m-d H:i:s'),
        ];

        if ($notes !== null) {
            $payload['notes'] = $notes;
        }
        if ($assignedTo !== null) {
            $payload['assigned_to'] = $assignedTo;
        }

        $res = $this->db->table('social_enquiries')->where('id', $id)->update($payload);

        $this->settings->logActivity($adminUserId, 'update_enquiry_status', 'social_enquiries', (string) $id, [
            'new_status'  => $status,
            'assigned_to' => $assignedTo,
        ]);

        return $res;
    }
}


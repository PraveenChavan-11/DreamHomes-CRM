<?php

namespace App\Services;

use App\Models\MailMessageIndexModel;
use App\Models\MailRecruitmentLinkModel;
use App\Models\ApplicationModel;
use App\Models\CandidateModel;
use App\Models\EmployerEnquiryModel;
use App\Models\ContactEnquiryModel;
use App\Models\JobModel;

class MailRecruitmentService
{
    protected MailMessageIndexModel $msgIndex;
    protected MailRecruitmentLinkModel $linkModel;

    public function __construct()
    {
        $this->msgIndex = new MailMessageIndexModel();
        $this->linkModel = new MailRecruitmentLinkModel();
    }

    /**
     * Automatically associate an incoming or outbound email with existing recruitment entities
     */
    public function autoClassifyAndLink(array &$message): array
    {
        $fromEmail = strtolower(trim($message['from_email'] ?? ''));
        $subject   = strtolower($message['subject'] ?? '');

        // 1. Check if matches candidate in candidates table
        if (!empty($fromEmail)) {
            $candModel = new CandidateModel();
            $candidate = $candModel->where('LOWER(email)', $fromEmail)->first();
            if ($candidate) {
                $appModel = new ApplicationModel();
                $app = $appModel->where('candidate_id', $candidate['id'])->orderBy('id', 'DESC')->first();
                if ($app) {
                    $message['category'] = 'candidate_app';
                    $message['recruitment_type'] = 'application';
                    $message['recruitment_id'] = $app['id'];
                    return $message;
                }

                $message['category'] = 'candidate_app';
                $message['recruitment_type'] = 'candidate';
                $message['recruitment_id'] = $candidate['id'];
                return $message;
            }

            // 2. Check if matches employer inquiry
            $empModel = new EmployerEnquiryModel();
            $emp = $empModel->where('LOWER(email)', $fromEmail)->orderBy('id', 'DESC')->first();
            if ($emp) {
                $message['category'] = 'employer_req';
                $message['recruitment_type'] = 'employer_enquiry';
                $message['recruitment_id'] = $emp['id'];
                return $message;
            }

            // 3. Check if matches contact inquiry
            $conModel = new ContactEnquiryModel();
            $con = $conModel->where('LOWER(email)', $fromEmail)->orderBy('id', 'DESC')->first();
            if ($con) {
                $message['category'] = 'general_enquiry';
                $message['recruitment_type'] = 'contact_enquiry';
                $message['recruitment_id'] = $con['id'];
                return $message;
            }
        }

        // 4. Keyword heuristic
        if (str_contains($subject, 'interview') || str_contains($subject, 'shortlist')) {
            $message['category'] = 'interview';
        } elseif (str_contains($subject, 'apply') || str_contains($subject, 'application') || str_contains($subject, 'resume') || str_contains($subject, 'cv')) {
            $message['category'] = 'candidate_app';
        } elseif (str_contains($subject, 'hiring') || str_contains($subject, 'requirement') || str_contains($subject, 'mandate') || str_contains($subject, 'staffing')) {
            $message['category'] = 'employer_req';
        } else {
            $message['category'] = 'general_enquiry';
        }

        return $message;
    }

    /**
     * Manually link an email to a recruitment record
     */
    public function linkRecord(string $mailboxId, string $uid, string $folder, string $entityType, int $entityId, ?string $notes = null): bool
    {
        $msg = $this->msgIndex->findByUid($mailboxId, $folder, $uid);
        if (!$msg) return false;

        $this->msgIndex->update($msg['id'], [
            'recruitment_type' => $entityType,
            'recruitment_id'   => $entityId,
            'internal_notes'   => $notes ?? $msg['internal_notes'],
        ]);

        $this->linkModel->insert([
            'mailbox_resource_id' => $mailboxId,
            'message_uid'         => $uid,
            'folder'              => $folder,
            'entity_type'         => $entityType,
            'entity_id'           => $entityId,
            'notes'               => $notes,
            'created_by'          => session()->get('user_id') ?? null,
            'created_at'          => date('Y-m-d H:i:s'),
        ]);

        return true;
    }

    /**
     * Retrieve full context for a linked recruitment record
     */
    public function getRecruitmentContext(?string $type, ?int $id): ?array
    {
        if (empty($type) || empty($id)) return null;

        switch ($type) {
            case 'application':
                $app = (new ApplicationModel())->find($id);
                if (!$app) return null;
                $candidate = !empty($app['candidate_id']) ? (new CandidateModel())->find($app['candidate_id']) : null;
                $job = !empty($app['job_id']) ? (new JobModel())->find($app['job_id']) : null;
                return [
                    'type'         => 'Job Application',
                    'id'           => $id,
                    'title'        => ($candidate['full_name'] ?? 'Candidate') . ' — ' . ($job['title'] ?? 'Direct Application'),
                    'email'        => $candidate['email'] ?? '',
                    'phone'        => $candidate['phone'] ?? '',
                    'status'       => $app['status'] ?? 'NEW',
                    'detail_url'   => '/admin/applications',
                    'meta'         => [
                        'Experience' => ($candidate['total_experience_years'] ?? '0') . ' Yrs',
                        'Job Role'   => $job['title'] ?? 'Open Candidate',
                    ]
                ];

            case 'candidate':
                $candidate = (new CandidateModel())->find($id);
                if (!$candidate) return null;
                return [
                    'type'         => 'Candidate Profile',
                    'id'           => $id,
                    'title'        => $candidate['full_name'] ?? 'Candidate',
                    'email'        => $candidate['email'] ?? '',
                    'phone'        => $candidate['phone'] ?? '',
                    'status'       => 'ACTIVE',
                    'detail_url'   => '/admin/candidates',
                    'meta'         => [
                        'Experience' => ($candidate['total_experience_years'] ?? '0') . ' Yrs',
                        'Skills'     => $candidate['primary_skills'] ?? '',
                    ]
                ];

            case 'employer_enquiry':
                $emp = (new EmployerEnquiryModel())->find($id);
                if (!$emp) return null;
                return [
                    'type'         => 'Employer Hiring Mandate',
                    'id'           => $id,
                    'title'        => ($emp['company_name'] ?? '') . ' (' . ($emp['contact_person'] ?? '') . ')',
                    'email'        => $emp['email'] ?? '',
                    'phone'        => $emp['phone'] ?? '',
                    'status'       => $emp['status'] ?? 'NEW',
                    'detail_url'   => '/admin/enquiries',
                    'meta'         => [
                        'Required Roles' => $emp['required_roles'] ?? 'Various',
                        'Urgency'        => $emp['urgency'] ?? 'Immediate',
                    ]
                ];

            case 'contact_enquiry':
                $con = (new ContactEnquiryModel())->find($id);
                if (!$con) return null;
                return [
                    'type'         => 'General Inquiry',
                    'id'           => $id,
                    'title'        => (($con['first_name'] ?? '') . ' ' . ($con['last_name'] ?? '')) . ' — ' . ($con['subject'] ?? 'Website Inquiry'),
                    'email'        => $con['email'] ?? '',
                    'phone'        => $con['phone'] ?? '',
                    'status'       => $con['status'] ?? 'NEW',
                    'detail_url'   => '/admin/enquiries',
                ];

            default:
                return null;
        }
    }
}

<?php

namespace Crm\Controllers;

use Crm\Services\AccessScope;
use Crm\Services\Crm;
use Crm\Services\CrmException;
use Crm\Services\EnquiryService;
use Crm\Services\NumberSequenceService;

/**
 * Dedicated Leads Management Controller
 * Provides visual Kanban pipeline, quick lead intake, qualification, and stage progression.
 */
class LeadController extends CrmController
{
    private const STAGES = [
        'new' => 'New Lead',
        'assigned' => 'Contact Pending',
        'contacted' => 'Contacted',
        'qualified' => 'Qualified',
        'site_visit' => 'Site Visit Scheduled',
        'quotation_preparation' => 'Requirement / Quote Prep',
        'quotation_sent' => 'Quotation Sent',
        'negotiation' => 'Under Negotiation',
        'won' => 'Won Deal',
        'lost' => 'Lost Lead',
        'on_hold' => 'On Hold',
    ];

    /** Visual Pipeline & Kanban view of all active leads */
    public function index(): string
    {
        $db = Crm::db();
        $user = $this->user();
        $q = trim((string) $this->request->getGet('q'));
        $priority = (string) $this->request->getGet('priority');
        $sourceId = (int) $this->request->getGet('source_id');
        $viewMode = (string) ($this->request->getGet('view') ?: 'kanban');

        $builder = $db->table('crm_enquiries e')
            ->select("e.*, c.name AS customer_name, c.mobile AS customer_mobile, c.city AS customer_city, CONCAT(u.first_name, ' ', u.last_name) AS owner_name, ls.name AS source_name, pc.name AS category_name, p.name AS project_name")
            ->join('crm_customers c', 'c.id = e.customer_id', 'left')
            ->join('admin_users u', 'u.id = e.current_owner_id', 'left')
            ->join('crm_lead_sources ls', 'ls.id = e.source_id', 'left')
            ->join('crm_product_categories pc', 'pc.id = e.product_category_id', 'left')
            ->join('crm_projects p', 'p.id = e.project_id', 'left');

        $builder = AccessScope::scopeEnquiries($builder, $user);

        if ($q !== '') {
            $builder->groupStart()
                ->like('e.enquiry_no', $q)
                ->orLike('c.name', $q)
                ->orLike('c.mobile', $q)
                ->orLike('e.site_location', $q)
                ->orLike('p.name', $q)
                ->groupEnd();
        }
        if ($priority !== '') {
            $builder->where('e.priority', $priority);
        }
        if ($sourceId > 0) {
            $builder->where('e.source_id', $sourceId);
        }

        $allLeads = $builder->orderBy('e.id', 'DESC')->get()->getResultArray();

        // Organize into Kanban columns
        $columns = [];
        foreach (self::STAGES as $stageKey => $stageLabel) {
            $columns[$stageKey] = [
                'key' => $stageKey,
                'label' => $stageLabel,
                'leads' => [],
                'total_value' => 0.0,
            ];
        }

        foreach ($allLeads as $lead) {
            $st = $lead['status'];
            if (isset($columns[$st])) {
                $columns[$st]['leads'][] = $lead;
                $columns[$st]['total_value'] += (float) ($lead['est_budget'] ?: $lead['final_value'] ?: 0);
            }
        }

        $sources = $db->table('crm_lead_sources')->where('is_active', 1)->orderBy('name')->get()->getResultArray();
        $categories = $db->table('crm_product_categories')->orderBy('sort_order')->get()->getResultArray();

        return $this->render('leads/index', [
            'columns' => $columns,
            'allLeads' => $allLeads,
            'stages' => self::STAGES,
            'sources' => $sources,
            'categories' => $categories,
            'q' => $q,
            'priority' => $priority,
            'sourceId' => $sourceId,
            'viewMode' => $viewMode,
            'totalCount' => count($allLeads),
        ], 'Leads Pipeline Management', 'leads');
    }

    /** Quick lead capture */
    public function create()
    {
        $post = (array) $this->request->getPost();
        return $this->act(function () use ($post) {
            $user = $this->user();
            // Reuse EnquiryService creation with is_lead flag
            $post['is_lead'] = 1;
            $enquiryId = EnquiryService::create($post, $user);
            return 'Lead ' . $enquiryId . ' created successfully.';
        }, 'New lead captured.', site_url('crm/leads'));
    }

    /** Quick stage move for Kanban drag-and-drop or select */
    public function moveStage(int $id)
    {
        $newStage = (string) $this->request->getPost('stage');
        $remarks = (string) ($this->request->getPost('remarks') ?: 'Stage updated from Leads Pipeline');
        return $this->act(function () use ($id, $newStage, $remarks) {
            EnquiryService::changeStatus($id, $newStage, $this->user(), $remarks);
            return 'Lead stage updated to ' . (self::STAGES[$newStage] ?? $newStage);
        }, 'Stage updated.', site_url('crm/leads'));
    }
}

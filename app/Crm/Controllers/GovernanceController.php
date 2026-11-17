<?php

namespace Crm\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use Crm\Services\Crm;
use Crm\Services\CrmException;
use Crm\Services\ApprovalService;

class GovernanceController extends CrmController
{
    public function index(): string
    {
        $this->authorize('settings.manage');
        ApprovalService::seedDefaultRules();
        $db = Crm::db();

        $rules = $db->table('crm_approval_rules')->orderBy('priority', 'ASC')->get()->getResultArray();
        $categories = $db->table('crm_product_categories')->where('is_active', 1)->orderBy('name')->get()->getResultArray();
        $brands = $db->table('crm_product_brands')->where('is_active', 1)->orderBy('name')->get()->getResultArray();
        $users = $db->query("SELECT id, CONCAT(first_name, ' ', last_name) AS name FROM admin_users WHERE is_active = 1 ORDER BY first_name")->getResultArray();

        return $this->render('settings/governance', [
            'rules' => $rules,
            'categories' => $categories,
            'brands' => $brands,
            'users' => $users,
        ], 'Sales Governance & Commercial Approval Matrix', 'settings');
    }

    public function saveRule(): RedirectResponse
    {
        $this->authorize('settings.manage');
        $db = Crm::db();
        $post = $this->request->getPost();
        $id = ! empty($post['id']) ? (int) $post['id'] : null;

        $ruleName = trim((string) ($post['rule_name'] ?? ''));
        if ($ruleName === '') {
            return redirect()->back()->withInput()->with('error', 'Rule name is required.');
        }

        $ruleCode = ! empty($post['rule_code']) ? trim((string) $post['rule_code']) : 'RULE-' . strtoupper(bin2hex(random_bytes(4)));

        $data = [
            'rule_code' => $ruleCode,
            'rule_name' => $ruleName,
            'rule_description' => $post['rule_description'] ?? null,
            'category_id' => ! empty($post['category_id']) ? (int) $post['category_id'] : null,
            'brand_id' => ! empty($post['brand_id']) ? (int) $post['brand_id'] : null,
            'min_discount_pct' => (float) ($post['min_discount_pct'] ?? 0),
            'max_discount_pct' => $post['max_discount_pct'] !== '' ? (float) $post['max_discount_pct'] : null,
            'min_discount_amount' => $post['min_discount_amount'] !== '' ? (float) $post['min_discount_amount'] : null,
            'margin_floor_pct' => (float) ($post['margin_floor_pct'] ?? 15.00),
            'required_role' => $post['required_role'] ?? 'sales_manager',
            'approval_level' => (int) ($post['approval_level'] ?? 1),
            'priority' => (int) ($post['priority'] ?? 10),
            'is_active' => ! empty($post['is_active']) ? 1 : 0,
            'updated_at' => Crm::now(),
        ];

        if ($id) {
            $db->table('crm_approval_rules')->where('id', $id)->update($data);
            $msg = "Approval rule '{$ruleName}' updated successfully.";
        } else {
            $data['created_at'] = Crm::now();
            $db->table('crm_approval_rules')->insert($data);
            $msg = "New approval rule '{$ruleName}' created.";
        }

        return redirect()->to(site_url('crm/settings/governance'))->with('success', $msg);
    }

    public function toggleRule(int $id): RedirectResponse
    {
        $this->authorize('settings.manage');
        $db = Crm::db();
        $rule = $db->table('crm_approval_rules')->where('id', $id)->get()->getRowArray() ?? throw new PageNotFoundException('Rule not found.');
        $newActive = (int) $rule['is_active'] === 1 ? 0 : 1;

        $db->table('crm_approval_rules')->where('id', $id)->update(['is_active' => $newActive, 'updated_at' => Crm::now()]);
        return redirect()->to(site_url('crm/settings/governance'))->with('success', "Approval rule status changed to " . ($newActive ? 'Active' : 'Inactive') . ".");
    }
}

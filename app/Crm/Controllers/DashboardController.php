<?php

namespace Crm\Controllers;

use Crm\Services\AccessScope;
use Crm\Services\Crm;
use Crm\Services\DashboardService;
use Crm\Services\NotificationService;

/** Role-specific dashboards, month calendar and the notification centre. Every figure comes from DashboardService / live queries. */
class DashboardController extends CrmController
{
    private function variant(): string
    {
        $r = $this->user()['roles'] ?? [];
        if (array_intersect($r, ['super_admin', 'admin'])) {
            return 'admin';
        }
        if (in_array('sales_manager', $r, true)) {
            return 'manager';
        }
        if (in_array('management', $r, true)) {
            return 'management';
        }

        return 'executive';
    }

    private function inList(string $col): string
    {
        $ids = AccessScope::visibleUserIds($this->user());

        return $ids === null ? '1=1' : "{$col} IN (" . implode(',', array_map('intval', $ids)) . ')';
    }

    public function index(): string
    {
        $u   = $this->user();
        $m   = DashboardService::metrics($u);
        $db  = Crm::db();
        $now = Crm::now();
        $fo  = $this->inList('f.assigned_to');
        $base = "SELECT f.id, f.due_at, f.activity_type, f.status, f.next_action, f.enquiry_id, e.enquiry_no, c.id customer_id, c.name customer_name, CONCAT(a.first_name,' ',a.last_name) assignee
                 FROM crm_followups f JOIN crm_customers c ON c.id = f.customer_id LEFT JOIN crm_enquiries e ON e.id = f.enquiry_id LEFT JOIN admin_users a ON a.id = f.assigned_to";
        $today   = $db->query("{$base} WHERE f.status = 'pending' AND DATE(f.due_at) = DATE(?) AND {$fo} ORDER BY f.due_at LIMIT 8", [$now])->getResultArray();
        $overdue = $db->query("{$base} WHERE ((f.status = 'pending' AND f.due_at < ?) OR f.status = 'missed') AND {$fo} ORDER BY f.due_at LIMIT 8", [$now])->getResultArray();
        $visits  = $db->query("SELECT v.id, v.visit_no, v.scheduled_at, v.enquiry_id, e.enquiry_no, c.name customer_name FROM crm_site_visits v JOIN crm_enquiries e ON e.id = v.enquiry_id JOIN crm_customers c ON c.id = v.customer_id
                               WHERE v.status = 'scheduled' AND v.scheduled_at >= ? AND " . $this->inList('v.assigned_to') . ' ORDER BY v.scheduled_at LIMIT 6', [$now])->getResultArray();
        $approvals = [];
        if (crm_can('quotations.view')) {
            $o = $this->inList('e.current_owner_id');
            $approvals = $db->query("SELECT n.id, n.negotiation_no, n.status, n.requested_discount_pct, n.proposed_price, n.required_level, n.created_at, q.quotation_no, c.name customer_name
                 FROM crm_negotiations n JOIN crm_quotations q ON q.id = n.quotation_id JOIN crm_enquiries e ON e.id = q.enquiry_id JOIN crm_customers c ON c.id = q.customer_id
                 WHERE n.status IN ('pending','escalated') AND {$o} ORDER BY n.created_at LIMIT 8")->getResultArray();
        }
        $quotesPending = (int) $db->query("SELECT COUNT(*) c FROM crm_quotations q JOIN crm_enquiries e ON e.id = q.enquiry_id WHERE q.status IN ('draft','sent') AND " . $this->inList('e.current_owner_id'))->getRow()->c;
        $stageOrder = ['new', 'assigned', 'contacted', 'qualified', 'site_visit', 'quotation_preparation', 'quotation_sent', 'negotiation', 'won', 'lost', 'on_hold'];
        $stages = array_fill_keys($stageOrder, 0);
        foreach ($m['by_stage'] as $s) {
            $stages[$s['status']] = (int) $s['n'];
        }

        return $this->render('dashboard/index', [
            'variant' => $this->variant(), 'm' => $m, 'today' => $today, 'overdue' => $overdue, 'visits' => $visits,
            'approvals' => $approvals, 'quotesPending' => $quotesPending, 'stages' => $stages,
        ], 'Dashboard', 'dashboard');
    }

    public function calendar(): string
    {
        $month = (string) ($this->request->getGet('m') ?? date('Y-m'));
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            $month = date('Y-m');
        }
        $first = strtotime($month . '-01');
        $last  = strtotime(date('Y-m-t', $first));
        $db    = Crm::db();
        $f     = $db->query("SELECT f.id, f.due_at, f.status, f.activity_type, f.enquiry_id, c.name customer_name FROM crm_followups f JOIN crm_customers c ON c.id = f.customer_id
                             WHERE f.due_at BETWEEN ? AND ? AND f.status <> 'cancelled' AND " . $this->inList('f.assigned_to') . ' ORDER BY f.due_at', [date('Y-m-d 00:00:00', $first), date('Y-m-d 23:59:59', $last)])->getResultArray();
        $v     = $db->query("SELECT v.id, v.visit_no, v.scheduled_at, v.status, c.name customer_name FROM crm_site_visits v JOIN crm_customers c ON c.id = v.customer_id
                             WHERE v.scheduled_at BETWEEN ? AND ? AND v.status <> 'cancelled' AND " . $this->inList('v.assigned_to') . ' ORDER BY v.scheduled_at', [date('Y-m-d 00:00:00', $first), date('Y-m-d 23:59:59', $last)])->getResultArray();
        $events = [];
        $now = Crm::now();
        foreach ($f as $r) {
            $overdue = $r['status'] === 'missed' || ($r['status'] === 'pending' && $r['due_at'] < $now);
            $events[substr($r['due_at'], 0, 10)][] = ['cls' => $overdue ? 'o' : '', 'time' => substr($r['due_at'], 11, 5), 'text' => crm_label($r['activity_type']) . ' · ' . $r['customer_name'], 'url' => $r['enquiry_id'] ? 'crm/enquiries/' . $r['enquiry_id'] : 'crm/followups'];
        }
        foreach ($v as $r) {
            $events[substr($r['scheduled_at'], 0, 10)][] = ['cls' => 'v', 'time' => substr($r['scheduled_at'], 11, 5), 'text' => 'Visit ' . $r['visit_no'] . ' · ' . $r['customer_name'], 'url' => 'crm/visits/' . $r['id']];
        }

        return $this->render('dashboard/calendar', [
            'month' => $month, 'first' => $first, 'daysIn' => (int) date('t', $first), 'startDow' => (int) date('N', $first) - 1, 'events' => $events,
            'prev' => date('Y-m', strtotime('-1 month', $first)), 'next' => date('Y-m', strtotime('+1 month', $first)), 'today' => date('Y-m-d'),
        ], 'Calendar', 'calendar');
    }

    public function notifications(): string
    {
        $page  = $this->page();
        $per   = 25;
        $q     = Crm::db()->table('crm_notifications')->where('user_id', $this->uid());
        $total = (int) (clone $q)->countAllResults();
        $rows  = $q->orderBy('id', 'DESC')->limit($per, ($page - 1) * $per)->get()->getResultArray();

        return $this->render('dashboard/notifications', ['rows' => $rows, 'total' => $total, 'per' => $per, 'page' => $page, 'unread' => NotificationService::unreadCount($this->uid())], 'Notifications', '');
    }

    public function notificationCount()
    {
        return $this->json(['count' => NotificationService::unreadCount($this->uid())]);
    }

    public function markRead(?string $id = null)
    {
        NotificationService::markRead($this->uid(), $id !== null ? (int) $id : null);
        $link = $id !== null ? Crm::db()->table('crm_notifications')->select('link')->where(['id' => (int) $id, 'user_id' => $this->uid()])->get()->getRowArray()['link'] ?? null : null;
        if ($link && str_starts_with($link, 'crm') && ! str_contains($link, '//')) {
            return redirect()->to(site_url($link));
        }

        return redirect()->to(site_url('crm/notifications'))->with('success', $id !== null ? 'Notification marked as read.' : 'All notifications marked as read.');
    }
}

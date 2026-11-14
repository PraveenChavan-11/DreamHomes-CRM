<?php

namespace Tests\Crm;

use Crm\Services\AccessScope;
use Crm\Services\Crm;
use Crm\Services\CrmException;
use Crm\Services\DemoService;
use Crm\Services\EmailService;
use Crm\Services\RuleAdminService;
use Crm\Services\Settings;

/** Service-level coverage for the admin / email / product / user web slice (HTTP behaviour is covered by the smoke checks in docs/CRM_TESTING_REPORT.md). */
final class WebAdminTest extends CrmTestCase
{
    public function testRuleChangesRequireSettingsPermissionAndAreAudited(): void
    {
        $exec = $this->sessionUser($this->user('sales_executive'));
        $this->expectException(CrmException::class);
        RuleAdminService::saveAssignmentRules([1 => ['priority' => 5, 'active' => 1]], $exec);
    }

    public function testAdminCanReprioritiseAssignmentRuleWithAuditEvent(): void
    {
        $admin = $this->sessionUser($this->user('admin'));
        $db    = Crm::db();
        $db->table('crm_assignment_rules')->insert(['priority' => 50, 'mode' => 'workload', 'is_active' => 1]);
        $id = (int) $db->insertID();
        $n  = RuleAdminService::saveAssignmentRules([$id => ['priority' => 7, 'active' => 0]], $admin);
        $this->assertSame(1, $n);
        $row = $db->table('crm_assignment_rules')->where('id', $id)->get()->getRowArray();
        $this->assertSame(7, (int) $row['priority']);
        $this->assertSame(0, (int) $row['is_active']);
        $this->assertGreaterThan(0, $db->table('crm_audit_events')->where(['event_type' => 'assignment_rule.updated', 'entity_id' => (string) $id])->countAllResults());
    }

    public function testNotificationPrefsAreStoredPerUser(): void
    {
        $uid = $this->user('sales_executive');
        RuleAdminService::saveMyPrefs($uid, ['discount.approved' => ['in_app' => 1, 'email' => 1]]);
        $db  = Crm::db();
        $row = $db->table('crm_notification_prefs')->where(['user_id' => $uid, 'event_type' => 'discount.approved'])->get()->getRowArray();
        $this->assertSame([1, 1], [(int) $row['in_app'], (int) $row['email']]);
        $off = $db->table('crm_notification_prefs')->where(['user_id' => $uid, 'event_type' => 'order.created'])->get()->getRowArray();
        $this->assertSame(0, (int) $off['in_app'], 'events not ticked are switched off');
    }

    public function testManualEmailInLogOnlyModeIsNeverMarkedSentOrDelivered(): void
    {
        Settings::set('email.mode', 'log_only');
        $id  = EmailService::queue('someone@example.invalid', ['subject' => 'Hello', 'body' => 'Body']);
        $row = Crm::db()->table('crm_email_log')->where('id', $id)->get()->getRowArray();
        $this->assertSame('queued', $row['status']);
        $this->assertStringContainsString('NOT TRANSMITTED', (string) $row['provider_response']);
    }

    public function testDeliveredCanOnlyBeConfirmedFromSent(): void
    {
        $id = EmailService::queue('someone@example.invalid', ['subject' => 'Hello', 'body' => 'Body']);
        $this->expectException(CrmException::class);
        EmailService::confirmDelivery($id, 'delivered');
    }

    public function testDemoResetRefusesWithoutConfirmationPhraseAndForUnauthorisedUsers(): void
    {
        $exec = $this->sessionUser($this->user('sales_executive'));
        $this->assertFalse(AccessScope::can($exec, 'demo.reset'));
        try {
            DemoService::reset($exec, DemoService::CONFIRM_PHRASE);
            $this->fail('An executive must never be able to reset demo data.');
        } catch (CrmException $e) {
            $this->assertNotSame('', $e->getMessage());
        }
    }

    public function testManagerAndManagementCannotManageUsersOrSettings(): void
    {
        foreach (['sales_manager', 'management', 'sales_executive'] as $role) {
            $u = $this->sessionUser($this->user($role));
            $this->assertFalse(AccessScope::can($u, 'users.manage'), "{$role} must not manage users");
            $this->assertFalse(AccessScope::can($u, 'settings.manage'), "{$role} must not change settings");
            $this->assertFalse(AccessScope::can($u, 'demo.reset'), "{$role} must not reset demo data");
        }
        $this->assertTrue(AccessScope::can($this->sessionUser($this->user('management')), 'audit.view'));
    }
}

<?php

namespace Tests\Unit;

use App\Models\AdminUserModel;
use App\Models\PermissionModel;
use App\Models\RoleModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * TalentHub Role-Based Access Control (RBAC) & Security Hardening Test Suite
 *
 * Verifies:
 * 1. Role and Permission Models (CRUD, groupings, synchronization)
 * 2. Admin User Model (user roles assignment, retrieval)
 * 3. URL-level Security & Permission Enforcement (Super Admin vs HR Manager vs Unauthenticated)
 * 4. Preventing unauthorized route access via direct URL navigation
 *
 * @internal
 */
final class RbacSecurityTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected RoleModel $roleModel;
    protected PermissionModel $permissionModel;
    protected AdminUserModel $userModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->roleModel = new RoleModel();
        $this->permissionModel = new PermissionModel();
        $this->userModel = new AdminUserModel();
    }

    /**
     * 1. Test Permissions Grouped by Category
     */
    public function testPermissionsGroupedByCategory(): void
    {
        $grouped = $this->permissionModel->getAllGroupedByCategory();

        $this->assertIsArray($grouped);
        $this->assertNotEmpty($grouped);

        // Core categories must exist (titled casing)
        $this->assertArrayHasKey('Jobs', $grouped);
        $this->assertArrayHasKey('Applications', $grouped);
        $this->assertArrayHasKey('Candidates', $grouped);
        $this->assertArrayHasKey('Administration', $grouped);

        // Verify structure of permissions inside category
        $this->assertNotEmpty($grouped['Jobs']);
        $firstJobPerm = $grouped['Jobs'][0];
        $this->assertArrayHasKey('id', $firstJobPerm);
        $this->assertArrayHasKey('code', $firstJobPerm);
        $this->assertArrayHasKey('name', $firstJobPerm);
    }

    /**
     * 2. Test Role Model Methods and Counts
     */
    public function testRoleModelRetrievalAndCounts(): void
    {
        $rolesWithCounts = $this->roleModel->getRolesWithCounts();

        $this->assertIsArray($rolesWithCounts);
        $this->assertNotEmpty($rolesWithCounts);

        $superAdminRole = null;
        $hrRole = null;

        foreach ($rolesWithCounts as $r) {
            if ($r['slug'] === 'super_admin') {
                $superAdminRole = $r;
            } elseif ($r['slug'] === 'hr_manager') {
                $hrRole = $r;
            }
        }

        $this->assertNotNull($superAdminRole, 'super_admin role must exist in database.');
        $this->assertNotNull($hrRole, 'hr_manager role must exist in database.');

        $this->assertArrayHasKey('users_count', $superAdminRole);
        $this->assertArrayHasKey('permissions_count', $superAdminRole);
        $this->assertGreaterThanOrEqual(1, (int) $superAdminRole['users_count']);

        // Check HR role permissions
        $hrWithPerms = $this->roleModel->getRoleWithPermissions((int) $hrRole['id']);
        $this->assertNotNull($hrWithPerms);
        $this->assertNotEmpty($hrWithPerms['permissions']);
    }

    /**
     * 3. Test Role Permission Synchronization
     */
    public function testRolePermissionSync(): void
    {
        // Create a temporary test role
        $roleId = $this->roleModel->insert([
            'name'        => 'Test Automation Role',
            'slug'        => 'test_auto_role_' . bin2hex(random_bytes(3)),
            'description' => 'Temporary test role for automated RBAC testing',
        ]);

        $this->assertIsNumeric($roleId);

        // Pick 2 sample permissions
        $db = \Config\Database::connect();
        $samplePerms = $db->table('permissions')->limit(2)->get()->getResultArray();
        $this->assertCount(2, $samplePerms);
        $permIds = [(int) $samplePerms[0]['id'], (int) $samplePerms[1]['id']];

        // Sync permissions
        $syncResult = $this->roleModel->syncPermissions((int) $roleId, $permIds);
        $this->assertTrue($syncResult);

        $roleData = $this->roleModel->getRoleWithPermissions((int) $roleId);
        $this->assertCount(2, $roleData['permissions']);

        // Clear permissions
        $this->roleModel->syncPermissions((int) $roleId, []);
        $roleDataAfterClear = $this->roleModel->getRoleWithPermissions((int) $roleId);
        $this->assertEmpty($roleDataAfterClear['permissions']);

        // Clean up test role
        $this->roleModel->delete((int) $roleId);
    }

    /**
     * 4. Test User Role Assignment
     */
    public function testUserRoleAssignment(): void
    {
        $db = \Config\Database::connect();
        $superAdminUser = $db->table('admin_users')->where('username', 'admin')->get()->getRowArray();
        $this->assertNotNull($superAdminUser);

        $userWithRoles = $this->userModel->getUserWithRoles((int) $superAdminUser['id']);
        $this->assertNotNull($userWithRoles);
        $this->assertNotEmpty($userWithRoles['roles']);
        $this->assertSame('super_admin', $userWithRoles['roles'][0]['slug']);
    }

    /**
     * 5. Test Unauthenticated Access Blocked
     * Unauthorized requests to /admin/... without session must redirect to /admin/login
     */
    public function testUnauthenticatedUserRedirectedToLogin(): void
    {
        $result = $this->withSession([])->get('admin/jobs');
        $result->assertRedirectTo(base_url('admin/login'));

        $resultUsers = $this->withSession([])->get('admin/users');
        $resultUsers->assertRedirectTo(base_url('admin/login'));

        $resultSettings = $this->withSession([])->get('admin/settings');
        $resultSettings->assertRedirectTo(base_url('admin/login'));
    }

    /**
     * 6. Test Super Admin Access to All Protected Routes
     */
    public function testSuperAdminHasFullAccess(): void
    {
        $superAdminSession = [
            'admin_user' => [
                'id'          => 1,
                'username'    => 'admin',
                'name'        => 'Super Administrator',
                'email'       => 'admin@talenthubconsultant.com',
                'roles'       => [
                    [
                        'id'   => 1,
                        'name' => 'Super Administrator',
                        'slug' => 'super_admin',
                    ],
                ],
                'permissions' => ['*'],
            ],
        ];

        // Should be able to view jobs
        $resultJobs = $this->withSession($superAdminSession)->get('admin/jobs');
        $resultJobs->assertStatus(200);

        // Should be able to view users management
        $resultUsers = $this->withSession($superAdminSession)->get('admin/users');
        $resultUsers->assertStatus(200);

        // Should be able to view roles management
        $resultRoles = $this->withSession($superAdminSession)->get('admin/roles');
        $resultRoles->assertStatus(200);

        // Should be able to view settings
        $resultSettings = $this->withSession($superAdminSession)->get('admin/settings');
        $resultSettings->assertStatus(200);

        // Engagement subscribers and modules
        $this->withSession($superAdminSession)->get('admin/engagement/subscribers')->assertStatus(200);
        $this->withSession($superAdminSession)->get('admin/engagement/announcements')->assertStatus(200);
        $this->withSession($superAdminSession)->get('admin/engagement/quick-notice')->assertStatus(200);
        $this->withSession($superAdminSession)->get('admin/engagement/campaigns')->assertStatus(200);
        $this->withSession($superAdminSession)->get('admin/engagement/leads')->assertStatus(200);
        $this->withSession($superAdminSession)->get('admin/engagement/analytics')->assertStatus(200);
        $this->withSession($superAdminSession)->get('admin/engagement/cookies')->assertStatus(200);

        // Content Articles CMS
        $this->withSession($superAdminSession)->get('admin/content/articles')->assertStatus(200);
    }

    /**
     * 7. Test HR Manager Access: Allowed for recruitment, BLOCKED for administration
     * An HR Manager must NOT be able to open /admin/users, /admin/roles, or /admin/settings
     * even by typing the URL directly.
     */
    public function testHrManagerUrlAccessControl(): void
    {
        $hrSession = [
            'admin_user' => [
                'id'          => 999,
                'username'    => 'hr_partner',
                'name'        => 'HR Talent Partner',
                'email'       => 'hr@talenthubconsultant.com',
                'roles'       => [
                    [
                        'id'   => 4,
                        'name' => 'HR Manager / Talent Partner',
                        'slug' => 'hr_manager',
                    ],
                ],
                'permissions' => [
                    'jobs.publish',
                    'jobs.close',
                    'candidates.view',
                    'candidates.manage',
                    'resumes.download',
                    'applications.view',
                    'applications.transition',
                    'applications.notes',
                    'employers.manage',
                    'enquiries.manage',
                ],
            ],
        ];

        // 1. HR Manager CAN access Recruitment Job Postings
        $resultJobs = $this->withSession($hrSession)->get('admin/jobs');
        $resultJobs->assertStatus(200);

        // 2. HR Manager CAN access Candidate Applications
        $resultApps = $this->withSession($hrSession)->get('admin/applications');
        $resultApps->assertStatus(200);

        // 3. HR Manager CAN access Employer Enquiries
        $resultEnq = $this->withSession($hrSession)->get('admin/enquiries/employers');
        $resultEnq->assertStatus(200);

        // 4. HR Manager CAN access General Resume Bank
        $resultResumes = $this->withSession($hrSession)->get('admin/resume-bank');
        $resultResumes->assertStatus(200);

        // 5. HR Manager CANNOT access Staff & Users Management (/admin/users) -> Redirected to /admin
        $resultUsers = $this->withSession($hrSession)->get('admin/users');
        $resultUsers->assertRedirectTo(base_url('admin'));
        $resultUsers->assertSessionHas('error');

        // 6. HR Manager CANNOT access Roles & Permissions (/admin/roles) -> Redirected to /admin
        $resultRoles = $this->withSession($hrSession)->get('admin/roles');
        $resultRoles->assertRedirectTo(base_url('admin'));
        $resultRoles->assertSessionHas('error');

        // 7. HR Manager CANNOT access Website Settings (/admin/settings) -> Redirected to /admin
        $resultSettings = $this->withSession($hrSession)->get('admin/settings');
        $resultSettings->assertRedirectTo(base_url('admin'));
        $resultSettings->assertSessionHas('error');

        // 8. HR Manager CANNOT access Security Audit Logs (/admin/audit-logs) -> Redirected to /admin
        $resultAudit = $this->withSession($hrSession)->get('admin/audit-logs');
        $resultAudit->assertRedirectTo(base_url('admin'));
        $resultAudit->assertSessionHas('error');
    }

    /**
     * 8. Test Super Admin Protection from Deletion
     */
    public function testSuperAdminProtectedFromDeletion(): void
    {
        $role1 = $this->roleModel->find(1);
        $this->assertNotNull($role1);
        $this->assertSame('super_admin', $role1['slug']);

        session()->set('admin_user', [
            'id'          => 1,
            'username'    => 'admin',
            'name'        => 'Super Administrator',
            'roles'       => [['id' => 1, 'slug' => 'super_admin', 'name' => 'Super Administrator']],
            'permissions' => ['*'],
        ]);

        // Call controller directly to verify business validation prevents super_admin deletion
        $controller = new \App\Controllers\Admin\RolesController();
        $controller->initController(
            service('request'),
            service('response'),
            service('logger')
        );

        $response = $controller->delete(1);

        // Verify flash error is set
        $this->assertTrue(session()->has('error'));
        $this->assertStringContainsString('cannot be deleted', session()->getFlashdata('error'));

        // Verify role 1 was NOT deleted
        $roleStillExists = $this->roleModel->find(1);
        $this->assertNotNull($roleStillExists);
        $this->assertSame('super_admin', $roleStillExists['slug']);
    }
}

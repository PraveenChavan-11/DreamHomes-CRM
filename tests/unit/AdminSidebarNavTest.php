<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class AdminSidebarNavTest extends CIUnitTestCase
{
    private function render(array $perms, string $activeMenu, bool $super = false): string
    {
        $canAccess = static function (string ...$codes) use ($perms, $super): bool {
            if ($super) {
                return true;
            }
            foreach ($codes as $c) {
                if (in_array($c, $perms, true)) {
                    return true;
                }
            }
            return false;
        };
        $counts = ['new_applications' => 4];
        ob_start();
        include APPPATH . 'Views/admin/layouts/_sidebar_nav.php';
        return (string) ob_get_clean();
    }

    public function testSuperAdminSeesAllGroupsAndActiveGroupIsFlagged(): void
    {
        $html = $this->render([], 'social_queue', true);
        foreach (['recruitment', 'consulting', 'products', 'social', 'comms', 'content', 'system'] as $g) {
            $this->assertStringContainsString('data-group="' . $g . '"', $html);
        }
        $this->assertMatchesRegularExpression('/data-group="social" data-active="1"/', $html);
        $this->assertMatchesRegularExpression('/data-group="recruitment" data-active="0"/', $html);
        $this->assertStringContainsString('aria-current="page"', $html);
    }

    public function testLimitedUserOnlySeesPermittedGroups(): void
    {
        $html = $this->render(['applications.view'], 'applications');
        $this->assertStringContainsString('data-group="recruitment"', $html);
        $this->assertStringNotContainsString('data-group="social"', $html);
        $this->assertStringNotContainsString('data-group="comms"', $html);
        $this->assertStringContainsString('Applications', $html);
        $this->assertStringNotContainsString('General Resume Bank', $html);
        $this->assertStringContainsString('>4<', $html); // real badge count rendered
    }
}

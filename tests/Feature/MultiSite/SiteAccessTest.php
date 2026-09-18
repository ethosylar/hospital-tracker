<?php

namespace Tests\Feature\MultiSite;

use App\Support\SiteAccess;

class SiteAccessTest extends MultiSiteTestCase
{
    public function test_view_access_can_view_but_cannot_manage(): void
    {
        $site =
            $this->createSite(
                'VIEW'
            );

        $user =
            $this->createUser(
                [],
                [
                    [$site, 'VIEW'],
                ]
            );

        $this->assertTrue(
            SiteAccess::canViewSite(
                $user,
                (int) $site->id
            )
        );

        $this->assertFalse(
            SiteAccess::canManageSite(
                $user,
                (int) $site->id
            )
        );
    }

    public function test_manage_access_can_view_and_manage(): void
    {
        $site =
            $this->createSite(
                'MANAGE'
            );

        $user =
            $this->createUser(
                [],
                [
                    [$site, 'MANAGE'],
                ]
            );

        $this->assertTrue(
            SiteAccess::canViewSite(
                $user,
                (int) $site->id
            )
        );

        $this->assertTrue(
            SiteAccess::canManageSite(
                $user,
                (int) $site->id
            )
        );
    }

    public function test_user_without_site_assignment_has_no_site_access(): void
    {
        $site =
            $this->createSite(
                'NONE'
            );

        $user =
            $this->createUser();

        $this->assertFalse(
            SiteAccess::canViewSite(
                $user,
                (int) $site->id
            )
        );

        $this->assertFalse(
            SiteAccess::canManageSite(
                $user,
                (int) $site->id
            )
        );
    }

    public function test_system_all_bypasses_normal_site_assignments(): void
    {
        $siteA =
            $this->createSite(
                'SYS_A'
            );

        $siteB =
            $this->createSite(
                'SYS_B'
            );

        $user =
            $this->createUser([
                'system.all',
            ]);

        $this->assertTrue(
            SiteAccess::canViewSite(
                $user,
                (int) $siteA->id
            )
        );

        $this->assertTrue(
            SiteAccess::canManageSite(
                $user,
                (int) $siteA->id
            )
        );

        $this->assertTrue(
            SiteAccess::canViewSite(
                $user,
                (int) $siteB->id
            )
        );

        $this->assertTrue(
            SiteAccess::canManageSite(
                $user,
                (int) $siteB->id
            )
        );
    }
}

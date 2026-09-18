<?php

namespace Tests\Feature\MultiSite;

class PermitRiskIssueSiteAccessTest extends MultiSiteTestCase
{
    public function test_permit_detail_is_hidden_outside_users_sites(): void
    {
        $klg =
            $this->createSite(
                'KLG'
            );

        $amp =
            $this->createSite(
                'AMP'
            );

        $klgPermit =
            $this->createPermit(
                $klg
            );

        $ampPermit =
            $this->createPermit(
                $amp
            );

        $user =
            $this->createUser(
                [
                    'permits.read',
                ],
                [
                    [$klg, 'VIEW'],
                ]
            );

        $this->signIn(
            $user
        );

        $this->getJson(
            '/api/external-permits/'
                . $klgPermit->id
        )->assertOk();

        $this->getJson(
            '/api/external-permits/'
                . $ampPermit->id
        )->assertNotFound();
    }

    public function test_sync_run_detail_is_site_scoped(): void
    {
        $klg =
            $this->createSite(
                'KLG'
            );

        $amp =
            $this->createSite(
                'AMP'
            );

        $klgRun =
            $this->createSyncRun(
                $klg
            );

        $ampRun =
            $this->createSyncRun(
                $amp
            );

        /*
         * Historically these routes sit under audit.view.
         * permits.read is also supplied so this test remains
         * compatible if route permissions are later separated.
         */
        $user =
            $this->createUser(
                [
                    'audit.view',
                    'permits.read',
                ],
                [
                    [$klg, 'VIEW'],
                ]
            );

        $this->signIn(
            $user
        );

        $this->getJson(
            '/api/integrations/eptw/sync-runs/'
                . $klgRun->id
        )->assertOk();

        $this->getJson(
            '/api/integrations/eptw/sync-runs/'
                . $ampRun->id
        )->assertNotFound();
    }

    public function test_view_only_site_cannot_start_sync_even_with_permits_sync_permission(): void
    {
        $site =
            $this->createSite(
                'SYNC'
            );

        $this->createExternalSource(
            $site
        );

        $user =
            $this->createUser(
                [
                    'permits.sync',
                ],
                [
                    [$site, 'VIEW'],
                ]
            );

        $this->signIn(
            $user
        );

        /*
         * Site authorization must reject this before
         * an external ePTW call is attempted.
         */
        $this->postJson(
            '/api/integrations/eptw/sync',
            [
                'site_id' =>
                $site->id,

                'mode' =>
                'FULL',

                'run_async' =>
                false,
            ]
        )->assertForbidden();
    }

    public function test_cross_site_permit_to_project_link_is_rejected(): void
    {
        $klg =
            $this->createSite(
                'KLG'
            );

        $amp =
            $this->createSite(
                'AMP'
            );

        $klgProject =
            $this->createProject(
                $klg
            );

        $ampPermit =
            $this->createPermit(
                $amp
            );

        $user =
            $this->createUser(
                [
                    'permits.link',
                ],
                [
                    [$klg, 'MANAGE'],
                    [$amp, 'VIEW'],
                ]
            );

        $this->signIn(
            $user
        );

        $this->postJson(
            '/api/projects/'
                . $klgProject->id
                . '/permit-links',
            [
                'permit_id' =>
                $ampPermit->id,
            ]
        )->assertUnprocessable();
    }

    public function test_risk_issue_detail_is_hidden_outside_users_sites(): void
    {
        $klg =
            $this->createSite(
                'KLG'
            );

        $amp =
            $this->createSite(
                'AMP'
            );

        $klgIssue =
            $this->createRiskIssue(
                $klg
            );

        $ampIssue =
            $this->createRiskIssue(
                $amp
            );

        $user =
            $this->createUser(
                [
                    'risks.read',
                ],
                [
                    [$klg, 'VIEW'],
                ]
            );

        $this->signIn(
            $user
        );

        $this->getJson(
            '/api/external-risk-issues/'
                . $klgIssue->id
        )->assertOk();

        $this->getJson(
            '/api/external-risk-issues/'
                . $ampIssue->id
        )->assertNotFound();
    }

    public function test_cross_site_risk_project_link_is_rejected(): void
    {
        $klg =
            $this->createSite(
                'KLG'
            );

        $amp =
            $this->createSite(
                'AMP'
            );

        $klgIssue =
            $this->createRiskIssue(
                $klg
            );

        $ampProject =
            $this->createProject(
                $amp
            );

        $user =
            $this->createUser(
                [
                    'risks.write',
                ],
                [
                    [$klg, 'MANAGE'],
                    [$amp, 'VIEW'],
                ]
            );

        $this->signIn(
            $user
        );

        $this->postJson(
            '/api/external-risk-issues/'
                . $klgIssue->id
                . '/links',
            [
                'project_id' =>
                $ampProject->id,
            ]
        )->assertUnprocessable();
    }
}

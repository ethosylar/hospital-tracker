<?php

namespace Tests\Feature\MultiSite;

class AuditLogSiteAccessTest extends MultiSiteTestCase
{
    public function test_normal_auditor_only_sees_logs_from_visible_sites(): void
    {
        $klg =
            $this->createSite(
                'KLG'
            );

        $amp =
            $this->createSite(
                'AMP'
            );

        $user =
            $this->createUser(
                [
                    'audit.view',
                ],
                [
                    [$klg, 'VIEW'],
                ]
            );

        $klgType =
            $this->token(
                'KLG_LOG'
            );

        $ampType =
            $this->token(
                'AMP_LOG'
            );

        $globalType =
            $this->token(
                'GLOBAL_LOG'
            );

        $klgLogId =
            $this->createAuditLog(
                $klg,
                $user,
                $klgType
            );

        $ampLogId =
            $this->createAuditLog(
                $amp,
                $user,
                $ampType
            );

        $globalLogId =
            $this->createAuditLog(
                null,
                $user,
                $globalType
            );

        $this->signIn(
            $user
        );

        $response =
            $this->getJson(
                '/api/audit-logs?per_page=100'
            );

        $response
            ->assertOk()
            ->assertJsonFragment([
                'entity_type' =>
                $klgType,
            ])
            ->assertJsonMissing([
                'entity_type' =>
                $ampType,
            ])
            ->assertJsonMissing([
                'entity_type' =>
                $globalType,
            ]);

        $this->getJson(
            '/api/audit-logs/'
                . $klgLogId
        )->assertOk();

        $this->getJson(
            '/api/audit-logs/'
                . $ampLogId
        )->assertNotFound();

        $this->getJson(
            '/api/audit-logs/'
                . $globalLogId
        )->assertNotFound();
    }

    public function test_system_all_user_can_view_global_audit_log(): void
    {
        $globalLogId =
            $this->createAuditLog(
                null
            );

        $user =
            $this->createUser([
                'system.all',
                'audit.view',
            ]);

        $this->signIn(
            $user
        );

        $this->getJson(
            '/api/audit-logs/'
                . $globalLogId
        )->assertOk();
    }
}

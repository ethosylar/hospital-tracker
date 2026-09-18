<?php

namespace Tests\Feature\MultiSite;

class LookupSiteAccessTest extends MultiSiteTestCase
{
    public function test_site_filtered_lookups_only_return_records_from_selected_visible_site(): void
    {
        $klg =
            $this->createSite(
                'KLG'
            );

        $amp =
            $this->createSite(
                'AMP'
            );

        $klgDepartment =
            $this->createDepartment(
                $klg,
                'KLG_DEPT'
            );

        $ampDepartment =
            $this->createDepartment(
                $amp,
                'AMP_DEPT'
            );

        $klgSource =
            $this->createExternalSource(
                $klg
            );

        $ampSource =
            $this->createExternalSource(
                $amp
            );

        $klgOwner =
            $this->createUser(
                [],
                [
                    [$klg, 'VIEW'],
                ],
                $klgDepartment
            );

        $ampOwner =
            $this->createUser(
                [],
                [
                    [$amp, 'VIEW'],
                ],
                $ampDepartment
            );

        $viewer =
            $this->createUser(
                [],
                [
                    [$klg, 'VIEW'],
                ],
                $klgDepartment
            );

        $this->signIn(
            $viewer
        );

        $response =
            $this->getJson(
                '/api/lookups?site_id='
                    . $klg->id
            );

        $response->assertOk();

        /*
         * Site
         */
        $response->assertJsonFragment([
            'code' =>
            $klg->code,
        ]);

        $response->assertJsonMissing([
            'code' =>
            $amp->code,
        ]);

        /*
         * Department
         */
        $response->assertJsonFragment([
            'name' =>
            $klgDepartment->name,
        ]);

        $response->assertJsonMissing([
            'name' =>
            $ampDepartment->name,
        ]);

        /*
         * External Source
         */
        $response->assertJsonFragment([
            'name' =>
            $klgSource->name,
        ]);

        $response->assertJsonMissing([
            'name' =>
            $ampSource->name,
        ]);

        /*
         * Owner
         */
        $response->assertJsonFragment([
            'email' =>
            $klgOwner->email,
        ]);

        $response->assertJsonMissing([
            'email' =>
            $ampOwner->email,
        ]);
    }

    public function test_explicit_lookup_for_inaccessible_site_returns_404(): void
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
                [],
                [
                    [$klg, 'VIEW'],
                ]
            );

        $this->signIn(
            $user
        );

        $this->getJson(
            '/api/lookups?site_id='
                . $amp->id
        )->assertNotFound();
    }

    public function test_system_all_user_can_request_any_site_lookup(): void
    {
        $site =
            $this->createSite(
                'SYSTEM'
            );

        $department =
            $this->createDepartment(
                $site
            );

        $user =
            $this->createUser([
                'system.all',
            ]);

        $this->signIn(
            $user
        );

        $this->getJson(
            '/api/lookups?site_id='
                . $site->id
        )
            ->assertOk()
            ->assertJsonFragment([
                'name' =>
                $department->name,
            ]);
    }
}

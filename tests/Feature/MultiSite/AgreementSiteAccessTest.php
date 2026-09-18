<?php

namespace Tests\Feature\MultiSite;

class AgreementSiteAccessTest extends MultiSiteTestCase
{
    public function test_user_can_view_agreement_in_visible_site_but_not_other_site(): void
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
                $klg
            );

        $ampDepartment =
            $this->createDepartment(
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

        $klgAgreement =
            $this->createAgreement(
                $klg,
                $klgDepartment,
                $klgOwner
            );

        $ampAgreement =
            $this->createAgreement(
                $amp,
                $ampDepartment,
                $ampOwner
            );

        $viewer =
            $this->createUser(
                [
                    'agreements.view.all',
                ],
                [
                    [$klg, 'VIEW'],
                ],
                $klgDepartment
            );

        $this->signIn(
            $viewer
        );

        $this->getJson(
            '/api/agreements/'
                . $klgAgreement->id
        )->assertOk();

        $this->getJson(
            '/api/agreements/'
                . $ampAgreement->id
        )->assertNotFound();
    }

    public function test_view_site_cannot_edit_agreement_even_with_edit_permission(): void
    {
        $site =
            $this->createSite(
                'AGR_VIEW'
            );

        $department =
            $this->createDepartment(
                $site
            );

        $user =
            $this->createUser(
                [
                    'agreements.view.all',
                    'agreements.edit',
                ],
                [
                    [$site, 'VIEW'],
                ],
                $department
            );

        $agreement =
            $this->createAgreement(
                $site,
                $department,
                $user
            );

        $this->signIn(
            $user
        );

        $this->putJson(
            '/api/agreements/'
                . $agreement->id,
            [
                'title' =>
                'Should Not Update Agreement',
            ]
        )->assertForbidden();
    }

    public function test_manage_site_can_edit_draft_agreement(): void
    {
        $site =
            $this->createSite(
                'AGR_MANAGE'
            );

        $department =
            $this->createDepartment(
                $site
            );

        $user =
            $this->createUser(
                [
                    'agreements.view.all',
                    'agreements.edit',
                ],
                [
                    [$site, 'MANAGE'],
                ],
                $department
            );

        $agreement =
            $this->createAgreement(
                $site,
                $department,
                $user
            );

        $this->signIn(
            $user
        );

        $this->putJson(
            '/api/agreements/'
                . $agreement->id,
            [
                'title' =>
                'Updated Phase 1J Agreement',
            ]
        )->assertOk();

        $this->assertDatabaseHas(
            'dt_agreements',
            [
                'id' =>
                $agreement->id,

                'title' =>
                'Updated Phase 1J Agreement',
            ]
        );
    }
}

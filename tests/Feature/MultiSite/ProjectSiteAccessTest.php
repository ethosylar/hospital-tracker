<?php

namespace Tests\Feature\MultiSite;

class ProjectSiteAccessTest extends MultiSiteTestCase
{
    public function test_project_list_only_contains_visible_sites(): void
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

        $klgProject =
            $this->createProject(
                $klg,
                $klgDepartment
            );

        $ampProject =
            $this->createProject(
                $amp,
                $ampDepartment
            );

        $user =
            $this->createUser(
                [
                    'projects.read',
                ],
                [
                    [$klg, 'VIEW'],
                ],
                $klgDepartment
            );

        $this->signIn(
            $user
        );

        $response =
            $this->getJson(
                '/api/projects?per_page=100'
            );

        $response
            ->assertOk()
            ->assertJsonFragment([
                'code' =>
                $klgProject->code,
            ])
            ->assertJsonMissing([
                'code' =>
                $ampProject->code,
            ]);
    }

    public function test_inaccessible_project_detail_returns_404(): void
    {
        $klg =
            $this->createSite(
                'KLG'
            );

        $amp =
            $this->createSite(
                'AMP'
            );

        $ampProject =
            $this->createProject(
                $amp
            );

        $user =
            $this->createUser(
                [
                    'projects.read',
                ],
                [
                    [$klg, 'VIEW'],
                ]
            );

        $this->signIn(
            $user
        );

        $this->getJson(
            '/api/projects/'
                . $ampProject->id
        )->assertNotFound();
    }

    public function test_view_only_user_cannot_update_project_even_with_projects_write_permission(): void
    {
        $site =
            $this->createSite(
                'VIEW'
            );

        $project =
            $this->createProject(
                $site
            );

        $user =
            $this->createUser(
                [
                    'projects.write',
                ],
                [
                    [$site, 'VIEW'],
                ]
            );

        $this->signIn(
            $user
        );

        $this->putJson(
            '/api/projects/'
                . $project->id,
            [
                'name' =>
                'Should Not Update',
            ]
        )->assertForbidden();

        $this->assertDatabaseMissing(
            'dt_projects',
            [
                'id' =>
                $project->id,

                'name' =>
                'Should Not Update',
            ]
        );
    }

    public function test_manage_user_can_update_project(): void
    {
        $site =
            $this->createSite(
                'MANAGE'
            );

        $project =
            $this->createProject(
                $site
            );

        $user =
            $this->createUser(
                [
                    'projects.write',
                ],
                [
                    [$site, 'MANAGE'],
                ]
            );

        $this->signIn(
            $user
        );

        $this->putJson(
            '/api/projects/'
                . $project->id,
            [
                'name' =>
                'Updated Phase 1J Project',
            ]
        )->assertOk();

        $this->assertDatabaseHas(
            'dt_projects',
            [
                'id' =>
                $project->id,

                'name' =>
                'Updated Phase 1J Project',
            ]
        );
    }

    public function test_project_create_rejects_cross_site_department(): void
    {
        $klg =
            $this->createSite(
                'KLG'
            );

        $amp =
            $this->createSite(
                'AMP'
            );

        $ampDepartment =
            $this->createDepartment(
                $amp
            );

        $user =
            $this->createUser(
                [
                    'projects.write',
                ],
                [
                    [$klg, 'MANAGE'],
                ]
            );

        $this->signIn(
            $user
        );

        $response =
            $this->postJson(
                '/api/projects',
                [
                    'site_id' =>
                    $klg->id,

                    'code' =>
                    $this->token(
                        'NEWPRJ'
                    ),

                    'name' =>
                    'Forged Cross Site Project',

                    'department_id' =>
                    $ampDepartment->id,

                    'project_status_id' =>
                    $this->projectStatusId(),

                    'priority_id' =>
                    $this->priorityId(),
                ]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'department_id',
            ]);
    }

    public function test_system_all_user_can_access_project_without_explicit_site_assignment(): void
    {
        $site =
            $this->createSite(
                'SYS'
            );

        $project =
            $this->createProject(
                $site
            );

        $user =
            $this->createUser([
                'system.all',
                'projects.read',
            ]);

        $this->signIn(
            $user
        );

        $this->getJson(
            '/api/projects/'
                . $project->id
        )->assertOk();
    }
}

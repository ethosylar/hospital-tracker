<?php

namespace Tests\Feature\MultiSite;

class NestedProjectSiteAccessTest extends MultiSiteTestCase
{
    public function test_direct_task_file_route_uses_task_site_boundary(): void
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

        $ampProject =
            $this->createProject(
                $amp
            );

        $klgTask =
            $this->createTask(
                $klgProject
            );

        $ampTask =
            $this->createTask(
                $ampProject
            );

        $user =
            $this->createUser(
                [
                    'files.read',
                ],
                [
                    [$klg, 'VIEW'],
                ]
            );

        $this->signIn(
            $user
        );

        $this->getJson(
            '/api/tasks/'
                . $klgTask->id
                . '/files'
        )->assertOk();

        $this->getJson(
            '/api/tasks/'
                . $ampTask->id
                . '/files'
        )->assertNotFound();
    }

    public function test_project_nested_budget_route_respects_project_site(): void
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

        $ampProject =
            $this->createProject(
                $amp
            );

        $user =
            $this->createUser(
                [
                    'budget.read',
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
                . $klgProject->id
                . '/budget-lines'
        )->assertOk();

        $this->getJson(
            '/api/projects/'
                . $ampProject->id
                . '/budget-lines'
        )->assertNotFound();
    }

    public function test_project_nested_milestones_respect_project_site(): void
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

        $ampProject =
            $this->createProject(
                $amp
            );

        $this->createMilestone(
            $klgProject
        );

        $this->createMilestone(
            $ampProject
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
                . $klgProject->id
                . '/milestones'
        )->assertOk();

        $this->getJson(
            '/api/projects/'
                . $ampProject->id
                . '/milestones'
        )->assertNotFound();
    }

    public function test_view_site_cannot_update_task_even_with_tasks_write_permission(): void
    {
        $site =
            $this->createSite(
                'TASK_VIEW'
            );

        $project =
            $this->createProject(
                $site
            );

        $task =
            $this->createTask(
                $project
            );

        $user =
            $this->createUser(
                [
                    'tasks.write',
                ],
                [
                    [$site, 'VIEW'],
                ]
            );

        $this->signIn(
            $user
        );

        $this->putJson(
            '/api/tasks/'
                . $task->id,
            [
                'name' =>
                'Should Not Change',
            ]
        )->assertForbidden();
    }

    public function test_manage_site_can_update_task(): void
    {
        $site =
            $this->createSite(
                'TASK_MANAGE'
            );

        $project =
            $this->createProject(
                $site
            );

        $task =
            $this->createTask(
                $project
            );

        $user =
            $this->createUser(
                [
                    'tasks.write',
                ],
                [
                    [$site, 'MANAGE'],
                ]
            );

        $this->signIn(
            $user
        );

        $this->putJson(
            '/api/tasks/'
                . $task->id,
            [
                'name' =>
                'Updated Phase 1J Task',
            ]
        )->assertOk();

        $this->assertDatabaseHas(
            'dt_project_tasks',
            [
                'id' =>
                $task->id,

                'name' =>
                'Updated Phase 1J Task',
            ]
        );
    }
}

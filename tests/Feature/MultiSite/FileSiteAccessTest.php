<?php

namespace Tests\Feature\MultiSite;

class FileSiteAccessTest extends MultiSiteTestCase
{
    public function test_same_site_file_can_be_attached_to_project(): void
    {
        $site =
            $this->createSite(
                'FILE'
            );

        $project =
            $this->createProject(
                $site
            );

        $user =
            $this->createUser(
                [
                    'files.write',
                ],
                [
                    [$site, 'MANAGE'],
                ]
            );

        $file =
            $this->createStoredFile(
                $site,
                $user
            );

        $this->signIn(
            $user
        );

        $this->postJson(
            '/api/projects/'
                . $project->id
                . '/files/attach',
            [
                'file_id' =>
                $file->id,
            ]
        )->assertOk();

        $this->assertDatabaseHas(
            'dt_project_files',
            [
                'project_id' =>
                $project->id,

                'file_id' =>
                $file->id,
            ]
        );
    }

    public function test_cross_site_file_cannot_be_attached_to_project(): void
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

        $user =
            $this->createUser(
                [
                    'files.write',
                ],
                [
                    [$klg, 'MANAGE'],
                    [$amp, 'VIEW'],
                ]
            );

        $ampFile =
            $this->createStoredFile(
                $amp,
                $user
            );

        $this->signIn(
            $user
        );

        $this->postJson(
            '/api/projects/'
                . $klgProject->id
                . '/files/attach',
            [
                'file_id' =>
                $ampFile->id,
            ]
        )->assertUnprocessable();

        $this->assertDatabaseMissing(
            'dt_project_files',
            [
                'project_id' =>
                $klgProject->id,

                'file_id' =>
                $ampFile->id,
            ]
        );
    }

    public function test_cross_site_file_cannot_be_attached_to_task(): void
    {
        $klg =
            $this->createSite(
                'KLG'
            );

        $amp =
            $this->createSite(
                'AMP'
            );

        $project =
            $this->createProject(
                $klg
            );

        $task =
            $this->createTask(
                $project
            );

        $user =
            $this->createUser(
                [
                    'files.write',
                ],
                [
                    [$klg, 'MANAGE'],
                    [$amp, 'VIEW'],
                ]
            );

        $file =
            $this->createStoredFile(
                $amp,
                $user
            );

        $this->signIn(
            $user
        );

        $this->postJson(
            '/api/tasks/'
                . $task->id
                . '/files/attach',
            [
                'file_id' =>
                $file->id,
            ]
        )->assertUnprocessable();

        $this->assertDatabaseMissing(
            'dt_task_files',
            [
                'task_id' =>
                $task->id,

                'file_id' =>
                $file->id,
            ]
        );
    }
}

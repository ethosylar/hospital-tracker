<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class AuditSiteResolver
{
    public function resolve(string $entityType, int $entityId, array $changes = []): ?int
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Explicit Site in payload
        |--------------------------------------------------------------------------
        */

        $siteId = $this->findInt($changes, 'site_id');

        if ($siteId !== null) {
            return $siteId;
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Project reference in payload
        |--------------------------------------------------------------------------
        */

        $projectId = $this->findInt($changes, 'project_id');

        if ($projectId !== null) {
            $siteId = $this->projectSite($projectId);

            if ($siteId !== null) {
                return $siteId;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Task reference
        |--------------------------------------------------------------------------
        */

        $taskId = $this->findInt($changes, 'task_id');

        if ($taskId !== null) {
            $siteId = $this->taskSite($taskId);

            if ($siteId !== null) {
                return $siteId;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Milestone reference
        |--------------------------------------------------------------------------
        */

        $milestoneId = $this->findInt($changes, 'milestone_id');

        if ($milestoneId !== null) {
            $siteId = $this->milestoneSite($milestoneId);

            if ($siteId !== null) {
                return $siteId;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Agreement reference
        |--------------------------------------------------------------------------
        */

        $agreementId = $this->findInt($changes, 'agreement_id');

        if ($agreementId !== null) {
            $siteId = $this->agreementSite($agreementId);

            if ($siteId !== null) {
                return $siteId;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 6. Permit reference
        |--------------------------------------------------------------------------
        */

        $permitId = $this->findInt($changes, 'permit_id');

        if ($permitId !== null) {
            $siteId = $this->permitSite($permitId);

            if ($siteId !== null) {
                return $siteId;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 7. External Source reference
        |--------------------------------------------------------------------------
        */

        $sourceId = $this->findInt($changes, 'external_source_id');

        if ($sourceId !== null) {
            $siteId = $this->externalSourceSite($sourceId);

            if ($siteId !== null) {
                return $siteId;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 8. Risk/Issue reference
        |--------------------------------------------------------------------------
        */

        $riskIssueId = $this->findInt($changes, 'external_risk_issue_id');

        if ($riskIssueId !== null) {
            $siteId = $this->riskIssueSite($riskIssueId);

            if ($siteId !== null) {
                return $siteId;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 9. Entity itself
        |--------------------------------------------------------------------------
        */

        return match (strtoupper($entityType)) {
            'PROJECT' => $this->projectSite($entityId),
            'TASK' => $this->taskSite($entityId),
            'PROJECT_MILESTONE' => $this->milestoneSite($entityId),
            'PROJECT_BUDGET',
            'PROJECT_BUDGET_LINE' => $this->budgetLineSite($entityId),
            'PROJECT_BUDGET_ALLOCATION' => $this->budgetAllocationSite($entityId),
            'DEPARTMENT' => $this->departmentSite($entityId),
            'EXTERNAL_SOURCE' => $this->externalSourceSite($entityId),
            'EXTERNAL_PERMIT' => $this->permitSite($entityId),
            'PROJECT_PERMIT_LINK' => $this->permitLinkSite($entityId),
            'EPTW_SYNC' => $this->syncRunSite($entityId),
            'EXTERNAL_RISK_ISSUE' => $this->riskIssueSite($entityId),
            'EXTERNAL_RISK_ISSUE_LINK' => $this->riskIssueLinkSite($entityId),
            'FILE',
            'FILE_LINK' => $this->fileSite($entityId),
            'AGREEMENT' => $this->agreementSite($entityId),
            'AGREEMENT_FILE' => $this->agreementFileSite($entityId),
            'AGREEMENT_PROJECT_LINK' => $this->agreementProjectLinkSite($entityId),
            /*
             * AUTH, USER, ROLE, PERMISSION,
             * global master data, etc.
             */
            default => null,
        };
    }

    private function fileSite(int $fileId): ?int
    {
        return $this->asInt(
            DB::table('dt_files')
                ->where('id', $fileId)
                ->value('site_id')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Project
    |--------------------------------------------------------------------------
    */

    private function projectSite(int $projectId): ?int
    {
        return $this->asInt(
            DB::table('dt_projects')
                ->where('id', $projectId)
                ->value('site_id')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Task
    |--------------------------------------------------------------------------
    */

    private function taskSite(int $taskId): ?int
    {
        return $this->asInt(
            DB::table('dt_project_tasks as task')
                ->join('dt_projects as project', 'project.id', '=', 'task.project_id')
                ->where('task.id', $taskId)
                ->value('project.site_id')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Milestone
    |--------------------------------------------------------------------------
    */

    private function milestoneSite(int $milestoneId): ?int
    {
        return $this->asInt(
            DB::table('dt_project_milestones as milestone')
                ->join('dt_projects as project', 'project.id', '=', 'milestone.project_id')
                ->where('milestone.id', $milestoneId)
                ->value('project.site_id')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Budget Line
    |--------------------------------------------------------------------------
    */

    private function budgetLineSite(int $budgetLineId): ?int
    {
        return $this->asInt(
            DB::table('dt_project_budget_lines as line')
                ->join('dt_projects as project', 'project.id', '=', 'line.project_id')
                ->where('line.id', $budgetLineId)
                ->value('project.site_id')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Budget Allocation
    |--------------------------------------------------------------------------
    */

    private function budgetAllocationSite(int $allocationId): ?int
    {
        return $this->asInt(
            DB::table('dt_project_budget_allocations as allocation')
                ->join('dt_projects as project', 'project.id', '=', 'allocation.project_id')
                ->where('allocation.id', $allocationId)
                ->value('project.site_id')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Department
    |--------------------------------------------------------------------------
    */

    private function departmentSite(int $departmentId): ?int
    {
        return $this->asInt(
            DB::table('lt_departments')
                ->where('id', $departmentId)
                ->value('site_id')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | External Source
    |--------------------------------------------------------------------------
    */

    private function externalSourceSite(int $sourceId): ?int
    {
        return $this->asInt(
            DB::table('lt_external_sources')
                ->where('id', $sourceId)
                ->value('site_id')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Permit
    |--------------------------------------------------------------------------
    */

    private function permitSite(int $permitId): ?int
    {
        return $this->asInt(
            DB::table('dt_external_permits')
                ->where('id', $permitId)
                ->value('site_id')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Permit Link
    |--------------------------------------------------------------------------
    */

    private function permitLinkSite(int $linkId): ?int
    {
        return $this->asInt(
            DB::table('dt_project_permit_links as link')
                ->join('dt_projects as project', 'project.id', '=', 'link.project_id')
                ->where('link.id', $linkId)
                ->value('project.site_id')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Sync Run
    |--------------------------------------------------------------------------
    */

    private function syncRunSite(int $runId): ?int
    {
        return $this->asInt(
            DB::table('dt_integration_sync_runs')
                ->where('id', $runId)
                ->value('site_id')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Risk / Issue
    |--------------------------------------------------------------------------
    */

    private function riskIssueSite(int $riskIssueId): ?int
    {
        return $this->asInt(
            DB::table('dt_external_risk_issues')
                ->where('id', $riskIssueId)
                ->value('site_id')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Risk / Issue Link
    |--------------------------------------------------------------------------
    */

    private function riskIssueLinkSite(int $linkId): ?int
    {
        return $this->asInt(
            DB::table('dt_external_risk_issue_links as link')
                ->join('dt_external_risk_issues as issue', 'issue.id', '=', 'link.external_risk_issue_id')
                ->where('link.id', $linkId)
                ->value('issue.site_id')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Agreement
    |--------------------------------------------------------------------------
    */

    private function agreementSite(int $agreementId): ?int
    {
        return $this->asInt(
            DB::table('dt_agreements')
                ->where('id', $agreementId)
                ->value('site_id')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Agreement File
    |--------------------------------------------------------------------------
    */

    private function agreementFileSite(int $agreementFileId): ?int
    {
        return $this->asInt(
            DB::table('dt_agreement_files as file')
                ->join('dt_agreements as agreement', 'agreement.id', '=', 'file.agreement_id')
                ->where('file.id', $agreementFileId)
                ->value('agreement.site_id')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Agreement Project Link
    |--------------------------------------------------------------------------
    */

    private function agreementProjectLinkSite(int $linkId): ?int
    {
        return $this->asInt(
            DB::table('dt_agreement_project_links as link')
                ->join('dt_agreements as agreement', 'agreement.id', '=', 'link.agreement_id')
                ->where('link.id', $linkId)
                ->value('agreement.site_id')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Recursive payload lookup
    |--------------------------------------------------------------------------
    |
    | Handles:
    |
    | ['site_id' => 1]
    |
    | and:
    |
    | [
    |     'snapshot' => [
    |         'site_id' => 1
    |     ]
    | ]
    |--------------------------------------------------------------------------
    */

    private function findInt(array $payload, string $key): ?int
    {
        if (array_key_exists($key, $payload) && is_numeric($payload[$key])) {
            $value = (int) $payload[$key];
            return $value > 0
                ? $value
                : null;
        }

        foreach ($payload as $value) {
            if (!is_array($value)) {
                continue;
            }

            $found = $this->findInt($value, $key);

            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    private function asInt(mixed $value): ?int
    {
        if ($value === null || !is_numeric($value)) {
            return null;
        }

        $value = (int) $value;

        return $value > 0
            ? $value
            : null;
    }
}

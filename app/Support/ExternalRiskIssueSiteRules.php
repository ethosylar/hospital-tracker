<?php

namespace App\Support;

use App\Models\ExternalSource;
use App\Models\Project;
use App\Models\Site;
use Illuminate\Validation\ValidationException;

class ExternalRiskIssueSiteRules
{
    public static function validate(int $siteId, ?int $externalSourceId, ?int $projectId): void
    {
        $siteExists = Site::query()
            ->whereKey($siteId)
            ->where('is_active', true)
            ->exists();

        if (!$siteExists) {
            throw ValidationException::withMessages([
                'site_id' => [
                    'The selected Site does not exist or is inactive.',
                ],
            ]);
        }

        if ($externalSourceId !== null) {
            $source = ExternalSource::query()->find($externalSourceId);

            if (!$source) {
                throw ValidationException::withMessages([
                    'external_source_id' => [
                        'The selected External Source does not exist.',
                    ],
                ]);
            }

            if ((int) $source->site_id !== $siteId) {
                throw ValidationException::withMessages([
                    'external_source_id' => [
                        'The selected External Source must belong to the same Site as the Risk/Issue.',
                    ],
                ]);
            }
        }

        if ($projectId !== null) {
            $project = Project::query()
                ->find($projectId);

            if (!$project) {
                throw ValidationException::withMessages([
                    'project_id' => [
                        'The selected Project does not exist.',
                    ],
                ]);
            }

            if ((int) $project->site_id !== $siteId) {
                throw ValidationException::withMessages([
                    'project_id' => [
                        'The selected Project must belong to the same Site as the Risk/Issue.',
                    ],
                ]);
            }
        }
    }
}

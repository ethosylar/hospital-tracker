<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
	public function toArray($request): array
	{
		$plannedCost = (float) ($this->planned_cost_total ?? 0);
		$actualCost = (float) ($this->actual_cost_total ?? 0);
		$committedCost = (float) ($this->committed_cost_total ?? 0);
		$plannedFunding = (float) ($this->planned_funding_total ?? 0);
		$actualFunding = (float) ($this->actual_funding_total ?? 0);

		return [
			'id' => (int) $this->id,
			'site_id' => (int) $this->site_id,

			'site' => $this->whenLoaded('site', function () {
				return $this->site
					? [
						'id' => (int) $this->site->id,
						'code' => $this->site->code,
						'name' => $this->site->name,
						'short_name' => $this->site->short_name,
						'site_type' => $this->site->site_type,
						'is_active' => (bool) $this->site->is_active,
					]
					: null;
			}),

			'code' => $this->code,
			'name' => $this->name,
			'description' => $this->description,
			'notes' => $this->notes,

			'department_id' => $this->department_id
				? (int) $this->department_id
				: null,

			'department' => $this->whenLoaded(
				'department',
				fn() => $this->department
					? [
						'id' => (int) $this->department->id,
						'site_id' => (int) $this->department->site_id,
						'code' => $this->department->code,
						'name' => $this->department->name,
					]
					: null
			),

			'project_category_id' => $this->project_category_id
				? (int) $this->project_category_id
				: null,

			'category' => $this->whenLoaded(
				'category',
				fn() => $this->category
					? [
						'id' => (int) $this->category->id,
						'code' => $this->category->code,
						'name' => $this->category->name,
					]
					: null
			),

			'owner_user_id' => $this->owner_user_id
				? (int) $this->owner_user_id
				: null,

			'owner' => $this->whenLoaded(
				'owner',
				fn() => $this->owner
					? [
						'id' => (int) $this->owner->id,
						'name' => $this->owner->name,
						'email' => $this->owner->email,
					]
					: null
			),

			'sponsor' => $this->sponsor,

			'project_status_id' => $this->project_status_id
				? (int) $this->project_status_id
				: null,

			'status' => $this->whenLoaded(
				'status',
				fn() => $this->status
					? [
						'id' => (int) $this->status->id,
						'code' => $this->status->code,
						'name' => $this->status->name,
					]
					: null
			),

			'priority_id' => $this->priority_id
				? (int) $this->priority_id
				: null,

			'priority' => $this->whenLoaded(
				'priority',
				fn() => $this->priority
					? [
						'id' => (int) $this->priority->id,
						'code' => $this->priority->code,
						'name' => $this->priority->name,
					]
					: null
			),

			'planned_progress' => (int) ($this->planned_progress ?? 0),
			'progress' => (int) ($this->progress ?? 0),

			'start_date' => $this->start_date?->format('Y-m-d'),
			'actual_start_date' => $this->actual_start_date?->format('Y-m-d'),
			'target_end_date' => $this->target_end_date?->format('Y-m-d'),
			'actual_end_date' => $this->actual_end_date?->format('Y-m-d'),

			'currency_code' => $this->currency_code,
			'planned_cost_total' => $this->planned_cost_total,
			'actual_cost_total' => $this->actual_cost_total,
			'committed_cost_total' => $this->committed_cost_total,
			'planned_funding_total' => $this->planned_funding_total,
			'actual_funding_total' => $this->actual_funding_total,
			'budget_notes' => $this->budget_notes,
			'budget_updated_at' =>
			$this->budget_updated_at?->toIso8601String(),

			'cost_utilization_pct' =>
			$plannedCost > 0
				? round(
					(($actualCost + $committedCost) / $plannedCost) * 100,
					2
				)
				: null,

			'cost_variance' =>
			round(
				$plannedCost - $actualCost - $committedCost,
				2
			),

			'funding_utilization_pct' =>
			$plannedFunding > 0
				? round(
					($actualFunding / $plannedFunding) * 100,
					2
				)
				: null,

			'funding_variance' =>
			round(
				$plannedFunding - $actualFunding,
				2
			),

			'last_status_changed_at' =>
			$this->last_status_changed_at?->toIso8601String(),

			'created_at' => $this->created_at?->toIso8601String(),
			'updated_at' => $this->updated_at?->toIso8601String(),
		];
	}
}

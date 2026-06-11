<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;
	
	class ProjectBudgetAllocation extends Model
	{
		protected $table = 'dt_project_budget_allocations';
		
		protected $fillable = [
		'project_id',
		'budget_line_id',
		'task_id',
		'milestone_id',
		'planned_amount',
		'actual_amount',
		'committed_amount',
		'sort_order',
		'is_active',
		'notes',
		];
		
		protected $casts = [
        'planned_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'committed_amount' => 'decimal:2',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'project_id' => 'integer',
        'budget_line_id' => 'integer',
        'task_id' => 'integer',
        'milestone_id' => 'integer',
		];
		
		public function project() { return $this->belongsTo(Project::class, 'project_id'); }
		public function line() { return $this->belongsTo(ProjectBudgetLine::class, 'budget_line_id'); }
		public function task() { return $this->belongsTo(ProjectTask::class, 'task_id'); }
		public function milestone() { return $this->belongsTo(ProjectMilestone::class, 'milestone_id'); }
	}		
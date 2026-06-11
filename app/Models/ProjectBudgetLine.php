<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;
	
	class ProjectBudgetLine extends Model
	{
		protected $table = 'dt_project_budget_lines';
		
		protected $fillable = [
        'project_id',
        'line_type',
        'code',
        'name',
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
		];
		
		public function project()
		{
			return $this->belongsTo(Project::class, 'project_id');
		}
		
		public function allocations()
		{
			return $this->hasMany(\App\Models\ProjectBudgetAllocation::class, 'budget_line_id');
		}
	}		
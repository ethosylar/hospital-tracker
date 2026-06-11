<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;
	
	class ProjectCategory extends Model
	{
		protected $table = 'lt_project_categories';
		
		protected $fillable = [
        'code','name','group','year','sort_order','is_active'
		];
		
		protected $casts = [
        'year' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
		];
		
		public function projects()
		{
			return $this->hasMany(Project::class, 'project_category_id');
		}
	}	
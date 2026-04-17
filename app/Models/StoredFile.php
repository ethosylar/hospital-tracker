<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsToMany;
	
	class StoredFile extends Model
	{
		protected $table = 'dt_files';
		
		protected $fillable = [
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'checksum',
        'uploaded_by_user_id',
		];
		
		protected $casts = [
        'size' => 'integer',
		];
		
		public function projects(): BelongsToMany
		{
			return $this->belongsToMany(Project::class, 'dt_project_files', 'file_id', 'project_id')
            ->withTimestamps();
		}
		
		public function tasks(): BelongsToMany
		{
			return $this->belongsToMany(ProjectTask::class, 'dt_task_files', 'file_id', 'task_id')
            ->withTimestamps();
		}
		
		public function uploader()
		{
			return $this->belongsTo(User::class, 'uploaded_by_user_id');
		}
	}	
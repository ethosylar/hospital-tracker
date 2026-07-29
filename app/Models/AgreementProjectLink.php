<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;
	
	class AgreementProjectLink extends Model
	{
		protected $table = 'dt_agreement_project_links';
		
		protected $fillable = [
        'agreement_id',
        'project_id',
        'linked_by_user_id',
        'notes',
		];
		
		protected $casts = [
        'agreement_id' => 'integer',
        'project_id' => 'integer',
        'linked_by_user_id' => 'integer',
		];
		
		public function agreement()
		{
			return $this->belongsTo(Agreement::class, 'agreement_id');
		}
		
		public function project()
		{
			return $this->belongsTo(Project::class, 'project_id');
		}
		
		public function linkedBy()
		{
			return $this->belongsTo(User::class, 'linked_by_user_id');
		}
	}	
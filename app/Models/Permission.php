<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;
	
	class Permission extends Model
	{
		protected $table = 'lt_permissions';
		
		protected $fillable = [
        'code',
        'name',
        'module',
        'description',
        'sort_order',
        'is_active',
		];
		
		protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
		];
		
		public function roles()
		{
			return $this->belongsToMany(
            Role::class,
            'lt_role_permissions',
            'permission_id',
            'role_id'
			)->withTimestamps();
		}
	}	
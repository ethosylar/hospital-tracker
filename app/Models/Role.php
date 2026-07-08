<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsToMany;
	
	class Role extends Model
	{
		protected $table = 'lt_roles';
		
		protected $fillable = ['code', 'name', 'is_active', 'is_system_role',];
		
		protected $casts = [
        'is_active' => 'boolean',
		'is_system_role' => 'boolean',
		];
		
		public function users()
		{
			return $this->belongsToMany(User::class, 'dt_user_roles', 'role_id', 'user_id')->withTimestamps();
		}
		
		public function permissions()
		{
			return $this->belongsToMany(
			\App\Models\Permission::class,
			'lt_role_permissions',
			'role_id',
			'permission_id'
			)->withTimestamps();
		}
	}

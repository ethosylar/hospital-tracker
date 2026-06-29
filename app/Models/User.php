<?php
	
	namespace App\Models;
	
	// use Illuminate\Contracts\Auth\MustVerifyEmail;
	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Foundation\Auth\User as Authenticatable;
	use Illuminate\Notifications\Notifiable;
	use Laravel\Sanctum\HasApiTokens;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;
	use Illuminate\Database\Eloquent\Relations\BelongsToMany;
	
	class User extends Authenticatable
	{
		use HasApiTokens, HasFactory, Notifiable;
		
		protected $fillable = [
        'name',
		'username',
        'email',
        'password',
		'department_id',
		];
		
		protected $hidden = [
        'password',
        'remember_token',
		];
		
		protected function casts(): array
		{
			return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
			];
		}
		
		public function roles(): BelongsToMany
		{
			return $this->belongsToMany(Role::class, 'dt_user_roles', 'user_id', 'role_id')
            ->withTimestamps();
		}
		
		public function department(): BelongsTo
		{
			return $this->belongsTo(Department::class, 'department_id');
		}
		
		public function permissions()
		{
			return $this->hasManyThrough(
			\App\Models\Permission::class,
			\App\Models\Role::class
			);
		}
		
		public function hasPermission(string $permissionCode): bool
		{
			$this->loadMissing('roles.permissions');
			
			foreach ($this->roles as $role) {
				foreach ($role->permissions as $permission) {
					if (!$permission->is_active) {
						continue;
					}
					
					if ($permission->code === 'system.all') {
						return true;
					}
					
					if ($permission->code === $permissionCode) {
						return true;
					}
				}
			}
			
			return false;
		}
		
		public function hasAnyPermission(array $permissionCodes): bool
		{
			foreach ($permissionCodes as $permissionCode) {
				if ($this->hasPermission($permissionCode)) {
					return true;
				}
			}
			
			return false;
		}
	}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSite extends Model
{
    public const LEVEL_VIEW = 'VIEW';
    public const LEVEL_MANAGE = 'MANAGE';
    public const LEVEL_ADMIN = 'ADMIN';

    protected $table = 'dt_user_sites';

    protected $fillable = [
        'user_id',
        'site_id',
        'access_level',
        'is_primary',
        'is_active',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'site_id' => 'integer',
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (UserSite $userSite) {
            $userSite->access_level = strtoupper(
                trim((string) $userSite->access_level)
            );
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }
}

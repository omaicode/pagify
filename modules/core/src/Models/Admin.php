<?php

namespace Pagify\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Pagify\Core\Traits\BelongsToSite;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable
{
    use BelongsToSite;
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use Notifiable;

    protected string $guard_name = 'web';

    protected $fillable = [
        "site_id",
        "name",
        "username",
        "email",
        "locale",
        "password",
        "remember_token"
    ];

    protected $hidden = [
        "password",
        "remember_token"
    ];

    protected $casts = [
        "email_verified_at" => "datetime"
    ];

    protected static function newFactory()
    {
        return \Pagify\Core\Database\Factories\AdminFactory::new();
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}

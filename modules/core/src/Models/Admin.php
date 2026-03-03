<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        "name",
        "username",
        "email",
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
        return \Modules\Core\Database\Factories\AdminFactory::new();
    }
}

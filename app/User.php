<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Notifiable;
    use HasRoles;
    //use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'image', 'enable', 'owner', 'tenant_id', 'is_platform_admin',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_platform_admin' => 'boolean',
    ];

    public function worker()
    {
        return $this->hasOne('App\Worker');
    }

    //protected $dates = ['deleted_at'];
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function companies()
    {
        return $this->belongsToMany(
            Company::class,
            'company_user'
        )
            ->withPivot([
                'is_default',
                'is_active',
            ])
            ->withTimestamps();
    }

    public function branches()
    {
        return $this->belongsToMany(
            Branch::class,
            'branch_user'
        )
            ->withPivot([
                'is_default',
                'is_active',
            ])
            ->withTimestamps();
    }

    public function isPlatformAdmin()
    {
        return (bool) $this->is_platform_admin;
    }

}

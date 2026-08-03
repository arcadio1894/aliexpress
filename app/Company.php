<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'tenant_id',
        'ruc',
        'business_name',
        'trade_name',
        'address',
        'phone',
        'email',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'company_user'
        )
            ->withPivot([
                'is_default',
                'is_active',
            ])
            ->withTimestamps();
    }
}

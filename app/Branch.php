<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'company_id',
        'code',
        'name',
        'address',
        'phone',
        'is_main',
        'is_active',
    ];

    protected $casts = [
        'is_main' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'branch_user'
        )
            ->withPivot([
                'is_default',
                'is_active',
            ])
            ->withTimestamps();
    }
}

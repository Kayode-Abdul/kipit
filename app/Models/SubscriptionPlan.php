<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_published' => 'boolean',
        'duration_in_months' => 'integer',
    ];

    public function prices()
    {
        return $this->hasMany(SubscriptionPlanPrice::class);
    }
}

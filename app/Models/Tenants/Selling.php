<?php

namespace App\Models\Tenants;

use App\Models\Tenants\Traits\Syncable;

use App\Traits\UseTimezoneAwareQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperSelling
 */
class Selling extends Model
{
    use Syncable;
    use \App\Models\Tenants\Traits\TamperProof;

    use HasFactory, UseTimezoneAwareQuery;

    protected $guarded = ['friend_price'];

    protected $tamperProtected = ['total_price', 'total_cost', 'tax_price', 'discount_price', 'is_paid'];

    protected $appends = [
        'grand_total_price',
    ];

    public function sellingDetails()
    {
        return $this->hasMany(SellingDetail::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cashDrawer()
    {
        return $this->belongsTo(CashDrawer::class);
    }

    public function scopeIsPaid(Builder $builder): Builder
    {
        return $builder->where('is_paid', true);
    }

    public function scopeIsNotPaid(Builder $builder): Builder
    {
        return $builder->where('is_paid', false);
    }

    public function grandTotalPrice(): Attribute
    {
        return Attribute::make(get: fn () => $this->total_price - $this->tax_price - $this->total_discount_per_item - $this->discount_price);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }
}

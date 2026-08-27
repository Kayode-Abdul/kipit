<?php

namespace App\Models\Tenants;

use App\Models\Tenants\Traits\Syncable;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperSellingDetail
 */
class SellingDetail extends Model
{
    use HasFactory, Syncable;
    use \App\Models\Tenants\Traits\TamperProof;

    protected $guarded = [];

    protected $tamperProtected = ['price', 'cost', 'qty', 'discount_price'];

    public function selling()
    {
        return $this->belongsTo(Selling::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function pricePerUnit(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->price / $this->qty,
        );
    }

    public function totalPrice(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->price - $this->discount_price,
        );
    }
}

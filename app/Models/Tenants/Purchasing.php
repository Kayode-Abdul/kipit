<?php

namespace App\Models\Tenants;

use App\Models\Tenants\Traits\Syncable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperPurchasing
 */
class Purchasing extends Model
{
    use HasFactory, Syncable;
    use \App\Models\Tenants\Traits\TamperProof;

    protected $guarded = ['id'];

    protected $tamperProtected = ['total_initial_price', 'total_selling_price', 'tax', 'status', 'payment_status'];

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}

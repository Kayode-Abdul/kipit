<?php

namespace App\Models\Tenants;

use App\Models\Tenants\Traits\Syncable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperStockOpnameItem
 */
class StockOpnameItem extends Model
{
    use HasFactory, Syncable;

    protected $guarded = ['id'];

    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

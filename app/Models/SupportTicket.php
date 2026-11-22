<?php

namespace App\Models;

use App\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SupportTicket extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->connection = config('tenancy.database.central_connection', 'mysql');
    }

    protected static function booted(): void
    {
        static::creating(function (self $ticket): void {
            if (empty($ticket->reference)) {
                $ticket->reference = 'TKT-'.strtoupper(Str::random(8));
            }
        });
    }

    public function replies(): HasMany
    {
        return $this->hasMany(SupportTicketReply::class)->orderBy('created_at');
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_admin_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public static function categories(): array
    {
        return [
            'billing' => 'Billing & Subscription',
            'activation' => 'Activation',
            'desktop' => 'Desktop App / Sync',
            'pos' => 'POS & Cashier',
            'inventory' => 'Inventory & Products',
            'printer' => 'Printer',
            'account' => 'Account & Users',
            'general' => 'General',
        ];
    }

    public static function statuses(): array
    {
        return [
            'open' => 'Open',
            'in_progress' => 'In Progress',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
        ];
    }
}

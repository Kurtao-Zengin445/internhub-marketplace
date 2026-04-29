<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscribable_type',
        'subscribable_id',
        'plan_type',
        'status',
        'amount',
        'midtrans_order_id',
        'midtrans_transaction_id',
        'midtrans_snap_token',
        'midtrans_redirect_url',
        'payload',
        'starts_at',
        'ends_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function subscribable()
    {
        return $this->morphTo();
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'airline_code',
        'currency',
        'customer_email',
        'customer_name',
        'amount_base',
        'amount_final',
        'status',
        'paid_at',
        'priced_offer_ref',
        'response_id',
        'primary_carrier',
        'payment_reference',
        'referral_code',
        'passenger_summary',
        'itinerary_json',
        'pricing_json',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'passenger_summary' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function scopeWithStatus($query, ?string $status)
    {
        if ($status) {
            $query->where('status', $status);
        }

        return $query;
    }
}

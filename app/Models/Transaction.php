<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'provider',
        'mode',
        'reference',
        'amount',
        'currency',
        'status',
        'raw_payload',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}

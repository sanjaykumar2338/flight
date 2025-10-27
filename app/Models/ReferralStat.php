<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'referral_code',
        'bookings_count',
        'payments_count',
    ];
}

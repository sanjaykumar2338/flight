<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AirlineCommission extends Model
{
    use HasFactory;

    protected $fillable = [
        'airline_code',
        'airline_name',
        'markup_percent',
        'flat_markup',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'markup_percent' => 'float',
        'flat_markup' => 'float',
        'is_active' => 'bool',
    ];

    public function setAirlineCodeAttribute(string $value): void
    {
        $this->attributes['airline_code'] = strtoupper(trim($value));
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

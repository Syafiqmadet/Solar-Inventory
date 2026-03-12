<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FuelRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'fuel_type',
        'date',
        'liters',
        'amount_rm',
        'do_number',
        'do_image',
        'supplier',
        'vehicle_no',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'liters' => 'decimal:2',
        'amount_rm' => 'decimal:2',
    ];

    public function getFuelTypeLabelAttribute(): string
    {
        return ucfirst($this->fuel_type);
    }

    public function getFuelTypeBadgeColorAttribute(): string
    {
        return $this->fuel_type === 'petrol' ? '#28a745' : '#0d6efd';
    }

    public function getPricePerLiterAttribute(): float
    {
        if ($this->liters > 0) {
            return round($this->amount_rm / $this->liters, 4);
        }
        return 0;
    }
    public function project() { return $this->belongsTo(Project::class); }
}

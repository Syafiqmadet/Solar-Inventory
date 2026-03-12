<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class DieselUsage extends Model
{
    protected $fillable = [
        'vehicle_id','fuel_type','date','liters_used',
        'balance_before','balance_after',
        'driver_name','odometer_km','purpose','notes'
    ];

    protected $casts = [
        'date'           => 'date',
        'liters_used'    => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after'  => 'decimal:2',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function getFuelIconAttribute(): string
    {
        return $this->fuel_type === 'petrol' ? '⛽' : '🛢️';
    }

    public function getFuelColorAttribute(): string
    {
        return $this->fuel_type === 'petrol' ? '#28a745' : '#0d6efd';
    }
}

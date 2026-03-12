<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id','vehicle_no','name','type','brand','model','color','notes','is_active'
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function dieselUsages()
    {
        return $this->hasMany(DieselUsage::class)->latest('date');
    }

    public function petrolUsages()
    {
        return $this->hasMany(DieselUsage::class)->where('fuel_type','petrol')->latest('date');
    }

    public function fuelUsages(string $fuelType)
    {
        return $this->hasMany(DieselUsage::class)->where('fuel_type', $fuelType)->latest('date');
    }

    public function totalLitersUsed(string $fuelType = null): float
    {
        $q = $this->hasMany(DieselUsage::class);
        if ($fuelType) $q = $q->where('fuel_type', $fuelType);
        return (float) $q->sum('liters_used');
    }

    public function lastUsageDate(string $fuelType = null)
    {
        $q = $this->dieselUsages();
        if ($fuelType) $q = $q->where('fuel_type', $fuelType);
        return $q->first()?->date;
    }

    public function getTypeIconAttribute(): string
    {
        return match(strtolower($this->type ?? '')) {
            'lorry','truck'   => '🚛',
            'pickup','van'    => '🚐',
            'excavator','jcb' => '🚜',
            'crane'           => '🏗️',
            'forklift'        => '🏭',
            'generator'       => '⚡',
            default           => '🚗',
        };
    }
    public function project() { return $this->belongsTo(Project::class); }
}

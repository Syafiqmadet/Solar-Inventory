<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = ['name','code','description','location','color','is_active'];
    protected $casts    = ['is_active' => 'boolean'];

    public function users()  { return $this->belongsToMany(User::class); }
    public function items()  { return $this->hasMany(Item::class); }
    public function zones()  { return $this->hasMany(Zone::class); }
    public function containers() { return $this->hasMany(Container::class); }
    public function fuelRecords() { return $this->hasMany(FuelRecord::class); }
    public function vehicles()    { return $this->hasMany(Vehicle::class); }
}

<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    protected $fillable = ['project_id','name','code','description','color'];
    public function transactions() { return $this->hasMany(StockTransaction::class); }
    public function project() { return $this->belongsTo(Project::class); }
}

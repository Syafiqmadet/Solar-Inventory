<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Item extends Model
{
    use HasFactory;
    protected $fillable = ['project_id','part_number','name','description','category','unit','color_code','current_stock','min_stock'];
    public function transactions() { return $this->hasMany(StockTransaction::class); }
    public function containerItems() { return $this->hasMany(ContainerItem::class); }
    public function isolatedItems() { return $this->hasMany(IsolatedItem::class); }
    public function project() { return $this->belongsTo(Project::class); }
}

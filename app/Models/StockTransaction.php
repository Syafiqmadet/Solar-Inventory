<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class StockTransaction extends Model
{
    protected $fillable = ['item_id','zone_id','type','quantity','notes'];
    public function item() { return $this->belongsTo(Item::class); }
    public function zone() { return $this->belongsTo(Zone::class); }
}

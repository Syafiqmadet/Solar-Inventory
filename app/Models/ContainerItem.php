<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ContainerItem extends Model
{
    protected $fillable = ['container_id','item_id','part_number','description','quantity'];
    public function container() { return $this->belongsTo(Container::class); }
    public function item() { return $this->belongsTo(Item::class); }
}

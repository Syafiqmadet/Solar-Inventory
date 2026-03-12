<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SubconMifItem extends Model
{
    protected $table    = 'subcon_mif_items';
    protected $fillable = ['mif_id','item_id','item_name','part_number','quantity','unit','remarks'];

    public function mif()  { return $this->belongsTo(SubconMif::class, 'mif_id'); }
    public function item() { return $this->belongsTo(Item::class); }
}

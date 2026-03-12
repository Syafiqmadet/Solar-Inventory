<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SubconMrfItem extends Model
{
    protected $table    = 'subcon_mrf_items';
    protected $fillable = ['mrf_id','item_id','item_name','part_number','quantity','unit','condition','remarks'];

    public function mrf()  { return $this->belongsTo(SubconMrf::class, 'mrf_id'); }
    public function item() { return $this->belongsTo(Item::class); }
}

<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SubconMrf extends Model
{
    protected $table    = 'subcon_mrf';
    protected $fillable = ['mrf_number','subcon_id','zone_id','project_id','date','notes'];
    protected $casts    = ['date'=>'date'];

    public function subcon()  { return $this->belongsTo(Subcon::class); }
    public function zone()    { return $this->belongsTo(Zone::class); }
    public function project() { return $this->belongsTo(Project::class); }
    public function items()   { return $this->hasMany(SubconMrfItem::class, 'mrf_id'); }
}

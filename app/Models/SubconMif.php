<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SubconMif extends Model
{
    protected $table    = 'subcon_mif';
    protected $fillable = ['mif_number','subcon_id','zone_id','project_id','issued_by','date','notes'];
    protected $casts    = ['date'=>'date'];

    public function subcon()   { return $this->belongsTo(Subcon::class); }
    public function zone()     { return $this->belongsTo(Zone::class); }
    public function project()  { return $this->belongsTo(Project::class); }
    public function issuedBy() { return $this->belongsTo(User::class, 'issued_by'); }
    public function items()    { return $this->hasMany(SubconMifItem::class, 'mif_id'); }
}

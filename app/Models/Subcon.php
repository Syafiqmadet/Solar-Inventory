<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Subcon extends Model
{
    protected $fillable = [
        'project_id','zone_id','name','contact_person','contact_number',
        'supervisor_name','supervisor_contact','start_date','end_date','status','notes'
    ];
    protected $casts = ['start_date'=>'date','end_date'=>'date'];

    public function project() { return $this->belongsTo(Project::class); }
    public function zone()    { return $this->belongsTo(Zone::class); }
    public function mifs()    { return $this->hasMany(SubconMif::class); }
    public function mrfs()    { return $this->hasMany(SubconMrf::class); }

    public function getStatusBadgeAttribute(): string {
        return match($this->status) {
            'active'     => '<span class="badge bg-success">Active</span>',
            'completed'  => '<span class="badge bg-secondary">Completed</span>',
            'terminated' => '<span class="badge bg-danger">Terminated</span>',
            default      => '<span class="badge bg-light text-dark">'.ucfirst($this->status).'</span>',
        };
    }
}

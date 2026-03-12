<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Container extends Model
{
    protected $fillable = ['project_id','container_id','batch','description','date_in','date_out','status','color_code'];

    public function items()
    {
        return $this->hasMany(ContainerItem::class);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'active'  => '<span class="badge bg-success">Active</span>',
            'closed'  => '<span class="badge bg-secondary">Closed</span>',
            'pending' => '<span class="badge bg-warning text-dark">Pending</span>',
            default   => '<span class="badge bg-light text-dark">'.ucfirst($this->status).'</span>',
        };
    }
    public function project() { return $this->belongsTo(Project::class); }
}

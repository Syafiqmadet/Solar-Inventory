<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role'];
    protected $hidden   = ['password', 'remember_token'];
    protected $casts    = ['password' => 'hashed'];

    public function isAdmin(): bool  { return $this->role === 'admin'; }
    public function isUser(): bool   { return $this->role === 'user'; }
    public function isViewer(): bool { return $this->role === 'viewer'; }
    public function canWrite(): bool { return in_array($this->role, ['admin', 'user']); }

    public function projects() { return $this->belongsToMany(Project::class); }

    /** Projects this user can access (admin gets all) */
    public function accessibleProjects()
    {
        if ($this->isAdmin()) {
            return Project::where('is_active', true)->orderBy('name')->get();
        }
        return $this->projects()->where('is_active', true)->orderBy('name')->get();
    }
}

<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IsolatedItem extends Model
{
    protected $fillable = [
        'project_id', 'item_id', 'name', 'part_number', 'quantity',
        'type', 'reason', 'isolated_date', 'status', 'notes', 'proof_images',
    ];

    protected $casts = [
        'isolated_date' => 'date',
        'proof_images'  => 'json',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function getTypeColorAttribute(): string
    {
        return $this->type === 'defect' ? '#fd7e14' : '#dc3545';
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'defect' ? '⚠️ Defect' : '💥 Damaged';
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'isolated' => '#fd7e14',
            'scrapped' => '#dc3545',
            'repaired' => '#28a745',
            default    => '#6c757d',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'isolated' => '🔒 Isolated',
            'scrapped' => '🗑️ Scrapped',
            'repaired' => '✅ Repaired',
            default    => ucfirst($this->status),
        };
    }

    public function project() { return $this->belongsTo(Project::class); }
}

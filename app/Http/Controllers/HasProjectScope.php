<?php
namespace App\Http\Controllers;

use App\Models\Project;

trait HasProjectScope
{
    protected function pid(): ?int
    {
        $pid = session('current_project_id');

        if (!$pid && auth()->check() && auth()->user()->isAdmin()) {
            $first = Project::where('is_active', true)->orderBy('name')->first();
            if ($first) {
                session(['current_project_id' => $first->id]);
                $pid = $first->id;
            }
        }

        return $pid ? (int) $pid : null;
    }

    protected function pidFilter($query, $col = 'project_id')
    {
        $pid = $this->pid();
        return $pid ? $query->where($col, $pid) : $query;
    }
}

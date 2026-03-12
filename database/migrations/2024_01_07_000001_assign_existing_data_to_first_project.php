<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Get the first active project
        $project = DB::table('projects')->where('is_active', 1)->orderBy('id')->first();
        if (!$project) return;

        $pid = $project->id;
        $tables = ['items', 'zones', 'containers', 'fuel_records', 'vehicles', 'isolated_items'];

        foreach ($tables as $table) {
            DB::table($table)->whereNull('project_id')->update(['project_id' => $pid]);
        }
    }

    public function down(): void {}
};

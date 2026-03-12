<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Get the first project
        $project = DB::table('projects')->orderBy('id')->first();
        if (!$project) return;

        $tables = ['items', 'zones', 'containers', 'fuel_records', 'vehicles', 'isolated_items'];
        foreach ($tables as $table) {
            DB::table($table)->whereNull('project_id')->update(['project_id' => $project->id]);
        }
    }

    public function down(): void {}
};

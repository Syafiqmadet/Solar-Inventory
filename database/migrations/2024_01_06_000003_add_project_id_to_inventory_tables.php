<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        $tables = ['items','zones','containers','fuel_records','vehicles','isolated_items'];
        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('project_id')->nullable()->after('id')->constrained()->nullOnDelete();
            });
        }
    }
    public function down(): void {
        $tables = ['items','zones','containers','fuel_records','vehicles','isolated_items'];
        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropForeign(['project_id']);
                $t->dropColumn('project_id');
            });
        }
    }
};

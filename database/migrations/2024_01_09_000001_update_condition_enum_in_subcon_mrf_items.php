<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE subcon_mrf_items MODIFY COLUMN `condition` ENUM('good','damaged','defect') NOT NULL DEFAULT 'good'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE subcon_mrf_items MODIFY COLUMN `condition` ENUM('good','damaged') NOT NULL DEFAULT 'good'");
    }
};

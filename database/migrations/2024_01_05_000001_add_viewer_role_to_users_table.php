<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Extend the enum to include 'viewer'
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','user','viewer') DEFAULT 'user'");
    }
    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','user') DEFAULT 'user'");
    }
};

<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('containers', function (Blueprint $table) {
            $table->string('batch')->nullable()->after('container_id');
        });
    }
    public function down() {
        Schema::table('containers', function (Blueprint $table) {
            $table->dropColumn('batch');
        });
    }
};

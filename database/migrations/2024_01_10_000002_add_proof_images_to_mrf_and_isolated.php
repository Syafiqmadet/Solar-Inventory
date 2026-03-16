<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subcon_mrf_items', function (Blueprint $table) {
            $table->json('proof_images')->nullable()->after('remarks');
        });

        Schema::table('isolated_items', function (Blueprint $table) {
            $table->json('proof_images')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('subcon_mrf_items', function (Blueprint $table) {
            $table->dropColumn('proof_images');
        });
        Schema::table('isolated_items', function (Blueprint $table) {
            $table->dropColumn('proof_images');
        });
    }
};

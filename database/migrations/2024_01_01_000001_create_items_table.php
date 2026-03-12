<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('part_number')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('unit')->default('pcs');
            $table->string('color_code', 10)->nullable();
            $table->integer('current_stock')->default(0);
            $table->integer('min_stock')->default(5);
            $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('items'); }
};

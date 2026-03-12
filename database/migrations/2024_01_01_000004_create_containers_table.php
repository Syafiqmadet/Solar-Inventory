<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('containers', function (Blueprint $table) {
            $table->id();
            $table->string('container_id')->unique();
            $table->text('description')->nullable();
            $table->date('date_in')->nullable();
            $table->date('date_out')->nullable();
            $table->enum('status', ['active','pending','closed'])->default('active');
            $table->string('color_code', 10)->nullable();
            $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('containers'); }
};

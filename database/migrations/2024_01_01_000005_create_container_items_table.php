<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('container_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('container_id')->constrained()->onDelete('cascade');
            $table->foreignId('item_id')->nullable()->constrained()->onDelete('set null');
            $table->string('part_number')->nullable();
            $table->text('description')->nullable();
            $table->integer('quantity')->default(1);
            $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('container_items'); }
};

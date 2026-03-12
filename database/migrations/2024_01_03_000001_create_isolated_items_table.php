<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('isolated_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->string('part_number')->nullable();
            $table->integer('quantity')->default(1);
            $table->enum('type', ['defect', 'damaged'])->default('defect');
            $table->text('reason')->nullable();
            $table->date('isolated_date');
            $table->enum('status', ['isolated', 'scrapped', 'repaired'])->default('isolated');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('isolated_items'); }
};

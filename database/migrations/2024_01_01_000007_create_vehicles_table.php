<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('vehicle_no');          // Plate number e.g. WXX 1234
            $table->string('name');                // e.g. Lorry, Excavator, Van
            $table->string('type');                // e.g. Lorry, Pickup, Crane, Excavator
            $table->string('brand')->nullable();   // e.g. Isuzu, Hino, Volvo
            $table->string('model')->nullable();   // e.g. NPR, 300 Series
            $table->string('color')->nullable();   // vehicle color
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('vehicles'); }
};

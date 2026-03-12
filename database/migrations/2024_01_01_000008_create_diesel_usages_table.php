<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('diesel_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');
            $table->enum('fuel_type', ['petrol', 'diesel'])->default('diesel');
            $table->date('date');
            $table->decimal('liters_used', 8, 2);
            $table->decimal('balance_before', 10, 2)->default(0);
            $table->decimal('balance_after',  10, 2)->default(0);
            $table->string('driver_name')->nullable();
            $table->integer('odometer_km')->nullable();
            $table->text('purpose')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('diesel_usages'); }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_records', function (Blueprint $table) {
            $table->id();
            $table->enum('fuel_type', ['petrol', 'diesel']);
            $table->date('date');
            $table->decimal('liters', 8, 2);
            $table->decimal('amount_rm', 10, 2);
            $table->string('do_number')->nullable()->comment('Delivery Order number');
            $table->string('do_image')->nullable()->comment('Uploaded DO image path');
            $table->string('supplier')->nullable();
            $table->string('vehicle_no')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_records');
    }
};

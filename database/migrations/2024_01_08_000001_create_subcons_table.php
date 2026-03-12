<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('subcons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('supervisor_name')->nullable();
            $table->string('supervisor_contact')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['active','completed','terminated'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('subcon_mif', function (Blueprint $table) {
            $table->id();
            $table->string('mif_number')->unique();
            $table->foreignId('subcon_id')->constrained('subcons')->cascadeOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('subcon_mif_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mif_id')->constrained('subcon_mif')->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->string('item_name');
            $table->string('part_number')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->string('unit')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('subcon_mrf', function (Blueprint $table) {
            $table->id();
            $table->string('mrf_number')->unique();
            $table->foreignId('subcon_id')->constrained('subcons')->cascadeOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('subcon_mrf_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mrf_id')->constrained('subcon_mrf')->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->string('item_name');
            $table->string('part_number')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->string('unit')->nullable();
            $table->enum('condition', ['good','damaged'])->default('good');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('subcon_mrf_items');
        Schema::dropIfExists('subcon_mrf');
        Schema::dropIfExists('subcon_mif_items');
        Schema::dropIfExists('subcon_mif');
        Schema::dropIfExists('subcons');
    }
};

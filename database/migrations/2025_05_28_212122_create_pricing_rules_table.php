<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('city_id')->nullable()->constrained('cities')->onDelete('set null'); // Ou onDelete('cascade') se preferir deletar as regras quando a cidade for deletada
            $table->foreignId('service_category_id')->constrained('service_categories')->onDelete('cascade');

            $table->decimal('base_fare', 8, 2)->default(0.00);
            $table->decimal('price_per_km', 8, 2)->default(0.00);
            $table->decimal('price_per_minute', 8, 2)->default(0.00);
            $table->decimal('min_fare', 8, 2)->nullable();

            $table->json('time_rules_json')->nullable(); 
            // Ex: [{"name": "Pico", "start_time": "07:00", "end_time": "09:00", "multiplier_km": 1.2, "days_of_week": [1,2,3,4,5]}]

            $table->boolean('is_active')->default(true);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();
            
            // $table->unique(['city_id', 'service_category_id', 'valid_from', 'valid_to']); // Considere um índice único mais complexo para evitar sobreposição de regras ativas
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
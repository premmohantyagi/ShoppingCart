<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_related_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_product_id')->constrained('products')->cascadeOnDelete();
            $table->string('relationship_type')->default('related');
            $table->timestamps();
            $table->unique(['product_id', 'related_product_id', 'relationship_type'], 'product_related_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_related_items');
    }
};

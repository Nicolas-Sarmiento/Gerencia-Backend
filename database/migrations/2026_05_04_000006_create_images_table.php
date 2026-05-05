<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void {
        Schema::create('images', function ( Blueprint $table ) {

            $table->string('imageId', 50)->primary();

            $table->text('imageurl');
            $table->text('alt');
            $table->string('productId', 50);
            
            $table->foreign('productId')
                    ->references('productId')
                    ->on('products')
                    ->onDelete('cascade');
                
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('images');
    }

};
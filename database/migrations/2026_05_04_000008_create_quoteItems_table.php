<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void {
        Schema::create('quoteItems', function ( Blueprint $table ) {

            $table->string('itemquoteId', 50)->primary();

            $table->timestamp('requestDate');
            $table->text('status');
            $table->text('description');

            $table->timestamps();

            $table->string('quoteId', 50);
            $table->foreign('quoteId')
                  ->references('quoteId')
                  ->on('quotes')
                  ->onDelete('cascade');

            $table->string('productId', 50);
            $table->foreign('productId')
                  ->references('productId')
                  ->on('products')
                  ->onDelete('cascade');

        });
    }

    public function down(): void {
        Schema::dropIfExists('quoteItems');
    }

};
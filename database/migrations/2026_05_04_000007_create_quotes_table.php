<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void {
        Schema::create('quotes', function ( Blueprint $table ) {

            $table->string('quoteId', 50)->primary();

            $table->timestamp('requestDate');
            $table->text('status');
            $table->text('description');

            $table->timestamps();

            $table->string('clientId', 50);
            $table->foreign('clientId')
                  ->references('clientId')
                  ->on('clients')
                  ->onDelete('cascade');

            

        });
    }

    public function down(): void {
        Schema::dropIfExists('quotes');
    }

};
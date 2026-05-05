<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void {
        Schema::create('annual_processed_wastes', function ( Blueprint $table ) {

            $table->string('wasteId', 50)->primary();

            $table->timestamp('year');
            $table->double('processedWaste');

            $table->timestamps();

        });
    }

    public function down(): void {
        Schema::dropIfExists('annual_processed_wastes');
    }

};
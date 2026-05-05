<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void {
        Schema::create('clients', function ( Blueprint $table ) {

            $table->string('clientId', 50)->primary();

            $table->string('name', 50);
            $table->string('phone', 50);
            $table->string('mail', 80);

            $table->timestamps();

        });
    }

    public function down(): void {
        Schema::dropIfExists('clients');
    }

};
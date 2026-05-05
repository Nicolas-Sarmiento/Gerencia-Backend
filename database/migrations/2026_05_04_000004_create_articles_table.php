<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void {
        Schema::create('articles', function ( Blueprint $table ) {

            $table->string('articleId', 50)->primary();

            $table->string('title', 50);
            $table->string('userId', 50);

            $table->foreign('userId')
                  ->references('userId')
                  ->on('users')
                  ->onDelete('cascade');

            $table->timestamps();

        });
    }

    public function down(): void {
        Schema::dropIfExists('articles');
    }

};
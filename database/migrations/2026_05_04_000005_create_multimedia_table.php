<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void {
        Schema::create('multimedia', function ( Blueprint $table ) {

            $table->string('multimediaId', 50)->primary();

            $table->text('content')->nullable();
            $table->text('resourceUrl')->nullable();
            $table->enum('type', ['TEXT', 'IMAGE', 'VIDEO']);


            $table->string('articleId', 50);
            $table->foreign('articleId')
                  ->references('articleId')
                  ->on('articles')
                  ->onDelete('cascade');

            $table->timestamps();

        });
    }

    public function down(): void {
        Schema::dropIfExists('multimedia');
    }

};
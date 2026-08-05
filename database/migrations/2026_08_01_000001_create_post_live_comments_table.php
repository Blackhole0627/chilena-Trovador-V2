<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comentarios en vivo (hilo plano) sobre publicaciones + nota de voz del creador.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('post_live_comments')) {
            Schema::create('post_live_comments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('updates_id')->index();
                $table->text('comment')->nullable();
                $table->string('media')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('post_live_comments');
    }
};

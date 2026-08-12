<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stickers y GIFs en los comentarios en vivo (paridad con los comentarios base).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_live_comments', function (Blueprint $table) {
            if (! Schema::hasColumn('post_live_comments', 'sticker')) {
                $table->string('sticker', 500)->nullable()->after('media');
            }
            if (! Schema::hasColumn('post_live_comments', 'gif_image')) {
                $table->string('gif_image', 500)->nullable()->after('sticker');
            }
        });
    }

    public function down(): void
    {
        Schema::table('post_live_comments', function (Blueprint $table) {
            if (Schema::hasColumn('post_live_comments', 'sticker')) {
                $table->dropColumn('sticker');
            }
            if (Schema::hasColumn('post_live_comments', 'gif_image')) {
                $table->dropColumn('gif_image');
            }
        });
    }
};

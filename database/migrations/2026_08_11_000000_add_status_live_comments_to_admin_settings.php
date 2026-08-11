<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Interruptor para alternar entre los comentarios en vivo (hilo plano, 6 visibles,
 * solo el creador responde) y los comentarios normales del script. Permite volver atras.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('admin_settings', 'status_live_comments')) {
            Schema::table('admin_settings', function (Blueprint $table) {
                $table->boolean('status_live_comments')->default(1);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('admin_settings', 'status_live_comments')) {
            Schema::table('admin_settings', function (Blueprint $table) {
                $table->dropColumn('status_live_comments');
            });
        }
    }
};

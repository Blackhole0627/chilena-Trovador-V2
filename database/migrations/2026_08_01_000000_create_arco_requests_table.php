<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de solicitudes ARCO+ (Ley 21.719).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('arco_requests')) {
            Schema::create('arco_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('type', 30);
                $table->text('details')->nullable();
                $table->string('status', 20)->default('pending');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('arco_requests');
    }
};

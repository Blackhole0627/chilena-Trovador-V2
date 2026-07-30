<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Evita el doble abono en la billetera (double-spend / TOCTOU).
 * Garantiza a nivel de base de datos que un mismo txn_id no se
 * pueda insertar dos veces, aunque dos webhooks o clics lleguen
 * al mismo tiempo. Idempotente: solo agrega el indice si falta.
 */
return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::select("SHOW INDEX FROM deposits WHERE Key_name = 'deposits_txn_id_unique'");
        if (empty($exists)) {
            Schema::table('deposits', function ($table) {
                $table->unique('txn_id', 'deposits_txn_id_unique');
            });
        }
    }

    public function down(): void
    {
        $exists = DB::select("SHOW INDEX FROM deposits WHERE Key_name = 'deposits_txn_id_unique'");
        if (! empty($exists)) {
            Schema::table('deposits', function ($table) {
                $table->dropUnique('deposits_txn_id_unique');
            });
        }
    }
};

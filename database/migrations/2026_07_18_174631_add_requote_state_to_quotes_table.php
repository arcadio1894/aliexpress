<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRequoteStateToQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            ALTER TABLE quotes
            MODIFY COLUMN state ENUM(
                'created',
                'confirmed',
                'canceled',
                'expired',
                'requote'
            ) NOT NULL
        ");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Antes de eliminar el estado, evitamos que existan registros con 'requote'.
        DB::table('quotes')
            ->where('state', 'requote')
            ->update([
                'state' => 'canceled'
            ]);

        DB::statement("
            ALTER TABLE quotes
            MODIFY COLUMN state ENUM(
                'created',
                'confirmed',
                'canceled',
                'expired'
            ) NOT NULL
        ");
    }
}

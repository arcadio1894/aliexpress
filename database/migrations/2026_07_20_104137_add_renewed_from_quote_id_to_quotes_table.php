<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRenewedFromQuoteIdToQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->unsignedBigInteger('renewed_from_quote_id')
                ->nullable()
                ->after('id');

            $table->foreign('renewed_from_quote_id')
                ->references('id')
                ->on('quotes')
                ->onDelete('set null');

            /*
             * Una cotización original solamente puede tener
             * una recotización asociada.
             */
            $table->unique('renewed_from_quote_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropUnique([
                'renewed_from_quote_id'
            ]);

            $table->dropForeign([
                'renewed_from_quote_id'
            ]);

            $table->dropColumn('renewed_from_quote_id');
        });
    }
}

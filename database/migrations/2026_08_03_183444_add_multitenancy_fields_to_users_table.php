<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMultitenancyFieldsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            /*
             * Nullable por dos motivos:
             *
             * 1. La base ya contiene usuarios existentes.
             * 2. Los superadministradores de Venti360 no
             *    pertenecerán a un tenant cliente.
             */
            $table->unsignedBigInteger('tenant_id')
                ->nullable()
                ->after('id');

            $table->boolean('is_platform_admin')
                ->default(false)
                ->after('tenant_id');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('set null');

            $table->index('tenant_id');
            $table->index('is_platform_admin');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign([
                'tenant_id',
            ]);

            $table->dropIndex([
                'tenant_id',
            ]);

            $table->dropIndex([
                'is_platform_admin',
            ]);

            $table->dropColumn([
                'tenant_id',
                'is_platform_admin',
            ]);
        });
    }
}

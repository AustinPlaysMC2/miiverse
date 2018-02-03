<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Miiverse\DB;

class FixCommunityPermEnum extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $schema = DB::getSchemaBuilder();

        $schema->table('communities', function (Blueprint $table) {
            $table->dropColumn('permissions');
        });

        $schema->table('communities', function (Blueprint $table) {
            $table->smallInteger('permissions')->unsigned()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $schema = DB::getSchemaBuilder();

        $schema->table('communities', function (Blueprint $table) {
            $table->dropColumn('permissions');
        });

        $schema->table('communities', function (Blueprint $table) {
            $table->enum('permissions', ['post', 'draw', 'like']);
        });
    }
}

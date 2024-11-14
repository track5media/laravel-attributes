<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class IncreaseVarcharColumnLength extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('rinvex.attributes.tables.attribute_varchar_values'), function (Blueprint $table) {
            $table->string('content', 500)
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(config('rinvex.attributes.tables.attribute_varchar_values'), function (Blueprint $table) {
            $table->string('content', 250)
                ->change();
        });
    }
}

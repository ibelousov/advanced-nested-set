<?php

use Ibelousov\AdvancedNestedSet\MigrationMacro;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->unsignedBigInteger(MigrationMacro::LFT)->nullable();
            $table->unsignedBigInteger(MigrationMacro::RGT)->nullable();
            $table->unsignedBigInteger(MigrationMacro::PARENT_ID)->nullable();
            $table->unsignedBigInteger(MigrationMacro::DEPTH)->nullable();

            $table->foreign(MigrationMacro::PARENT_ID)->references('id')->on($table->getTable());

            $table->softDeletes();

            $table->index([MigrationMacro::LFT, MigrationMacro::RGT, MigrationMacro::PARENT_ID, MigrationMacro::DEPTH]);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('categories');
    }
}

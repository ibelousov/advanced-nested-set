<?php

namespace Ibelousov\AdvancedNestedSet;

use Illuminate\Database\Schema\Blueprint;

class MigrationMacro
{
    const LFT = 'lft';
    const RGT = 'rgt';
    const DEPTH = 'depth';
    const PARENT_ID = 'parent_id';
    const DISTANCE = 'distance';

    public static function columns(Blueprint $table)
    {
        $table->unsignedBigInteger(self::LFT)->nullable();
        $table->unsignedBigInteger(self::RGT)->nullable();
        $table->unsignedBigInteger(self::PARENT_ID)->nullable();
        $table->unsignedBigInteger(self::DEPTH)->nullable();
        $table->unsignedBigInteger(self::DISTANCE)->default(1);

        $table->foreign(self::PARENT_ID)->references('id')->on($table->getTable());

        $table->softDeletes();

        $table->index([self::LFT, self::RGT, self::PARENT_ID, self::DEPTH, self::DISTANCE]);
    }

    public static function dropColumns(Blueprint $table)
    {
        $table->dropIndex([self::LFT, self::RGT, self::PARENT_ID, self::DEPTH, self::DISTANCE]);
        $table->dropColumn([self::LFT, self::RGT, self::PARENT_ID, self::DEPTH, self::DISTANCE]);
        $table->dropForeign($table->getTable().'_'.self::PARENT_ID.'_foreign');

        $table->dropSoftDeletes();
    }
}

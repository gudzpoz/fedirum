<?php

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\Blueprint;

return [
    'up' => function (Builder $schema) {
        return Migration::createTable('fedirum', function (Blueprint $table) {
            $table->integer('id');
            $table->string('follower');
            $table->string('inbox');
        });
    },
    'down' => function (Builder $schema) {
        $schema->dropIfExists('fedirum');
    }
];

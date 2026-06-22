<?php

declare(strict_types=1);

use App\Database\Contracts\DatabaseConnection;
use App\Database\Schema\Blueprint;

return [
    'up' => function (DatabaseConnection $db): void {
        $db->create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password_hash');
            $table->timestamps();
        });

        $db->create('auth_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name')->default('default');
            $table->string('token_hash')->unique();
            $table->timestamp('created_at');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
        });
    },
    'down' => function (DatabaseConnection $db): void {
        $db->dropIfExists('auth_tokens');
        $db->dropIfExists('users');
    },
];

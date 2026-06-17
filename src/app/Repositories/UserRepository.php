<?php

declare(strict_types=1);

namespace App\Repositories;

final class UserRepository extends BaseRepository
{
    protected string $table = 'users';

    /** @return array<string, mixed>|null */
    public function findByEmail(string $email): ?array
    {
        return $this->findOne('email', strtolower(trim($email)));
    }
}

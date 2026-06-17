<?php

declare(strict_types=1);

namespace Tests;

use App\Database\Drivers\Json\JsonConnection;
use App\Repositories\BaseRepository;
use PHPUnit\Framework\TestCase;

final class RelationsTest extends TestCase
{
    public function testHasManyRelationship(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-relations-' . uniqid('', true) . '.json';
        $connection = new JsonConnection($path);

        $users = new UserTestRepository($connection);
        $posts = new PostTestRepository($connection);

        $alice = $users->insert(['name' => 'Alice']);
        $bob = $users->insert(['name' => 'Bob']);

        $posts->insert(['title' => 'Post A', 'user_id' => $alice['id']]);
        $posts->insert(['title' => 'Post B', 'user_id' => $alice['id']]);
        $posts->insert(['title' => 'Post C', 'user_id' => $bob['id']]);

        $alicePosts = $users->posts()->for($alice)->get();

        self::assertCount(2, $alicePosts);
        self::assertSame('Post A', $alicePosts[0]['title']);
        self::assertSame('Post B', $alicePosts[1]['title']);

        $created = $users->posts()->for($alice)->create(['title' => 'Post D']);

        self::assertSame($alice['id'], $created['user_id']);

        @unlink($path);
    }

    public function testBelongsToRelationship(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-relations-' . uniqid('', true) . '.json';
        $connection = new JsonConnection($path);

        $users = new UserTestRepository($connection);
        $posts = new PostTestRepository($connection);

        $user = $users->insert(['name' => 'Alice']);
        $post = $posts->insert(['title' => 'Post A', 'user_id' => $user['id']]);

        $author = $posts->user()->for($post)->first();

        self::assertSame('Alice', $author['name'] ?? null);

        $orphan = $posts->insert(['title' => 'Orphan', 'user_id' => 999]);

        self::assertNull($posts->user()->for($orphan)->first());

        @unlink($path);
    }

    public function testBelongsToManyRelationship(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-relations-' . uniqid('', true) . '.json';
        $connection = new JsonConnection($path);

        $users = new UserTestRepository($connection);
        $roles = new RoleTestRepository($connection);
        $roleUser = new RoleUserTestRepository($connection);

        $user = $users->insert(['name' => 'Alice']);
        $admin = $roles->insert(['name' => 'admin']);
        $editor = $roles->insert(['name' => 'editor']);

        $users->roles()->for($user)->attach($admin['id']);
        $users->roles()->for($user)->attach($editor['id']);

        $userRoles = $users->roles()->for($user)->get();

        self::assertCount(2, $userRoles);

        $users->roles()->for($user)->detach($admin['id']);

        self::assertCount(1, $users->roles()->for($user)->get());

        $viewer = $roles->insert(['name' => 'viewer']);
        $users->roles()->for($user)->sync([(int) $editor['id'], (int) $viewer['id']]);

        $synced = $users->roles()->for($user)->get();
        self::assertCount(2, $synced);

        @unlink($path);
    }
}

final class UserTestRepository extends BaseRepository
{
    protected string $table = 'users';

    public function posts(): \App\Database\Relations\HasMany
    {
        return $this->hasMany(PostTestRepository::class, 'user_id');
    }

    public function roles(): \App\Database\Relations\BelongsToMany
    {
        return $this->belongsToMany(RoleTestRepository::class, RoleUserTestRepository::class, 'user_id', 'role_id');
    }
}

final class PostTestRepository extends BaseRepository
{
    protected string $table = 'posts';

    public function user(): \App\Database\Relations\BelongsTo
    {
        return $this->belongsTo(UserTestRepository::class, 'user_id');
    }
}

final class RoleTestRepository extends BaseRepository
{
    protected string $table = 'roles';
}

final class RoleUserTestRepository extends BaseRepository
{
    protected string $table = 'role_user';
}

<?php

declare(strict_types=1);

namespace Tests;

use App\Database\Drivers\Json\JsonConnection;
use App\Database\Drivers\SQLite\SQLiteConnection;
use App\Repositories\BaseRepository;
use PHPUnit\Framework\TestCase;

final class QueryBuilderTest extends TestCase
{
    public function testArrayQueryBuilderSelectsSpecificColumns(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-query-' . uniqid('', true) . '.json';
        $connection = new JsonConnection($path);
        $table = $connection->table('users');

        $table->insert(['name' => 'Alice', 'email' => 'alice@example.com', 'role' => 'admin']);
        $table->insert(['name' => 'Bob', 'email' => 'bob@example.com', 'role' => 'user']);

        $result = $table->query()->select(['name', 'role'])->get();

        self::assertCount(2, $result);
        self::assertSame(['name' => 'Alice', 'role' => 'admin'], $result[0]);
        self::assertSame(['name' => 'Bob', 'role' => 'user'], $result[1]);

        @unlink($path);
    }

    public function testArrayQueryBuilderFiltersAndOrders(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-query-' . uniqid('', true) . '.json';
        $connection = new JsonConnection($path);
        $table = $connection->table('users');

        $table->insert(['name' => 'Alice', 'age' => 30]);
        $table->insert(['name' => 'Bob', 'age' => 25]);
        $table->insert(['name' => 'Carol', 'age' => 35]);

        $result = $table->query()
            ->where('age', 25, '>=')
            ->orderBy('age', 'desc')
            ->limit(2)
            ->get();

        self::assertCount(2, $result);
        self::assertSame('Carol', $result[0]['name']);
        self::assertSame('Alice', $result[1]['name']);

        @unlink($path);
    }

    public function testArrayQueryBuilderPaginates(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-query-' . uniqid('', true) . '.json';
        $connection = new JsonConnection($path);
        $table = $connection->table('users');

        foreach (range(1, 10) as $i) {
            $table->insert(['name' => "User {$i}"]);
        }

        $paginated = $table->query()->orderBy('id')->paginate(2, 3);

        self::assertCount(3, $paginated['data']);
        self::assertSame(2, $paginated['meta']['page']);
        self::assertSame(3, $paginated['meta']['per_page']);
        self::assertSame(10, $paginated['meta']['total']);
        self::assertSame(4, $paginated['meta']['last_page']);

        @unlink($path);
    }

    public function testArrayQueryBuilderFirstAndCount(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-query-' . uniqid('', true) . '.json';
        $connection = new JsonConnection($path);
        $table = $connection->table('users');

        $table->insert(['name' => 'Alice', 'active' => true]);
        $table->insert(['name' => 'Bob', 'active' => false]);
        $table->insert(['name' => 'Carol', 'active' => true]);

        $first = $table->query()->where('active', true)->orderBy('name')->first();

        self::assertSame('Alice', $first['name'] ?? null);
        self::assertSame(2, $table->query()->where('active', true)->count());

        @unlink($path);
    }

    public function testArrayQueryBuilderWhereLikeAndIn(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-query-' . uniqid('', true) . '.json';
        $connection = new JsonConnection($path);
        $table = $connection->table('users');

        $table->insert(['name' => 'Alice']);
        $table->insert(['name' => 'Alex']);
        $table->insert(['name' => 'Bob']);

        $like = $table->query()->whereLike('name', 'Al%')->get();
        self::assertCount(2, $like);

        $in = $table->query()->whereIn('name', ['Alice', 'Bob'])->get();
        self::assertCount(2, $in);

        @unlink($path);
    }

    public function testSQLiteQueryBuilderSelectsAndFilters(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-query-' . uniqid('', true) . '.sqlite';
        $connection = new SQLiteConnection($path);
        $table = $connection->table('users');

        $table->insert(['name' => 'Alice', 'email' => 'alice@example.com']);
        $table->insert(['name' => 'Bob', 'email' => 'bob@example.com']);

        $result = $table->query()
            ->select(['name', 'email'])
            ->where('name', 'Alice')
            ->first();

        self::assertSame('Alice', $result['name'] ?? null);
        self::assertSame('alice@example.com', $result['email'] ?? null);
        self::assertArrayNotHasKey('id', $result ?? []);

        @unlink($path);
    }

    public function testSQLiteQueryBuilderOrdersLimitsAndCounts(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-query-' . uniqid('', true) . '.sqlite';
        $connection = new SQLiteConnection($path);
        $table = $connection->table('users');

        $table->insert(['name' => 'Alice', 'age' => 30]);
        $table->insert(['name' => 'Bob', 'age' => 25]);
        $table->insert(['name' => 'Carol', 'age' => 35]);

        $result = $table->query()
            ->where('age', 25, '>=')
            ->orderBy('age', 'desc')
            ->limit(2)
            ->get();

        self::assertCount(2, $result);
        self::assertSame('Carol', $result[0]['name'] ?? null);
        self::assertSame('Alice', $result[1]['name'] ?? null);
        self::assertSame(3, $table->query()->where('age', 25, '>=')->count());

        @unlink($path);
    }

    public function testRepositoryQueryProxy(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-query-' . uniqid('', true) . '.json';
        $connection = new JsonConnection($path);

        $repository = new class ($connection) extends BaseRepository {
            protected string $table = 'users';
        };

        $repository->insert(['name' => 'Alice', 'role' => 'admin']);
        $repository->insert(['name' => 'Bob', 'role' => 'user']);

        $admins = $repository->query()->where('role', 'admin')->get();

        self::assertCount(1, $admins);
        self::assertSame('Alice', $admins[0]['name'] ?? null);

        @unlink($path);
    }

    public function testRepositoryUsesQueryBuilderForConvenienceMethods(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-query-' . uniqid('', true) . '.json';
        $connection = new JsonConnection($path);

        $repository = new class ($connection) extends BaseRepository {
            protected string $table = 'users';
        };

        $repository->insert(['name' => 'Alice', 'role' => 'admin', 'active' => true]);
        $repository->insert(['name' => 'Bob', 'role' => 'user', 'active' => false]);
        $repository->insert(['name' => 'Carol', 'role' => 'admin', 'active' => true]);

        self::assertCount(2, $repository->where('role', 'admin'));
        self::assertCount(2, $repository->whereAll(['role' => 'admin', 'active' => true]));
        self::assertSame(3, $repository->count());
        self::assertSame('Alice', $repository->first()['name'] ?? null);

        $paginated = $repository->paginate(1, 2);
        self::assertCount(2, $paginated['data']);
        self::assertSame(3, $paginated['meta']['total']);
        self::assertSame(2, $paginated['meta']['last_page']);

        @unlink($path);
    }

    public function testArrayQueryBuilderOrWhere(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-query-' . uniqid('', true) . '.json';
        $connection = new JsonConnection($path);
        $table = $connection->table('users');

        $table->insert(['name' => 'Alice', 'role' => 'admin']);
        $table->insert(['name' => 'Bob', 'role' => 'user']);
        $table->insert(['name' => 'Carol', 'role' => 'guest']);

        $result = $table->query()
            ->where('role', 'admin')
            ->orWhere('role', 'user')
            ->orderBy('name')
            ->get();

        self::assertCount(2, $result);
        self::assertSame('Alice', $result[0]['name']);
        self::assertSame('Bob', $result[1]['name']);

        @unlink($path);
    }

    public function testArrayQueryBuilderWhereNotIn(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-query-' . uniqid('', true) . '.json';
        $connection = new JsonConnection($path);
        $table = $connection->table('users');

        $table->insert(['name' => 'Alice', 'role' => 'admin']);
        $table->insert(['name' => 'Bob', 'role' => 'user']);
        $table->insert(['name' => 'Carol', 'role' => 'guest']);

        $result = $table->query()
            ->whereNotIn('role', ['admin', 'user'])
            ->get();

        self::assertCount(1, $result);
        self::assertSame('Carol', $result[0]['name']);

        @unlink($path);
    }

    public function testArrayQueryBuilderGroupByAndHaving(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-query-' . uniqid('', true) . '.json';
        $connection = new JsonConnection($path);
        $table = $connection->table('users');

        $table->insert(['name' => 'Alice', 'role' => 'admin', 'age' => 30]);
        $table->insert(['name' => 'Bob', 'role' => 'admin', 'age' => 25]);
        $table->insert(['name' => 'Carol', 'role' => 'user', 'age' => 35]);
        $table->insert(['name' => 'Dave', 'role' => 'user', 'age' => 20]);

        $result = $table->query()
            ->select(['role'])
            ->groupBy('role')
            ->having('age', 30, '>=')
            ->orderBy('role')
            ->get();

        self::assertCount(2, $result);
        self::assertSame('admin', $result[0]['role']);
        self::assertSame('user', $result[1]['role']);

        @unlink($path);
    }

    public function testSQLiteQueryBuilderOrWhereAndNotIn(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-query-' . uniqid('', true) . '.sqlite';
        $connection = new SQLiteConnection($path);
        $table = $connection->table('users');

        $table->insert(['name' => 'Alice', 'role' => 'admin']);
        $table->insert(['name' => 'Bob', 'role' => 'user']);
        $table->insert(['name' => 'Carol', 'role' => 'guest']);

        $result = $table->query()
            ->where('role', 'admin')
            ->orWhere('role', 'user')
            ->orderBy('name')
            ->get();

        self::assertCount(2, $result);

        $notIn = $table->query()->whereNotIn('role', ['admin', 'user'])->get();

        self::assertCount(1, $notIn);
        self::assertSame('Carol', $notIn[0]['name']);

        @unlink($path);
    }

    public function testSQLiteQueryBuilderGroupBy(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-query-' . uniqid('', true) . '.sqlite';
        $connection = new SQLiteConnection($path);
        $table = $connection->table('users');

        $table->insert(['name' => 'Alice', 'role' => 'admin']);
        $table->insert(['name' => 'Bob', 'role' => 'admin']);
        $table->insert(['name' => 'Carol', 'role' => 'user']);

        $result = $table->query()
            ->select(['role'])
            ->groupBy('role')
            ->orderBy('role')
            ->get();

        self::assertCount(2, $result);
        self::assertSame('admin', $result[0]['role']);
        self::assertSame('user', $result[1]['role']);

        @unlink($path);
    }

    public function testArrayQueryBuilderJoin(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-query-' . uniqid('', true) . '.json';
        $connection = new JsonConnection($path);

        $users = $connection->table('users');
        $posts = $connection->table('posts');

        $alice = $users->insert(['name' => 'Alice']);
        $bob = $users->insert(['name' => 'Bob']);
        $posts->insert(['title' => 'Post 1', 'user_id' => $alice['id']]);
        $posts->insert(['title' => 'Post 2', 'user_id' => $alice['id']]);
        $posts->insert(['title' => 'Post 3', 'user_id' => $bob['id']]);

        $result = $users->query()
            ->join('posts', 'id', 'user_id')
            ->where('name', 'Alice')
            ->get();

        self::assertCount(2, $result);
        self::assertSame('Post 1', $result[0]['posts_title']);
        self::assertSame('Post 2', $result[1]['posts_title']);

        $left = $users->query()
            ->leftJoin('posts', 'id', 'user_id')
            ->where('name', 'Alice')
            ->get();

        self::assertCount(2, $left);

        @unlink($path);
    }

    public function testSQLiteQueryBuilderJoin(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-query-' . uniqid('', true) . '.sqlite';
        $connection = new SQLiteConnection($path);

        $users = $connection->table('users');
        $posts = $connection->table('posts');

        $alice = $users->insert(['name' => 'Alice']);
        $bob = $users->insert(['name' => 'Bob']);
        $posts->insert(['title' => 'Post 1', 'user_id' => $alice['id']]);
        $posts->insert(['title' => 'Post 2', 'user_id' => $alice['id']]);
        $posts->insert(['title' => 'Post 3', 'user_id' => $bob['id']]);

        $result = $users->query()
            ->join('posts', 'id', 'user_id')
            ->where('name', 'Alice')
            ->get();

        self::assertCount(2, $result);

        @unlink($path);
    }
}

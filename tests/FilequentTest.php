<?php

use PHPUnit\Framework\TestCase;
use Zidbih\Filequent\Filequent;
use Zidbih\Filequent\FileManager;

class User extends Filequent
{
    protected static string $collection = 'users';
    protected static ?string $basePath = __DIR__ . '/testData';

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function latestPost()
    {
        return $this->hasOne(Post::class);
    }
}

class Post extends Filequent
{
    protected static string $collection = 'posts';
    protected static ?string $basePath = __DIR__ . '/testData';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

final class FilequentTest extends TestCase
{
    protected string $dataPath;

    protected function setUp(): void
    {
        $this->dataPath = __DIR__ . '/testData';

        if (!is_dir($this->dataPath)) {
            mkdir($this->dataPath, 0777, true);
        }

        foreach (['users', 'posts'] as $collection) {
            $file = $this->dataPath ."/". $collection . '.json';
            file_put_contents($file, json_encode([]));
        }
    }

    protected function tearDown(): void
    {
        foreach (['users', 'posts'] as $collection) {
            $file = $this->dataPath . "/". $collection . '.json';
            if (file_exists($file)) {
                unlink($file);
            }
        }

        if (is_dir($this->dataPath)) {
            @rmdir($this->dataPath);
        }
    }

    public function testFileManagerInsertAndRead()
    {
        $manager = new FileManager('users', $this->dataPath);
        $inserted = $manager->insert(['name' => 'Alice']);

        $this->assertArrayHasKey('id', $inserted);
        $this->assertEquals('Alice', $inserted['name']);

        $readData = $manager->read();
        $this->assertCount(1, $readData);
    }

    public function testCreateAndFind()
    {
        $user = User::create(['name' => 'Alice']);
        $found = User::find($user->getAttribute('id'));
        $this->assertEquals('Alice', $found->getAttribute('name'));
    }

        public function testUpdate()
    {
        $user = User::create(['name' => 'Original']);
        $this->assertEquals('Original', $user->getAttribute('name'));

        $user->update(['name' => 'Updated']);
        $updated = User::find($user->getAttribute('id'));

        $this->assertEquals('Updated', $updated->getAttribute('name'));
    }

    public function testDelete()
    {
        $user = User::create(['name' => 'ToDelete']);
        $id = $user->getAttribute('id');

        $this->assertNotNull(User::find($id));
        $deleted = $user->delete();
        $this->assertTrue($deleted);
        $this->assertNull(User::find($id));
    }

    public function testFindReturnsNullWhenNotFound()
    {
        $found = User::find(9999);
        $this->assertNull($found);
    }

    public function testAllAndWhere()
    {
        User::create(['name' => 'Alice']);
        User::create(['name' => 'Bob']);

        $all = User::all();
        $this->assertCount(2, $all);

        $bob = User::where('name', '=', 'Bob')->get();
        $this->assertCount(1, $bob);
        $this->assertEquals('Bob', $bob[0]->getAttribute('name'));
    }

    public function testWhereMultipleConditions()
    {
        User::create(['name' => 'Alice', 'age' => 25]);
        User::create(['name' => 'Alice', 'age' => 30]);
        User::create(['name' => 'Bob', 'age' => 25]);

        $results = User::where('name', '=', 'Alice')
            ->where('age', '=', 30)
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals(30, $results[0]->getAttribute('age'));
    }

    public function testQueryBuilderLikeOperator()
    {
        User::create(['name' => 'Ahmed']);
        User::create(['name' => 'Ahmad']);
        User::create(['name' => 'John']);

        $results = User::where('name', 'LIKE', 'Ah%')->get();
        $this->assertCount(2, $results);
    }

    public function testUnsupportedOperatorReturnsEmpty()
    {
        User::create(['name' => 'Alice']);
        $results = User::where('name', 'INVALID_OP', 'Alice')->get();
        $this->assertCount(0, $results);
    }

    public function testBelongsToAndHasMany()
    {
        $user = User::create(['name' => 'Charlie']);
        Post::create(['title' => 'Post 1', 'user_id' => $user->getAttribute('id')]);
        Post::create(['title' => 'Post 2', 'user_id' => $user->getAttribute('id')]);

        $posts = $user->posts();
        $this->assertCount(2, $posts);
        $this->assertEquals('Charlie', $posts[0]->user()->getAttribute('name'));
    }

    public function testHasOne()
    {
        $user = User::create(['name' => 'Single']);
        Post::create(['title' => 'Only Post', 'user_id' => $user->getAttribute('id')]);

        $firstPost = $user->hasOne(Post::class);
        $this->assertEquals('Only Post', $firstPost->getAttribute('title'));
    }

    public function testMissingForeignKeyThrows()
    {
        $this->expectException(Exception::class);
        $post = Post::create(['title' => 'Bad Post', 'userd_id' => 1]); // typo in foreign key
        $post->user();
    }

    public function testMissingIdOnHasManyThrows()
    {
        $this->expectException(Exception::class);
        $user = new User([]);
        $user->posts();
    }

    public function testDefaultForeignKeyConvention()
    {
        $user = User::create(['name' => 'Auto']);
        $post = Post::create(['title' => 'AutoFK', 'user_id' => $user->getAttribute('id')]);

        $userName = $post->user()->getAttribute('name');
        $this->assertEquals('Auto', $userName);
    }

    public function testRelationsWithExplicitForeignKey()
    {
        $user = User::create(['name' => 'Explicit']);
        Post::create(['title' => 'Explicit FK', 'custom_user_id' => $user->getAttribute('id')]);

        // Using explicit foreign key on relations
        $post = Post::where('custom_user_id', '=', $user->getAttribute('id'))->first();
        $this->assertEquals('Explicit FK', $post->getAttribute('title'));

        // belongsTo with explicit foreign key
        $userFromPost = $post->belongsTo(User::class, 'custom_user_id');
        $this->assertEquals('Explicit', $userFromPost->getAttribute('name'));

        // hasMany with explicit foreign key
        $posts = $user->hasMany(Post::class, 'custom_user_id');
        $this->assertCount(1, $posts);
    }

    public function testRelationReturnsNullWhenForeignIdEmpty()
    {
        $user = User::create(['name' => 'NoRelation']);
        $post = Post::create(['title' => 'No User Post', 'user_id' => null]);

        $this->assertNull($post->user());
    }

    public function testEmptyCollectionsReturnEmpty()
    {
        $users = User::all();
        $this->assertIsArray($users);
        $this->assertCount(0, $users);
    }
}

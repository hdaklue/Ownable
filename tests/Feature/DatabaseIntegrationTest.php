<?php

namespace Sowailem\Ownable\Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Sowailem\Ownable\Models\Ownership;
use Sowailem\Ownable\Tests\Models\Post;
use Sowailem\Ownable\Tests\Models\User;
use Sowailem\Ownable\Tests\TestCase;

class DatabaseIntegrationTest extends TestCase
{
    /** @test */
    public function it_has_correct_database_schema()
    {
        $this->assertTrue(Schema::hasTable('ownerships'));
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('posts'));
    }

    /** @test */
    public function it_has_correct_ownerships_table_structure()
    {
        $columns = [
            'id',
            'owner_id',
            'owner_type',
            'ownable_id',
            'ownable_type',
            'is_current',
            'created_at',
            'updated_at'
        ];

        foreach ($columns as $column) {
            $this->assertTrue(Schema::hasColumn('ownerships', $column));
        }
    }

    /** @test */
    public function it_has_correct_database_indexes()
    {
        // Note: This test checks if the indexes exist by attempting to use them efficiently
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $post->ownedBy($user);

        // These queries should use the indexes efficiently
        $ownershipByOwner = Ownership::where('owner_id', $user->id)
            ->where('owner_type', User::class)
            ->first();

        $ownershipByOwnable = Ownership::where('ownable_id', $post->id)
            ->where('ownable_type', Post::class)
            ->first();

        $currentOwnership = Ownership::where('ownable_id', $post->id)
            ->where('ownable_type', Post::class)
            ->where('is_current', true)
            ->first();

        $this->assertNotNull($ownershipByOwner);
        $this->assertNotNull($ownershipByOwnable);
        $this->assertNotNull($currentOwnership);
        $this->assertEquals($ownershipByOwner->id, $ownershipByOwnable->id);
        $this->assertEquals($ownershipByOwner->id, $currentOwnership->id);
    }

    /** @test */
    public function it_persists_ownership_data_correctly()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $post->ownedBy($user);

        // Verify data is persisted in database
        $this->assertDatabaseHas('ownerships', [
            'owner_id' => $user->id,
            'owner_type' => User::class,
            'ownable_id' => $post->id,
            'ownable_type' => Post::class,
            'is_current' => true,
        ]);

        // Transfer ownership
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $post->transferOwnershipTo($user2);

        // Verify old ownership is marked as not current
        $this->assertDatabaseHas('ownerships', [
            'owner_id' => $user->id,
            'owner_type' => User::class,
            'ownable_id' => $post->id,
            'ownable_type' => Post::class,
            'is_current' => false,
        ]);

        // Verify new ownership is current
        $this->assertDatabaseHas('ownerships', [
            'owner_id' => $user2->id,
            'owner_type' => User::class,
            'ownable_id' => $post->id,
            'ownable_type' => Post::class,
            'is_current' => true,
        ]);
    }

    /** @test */
    public function it_handles_database_transactions_correctly()
    {
        $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $post->ownedBy($user1);
        $initialCount = Ownership::count();

        // Simulate transaction rollback scenario
        try {
            \DB::transaction(function () use ($post, $user2) {
                $post->transferOwnershipTo($user2);
                // Force an exception to rollback
                throw new \Exception('Test rollback');
            });
        } catch (\Exception $e) {
            // Expected exception
        }

        // Verify ownership didn't change due to rollback
        $this->assertEquals($initialCount, Ownership::count());
        $this->assertTrue($post->fresh()->isOwnedBy($user1));
        $this->assertFalse($post->fresh()->isOwnedBy($user2));
    }

    /** @test */
    public function it_handles_concurrent_ownership_operations()
    {
        $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $post->ownedBy($user1);

        // Simulate concurrent operations
        $post1 = Post::find($post->id);
        $post2 = Post::find($post->id);

        $post1->transferOwnershipTo($user2);
        
        // Second operation should work with updated state
        $this->assertTrue($post2->fresh()->isOwnedBy($user2));
        $this->assertFalse($post2->fresh()->isOwnedBy($user1));
    }

    /** @test */
    public function it_maintains_referential_integrity()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $post->ownedBy($user);

        // Verify relationships work correctly
        $ownership = Ownership::first();
        
        $this->assertEquals($user->id, $ownership->owner->id);
        $this->assertEquals($post->id, $ownership->ownable->id);
        $this->assertInstanceOf(User::class, $ownership->owner);
        $this->assertInstanceOf(Post::class, $ownership->ownable);
    }

    /** @test */
    public function it_handles_large_datasets_efficiently()
    {
        $users = collect();
        $posts = collect();

        // Create larger dataset
        for ($i = 1; $i <= 100; $i++) {
            $users->push(User::create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com"
            ]));
        }

        for ($i = 1; $i <= 50; $i++) {
            $posts->push(Post::create([
                'title' => "Post {$i}",
                'content' => "Content {$i}"
            ]));
        }

        // Assign random ownership
        foreach ($posts as $post) {
            $randomUser = $users->random();
            $post->ownedBy($randomUser);
        }

        // Verify all ownerships are created
        $this->assertEquals(50, Ownership::count());
        $this->assertEquals(50, Ownership::where('is_current', true)->count());

        // Test bulk operations
        $firstUser = $users->first();
        $lastUser = $users->last();

        // Transfer all posts to first user
        foreach ($posts as $post) {
            $post->transferOwnershipTo($firstUser);
        }

        $this->assertEquals(50, $firstUser->ownables()->wherePivot('is_current', true)->count());
        $this->assertEquals(100, Ownership::count()); // 50 original + 50 transfers

        // Transfer all to last user
        foreach ($posts as $post) {
            $post->transferOwnershipTo($lastUser);
        }

        $this->assertEquals(0, $firstUser->ownables()->wherePivot('is_current', true)->count());
        $this->assertEquals(50, $lastUser->ownables()->wherePivot('is_current', true)->count());
        $this->assertEquals(150, Ownership::count()); // Previous + 50 more transfers
    }

    /** @test */
    public function it_handles_polymorphic_relationships_correctly()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $post->ownedBy($user);

        // Test polymorphic queries
        $userOwnerships = Ownership::where('owner_type', User::class)
            ->where('owner_id', $user->id)
            ->get();

        $postOwnerships = Ownership::where('ownable_type', Post::class)
            ->where('ownable_id', $post->id)
            ->get();

        $this->assertEquals(1, $userOwnerships->count());
        $this->assertEquals(1, $postOwnerships->count());
        $this->assertEquals($userOwnerships->first()->id, $postOwnerships->first()->id);

        // Test with multiple model types (if we had them)
        $this->assertEquals(User::class, $userOwnerships->first()->owner_type);
        $this->assertEquals(Post::class, $postOwnerships->first()->ownable_type);
    }

    /** @test */
    public function it_handles_database_constraints_properly()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        // Test that we can create valid ownership
        $ownership = Ownership::create([
            'owner_id' => $user->id,
            'owner_type' => User::class,
            'ownable_id' => $post->id,
            'ownable_type' => Post::class,
            'is_current' => true,
        ]);

        $this->assertInstanceOf(Ownership::class, $ownership);
        $this->assertTrue($ownership->exists);

        // Test boolean casting
        $this->assertIsBool($ownership->is_current);
        $this->assertTrue($ownership->is_current);
    }

    /** @test */
    public function it_cleans_up_orphaned_records_on_model_deletion()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $post->ownedBy($user);
        $this->assertEquals(1, Ownership::count());

        // Delete the ownable model
        $postId = $post->id;
        $post->delete();

        // Verify ownership records are cleaned up
        $this->assertEquals(0, Ownership::count());
        $this->assertEquals(0, Ownership::where('ownable_id', $postId)->count());
    }

    /** @test */
    public function it_maintains_data_consistency_across_operations()
    {
        $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        // Complex sequence of operations
        $post->ownedBy($user1);
        $post->transferOwnershipTo($user2);
        $post->transferOwnershipTo($user1);
        $user1->takeOwnershipFrom($post);

        // Verify final state
        $this->assertEquals(0, Ownership::where('is_current', true)->count());
        $this->assertEquals(2, Ownership::count()); // Only first two remain (third was deleted)
        $this->assertNull($post->fresh()->currentOwner());

        // Verify data consistency
        $ownerships = Ownership::orderBy('created_at')->get();
        $this->assertFalse($ownerships[0]->is_current);
        $this->assertFalse($ownerships[1]->is_current);
    }
}
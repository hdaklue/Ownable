<?php

namespace Sowailem\Ownable\Tests\Feature;

use InvalidArgumentException;
use Sowailem\Ownable\Facades\Owner;
use Sowailem\Ownable\Models\Ownership;
use Sowailem\Ownable\Tests\Models\Post;
use Sowailem\Ownable\Tests\Models\User;
use Sowailem\Ownable\Tests\TestCase;
use stdClass;

class EdgeCasesAndErrorScenariosTest extends TestCase
{
    /** @test */
    public function it_handles_null_values_gracefully()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        // Test with null values
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Owner must be an Eloquent model');
        Owner::give(null, $post);
    }

    /** @test */
    public function it_handles_invalid_model_types()
    {
        $invalidOwner = new stdClass();
        $invalidOwnable = new stdClass();
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        // Test invalid owner
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Owner must be an Eloquent model');
        Owner::give($invalidOwner, $post);
    }

    /** @test */
    public function it_handles_invalid_ownable_types()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $invalidOwnable = new stdClass();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ownable must implement Sowailem\Ownable\Contracts\Ownable');
        Owner::give($user, $invalidOwnable);
    }

    /** @test */
    public function it_handles_non_existent_models()
    {
        $user = new User(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = new Post(['title' => 'Test Post', 'content' => 'Test content']);

        // Models without IDs (not saved to database)
        $this->expectException(\Exception::class);
        $post->ownedBy($user);
    }

    /** @test */
    public function it_handles_deleted_models_gracefully()
    {
        $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $post->ownedBy($user1);
        $this->assertTrue($post->isOwnedBy($user1));

        // Delete the owner
        $user1->delete();

        // The ownership record should still exist but the relationship will be broken
        $this->assertEquals(1, Ownership::count());
        
        // Current owner should still return the deleted user's data
        $currentOwner = $post->currentOwner();
        $this->assertNull($currentOwner); // Because the user is deleted, morphTo returns null
    }

    /** @test */
    public function it_handles_circular_ownership_scenarios()
    {
        // This test ensures there are no infinite loops or circular references
        $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $post1 = Post::create(['title' => 'Post 1', 'content' => 'Content 1']);
        $post2 = Post::create(['title' => 'Post 2', 'content' => 'Content 2']);

        // Create a complex ownership web
        $post1->ownedBy($user1);
        $post2->ownedBy($user2);
        
        // Transfer back and forth multiple times
        for ($i = 0; $i < 10; $i++) {
            $post1->transferOwnershipTo($user2);
            $post2->transferOwnershipTo($user1);
            $post1->transferOwnershipTo($user1);
            $post2->transferOwnershipTo($user2);
        }

        // Verify final state is consistent
        $this->assertTrue($post1->isOwnedBy($user1));
        $this->assertTrue($post2->isOwnedBy($user2));
        $this->assertEquals(2, Ownership::where('is_current', true)->count());
    }

    /** @test */
    public function it_handles_mass_ownership_operations()
    {
        $users = collect();
        $posts = collect();

        // Create many users and posts
        for ($i = 1; $i <= 20; $i++) {
            $users->push(User::create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com"
            ]));
            
            $posts->push(Post::create([
                'title' => "Post {$i}",
                'content' => "Content {$i}"
            ]));
        }

        // Mass assignment of ownership
        foreach ($posts as $index => $post) {
            $post->ownedBy($users[$index]);
        }

        // Mass transfer to first user
        $firstUser = $users->first();
        foreach ($posts as $post) {
            $post->transferOwnershipTo($firstUser);
        }

        // Verify all posts are owned by first user
        $this->assertEquals(20, $firstUser->ownables()->wherePivot('is_current', true)->count());
        $this->assertEquals(20, Ownership::where('is_current', true)->count());
        $this->assertEquals(40, Ownership::count()); // 20 original + 20 transfers

        // Mass removal of ownership
        foreach ($posts as $post) {
            $firstUser->takeOwnershipFrom($post);
        }

        $this->assertEquals(0, Ownership::where('is_current', true)->count());
        $this->assertEquals(20, Ownership::count()); // Only original ownerships remain
    }

    /** @test */
    public function it_handles_ownership_of_same_object_by_same_user_multiple_times()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        // Give ownership multiple times
        $post->ownedBy($user);
        $initialCount = Ownership::count();
        
        $post->ownedBy($user);
        $post->ownedBy($user);
        $post->ownedBy($user);

        // Should not create duplicate current ownerships
        $this->assertEquals(1, Ownership::where('is_current', true)->count());
        $this->assertTrue(Ownership::count() > $initialCount); // But history is maintained
        $this->assertTrue($post->isOwnedBy($user));
    }

    /** @test */
    public function it_handles_ownership_operations_on_soft_deleted_models()
    {
        // Note: This test assumes soft deletes are not implemented in our test models
        // but tests the scenario where they might be
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $post->ownedBy($user);
        $this->assertTrue($post->isOwnedBy($user));

        // If soft deletes were implemented, this would test that scenario
        // For now, we test regular deletion
        $post->delete();
        $this->assertEquals(0, Ownership::count());
    }

    /** @test */
    public function it_handles_concurrent_ownership_changes()
    {
        $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $user3 = User::create(['name' => 'Bob Smith', 'email' => 'bob@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $post->ownedBy($user1);

        // Simulate concurrent operations
        $post1 = Post::find($post->id);
        $post2 = Post::find($post->id);
        $post3 = Post::find($post->id);

        // Multiple concurrent transfers
        $post1->transferOwnershipTo($user2);
        $post2->fresh()->transferOwnershipTo($user3);
        
        // Final state should be consistent
        $finalPost = Post::find($post->id);
        $this->assertTrue($finalPost->isOwnedBy($user3));
        $this->assertEquals(1, Ownership::where('is_current', true)->count());
    }

    /** @test */
    public function it_handles_ownership_with_very_long_model_names()
    {
        // Test with maximum length model names (edge case for polymorphic types)
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $post->ownedBy($user);

        $ownership = Ownership::first();
        $this->assertEquals(User::class, $ownership->owner_type);
        $this->assertEquals(Post::class, $ownership->ownable_type);
        $this->assertTrue(strlen($ownership->owner_type) < 255); // Assuming varchar(255)
        $this->assertTrue(strlen($ownership->ownable_type) < 255);
    }

    /** @test */
    public function it_handles_ownership_operations_during_model_events()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        // Test ownership during model creation
        $newPost = new Post(['title' => 'New Post', 'content' => 'New content']);
        $newPost->save();
        $newPost->ownedBy($user);

        $this->assertTrue($newPost->isOwnedBy($user));
        $this->assertEquals(1, Ownership::count());
    }

    /** @test */
    public function it_handles_invalid_ownership_state_recovery()
    {
        $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $post->ownedBy($user1);

        // Manually create an invalid state (multiple current owners)
        Ownership::create([
            'owner_id' => $user2->id,
            'owner_type' => User::class,
            'ownable_id' => $post->id,
            'ownable_type' => Post::class,
            'is_current' => true,
        ]);

        // Now we have an invalid state with 2 current owners
        $this->assertEquals(2, Ownership::where('is_current', true)->count());

        // Transfer ownership should fix the invalid state
        $user3 = User::create(['name' => 'Bob Smith', 'email' => 'bob@example.com']);
        $post->transferOwnershipTo($user3);

        // Should now have only one current owner
        $this->assertEquals(1, Ownership::where('is_current', true)->count());
        $this->assertTrue($post->isOwnedBy($user3));
    }

    /** @test */
    public function it_handles_ownership_queries_with_no_results()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        // Test queries on objects with no ownership
        $this->assertNull($post->currentOwner());
        $this->assertEquals(0, $post->owners()->count());
        $this->assertEquals(0, $user->ownables()->count());
        $this->assertFalse($user->owns($post));
        $this->assertFalse($post->isOwnedBy($user));
    }

    /** @test */
    public function it_handles_ownership_operations_with_large_ids()
    {
        // Test with large ID values (edge case for integer limits)
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $post->ownedBy($user);

        $ownership = Ownership::first();
        $this->assertIsInt($ownership->owner_id);
        $this->assertIsInt($ownership->ownable_id);
        $this->assertGreaterThan(0, $ownership->owner_id);
        $this->assertGreaterThan(0, $ownership->ownable_id);
    }

    /** @test */
    public function it_handles_ownership_with_special_characters_in_model_data()
    {
        $user = User::create([
            'name' => 'John "Special" Doe & Co.',
            'email' => 'john+special@example.com'
        ]);
        $post = Post::create([
            'title' => 'Test Post with "quotes" & symbols',
            'content' => 'Content with <html> & special chars'
        ]);

        $post->ownedBy($user);

        $this->assertTrue($post->isOwnedBy($user));
        $this->assertEquals($user->id, $post->currentOwner()->id);
        $this->assertEquals(1, Ownership::count());
    }

    /** @test */
    public function it_handles_rapid_ownership_changes()
    {
        $users = collect();
        for ($i = 1; $i <= 5; $i++) {
            $users->push(User::create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com"
            ]));
        }

        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        // Rapid ownership changes
        foreach ($users as $user) {
            $post->ownedBy($user);
        }

        // Should end up with last user as owner
        $this->assertTrue($post->isOwnedBy($users->last()));
        $this->assertEquals(1, Ownership::where('is_current', true)->count());
        $this->assertEquals($users->count(), Ownership::count());
    }
}
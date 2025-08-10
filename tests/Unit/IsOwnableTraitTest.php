<?php

namespace Sowailem\Ownable\Tests\Unit;

use Sowailem\Ownable\Models\Ownership;
use Sowailem\Ownable\Tests\Models\Post;
use Sowailem\Ownable\Tests\Models\User;
use Sowailem\Ownable\Tests\TestCase;

class IsOwnableTraitTest extends TestCase
{
    /** @test */
    public function it_has_ownerships_relationship()
    {
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class, $post->ownerships());
    }

    /** @test */
    public function it_has_owners_relationship()
    {
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphToMany::class, $post->owners());
    }

    /** @test */
    public function it_can_be_owned_by_a_user()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $result = $post->ownedBy($user);

        $this->assertInstanceOf(Post::class, $result);
        $this->assertTrue($post->isOwnedBy($user));
        $this->assertEquals($user->id, $post->currentOwner()->id);
    }

    /** @test */
    public function it_can_check_if_owned_by_specific_user()
    {
        $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $this->assertFalse($post->isOwnedBy($user1));
        $this->assertFalse($post->isOwnedBy($user2));

        $post->ownedBy($user1);

        $this->assertTrue($post->isOwnedBy($user1));
        $this->assertFalse($post->isOwnedBy($user2));
    }

    /** @test */
    public function it_can_transfer_ownership_to_another_user()
    {
        $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $post->ownedBy($user1);
        $this->assertTrue($post->isOwnedBy($user1));

        $result = $post->transferOwnershipTo($user2);

        $this->assertInstanceOf(Post::class, $result);
        $this->assertFalse($post->isOwnedBy($user1));
        $this->assertTrue($post->isOwnedBy($user2));
        $this->assertEquals($user2->id, $post->currentOwner()->id);
    }

    /** @test */
    public function it_returns_current_owner()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $this->assertNull($post->currentOwner());

        $post->ownedBy($user);

        $currentOwner = $post->currentOwner();
        $this->assertInstanceOf(User::class, $currentOwner);
        $this->assertEquals($user->id, $currentOwner->id);
    }

    /** @test */
    public function it_updates_previous_ownership_to_not_current_when_new_owner_assigned()
    {
        $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $post->ownedBy($user1);
        $post->ownedBy($user2);

        // Check that user1's ownership is marked as not current
        $user1Ownership = Ownership::where('owner_id', $user1->id)
            ->where('owner_type', User::class)
            ->where('ownable_id', $post->id)
            ->where('ownable_type', Post::class)
            ->first();

        $this->assertFalse($user1Ownership->is_current);

        // Check that user2's ownership is current
        $user2Ownership = Ownership::where('owner_id', $user2->id)
            ->where('owner_type', User::class)
            ->where('ownable_id', $post->id)
            ->where('ownable_type', Post::class)
            ->first();

        $this->assertTrue($user2Ownership->is_current);
    }

    /** @test */
    public function it_maintains_ownership_history()
    {
        $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $user3 = User::create(['name' => 'Bob Smith', 'email' => 'bob@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $post->ownedBy($user1);
        $post->ownedBy($user2);
        $post->ownedBy($user3);

        // Should have 3 ownership records
        $this->assertEquals(3, $post->ownerships()->count());

        // Only user3 should be current owner
        $this->assertTrue($post->isOwnedBy($user3));
        $this->assertFalse($post->isOwnedBy($user1));
        $this->assertFalse($post->isOwnedBy($user2));

        // All owners should be in the owners relationship
        $this->assertEquals(3, $post->owners()->count());
    }

    /** @test */
    public function it_deletes_ownerships_when_model_is_deleted()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $post->ownedBy($user);

        $this->assertEquals(1, Ownership::count());

        $post->delete();

        $this->assertEquals(0, Ownership::count());
    }

    /** @test */
    public function it_can_handle_multiple_ownerships_of_same_user()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $post->ownedBy($user);
        $post->ownedBy($user); // Same user again

        // Should still only have one current ownership
        $currentOwnerships = Ownership::where('ownable_id', $post->id)
            ->where('ownable_type', Post::class)
            ->where('is_current', true)
            ->count();

        $this->assertEquals(1, $currentOwnerships);
        $this->assertTrue($post->isOwnedBy($user));
    }

    /** @test */
    public function it_returns_null_when_no_current_owner()
    {
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $this->assertNull($post->currentOwner());
    }
}
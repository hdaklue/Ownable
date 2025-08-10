<?php

namespace Sowailem\Ownable\Tests\Unit;

use Sowailem\Ownable\Models\Ownership;
use Sowailem\Ownable\Tests\Models\Post;
use Sowailem\Ownable\Tests\Models\User;
use Sowailem\Ownable\Tests\TestCase;

class HasOwnablesTraitTest extends TestCase
{
    /** @test */
    public function it_has_ownerships_relationship()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class, $user->ownerships());
    }

    /** @test */
    public function it_has_ownables_relationship()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphToMany::class, $user->ownables());
    }

    /** @test */
    public function it_can_check_if_user_owns_an_object()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $this->assertFalse($user->owns($post));

        $post->ownedBy($user);

        $this->assertTrue($user->owns($post));
    }

    /** @test */
    public function it_can_give_ownership_to_an_object()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $result = $user->giveOwnershipTo($post);

        $this->assertTrue($result);
        $this->assertTrue($user->owns($post));
        $this->assertTrue($post->isOwnedBy($user));
    }

    /** @test */
    public function it_returns_false_when_trying_to_give_ownership_to_already_owned_object()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $post->ownedBy($user);

        $result = $user->giveOwnershipTo($post);

        $this->assertFalse($result);
        $this->assertTrue($user->owns($post));
    }

    /** @test */
    public function it_can_take_ownership_from_an_object()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $post->ownedBy($user);
        $this->assertTrue($user->owns($post));

        $result = $user->takeOwnershipFrom($post);

        $this->assertTrue($result);
        $this->assertFalse($user->owns($post));
        $this->assertFalse($post->isOwnedBy($user));
    }

    /** @test */
    public function it_returns_false_when_trying_to_take_ownership_from_not_owned_object()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $result = $user->takeOwnershipFrom($post);

        $this->assertFalse($result);
        $this->assertFalse($user->owns($post));
    }

    /** @test */
    public function it_can_transfer_ownership_to_another_user()
    {
        $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $post->ownedBy($user1);

        $result = $user1->transferOwnership($post, $user2);

        $this->assertTrue($result);
        $this->assertFalse($user1->owns($post));
        $this->assertTrue($user2->owns($post));
        $this->assertTrue($post->isOwnedBy($user2));
    }

    /** @test */
    public function it_returns_false_when_trying_to_transfer_ownership_of_not_owned_object()
    {
        $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $result = $user1->transferOwnership($post, $user2);

        $this->assertFalse($result);
        $this->assertFalse($user1->owns($post));
        $this->assertFalse($user2->owns($post));
    }

    /** @test */
    public function it_can_own_multiple_objects()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post1 = Post::create(['title' => 'Test Post 1', 'content' => 'Test content 1']);
        $post2 = Post::create(['title' => 'Test Post 2', 'content' => 'Test content 2']);
        $post3 = Post::create(['title' => 'Test Post 3', 'content' => 'Test content 3']);

        $user->giveOwnershipTo($post1);
        $user->giveOwnershipTo($post2);
        $user->giveOwnershipTo($post3);

        $this->assertTrue($user->owns($post1));
        $this->assertTrue($user->owns($post2));
        $this->assertTrue($user->owns($post3));
        $this->assertEquals(3, $user->ownables()->count());
    }

    /** @test */
    public function it_maintains_ownership_relationships_correctly()
    {
        $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $user1->giveOwnershipTo($post);
        $user1->transferOwnership($post, $user2);

        // Check ownership records
        $this->assertEquals(2, Ownership::count());
        
        // Check current ownership
        $this->assertFalse($user1->owns($post));
        $this->assertTrue($user2->owns($post));
        
        // Check relationships
        $this->assertEquals(1, $user1->ownables()->count());
        $this->assertEquals(1, $user2->ownables()->count());
        
        // But only user2 should have current ownership
        $this->assertEquals(0, $user1->ownables()->wherePivot('is_current', true)->count());
        $this->assertEquals(1, $user2->ownables()->wherePivot('is_current', true)->count());
    }

    /** @test */
    public function it_handles_ownership_of_different_model_types()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post1 = Post::create(['title' => 'Test Post 1', 'content' => 'Test content 1']);
        $post2 = Post::create(['title' => 'Test Post 2', 'content' => 'Test content 2']);

        $user->giveOwnershipTo($post1);
        $user->giveOwnershipTo($post2);

        $this->assertTrue($user->owns($post1));
        $this->assertTrue($user->owns($post2));
        $this->assertEquals(2, $user->ownerships()->count());
    }

    /** @test */
    public function it_correctly_identifies_ownership_by_model_instance()
    {
        $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $post->ownedBy($user1);

        $this->assertTrue($user1->owns($post));
        $this->assertFalse($user2->owns($post));
    }

    /** @test */
    public function it_deletes_ownership_records_when_taking_ownership()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $user->giveOwnershipTo($post);
        $this->assertEquals(1, Ownership::count());

        $user->takeOwnershipFrom($post);
        $this->assertEquals(0, Ownership::count());
    }

    /** @test */
    public function it_can_handle_complex_ownership_scenarios()
    {
        $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $user3 = User::create(['name' => 'Bob Smith', 'email' => 'bob@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        // Complex ownership chain
        $user1->giveOwnershipTo($post);
        $user1->transferOwnership($post, $user2);
        $user2->transferOwnership($post, $user3);
        $user3->takeOwnershipFrom($post);

        // Final state: no one owns the post
        $this->assertFalse($user1->owns($post));
        $this->assertFalse($user2->owns($post));
        $this->assertFalse($user3->owns($post));
        $this->assertNull($post->currentOwner());
        
        // But ownership history should be maintained (except for the deleted one)
        $this->assertEquals(2, Ownership::count()); // user3's ownership was deleted
    }
}
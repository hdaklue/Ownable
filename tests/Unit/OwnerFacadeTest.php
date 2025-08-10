<?php

namespace Sowailem\Ownable\Tests\Unit;

use InvalidArgumentException;
use Sowailem\Ownable\Facades\Owner as OwnerFacade;
use Sowailem\Ownable\Tests\Models\Post;
use Sowailem\Ownable\Tests\Models\User;
use Sowailem\Ownable\Tests\TestCase;
use stdClass;

class OwnerFacadeTest extends TestCase
{
    /** @test */
    public function it_can_give_ownership_through_facade()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $result = OwnerFacade::give($user, $post);

        $this->assertInstanceOf(Post::class, $result);
        $this->assertTrue($post->isOwnedBy($user));
    }

    /** @test */
    public function it_throws_exception_when_giving_ownership_with_invalid_owner()
    {
        $invalidOwner = new stdClass();
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Owner must be an Eloquent model');

        OwnerFacade::give($invalidOwner, $post);
    }

    /** @test */
    public function it_throws_exception_when_giving_ownership_with_invalid_ownable()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $invalidOwnable = new stdClass();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ownable must implement Sowailem\Ownable\Contracts\Ownable');

        OwnerFacade::give($user, $invalidOwnable);
    }

    /** @test */
    public function it_can_transfer_ownership_through_facade()
    {
        $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        // Give initial ownership
        $post->ownedBy($user1);

        $result = OwnerFacade::transfer($user1, $user2, $post);

        $this->assertInstanceOf(Post::class, $result);
        $this->assertTrue($post->isOwnedBy($user2));
        $this->assertFalse($post->isOwnedBy($user1));
    }

    /** @test */
    public function it_throws_exception_when_transferring_with_invalid_from_owner()
    {
        $invalidOwner = new stdClass();
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Owners must be Eloquent models');

        OwnerFacade::transfer($invalidOwner, $user2, $post);
    }

    /** @test */
    public function it_throws_exception_when_transferring_with_invalid_to_owner()
    {
        $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $invalidOwner = new stdClass();
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Owners must be Eloquent models');

        OwnerFacade::transfer($user1, $invalidOwner, $post);
    }

    /** @test */
    public function it_throws_exception_when_transferring_invalid_ownable()
    {
        $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $invalidOwnable = new stdClass();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ownable must implement Sowailem\Ownable\Contracts\Ownable');

        OwnerFacade::transfer($user1, $user2, $invalidOwnable);
    }

    /** @test */
    public function it_can_check_ownership_through_facade()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        // Initially not owned
        $this->assertFalse(OwnerFacade::check($user, $post));

        // Give ownership
        $post->ownedBy($user);

        // Now owned
        $this->assertTrue(OwnerFacade::check($user, $post));
    }

    /** @test */
    public function it_throws_exception_when_checking_with_invalid_owner()
    {
        $invalidOwner = new stdClass();
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Owner must be an Eloquent model');

        OwnerFacade::check($invalidOwner, $post);
    }

    /** @test */
    public function it_throws_exception_when_checking_invalid_ownable()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $invalidOwnable = new stdClass();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ownable must implement Sowailem\Ownable\Contracts\Ownable');

        OwnerFacade::check($user, $invalidOwnable);
    }

    /** @test */
    public function it_returns_false_when_checking_ownership_of_different_user()
    {
        $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $post->ownedBy($user1);

        $this->assertTrue(OwnerFacade::check($user1, $post));
        $this->assertFalse(OwnerFacade::check($user2, $post));
    }

    /** @test */
    public function it_can_handle_complex_ownership_workflow_through_facade()
    {
        $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $user3 = User::create(['name' => 'Bob Smith', 'email' => 'bob@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        // Complex workflow using facade
        OwnerFacade::give($user1, $post);
        $this->assertTrue(OwnerFacade::check($user1, $post));

        OwnerFacade::transfer($user1, $user2, $post);
        $this->assertFalse(OwnerFacade::check($user1, $post));
        $this->assertTrue(OwnerFacade::check($user2, $post));

        OwnerFacade::transfer($user2, $user3, $post);
        $this->assertFalse(OwnerFacade::check($user1, $post));
        $this->assertFalse(OwnerFacade::check($user2, $post));
        $this->assertTrue(OwnerFacade::check($user3, $post));
    }

    /** @test */
    public function it_maintains_same_behavior_as_direct_owner_class_usage()
    {
        $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $post1 = Post::create(['title' => 'Test Post 1', 'content' => 'Test content 1']);
        $post2 = Post::create(['title' => 'Test Post 2', 'content' => 'Test content 2']);

        // Using facade
        OwnerFacade::give($user1, $post1);
        $facadeResult = OwnerFacade::check($user1, $post1);

        // Using direct class
        $owner = app('owner');
        $owner->give($user2, $post2);
        $directResult = $owner->check($user2, $post2);

        // Both should behave the same
        $this->assertTrue($facadeResult);
        $this->assertTrue($directResult);
        $this->assertEquals($facadeResult, $directResult);
    }

    /** @test */
    public function it_resolves_to_same_instance_when_called_multiple_times()
    {
        // Since it's registered as singleton, multiple calls should return same instance
        $instance1 = app('owner');
        $instance2 = app('owner');

        $this->assertSame($instance1, $instance2);
    }
}
<?php

namespace Sowailem\Ownable\Tests\Unit;

use InvalidArgumentException;
use Sowailem\Ownable\Owner;
use Sowailem\Ownable\Tests\Models\Post;
use Sowailem\Ownable\Tests\Models\User;
use Sowailem\Ownable\Tests\TestCase;
use stdClass;

class OwnerTest extends TestCase
{
    private Owner $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = new Owner();
    }

    /** @test */
    public function it_can_give_ownership_to_valid_models()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $result = $this->owner->give($user, $post);

        $this->assertInstanceOf(Post::class, $result);
        $this->assertTrue($post->isOwnedBy($user));
    }

    /** @test */
    public function it_throws_exception_when_owner_is_not_eloquent_model()
    {
        $invalidOwner = new stdClass();
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Owner must be an Eloquent model');

        $this->owner->give($invalidOwner, $post);
    }

    /** @test */
    public function it_throws_exception_when_ownable_does_not_implement_contract()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $invalidOwnable = new stdClass();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ownable must implement Sowailem\Ownable\Contracts\Ownable');

        $this->owner->give($user, $invalidOwnable);
    }

    /** @test */
    public function it_can_transfer_ownership_between_valid_models()
    {
        $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        // Give initial ownership
        $post->ownedBy($user1);

        $result = $this->owner->transfer($user1, $user2, $post);

        $this->assertInstanceOf(Post::class, $result);
        $this->assertTrue($post->isOwnedBy($user2));
        $this->assertFalse($post->isOwnedBy($user1));
    }

    /** @test */
    public function it_throws_exception_when_from_owner_is_not_eloquent_model()
    {
        $invalidOwner = new stdClass();
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Owners must be Eloquent models');

        $this->owner->transfer($invalidOwner, $user2, $post);
    }

    /** @test */
    public function it_throws_exception_when_to_owner_is_not_eloquent_model()
    {
        $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $invalidOwner = new stdClass();
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Owners must be Eloquent models');

        $this->owner->transfer($user1, $invalidOwner, $post);
    }

    /** @test */
    public function it_throws_exception_when_transfer_ownable_does_not_implement_contract()
    {
        $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $invalidOwnable = new stdClass();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ownable must implement Sowailem\Ownable\Contracts\Ownable');

        $this->owner->transfer($user1, $user2, $invalidOwnable);
    }

    /** @test */
    public function it_can_check_ownership_for_valid_models()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        // Initially not owned
        $this->assertFalse($this->owner->check($user, $post));

        // Give ownership
        $post->ownedBy($user);

        // Now owned
        $this->assertTrue($this->owner->check($user, $post));
    }

    /** @test */
    public function it_throws_exception_when_check_owner_is_not_eloquent_model()
    {
        $invalidOwner = new stdClass();
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Owner must be an Eloquent model');

        $this->owner->check($invalidOwner, $post);
    }

    /** @test */
    public function it_throws_exception_when_check_ownable_does_not_implement_contract()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $invalidOwnable = new stdClass();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ownable must implement Sowailem\Ownable\Contracts\Ownable');

        $this->owner->check($user, $invalidOwnable);
    }

    /** @test */
    public function it_returns_false_when_checking_ownership_of_different_user()
    {
        $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $post->ownedBy($user1);

        $this->assertTrue($this->owner->check($user1, $post));
        $this->assertFalse($this->owner->check($user2, $post));
    }
}
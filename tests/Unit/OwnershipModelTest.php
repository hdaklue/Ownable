<?php

namespace Sowailem\Ownable\Tests\Unit;

use Sowailem\Ownable\Models\Ownership;
use Sowailem\Ownable\Tests\Models\Post;
use Sowailem\Ownable\Tests\Models\User;
use Sowailem\Ownable\Tests\TestCase;

class OwnershipModelTest extends TestCase
{
    /** @test */
    public function it_has_correct_table_name()
    {
        $ownership = new Ownership();
        
        $this->assertEquals('ownerships', $ownership->getTable());
    }

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        $ownership = new Ownership();
        
        $expectedFillable = [
            'owner_id',
            'owner_type',
            'ownable_id',
            'ownable_type',
            'is_current',
        ];
        
        $this->assertEquals($expectedFillable, $ownership->getFillable());
    }

    /** @test */
    public function it_casts_is_current_to_boolean()
    {
        $ownership = new Ownership();
        
        $this->assertEquals(['is_current' => 'boolean', 'id' => 'int'], $ownership->getCasts());
    }

    /** @test */
    public function it_has_owner_morphto_relationship()
    {
        $ownership = new Ownership();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphTo::class, $ownership->owner());
    }

    /** @test */
    public function it_has_ownable_morphto_relationship()
    {
        $ownership = new Ownership();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphTo::class, $ownership->ownable());
    }

    /** @test */
    public function it_can_create_ownership_record()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $ownership = Ownership::create([
            'owner_id' => $user->id,
            'owner_type' => User::class,
            'ownable_id' => $post->id,
            'ownable_type' => Post::class,
            'is_current' => true,
        ]);

        $this->assertInstanceOf(Ownership::class, $ownership);
        $this->assertEquals($user->id, $ownership->owner_id);
        $this->assertEquals(User::class, $ownership->owner_type);
        $this->assertEquals($post->id, $ownership->ownable_id);
        $this->assertEquals(Post::class, $ownership->ownable_type);
        $this->assertTrue($ownership->is_current);
    }

    /** @test */
    public function it_can_retrieve_owner_through_relationship()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $ownership = Ownership::create([
            'owner_id' => $user->id,
            'owner_type' => User::class,
            'ownable_id' => $post->id,
            'ownable_type' => Post::class,
            'is_current' => true,
        ]);

        $owner = $ownership->owner;

        $this->assertInstanceOf(User::class, $owner);
        $this->assertEquals($user->id, $owner->id);
        $this->assertEquals($user->name, $owner->name);
    }

    /** @test */
    public function it_can_retrieve_ownable_through_relationship()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $ownership = Ownership::create([
            'owner_id' => $user->id,
            'owner_type' => User::class,
            'ownable_id' => $post->id,
            'ownable_type' => Post::class,
            'is_current' => true,
        ]);

        $ownable = $ownership->ownable;

        $this->assertInstanceOf(Post::class, $ownable);
        $this->assertEquals($post->id, $ownable->id);
        $this->assertEquals($post->title, $ownable->title);
    }

    /** @test */
    public function it_can_query_current_ownerships()
    {
        $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        // Create ownership history
        Ownership::create([
            'owner_id' => $user1->id,
            'owner_type' => User::class,
            'ownable_id' => $post->id,
            'ownable_type' => Post::class,
            'is_current' => false, // Previous owner
        ]);

        Ownership::create([
            'owner_id' => $user2->id,
            'owner_type' => User::class,
            'ownable_id' => $post->id,
            'ownable_type' => Post::class,
            'is_current' => true, // Current owner
        ]);

        $currentOwnerships = Ownership::where('is_current', true)->get();
        $allOwnerships = Ownership::all();

        $this->assertEquals(1, $currentOwnerships->count());
        $this->assertEquals(2, $allOwnerships->count());
        $this->assertEquals($user2->id, $currentOwnerships->first()->owner_id);
    }

    /** @test */
    public function it_can_query_ownerships_by_owner()
    {
        $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $post1 = Post::create(['title' => 'Test Post 1', 'content' => 'Test content 1']);
        $post2 = Post::create(['title' => 'Test Post 2', 'content' => 'Test content 2']);

        Ownership::create([
            'owner_id' => $user1->id,
            'owner_type' => User::class,
            'ownable_id' => $post1->id,
            'ownable_type' => Post::class,
            'is_current' => true,
        ]);

        Ownership::create([
            'owner_id' => $user1->id,
            'owner_type' => User::class,
            'ownable_id' => $post2->id,
            'ownable_type' => Post::class,
            'is_current' => true,
        ]);

        Ownership::create([
            'owner_id' => $user2->id,
            'owner_type' => User::class,
            'ownable_id' => $post1->id,
            'ownable_type' => Post::class,
            'is_current' => false,
        ]);

        $user1Ownerships = Ownership::where('owner_id', $user1->id)
            ->where('owner_type', User::class)
            ->get();

        $user2Ownerships = Ownership::where('owner_id', $user2->id)
            ->where('owner_type', User::class)
            ->get();

        $this->assertEquals(2, $user1Ownerships->count());
        $this->assertEquals(1, $user2Ownerships->count());
    }

    /** @test */
    public function it_can_query_ownerships_by_ownable()
    {
        $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        Ownership::create([
            'owner_id' => $user1->id,
            'owner_type' => User::class,
            'ownable_id' => $post->id,
            'ownable_type' => Post::class,
            'is_current' => false,
        ]);

        Ownership::create([
            'owner_id' => $user2->id,
            'owner_type' => User::class,
            'ownable_id' => $post->id,
            'ownable_type' => Post::class,
            'is_current' => true,
        ]);

        $postOwnerships = Ownership::where('ownable_id', $post->id)
            ->where('ownable_type', Post::class)
            ->get();

        $this->assertEquals(2, $postOwnerships->count());
    }

    /** @test */
    public function it_handles_boolean_casting_correctly()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        // Test with integer values
        $ownership1 = Ownership::create([
            'owner_id' => $user->id,
            'owner_type' => User::class,
            'ownable_id' => $post->id,
            'ownable_type' => Post::class,
            'is_current' => 1,
        ]);

        $ownership2 = Ownership::create([
            'owner_id' => $user->id,
            'owner_type' => User::class,
            'ownable_id' => $post->id,
            'ownable_type' => Post::class,
            'is_current' => 0,
        ]);

        $this->assertTrue($ownership1->is_current);
        $this->assertFalse($ownership2->is_current);
        $this->assertIsBool($ownership1->is_current);
        $this->assertIsBool($ownership2->is_current);
    }

    /** @test */
    public function it_has_timestamps()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test content']);

        $ownership = Ownership::create([
            'owner_id' => $user->id,
            'owner_type' => User::class,
            'ownable_id' => $post->id,
            'ownable_type' => Post::class,
            'is_current' => true,
        ]);

        $this->assertNotNull($ownership->created_at);
        $this->assertNotNull($ownership->updated_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $ownership->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $ownership->updated_at);
    }
}
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\CommunityPost;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CommunityPostTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_can_create_a_community_post()
    {
        DB::beginTransaction();

        $user = User::factory()->create();
        $post = CommunityPost::create([
            'user_id' => $user->id,
            'title' => 'Sample Post Title',
            'content' => 'This is a sample post content.',
            'image_url' => 'https://example.com/image.jpg',
        ]);

        $this->assertDatabaseHas('community_posts', [
            'title' => 'Sample Post Title',
            'user_id' => $user->id,
        ]);

        DB::rollBack();
    }

    /** @test */
    public function it_can_update_a_community_post()
    {
        DB::beginTransaction();

        $post = CommunityPost::factory()->create();

        $post->title = 'Updated Post Title';
        $post->save();

        $this->assertDatabaseHas('community_posts', [
            'id' => $post->id,
            'title' => 'Updated Post Title',
        ]);

        DB::rollBack();
    }

    /** @test */
    public function it_can_soft_delete_a_community_post()
    {
        DB::beginTransaction();

        $post = CommunityPost::factory()->create();

        // Soft delete the post
        $post->delete();

        // Assert the post is soft deleted
        $this->assertSoftDeleted('community_posts', [
            'id' => $post->id,
        ]);

        DB::rollBack();
    }

    /** @test */
    public function it_can_restore_a_soft_deleted_community_post()
    {
        DB::beginTransaction();

        $post = CommunityPost::factory()->create();
        $post->delete();

        // Restore the post
        $post->restore();

        // Assert the post is restored
        $this->assertDatabaseHas('community_posts', [
            'id' => $post->id,
        ]);

        DB::rollBack();
    }

    /** @test */
    public function it_has_a_user()
    {
        DB::beginTransaction();

        $post = CommunityPost::factory()->create();
        $this->assertEquals($post->user_id, $post->user->id);

        DB::rollBack();
    }

    /** @test */
    public function it_can_have_comments()
    {
        DB::beginTransaction();

        $post = CommunityPost::factory()->create();
        $comment = \App\Models\PostComment::factory()->create(['post_id' => $post->id]);

        $this->assertCount(1, $post->comments);
        $this->assertEquals($comment->id, $post->comments->first()->id);

        DB::rollBack();
    }
}

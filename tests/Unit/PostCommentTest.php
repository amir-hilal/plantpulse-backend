<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\PostComment;
use App\Models\User;
use App\Models\CommunityPost;
use Illuminate\Support\Facades\DB;

class PostCommentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_can_create_a_post_comment()
    {
        DB::beginTransaction();

        $user = User::factory()->create();
        $post = CommunityPost::factory()->create();

        $comment = PostComment::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'comment_text' => 'This is a sample comment.',
        ]);

        $this->assertDatabaseHas('post_comments', [
            'comment_text' => 'This is a sample comment.',
            'post_id' => $post->id,
        ]);

        DB::rollBack();
    }

    /** @test */
    public function it_can_soft_delete_a_post_comment()
    {
        DB::beginTransaction();

        $comment = PostComment::factory()->create();

        // Soft delete the comment
        $comment->delete();

        // Assert the comment is soft deleted
        $this->assertSoftDeleted('post_comments', [
            'id' => $comment->id,
        ]);

        DB::rollBack();
    }

    /** @test */
    public function it_can_restore_a_soft_deleted_post_comment()
    {
        DB::beginTransaction();

        $comment = PostComment::factory()->create();
        $comment->delete();

        // Restore the comment
        $comment->restore();

        // Assert the comment is restored
        $this->assertDatabaseHas('post_comments', [
            'id' => $comment->id,
        ]);

        DB::rollBack();
    }

    /** @test */
    public function it_belongs_to_a_community_post()
    {
        DB::beginTransaction();

        $comment = PostComment::factory()->create();
        $this->assertEquals($comment->post_id, $comment->post->id);

        DB::rollBack();
    }

    /** @test */
    public function it_belongs_to_a_user()
    {
        DB::beginTransaction();

        $comment = PostComment::factory()->create();
        $this->assertEquals($comment->user_id, $comment->user->id);

        DB::rollBack();
    }
}

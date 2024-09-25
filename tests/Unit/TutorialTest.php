<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Tutorial;
use App\Models\TutorialComment;
use Illuminate\Support\Facades\DB;

class TutorialTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_can_create_a_tutorial()
    {
        DB::beginTransaction();

        $tutorial = Tutorial::create([
            'title' => 'Sample Tutorial',
            'description' => 'This is a sample tutorial description.',
            'video_url' => 'https://www.youtube.com/watch?v=sample',
            'tags' => json_encode(['Laravel', 'Testing', 'Unit Tests']),
        ]);

        $this->assertDatabaseHas('tutorials', [
            'title' => 'Sample Tutorial',
            'video_url' => 'https://www.youtube.com/watch?v=sample',
        ]);

        DB::rollBack();
    }

    /** @test */
    public function it_can_update_a_tutorial()
    {
        DB::beginTransaction();

        $tutorial = Tutorial::factory()->create(); // Create a new tutorial using factory

        $tutorial->title = 'Updated Tutorial';
        $tutorial->save();

        $this->assertDatabaseHas('tutorials', [
            'id' => $tutorial->id,
            'title' => 'Updated Tutorial',
        ]);

        DB::rollBack();
    }

    /** @test */
    public function it_can_soft_delete_a_tutorial()
    {
        DB::beginTransaction();

        $tutorial = Tutorial::factory()->create(); // Create a new tutorial

        // Soft delete the tutorial
        $tutorial->delete();

        // Assert the tutorial is soft deleted
        $this->assertSoftDeleted('tutorials', [
            'id' => $tutorial->id,
        ]);

        DB::rollBack();
    }

    /** @test */
    public function it_can_restore_a_soft_deleted_tutorial()
    {
        DB::beginTransaction();

        $tutorial = Tutorial::factory()->create(); // Create a new tutorial
        $tutorial->delete(); // Soft delete the tutorial

        // Restore the tutorial
        $tutorial->restore();

        // Assert the tutorial is restored
        $this->assertDatabaseHas('tutorials', [
            'id' => $tutorial->id,
        ]);

        DB::rollBack();
    }

    /** @test */
    public function it_has_comments()
    {
        DB::beginTransaction();

        $tutorial = Tutorial::factory()->create(); // Create a new tutorial
        $comment = TutorialComment::factory()->create(['tutorial_id' => $tutorial->id]);

        $this->assertCount(1, $tutorial->comments);
        $this->assertEquals($comment->id, $tutorial->comments->first()->id);

        DB::rollBack();
    }
}

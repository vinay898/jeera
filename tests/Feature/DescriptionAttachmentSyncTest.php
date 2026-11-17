<?php

namespace Tests\Feature;

use App\Filament\Resources\Tickets\Schemas\TicketForm;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DescriptionAttachmentSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function descriptionWith(string ...$paths): string
    {
        return collect($paths)
            ->map(fn (string $path): string => '<p><img src="/storage/'.$path.'" data-id="'.$path.'"></p>')
            ->implode('');
    }

    public function test_description_upload_is_recorded_as_attachment_on_create(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $path = UploadedFile::fake()->image('shot.jpg')->store('ticket-descriptions', 'public');

        $ticket = Ticket::factory()->create(['description' => $this->descriptionWith($path)]);

        $this->assertDatabaseHas('attachments', [
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'path' => $path,
            'filename' => basename($path),
            'mime_type' => 'image/jpeg',
        ]);
    }

    public function test_description_upload_is_recorded_on_update_without_duplicates(): void
    {
        $this->actingAs(User::factory()->create());
        $first = UploadedFile::fake()->image('a.png')->store('ticket-descriptions', 'public');
        $second = UploadedFile::fake()->image('b.png')->store('ticket-descriptions', 'public');

        $ticket = Ticket::factory()->create(['description' => $this->descriptionWith($first)]);
        $ticket->update(['description' => $this->descriptionWith($first, $second)]);
        $ticket->update(['title' => 'Renamed']);

        $this->assertSame(2, $ticket->attachments()->count());
    }

    public function test_missing_files_and_plain_descriptions_create_no_attachments(): void
    {
        $this->actingAs(User::factory()->create());

        $plain = Ticket::factory()->create(['description' => '<p>No files here</p>']);
        $missing = Ticket::factory()->create([
            'team_id' => $plain->team_id,
            'project_id' => $plain->project_id,
            'description' => $this->descriptionWith('ticket-descriptions/gone.jpg'),
        ]);

        $this->assertSame(0, $plain->attachments()->count());
        $this->assertSame(0, $missing->attachments()->count());
    }

    public function test_falls_back_to_reporter_when_unauthenticated(): void
    {
        $path = UploadedFile::fake()->image('c.jpg')->store('ticket-descriptions', 'public');

        $ticket = Ticket::factory()->create(['description' => $this->descriptionWith($path)]);

        $this->assertSame($ticket->reporter_id, $ticket->attachments()->first()?->user_id);
    }

    public function test_editor_upload_keeps_original_file_name(): void
    {
        $path = TicketForm::storeDescriptionAttachment(UploadedFile::fake()->image('My Screenshot.PNG'));

        $this->assertMatchesRegularExpression('#^ticket-descriptions/[0-9A-Z]{26}/my-screenshot\.png$#', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_same_name_uploads_do_not_collide(): void
    {
        $first = TicketForm::storeDescriptionAttachment(UploadedFile::fake()->image('a.png'));
        $second = TicketForm::storeDescriptionAttachment(UploadedFile::fake()->image('a.png'));

        $this->assertNotSame($first, $second);
    }

    public function test_attachment_filename_is_original_name(): void
    {
        $this->actingAs(User::factory()->create());
        $path = TicketForm::storeDescriptionAttachment(UploadedFile::fake()->create('Q3 Report.pdf', 10, 'application/pdf'));

        $ticket = Ticket::factory()->create(['description' => $this->descriptionWith($path)]);

        $this->assertSame('q3-report.pdf', $ticket->attachments()->first()?->filename);
    }
}

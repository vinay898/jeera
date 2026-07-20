<?php

use App\Models\Attachment;
use App\Models\Ticket;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component implements HasForms
{
    use InteractsWithForms;

    public Ticket $ticket;

    public ?array $attachmentData = [];

    public ?int $deletingAttachmentId = null;

    public function mount(Ticket $ticket): void
    {
        $this->ticket = $ticket;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('files')
                    ->label('')
                    ->multiple()
                    ->disk('public')
                    ->directory('attachments/tickets')
                    ->maxSize(10240)
                    ->acceptedFileTypes([
                        'image/*',
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'text/plain',
                        'text/csv',
                    ])
                    ->uploadingMessage('Uploading...')
                    ->panelLayout('compact')
                    ->reorderable(false)
                    ->appendFiles()
                    ->previewable(true)
                    ->openable()
                    ->downloadable()
                    ->columnSpanFull(),
            ])
            ->statePath('attachmentData');
    }

    public function uploadFiles(): void
    {
        $data = $this->form->getState();

        if (empty($data['files'])) {
            return;
        }

        foreach ($data['files'] as $path) {
            $fullPath = Storage::disk('public')->path($path);
            $filename = basename($path);
            $mimeType = Storage::disk('public')->mimeType($path);
            $size = Storage::disk('public')->size($path);

            $this->ticket->attachments()->create([
                'user_id' => auth()->id(),
                'filename' => $filename,
                'path' => $path,
                'mime_type' => $mimeType,
                'size' => $size,
            ]);
        }

        // Reset the form
        $this->attachmentData = [];
        $this->form->fill();
        unset($this->attachments);

        Notification::make()
            ->title('Files uploaded')
            ->success()
            ->send();
    }

    #[Computed]
    public function attachments()
    {
        return $this->ticket->attachments()->with('user')->latest()->get();
    }

    public function confirmDelete(int $attachmentId): void
    {
        $this->deletingAttachmentId = $attachmentId;
    }

    public function cancelDelete(): void
    {
        $this->deletingAttachmentId = null;
    }

    public function deleteAttachment(): void
    {
        if (! $this->deletingAttachmentId) {
            return;
        }

        $attachment = $this->ticket->attachments()->find($this->deletingAttachmentId);

        if ($attachment) {
            if ($attachment->path && Storage::disk('public')->exists($attachment->path)) {
                Storage::disk('public')->delete($attachment->path);
            }
            $attachment->delete();

            Notification::make()
                ->title('Attachment deleted')
                ->success()
                ->send();
        }

        $this->deletingAttachmentId = null;
        unset($this->attachments);
    }

    public function formatFileSize(int $bytes): string
    {
        return Number::fileSize($bytes);
    }

    public function isImage(Attachment $attachment): bool
    {
        return str_starts_with($attachment->mime_type ?? '', 'image/');
    }
};
?>

<div class="space-y-4" x-data x-on:click.stop>
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h3 class="text-sm font-medium text-gray-900 dark:text-white">
            Attachments
        </h3>
        <span class="text-xs text-gray-500 dark:text-gray-400">
            {{ $this->attachments->count() }} file(s)
        </span>
    </div>

    {{-- Existing Attachments List --}}
    @if($this->attachments->isNotEmpty())
        <div class="grid gap-2 max-h-40 overflow-y-auto">
            @foreach($this->attachments as $attachment)
                <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-white/5 rounded-lg text-sm">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        {{-- Thumbnail or Icon --}}
                        @if($this->isImage($attachment))
                            <img
                                src="{{ Storage::disk('public')->url($attachment->path) }}"
                                alt="{{ $attachment->filename }}"
                                class="w-8 h-8 object-cover rounded flex-shrink-0"
                            />
                        @else
                            <div class="w-8 h-8 flex items-center justify-center bg-gray-200 dark:bg-gray-700 rounded flex-shrink-0">
                                <x-heroicon-o-document class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                            </div>
                        @endif

                        <div class="min-w-0 flex-1">
                            <a
                                href="{{ $attachment->url }}"
                                target="_blank"
                                class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline truncate block"
                                x-on:click.stop
                            >
                                {{ $attachment->filename }}
                            </a>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $this->formatFileSize($attachment->size) }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-1 flex-shrink-0">
                        {{-- Download --}}
                        <a
                            href="{{ $attachment->url }}"
                            target="_blank"
                            class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded"
                            title="Download"
                            x-on:click.stop
                        >
                            <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                        </a>

                        {{-- Delete --}}
                        @if($deletingAttachmentId === $attachment->id)
                            <button
                                type="button"
                                wire:click="deleteAttachment"
                                class="p-1.5 text-red-600 hover:text-red-800 rounded"
                                title="Confirm"
                                x-on:click.stop
                            >
                                <x-heroicon-o-check class="w-4 h-4" />
                            </button>
                            <button
                                type="button"
                                wire:click="cancelDelete"
                                class="p-1.5 text-gray-400 hover:text-gray-600 rounded"
                                title="Cancel"
                                x-on:click.stop
                            >
                                <x-heroicon-o-x-mark class="w-4 h-4" />
                            </button>
                        @else
                            <button
                                type="button"
                                wire:click="confirmDelete({{ $attachment->id }})"
                                class="p-1.5 text-gray-400 hover:text-red-600 rounded"
                                title="Delete"
                                x-on:click.stop
                            >
                                <x-heroicon-o-trash class="w-4 h-4" />
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Upload Section --}}
    <div class="space-y-3" x-on:click.stop>
        <form wire:submit="uploadFiles">
            {{ $this->form }}

            @if(!empty($attachmentData['files']))
                <div class="flex justify-end mt-2">
                    <button
                        type="submit"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-white bg-primary-600 hover:bg-primary-500 rounded-lg transition"
                        x-on:click.stop
                    >
                        <x-heroicon-o-arrow-up-tray class="w-4 h-4" />
                        Save Attachments
                    </button>
                </div>
            @endif
        </form>
    </div>
</div>

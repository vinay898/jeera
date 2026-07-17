<?php

use App\Models\Attachment;
use App\Models\Ticket;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public Ticket $ticket;

    public $newFile;

    public ?int $deletingAttachmentId = null;

    public function mount(Ticket $ticket): void
    {
        $this->ticket = $ticket;
    }

    #[Computed]
    public function attachments()
    {
        return $this->ticket->attachments()->with('user')->latest()->get();
    }

    public function uploadFile(): void
    {
        $this->validate([
            'newFile' => [
                'required',
                'file',
                'max:10240',
                'mimetypes:image/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/plain,text/csv',
            ],
        ]);

        $path = $this->newFile->store('attachments/tickets', 'public');

        $this->ticket->attachments()->create([
            'user_id' => auth()->id(),
            'filename' => $this->newFile->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $this->newFile->getMimeType(),
            'size' => $this->newFile->getSize(),
        ]);

        $this->reset('newFile');
        unset($this->attachments);
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
        if (!$this->deletingAttachmentId) {
            return;
        }

        $attachment = $this->ticket->attachments()->find($this->deletingAttachmentId);

        if ($attachment) {
            if ($attachment->path && Storage::disk('public')->exists($attachment->path)) {
                Storage::disk('public')->delete($attachment->path);
            }
            $attachment->delete();
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

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h3 class="text-sm font-medium text-gray-900 dark:text-white">Attachments</h3>
        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $this->attachments->count() }} file(s)</span>
    </div>

    {{-- Existing Attachments List --}}
    @if($this->attachments->isNotEmpty())
        <div class="space-y-2 max-h-48 overflow-y-auto">
            @foreach($this->attachments as $attachment)
                <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded-lg group">
                    <div class="flex items-center gap-3 min-w-0">
                        {{-- Thumbnail or Icon --}}
                        @if($this->isImage($attachment))
                            <img
                                src="{{ Storage::disk('public')->url($attachment->path) }}"
                                alt="{{ $attachment->filename }}"
                                class="w-10 h-10 object-cover rounded"
                            />
                        @else
                            <div class="w-10 h-10 flex items-center justify-center bg-gray-200 dark:bg-gray-700 rounded">
                                <x-heroicon-o-document class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                            </div>
                        @endif

                        <div class="min-w-0">
                            <a
                                href="{{ $attachment->url }}"
                                target="_blank"
                                class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline truncate block"
                            >
                                {{ $attachment->filename }}
                            </a>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $this->formatFileSize($attachment->size) }}
                                @if($attachment->user)
                                    &middot; {{ $attachment->user->name }}
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-1">
                        {{-- Download Button --}}
                        <a
                            href="{{ $attachment->url }}"
                            target="_blank"
                            class="p-1.5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                            title="Download"
                        >
                            <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                        </a>

                        {{-- Delete Button --}}
                        @if($deletingAttachmentId === $attachment->id)
                            <button
                                wire:click="deleteAttachment"
                                class="p-1.5 text-red-600 hover:text-red-800 dark:text-red-400"
                                title="Confirm delete"
                            >
                                <x-heroicon-o-check class="w-4 h-4" />
                            </button>
                            <button
                                wire:click="cancelDelete"
                                class="p-1.5 text-gray-500 hover:text-gray-700 dark:text-gray-400"
                                title="Cancel"
                            >
                                <x-heroicon-o-x-mark class="w-4 h-4" />
                            </button>
                        @else
                            <button
                                wire:click="confirmDelete({{ $attachment->id }})"
                                class="p-1.5 text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400"
                                title="Delete"
                            >
                                <x-heroicon-o-trash class="w-4 h-4" />
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No attachments yet</p>
    @endif

    {{-- Upload Section --}}
    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
        <form wire:submit="uploadFile" class="flex items-end gap-2">
            <div class="flex-1">
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Add attachment
                </label>
                <input
                    type="file"
                    wire:model="newFile"
                    class="block w-full text-sm text-gray-500 dark:text-gray-400
                        file:mr-4 file:py-1.5 file:px-3
                        file:rounded file:border-0
                        file:text-sm file:font-medium
                        file:bg-primary-50 file:text-primary-700
                        dark:file:bg-primary-900 dark:file:text-primary-300
                        hover:file:bg-primary-100 dark:hover:file:bg-primary-800
                        cursor-pointer"
                    accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.csv"
                />
                @error('newFile')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="newFile,uploadFile"
                class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span wire:loading.remove wire:target="newFile,uploadFile">
                    <x-heroicon-o-arrow-up-tray class="w-4 h-4" />
                </span>
                <span wire:loading wire:target="newFile,uploadFile">
                    <x-heroicon-o-arrow-path class="w-4 h-4 animate-spin" />
                </span>
                Upload
            </button>
        </form>
    </div>
</div>

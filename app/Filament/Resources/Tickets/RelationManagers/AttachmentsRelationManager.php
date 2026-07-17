<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tickets\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    protected static ?string $title = 'Attachments';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('path')
                    ->label('File')
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
                    ->required()
                    ->columnSpanFull()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state instanceof TemporaryUploadedFile) {
                            $set('filename', $state->getClientOriginalName());
                            $set('mime_type', $state->getMimeType());
                            $set('size', $state->getSize());
                        }
                    })
                    ->live(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('filename')
            ->columns([
                ImageColumn::make('path')
                    ->label('')
                    ->disk('public')
                    ->width(40)
                    ->height(40)
                    ->visibility(fn (Model $record): bool => $record->isImage()),
                TextColumn::make('filename')
                    ->label('File')
                    ->searchable()
                    ->url(fn (Model $record): string => $record->url)
                    ->openUrlInNewTab(),
                TextColumn::make('size')
                    ->formatStateUsing(fn (int $state): string => Number::fileSize($state))
                    ->label('Size'),
                TextColumn::make('user.name')
                    ->label('Uploaded by'),
                TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->headerActions([
                CreateAction::make()
                    ->label('Upload File')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();

                        if (! isset($data['filename']) && isset($data['path'])) {
                            $data['filename'] = basename($data['path']);
                        }

                        if (! isset($data['mime_type']) && isset($data['path'])) {
                            $data['mime_type'] = Storage::disk('public')->mimeType($data['path']);
                        }

                        if (! isset($data['size']) && isset($data['path'])) {
                            $data['size'] = Storage::disk('public')->size($data['path']);
                        }

                        return $data;
                    }),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Model $record): string => $record->url)
                    ->openUrlInNewTab(),
                DeleteAction::make()
                    ->after(function (Model $record): void {
                        if ($record->path && Storage::disk('public')->exists($record->path)) {
                            Storage::disk('public')->delete($record->path);
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->after(function ($records): void {
                            foreach ($records as $record) {
                                if ($record->path && Storage::disk('public')->exists($record->path)) {
                                    Storage::disk('public')->delete($record->path);
                                }
                            }
                        }),
                ]),
            ]);
    }
}

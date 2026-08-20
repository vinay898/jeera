<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\TeamRole;
use App\Mail\TeamInvitationMail;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Mail;

class TeamSettings extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Team';

    protected static ?string $title = 'Team Settings';

    protected static ?string $slug = 'team';

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.team-settings';

    public function getHeading(): string|Htmlable
    {
        return $this->getTeam()->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Manage team members and invitations';
    }

    public function getTeam(): Team
    {
        return Filament::getTenant();
    }

    public static function canAccess(): bool
    {
        /** @var User $user */
        $user = auth()->user();
        $team = Filament::getTenant();

        if (! $team) {
            return false;
        }

        return $user->canManageTeamMembers($team);
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('invite')
                ->label('Invite Member')
                ->icon(Heroicon::OutlinedUserPlus)
                ->form([
                    TextInput::make('email')
                        ->label('Email Address')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->rules([
                            fn () => function (string $attribute, mixed $value, \Closure $fail) {
                                $team = $this->getTeam();

                                // Check if already a member
                                if ($team->users()->where('email', $value)->exists()) {
                                    $fail('This user is already a member of this team.');
                                }

                                // Check for existing pending invitation
                                if ($team->pendingInvitations()->where('email', $value)->exists()) {
                                    $fail('An invitation is already pending for this email.');
                                }
                            },
                        ]),
                    Select::make('role')
                        ->label('Role')
                        ->options([
                            TeamRole::Admin->value => TeamRole::Admin->label().' - '.TeamRole::Admin->description(),
                            TeamRole::Member->value => TeamRole::Member->label().' - '.TeamRole::Member->description(),
                        ])
                        ->default(TeamRole::Member->value)
                        ->required()
                        ->native(false),
                ])
                ->action(function (array $data): void {
                    $team = $this->getTeam();

                    $invitation = TeamInvitation::create([
                        'team_id' => $team->id,
                        'email' => $data['email'],
                        'role' => $data['role'],
                        'token' => TeamInvitation::generateToken(),
                        'expires_at' => now()->addDays(7),
                        'invited_by' => auth()->id(),
                    ]);

                    Mail::to($data['email'])->send(new TeamInvitationMail($invitation));

                    Notification::make()
                        ->success()
                        ->title('Invitation sent')
                        ->body("An invitation has been sent to {$data['email']}")
                        ->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTeam()->users()->getQuery())
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('pivot.role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => TeamRole::from($state)->label())
                    ->color(fn (string $state): string => TeamRole::from($state)->color()),
                TextColumn::make('pivot.created_at')
                    ->label('Joined')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('change_role')
                        ->label('Change Role')
                        ->icon(Heroicon::OutlinedPencilSquare)
                        ->form([
                            Select::make('role')
                                ->label('New Role')
                                ->options(fn (User $record) => $this->getAvailableRoles($record))
                                ->default(fn (User $record) => $record->pivot->role)
                                ->required()
                                ->native(false),
                        ])
                        ->action(function (User $record, array $data): void {
                            $this->getTeam()->users()->updateExistingPivot($record->id, [
                                'role' => $data['role'],
                            ]);

                            Notification::make()
                                ->success()
                                ->title('Role updated')
                                ->send();

                            $this->resetTable();
                        })
                        ->visible(fn (User $record) => $this->canChangeRole($record)),
                    Action::make('remove')
                        ->label('Remove')
                        ->icon(Heroicon::OutlinedTrash)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Remove team member')
                        ->modalDescription(fn (User $record) => "Are you sure you want to remove {$record->name} from this team?")
                        ->action(function (User $record): void {
                            $this->getTeam()->users()->detach($record->id);

                            Notification::make()
                                ->success()
                                ->title('Member removed')
                                ->send();

                            $this->resetTable();
                        })
                        ->visible(fn (User $record) => $this->canRemoveMember($record)),
                ]),
            ])
            ->emptyStateHeading('No team members')
            ->emptyStateDescription('Invite members to collaborate on this team.');
    }

    /**
     * @return array<string, string>
     */
    protected function getAvailableRoles(User $record): array
    {
        /** @var User $currentUser */
        $currentUser = auth()->user();
        $isCurrentUserOwner = $currentUser->isTeamOwner($this->getTeam());

        $roles = [];

        // Only owners can assign owner role
        if ($isCurrentUserOwner) {
            $roles[TeamRole::Owner->value] = TeamRole::Owner->label();
        }

        $roles[TeamRole::Admin->value] = TeamRole::Admin->label();
        $roles[TeamRole::Member->value] = TeamRole::Member->label();

        return $roles;
    }

    protected function canChangeRole(User $record): bool
    {
        /** @var User $currentUser */
        $currentUser = auth()->user();

        // Can't change own role
        if ($currentUser->id === $record->id) {
            return false;
        }

        $currentUserRole = $currentUser->teamRole($this->getTeam());
        $targetUserRole = $record->teamRole($this->getTeam());

        // Owners can change anyone's role
        if ($currentUserRole === TeamRole::Owner) {
            return true;
        }

        // Admins can only change member roles (not other admins or owners)
        if ($currentUserRole === TeamRole::Admin) {
            return $targetUserRole === TeamRole::Member;
        }

        return false;
    }

    protected function canRemoveMember(User $record): bool
    {
        /** @var User $currentUser */
        $currentUser = auth()->user();

        // Can't remove yourself
        if ($currentUser->id === $record->id) {
            return false;
        }

        $currentUserRole = $currentUser->teamRole($this->getTeam());
        $targetUserRole = $record->teamRole($this->getTeam());

        // Owners can remove anyone except themselves
        if ($currentUserRole === TeamRole::Owner) {
            return true;
        }

        // Admins can only remove members
        if ($currentUserRole === TeamRole::Admin) {
            return $targetUserRole === TeamRole::Member;
        }

        return false;
    }

    public function cancelInvitation(int $invitationId): void
    {
        $invitation = TeamInvitation::where('team_id', $this->getTeam()->id)
            ->where('id', $invitationId)
            ->firstOrFail();

        $invitation->delete();

        Notification::make()
            ->success()
            ->title('Invitation cancelled')
            ->send();
    }
}

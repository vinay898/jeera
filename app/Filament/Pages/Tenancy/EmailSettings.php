<?php

declare(strict_types=1);

namespace App\Filament\Pages\Tenancy;

use App\Enums\TicketPriority;
use App\Enums\TicketType;
use App\Models\EmailConfiguration;
use App\Models\Project;
use App\Services\ImapService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use PhpImap\Exceptions\ConnectionException;

/**
 * @property-read Schema $form
 */
class EmailSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $navigationLabel = 'Email Settings';

    protected static ?string $title = 'Email Settings';

    protected static ?string $slug = 'email-settings';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.pages.email-settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->getRecord()->attributesToArray());
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('testConnection')
                ->label('Test Connection')
                ->icon(Heroicon::OutlinedSignal)
                ->color('gray')
                ->action('testConnection'),
            Action::make('save')
                ->label('Save Settings')
                ->icon(Heroicon::OutlinedCheck)
                ->action('save')
                ->keyBindings(['mod+s']),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Email Settings')
                    ->tabs([
                        Tabs\Tab::make('IMAP Configuration')
                            ->icon(Heroicon::OutlinedInboxArrowDown)
                            ->schema([
                                Section::make('Connection Settings')
                                    ->description('Configure your IMAP server connection')
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('imap_enabled')
                                            ->label('Enable IMAP Polling')
                                            ->helperText('Turn on to poll this mailbox for incoming emails')
                                            ->columnSpanFull(),
                                        TextInput::make('imap_host')
                                            ->label('IMAP Host')
                                            ->placeholder('imap.gmail.com')
                                            ->helperText('For Gmail: imap.gmail.com'),
                                        TextInput::make('imap_port')
                                            ->label('Port')
                                            ->numeric()
                                            ->default(993)
                                            ->helperText('Usually 993 for SSL'),
                                        Select::make('imap_encryption')
                                            ->label('Encryption')
                                            ->options([
                                                'ssl' => 'SSL (Recommended)',
                                                'tls' => 'TLS',
                                                'none' => 'None',
                                            ])
                                            ->default('ssl')
                                            ->native(false),
                                        TextInput::make('imap_mailbox')
                                            ->label('Mailbox')
                                            ->default('INBOX')
                                            ->helperText('Folder to monitor (usually INBOX)'),
                                    ]),
                                Section::make('Authentication')
                                    ->description('Enter your email credentials')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('imap_username')
                                            ->label('Email Address')
                                            ->email()
                                            ->placeholder('support@yourcompany.com'),
                                        TextInput::make('imap_password')
                                            ->label('Password')
                                            ->password()
                                            ->revealable()
                                            ->helperText('For Gmail: Use an App Password (requires 2FA)'),
                                    ]),
                                Section::make('Polling')
                                    ->description('Configure how often to check for new emails')
                                    ->schema([
                                        TextInput::make('poll_interval_seconds')
                                            ->label('Poll Interval (seconds)')
                                            ->numeric()
                                            ->default(60)
                                            ->minValue(10)
                                            ->maxValue(3600)
                                            ->helperText('How often to check for new emails (10-3600 seconds)')
                                            ->suffix('seconds'),
                                    ]),
                            ]),
                        Tabs\Tab::make('Outbound Settings')
                            ->icon(Heroicon::OutlinedPaperAirplane)
                            ->schema([
                                Section::make('Sender Information')
                                    ->description('Configure how outgoing emails appear')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('from_name')
                                            ->label('From Name')
                                            ->placeholder('Acme Support'),
                                        TextInput::make('from_address')
                                            ->label('From Email')
                                            ->email()
                                            ->placeholder('support@yourcompany.com'),
                                    ]),
                                Section::make('Email Signature')
                                    ->schema([
                                        Textarea::make('signature')
                                            ->label('Signature')
                                            ->rows(4)
                                            ->placeholder("Best regards,\nThe Support Team"),
                                    ]),
                            ]),
                        Tabs\Tab::make('Ticket Defaults')
                            ->icon(Heroicon::OutlinedTicket)
                            ->schema([
                                Section::make('Automatic Ticket Creation')
                                    ->description('Configure how tickets are created from incoming emails')
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('auto_create_tickets')
                                            ->label('Auto-create Tickets')
                                            ->helperText('Automatically create tickets from new emails')
                                            ->columnSpanFull(),
                                        Select::make('project_id')
                                            ->label('Default Project')
                                            ->options(fn () => Project::where('team_id', Filament::getTenant()->id)->pluck('name', 'id'))
                                            ->searchable()
                                            ->native(false)
                                            ->helperText('Assign new tickets to this project'),
                                        Select::make('default_ticket_type')
                                            ->label('Default Ticket Type')
                                            ->options(collect(TicketType::cases())->mapWithKeys(fn ($type) => [$type->value => $type->label()]))
                                            ->default('task')
                                            ->native(false),
                                        Select::make('default_priority')
                                            ->label('Default Priority')
                                            ->options(collect(TicketPriority::cases())->mapWithKeys(fn ($priority) => [$priority->value => $priority->label()]))
                                            ->default('medium')
                                            ->native(false),
                                    ]),
                            ]),
                    ])
                    ->persistTabInQueryString(),
            ])
            ->statePath('data');
    }

    public function testConnection(): void
    {
        $data = $this->form->getState();

        if (empty($data['imap_host']) || empty($data['imap_username']) || empty($data['imap_password'])) {
            Notification::make()
                ->danger()
                ->title('Missing Configuration')
                ->body('Please fill in the IMAP host, username, and password first.')
                ->send();

            return;
        }

        try {
            $imapService = app(ImapService::class);
            $imapService->testConnection($data);

            Notification::make()
                ->success()
                ->title('Connection Successful')
                ->body('Successfully connected to the IMAP server.')
                ->send();
        } catch (ConnectionException $e) {
            Notification::make()
                ->danger()
                ->title('Connection Failed')
                ->body($e->getMessage())
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Connection Error')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $record = $this->getRecord();
        $record->fill($data);
        $record->save();

        Notification::make()
            ->success()
            ->title('Settings saved')
            ->body('Email configuration has been updated.')
            ->send();
    }

    public function getRecord(): EmailConfiguration
    {
        $team = Filament::getTenant();

        return EmailConfiguration::firstOrCreate(
            ['team_id' => $team->id],
            [
                'inbound_email' => '',
                'inbound_secret' => bin2hex(random_bytes(32)),
                'from_name' => $team->name,
                'from_address' => '',
                'auto_create_tickets' => true,
                'default_ticket_type' => 'task',
                'default_priority' => 'medium',
                'is_active' => true,
            ]
        );
    }
}

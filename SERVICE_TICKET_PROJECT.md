# Service Ticket System (Jira Replacement)

A full-featured service ticket and project management system built on Laravel 13, Filament v5, and Livewire 4.

---

## Tech Stack

### Core Framework
- **PHP**: 8.3+
- **Laravel**: 13.x
- **Database**: SQLite (dev) / MySQL/PostgreSQL (prod)

### Frontend & UI
- **Filament**: v5.6+ - Admin panel & CRUD
- **Livewire**: v4.1+ - Reactive components
- **Flux UI**: v2.x (livewire/flux) - Component library
- **Tailwind CSS**: v4.x - Utility-first styling
- **Alpine.js**: Client-side interactivity
- **Vite**: 8.x - Asset bundling

### AI Integration
- **Prism PHP**: ^0.100 - LLM abstraction (OpenAI/Anthropic)

### Authentication
- **Laravel Fortify**: v1.x - Headless auth scaffolding

### Queue & Jobs
- **Driver**: Database (default)
- **Failed Jobs**: Database tracking
- **Job Batching**: Enabled

### Testing & Quality
- **PHPUnit**: v12+ (NOT Pest)
- **Laravel Pint**: Code formatting
- **Laravel Boost**: Development MCP server

### Dev Tools
- **Laravel Pail**: Real-time log monitoring
- **Laravel Tinker**: REPL debugging

---

## MCP Server Configuration

### Laravel Boost Tools
```json
{
  "mcpServers": {
    "laravel-boost": {
      "command": "php",
      "args": ["vendor/bin/boost", "mcp"]
    }
  }
}
```

**Available Tools:**
- `database-schema` - Inspect table structure
- `database-query` - Read-only SQL queries
- `search-docs` - Version-specific documentation
- `browser-logs` - Frontend debugging
- `last-error` - Backend error tracking
- `read-log-entries` - Application logs
- `application-info` - Package versions
- `get-absolute-url` - URL resolution

---

## Skills Activation

Activate domain-specific skills when working in these areas:

| Skill | Trigger |
|-------|---------|
| `fluxui-development` | Working with `<flux:*>` components |
| `livewire-development` | Livewire components, `wire:` directives |
| `tailwindcss-development` | Tailwind utility classes |
| `fortify-development` | Authentication features |
| `laravel-best-practices` | Backend PHP patterns |

---

## Architecture Patterns

### Directory Structure
```
app/
├── Ai/
│   ├── Tools/                    # AI tool implementations
│   └── ToolRegistry.php          # Tool discovery
├── Services/
│   ├── Ai/AgentService.php       # LLM orchestration
│   ├── TicketService.php         # Ticket business logic
│   ├── SprintService.php         # Sprint management
│   ├── SlaService.php            # SLA calculations
│   └── WorkflowService.php       # Automation engine
├── Livewire/
│   ├── KanbanBoard.php           # Drag-drop board
│   ├── SprintBoard.php           # Scrum board
│   ├── TicketModal.php           # Quick view/edit
│   └── TimeLogger.php            # Time tracking
├── Filament/
│   ├── Resources/
│   │   ├── Projects/
│   │   ├── Tickets/
│   │   ├── Epics/
│   │   └── Sprints/
│   ├── Pages/
│   │   ├── Dashboard.php
│   │   ├── Backlog.php
│   │   └── Reports.php
│   └── Widgets/
├── Models/
├── Enums/
├── Observers/
└── Jobs/
```

### Multi-Tenancy
- **Tenant Model**: Team (with `slug` attribute)
- **Ownership**: All models have `team_id`
- **Filament Config**: `->tenant(Team::class, slugAttribute: 'slug')`

### Service Layer
```
Request → Filament Resource / Livewire Component
    ↓
Service Class (TicketService, SprintService)
    ↓
Eloquent Model (with soft deletes)
    ↓
Database
```

---

## Domain Models

### Hierarchy
```
Team (tenant)
├── Projects
│   ├── Epics
│   │   └── Stories (tickets with type='story')
│   │       └── Subtasks (tickets with parent_id)
│   ├── Tickets (bugs, tasks, improvements)
│   ├── Sprints
│   └── Labels
├── Users (team_user pivot)
├── CustomFields
├── Workflows
└── SlaConfigurations
```

### Core Tables

**projects**
```
id, team_id, name, key (e.g., "PROJ"), description,
lead_user_id, default_assignee_id, workflow_id,
is_archived, created_at, updated_at, deleted_at
```

**tickets**
```
id, team_id, project_id, epic_id, parent_id, sprint_id,
key (e.g., "PROJ-123"), title, description, type,
status, priority, assignee_id, reporter_id,
story_points, original_estimate, time_spent, time_remaining,
due_date, resolution, resolved_at, labels (json),
custom_fields (json), watchers (json),
created_at, updated_at, deleted_at
```

**epics**
```
id, team_id, project_id, key, title, description,
status, start_date, end_date, color,
created_at, updated_at, deleted_at
```

**sprints**
```
id, team_id, project_id, name, goal,
start_date, end_date, status (planning/active/completed),
created_at, updated_at
```

**comments**
```
id, ticket_id, user_id, body, is_internal,
created_at, updated_at, deleted_at
```

**attachments**
```
id, ticket_id, user_id, filename, path, mime_type, size,
created_at
```

**time_logs**
```
id, ticket_id, user_id, started_at, ended_at,
duration_minutes, description, created_at
```

**sla_configurations**
```
id, team_id, project_id, name, priority,
response_time_minutes, resolution_time_minutes,
business_hours_only, is_active, created_at, updated_at
```

**sla_breaches**
```
id, ticket_id, sla_configuration_id, type (response/resolution),
breached_at, notified_at
```

**workflows**
```
id, team_id, name, is_default, statuses (json), transitions (json),
created_at, updated_at
```

**custom_fields**
```
id, team_id, project_id, name, type (text/number/select/date/user),
options (json), is_required, created_at, updated_at
```

**ticket_history**
```
id, ticket_id, user_id, field, old_value, new_value, created_at
```

### Enums

```php
enum TicketType: string {
    case Bug = 'bug';
    case Story = 'story';
    case Task = 'task';
    case Improvement = 'improvement';
    case Subtask = 'subtask';
}

enum TicketStatus: string {
    case Open = 'open';
    case InProgress = 'in_progress';
    case InReview = 'in_review';
    case Testing = 'testing';
    case Done = 'done';
    case Closed = 'closed';
}

enum TicketPriority: string {
    case Highest = 'highest';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';
    case Lowest = 'lowest';
}

enum SprintStatus: string {
    case Planning = 'planning';
    case Active = 'active';
    case Completed = 'completed';
}
```

---

## Key Features

### 1. Kanban Board
- Drag-drop tickets between columns
- Column = workflow status
- WIP limits per column
- Quick filters (assignee, label, type)
- Swimlanes by epic/assignee

### 2. Scrum Board
- Sprint backlog view
- Active sprint board
- Burndown/burnup charts
- Velocity tracking
- Sprint planning interface

### 3. Ticket Management
- Full CRUD with history
- Rich text description (Markdown)
- File attachments
- Comments (public/internal)
- Watchers & notifications
- Linked tickets (blocks/blocked by/relates to)
- Clone tickets

### 4. Time Tracking
- Log time against tickets
- Timer widget (start/stop)
- Original estimate vs actual
- Time remaining auto-calculation
- Worklog reports

### 5. SLA Management
- Response time tracking
- Resolution time tracking
- Business hours calculation
- Breach warnings & notifications
- SLA report dashboard

### 6. Custom Fields
- Text, number, date, select, multi-select, user
- Project-level or global
- Required/optional
- Searchable/filterable

### 7. Workflows & Automations
- Custom status workflows per project
- Transition rules (who can move where)
- Auto-assign on create
- Auto-transition triggers
- Notification rules
- Webhook integrations

### 8. Search & Filters
- Full-text search
- JQL-like query syntax
- Saved filters
- Quick filters on boards

### 9. Reports & Dashboards
- Configurable dashboard widgets
- Created vs resolved chart
- Time in status
- Workload by assignee
- Sprint reports (velocity, burndown)
- SLA compliance

---

## AI Tools

Implement these AI tools in `app/Ai/Tools/`:

| Tool | Description |
|------|-------------|
| `SearchTicketsTool` | Find tickets by criteria |
| `CreateTicketTool` | Create new tickets |
| `UpdateTicketTool` | Modify ticket fields |
| `MoveTicketTool` | Change status/sprint |
| `AssignTicketTool` | Assign to user |
| `LogTimeTool` | Record time entry |
| `CreateSprintTool` | Start new sprint |
| `GetSprintStatusTool` | Sprint progress summary |
| `SearchProjectsTool` | Find projects |
| `GetBacklogTool` | List backlog items |

---

## Filament Resources

### Resources to Create
1. **ProjectResource** - CRUD for projects
2. **TicketResource** - Full ticket management
3. **EpicResource** - Epic tracking
4. **SprintResource** - Sprint management
5. **WorkflowResource** - Status configuration
6. **CustomFieldResource** - Field definitions
7. **SlaConfigurationResource** - SLA rules

### Pages to Create
1. **Dashboard** - Widgets overview
2. **Backlog** - Prioritized list
3. **Board** - Kanban/Scrum view (Livewire)
4. **Reports** - Analytics

---

## Livewire Components

### Interactive Components
```php
// Kanban board with drag-drop
KanbanBoard::class
    - columns (statuses)
    - tickets per column
    - drag events via wire:sortable

// Sprint board
SprintBoard::class
    - active sprint view
    - story points tracking
    - burndown chart

// Quick ticket modal
TicketModal::class
    - view/edit in modal
    - comments inline
    - status transitions

// Time tracking widget
TimeLogger::class
    - start/stop timer
    - manual entry
    - today's logs
```

---

## Observers

```php
// Track all ticket changes
TicketObserver::class
    - Log history on update
    - Calculate SLA on create
    - Send notifications
    - Trigger automations

// Sprint calculations
SprintObserver::class
    - Recalculate velocity on complete
    - Update burndown data

// SLA monitoring
SlaObserver::class
    - Check breach conditions
    - Send warning notifications
```

---

## Queue Jobs

```php
// Background tasks
SendTicketNotificationJob::class
CalculateSlaBreachJob::class
RecalculateSprintMetricsJob::class
ProcessWorkflowAutomationJob::class
GenerateReportJob::class
```

---

## Coding Conventions

### PHP
- Constructor property promotion
- Explicit return types on all methods
- TitleCase for Enum keys
- PHPDoc blocks over inline comments
- Curly braces on all control structures

### Models
```php
#[Fillable(['team_id', 'project_id', 'title', ...])]
class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => TicketType::class,
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'labels' => 'array',
            'custom_fields' => 'array',
            'due_date' => 'date',
        ];
    }
}
```

### Filament v5
- Split forms/tables into Schemas/, Tables/ classes
- Use `Section` from `Filament\Schemas\Components`
- Tables use closures for records: `->records(fn () => $data)`
- Test with `->loadTable()` before assertions

### Livewire v4
- Public properties for state
- `#[On('event')]` for listeners
- `wire:sortable` for drag-drop

### Testing
- PHPUnit feature tests primarily
- Use factories for model creation
- Test with `RefreshDatabase` trait
- Sync queue in tests

---

## Environment Setup

```env
APP_NAME="Service Tickets"
APP_ENV=local
DB_CONNECTION=sqlite
SESSION_DRIVER=database
QUEUE_CONNECTION=database
LOG_CHANNEL=stack

# AI (optional)
OPENAI_API_KEY=
```

---

## Composer Scripts

```json
{
  "scripts": {
    "setup": "composer install && php artisan migrate && npm install && npm run build",
    "dev": "concurrently \"php artisan serve\" \"php artisan queue:listen\" \"php artisan pail\" \"npm run dev\"",
    "test": "php artisan test --compact",
    "lint": "vendor/bin/pint"
  }
}
```

---

## Installation Commands

```bash
# Create project
laravel new service-tickets
cd service-tickets

# Install dependencies
composer require filament/filament livewire/livewire livewire/flux
composer require laravel/fortify prism/prism
composer require --dev laravel/pint laravel/pail laravel/boost

# Setup
php artisan filament:install --panels
php artisan fortify:install
php artisan make:filament-panel admin

# Create models
php artisan make:model Project -mfs
php artisan make:model Ticket -mfs
php artisan make:model Epic -mfs
php artisan make:model Sprint -mfs
php artisan make:model Comment -mfs
php artisan make:model Attachment -m
php artisan make:model TimeLog -m
php artisan make:model SlaConfiguration -m
php artisan make:model Workflow -m
php artisan make:model CustomField -m

# Run migrations
php artisan migrate
```

---

## Verification

After implementation:
1. Create a project with workflow
2. Add epics and stories
3. Create and start a sprint
4. Move tickets through statuses
5. Log time against tickets
6. Verify SLA calculations
7. Test automations trigger
8. Run full test suite